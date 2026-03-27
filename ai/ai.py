import torch
import torch.nn as nn
from torch.nn import functional as F
from contextlib import nullcontext
import time
import ctypes
import os
from pathlib import Path

# Keep system awake during training
def keep_awake():
    """Prevent system sleep by simulating user activity."""
    try:
        ctypes.windll.kernel32.SetThreadExecutionState(0x80000002)  # ES_CONTINUOUS | ES_SYSTEM_REQUIRED
    except:
        pass

keep_awake()


def env_int(name, default):
    value = os.getenv(name)
    if value is None or value == '':
        return default
    return int(value)


def env_float(name, default):
    value = os.getenv(name)
    if value is None or value == '':
        return default
    return float(value)

# hyperparameters
batch_size = env_int('BATCH_SIZE', 64) # how many independent sequences will we process in parallel?
block_size = env_int('BLOCK_SIZE', 256) # what is the maximum context length for predictions?
max_iters = env_int('MAX_ITERS', 5000)
eval_interval = env_int('EVAL_INTERVAL', 500)
learning_rate = env_float('LEARNING_RATE', 3e-4)
device = 'cuda' if torch.cuda.is_available() else 'cpu'
eval_iters = env_int('EVAL_ITERS', 200)
n_embd = env_int('N_EMBD', 384)
n_head = env_int('N_HEAD', 6)
n_layer = env_int('N_LAYER', 6)
dropout = env_float('DROPOUT', 0.3)
weight_decay = env_float('WEIGHT_DECAY', 0.1)
temperature = env_float('TEMPERATURE', 0.9)
top_p = env_float('TOP_P', 0.9)
repetition_penalty = env_float('REPETITION_PENALTY', 1.1)
sample_tokens = env_int('SAMPLE_TOKENS', 250)
sample_prompt = os.getenv('SAMPLE_PROMPT', 'look outside').strip()

SCRIPT_DIR = Path(__file__).resolve().parent
checkpoint_path = Path(os.getenv('CHECKPOINT_PATH', str(SCRIPT_DIR / 'model_checkpoint.pt')))

input_file_env = os.getenv('INPUT_FILE', '').strip()
dataset_candidates = []
if input_file_env:
    dataset_candidates.append(Path(input_file_env))
dataset_candidates.extend([
    SCRIPT_DIR / 'input.txt',
    SCRIPT_DIR / 'inpuut.txt',
    Path('input.txt'),
    Path('inpuut.txt'),
])

dataset_path = next((candidate for candidate in dataset_candidates if candidate.exists()), None)
if dataset_path is None:
    raise FileNotFoundError(
        'No training text file found. Set INPUT_FILE or add ai/input.txt (or ai/inpuut.txt).'
    )

print(f'Using dataset: {dataset_path}')
print(f'Checkpoint output: {checkpoint_path}')
# ------------

torch.manual_seed(1337)

if torch.cuda.is_available():
    torch.cuda.manual_seed_all(1337)
    torch.set_float32_matmul_precision('high')

use_amp = device == 'cuda'

def autocast_context():
    if use_amp:
        return torch.autocast(device_type='cuda', dtype=torch.float16)
    return nullcontext()

# wget https://raw.githubusercontent.com/karpathy/char-rnn/master/data/tinyshakespeare/input.txt
with open(dataset_path, 'r', encoding='utf-8') as f:
    text = f.read()

def deduplicate_text(raw_text):
    blocks = [block.strip() for block in raw_text.split('\n\n') if block.strip()]
    seen = set()
    unique_blocks = []
    for block in blocks:
        if block not in seen:
            seen.add(block)
            unique_blocks.append(block)

    if len(unique_blocks) <= 1:
        lines = [line for line in raw_text.splitlines() if line.strip()]
        seen_lines = set()
        unique_lines = []
        for line in lines:
            if line not in seen_lines:
                seen_lines.add(line)
                unique_lines.append(line)
        return '\n'.join(unique_lines)

    return '\n\n'.join(unique_blocks)

deduped_text = deduplicate_text(text)
if len(deduped_text) > 100:
    text = deduped_text

# here are all the unique characters that occur in this text
chars = sorted(list(set(text)))
vocab_size = len(chars)
# create a mapping from characters to integers
stoi = { ch:i for i,ch in enumerate(chars) }
itos = { i:ch for i,ch in enumerate(chars) }
encode = lambda s: [stoi[c] for c in s] # encoder: take a string, output a list of integers
decode = lambda l: ''.join([itos[i] for i in l]) # decoder: take a list of integers, output a string

# Train and test splits
data = torch.tensor(encode(text), dtype=torch.long)
n = int(0.9*len(data)) # first 90% will be train, rest val
train_data = data[:n]
val_data = data[n:]

# data loading
def get_batch(split):
    # generate a small batch of data of inputs x and targets y
    data = train_data if split == 'train' else val_data
    ix = torch.randint(len(data) - block_size, (batch_size,))
    x = torch.stack([data[i:i+block_size] for i in ix])
    y = torch.stack([data[i+1:i+block_size+1] for i in ix])
    x, y = x.to(device), y.to(device)
    return x, y

@torch.no_grad()
def estimate_loss():
    out = {}
    model.eval()
    for split in ['train', 'val']:
        losses = torch.zeros(eval_iters)
        for k in range(eval_iters):
            X, Y = get_batch(split)
            with autocast_context():
                logits, loss = model(X, Y)
            losses[k] = loss.item()
        out[split] = losses.mean()
    model.train()
    return out

class Head(nn.Module):
    """ one head of self-attention """

    def __init__(self, head_size):
        super().__init__()
        self.key = nn.Linear(n_embd, head_size, bias=False)
        self.query = nn.Linear(n_embd, head_size, bias=False)
        self.value = nn.Linear(n_embd, head_size, bias=False)
        self.register_buffer('tril', torch.tril(torch.ones(block_size, block_size)))

        self.dropout = nn.Dropout(dropout)

    def forward(self, x):
        # input of size (batch, time-step, channels)
        # output of size (batch, time-step, head size)
        B,T,C = x.shape
        k = self.key(x)   # (B,T,hs)
        q = self.query(x) # (B,T,hs)
        # compute attention scores ("affinities")
        wei = q @ k.transpose(-2,-1) * k.shape[-1]**-0.5 # (B, T, hs) @ (B, hs, T) -> (B, T, T)
        wei = wei.masked_fill(self.tril[:T, :T] == 0, float('-inf')) # (B, T, T)
        wei = F.softmax(wei, dim=-1) # (B, T, T)
        wei = self.dropout(wei)
        # perform the weighted aggregation of the values
        v = self.value(x) # (B,T,hs)
        out = wei @ v # (B, T, T) @ (B, T, hs) -> (B, T, hs)
        return out

class MultiHeadAttention(nn.Module):
    """ multiple heads of self-attention in parallel """

    def __init__(self, num_heads, head_size):
        super().__init__()
        self.heads = nn.ModuleList([Head(head_size) for _ in range(num_heads)])
        self.proj = nn.Linear(head_size * num_heads, n_embd)
        self.dropout = nn.Dropout(dropout)

    def forward(self, x):
        out = torch.cat([h(x) for h in self.heads], dim=-1)
        out = self.dropout(self.proj(out))
        return out

class FeedForward(nn.Module):
    """ a simple linear layer followed by a non-linearity """

    def __init__(self, n_embd):
        super().__init__()
        self.net = nn.Sequential(
            nn.Linear(n_embd, 4 * n_embd),
            nn.ReLU(),
            nn.Linear(4 * n_embd, n_embd),
            nn.Dropout(dropout),
        )

    def forward(self, x):
        return self.net(x)

class Block(nn.Module):
    """ Transformer block: communication followed by computation """

    def __init__(self, n_embd, n_head):
        # n_embd: embedding dimension, n_head: the number of heads we'd like
        super().__init__()
        head_size = n_embd // n_head
        self.sa = MultiHeadAttention(n_head, head_size)
        self.ffwd = FeedForward(n_embd)
        self.ln1 = nn.LayerNorm(n_embd)
        self.ln2 = nn.LayerNorm(n_embd)

    def forward(self, x):
        x = x + self.sa(self.ln1(x))
        x = x + self.ffwd(self.ln2(x))
        return x

class GPTLanguageModel(nn.Module):

    def __init__(self):
        super().__init__()
        # each token directly reads off the logits for the next token from a lookup table
        self.token_embedding_table = nn.Embedding(vocab_size, n_embd)
        self.position_embedding_table = nn.Embedding(block_size, n_embd)
        self.blocks = nn.Sequential(*[Block(n_embd, n_head=n_head) for _ in range(n_layer)])
        self.ln_f = nn.LayerNorm(n_embd) # final layer norm
        self.lm_head = nn.Linear(n_embd, vocab_size)

        # better init, not covered in the original GPT video, but important, will cover in followup video
        self.apply(self._init_weights)

    def _init_weights(self, module):
        if isinstance(module, nn.Linear):
            torch.nn.init.normal_(module.weight, mean=0.0, std=0.02)
            if module.bias is not None:
                torch.nn.init.zeros_(module.bias)
        elif isinstance(module, nn.Embedding):
            torch.nn.init.normal_(module.weight, mean=0.0, std=0.02)

    def forward(self, idx, targets=None):
        B, T = idx.shape

        # idx and targets are both (B,T) tensor of integers
        tok_emb = self.token_embedding_table(idx) # (B,T,C)
        pos_emb = self.position_embedding_table(torch.arange(T, device=device)) # (T,C)
        x = tok_emb + pos_emb # (B,T,C)
        x = self.blocks(x) # (B,T,C)
        x = self.ln_f(x) # (B,T,C)
        logits = self.lm_head(x) # (B,T,vocab_size)

        if targets is None:
            loss = None
        else:
            B, T, C = logits.shape
            logits = logits.view(B*T, C)
            targets = targets.view(B*T)
            loss = F.cross_entropy(logits, targets)

        return logits, loss

    def generate(self, idx, max_new_tokens, temperature=1.0, top_p=0.9, repetition_penalty=1.1):
        # idx is (B, T) array of indices in the current context
        for _ in range(max_new_tokens):
            # crop idx to the last block_size tokens
            idx_cond = idx[:, -block_size:]
            # get the predictions
            logits, loss = self(idx_cond)
            # focus only on the last time step
            logits = logits[:, -1, :] / max(temperature, 1e-6) # becomes (B, C)

            for b in range(idx.shape[0]):
                unique_tokens = torch.unique(idx[b])
                logits[b, unique_tokens] = logits[b, unique_tokens] / repetition_penalty

            sorted_logits, sorted_indices = torch.sort(logits, descending=True, dim=-1)
            sorted_probs = F.softmax(sorted_logits, dim=-1)
            cumulative_probs = torch.cumsum(sorted_probs, dim=-1)
            sorted_indices_to_remove = cumulative_probs > top_p
            sorted_indices_to_remove[..., 1:] = sorted_indices_to_remove[..., :-1].clone()
            sorted_indices_to_remove[..., 0] = False
            sorted_logits = sorted_logits.masked_fill(sorted_indices_to_remove, float('-inf'))
            logits = torch.full_like(logits, float('-inf')).scatter(-1, sorted_indices, sorted_logits)

            # apply softmax to get probabilities
            probs = F.softmax(logits, dim=-1) # (B, C)
            # sample from the distribution
            idx_next = torch.multinomial(probs, num_samples=1) # (B, 1)
            # append sampled index to the running sequence
            idx = torch.cat((idx, idx_next), dim=1) # (B, T+1)
        return idx

model = GPTLanguageModel()
m = model.to(device)
# print the number of parameters in the model
print(sum(p.numel() for p in m.parameters())/1e6, 'M parameters')

# create a PyTorch optimizer
optimizer = torch.optim.AdamW(model.parameters(), lr=learning_rate, weight_decay=weight_decay)
scaler = torch.amp.GradScaler('cuda', enabled=use_amp)

training_start = time.time()
for iter in range(max_iters):
    # Keep system awake every 100 iterations
    if iter % 100 == 0:
        keep_awake()

    # every once in a while evaluate the loss on train and val sets
    if iter % eval_interval == 0 or iter == max_iters - 1:
        losses = estimate_loss()
        elapsed = time.time() - training_start
        iter_per_sec = iter / elapsed if elapsed > 0 else 0
        eta_sec = (max_iters - iter) / iter_per_sec if iter_per_sec > 0 else 0
        eta_min = eta_sec / 60
        print(f"step {iter}: train loss {losses['train']:.4f}, val loss {losses['val']:.4f} | elapsed {elapsed:.1f}s, ETA {eta_min:.1f}min")

    # sample a batch of data
    xb, yb = get_batch('train')

    # evaluate the loss
    with autocast_context():
        logits, loss = model(xb, yb)
    optimizer.zero_grad(set_to_none=True)
    scaler.scale(loss).backward()
    scaler.step(optimizer)
    scaler.update()

    # Save checkpoint every eval_interval (500 steps) so API can use latest weights
    if iter % eval_interval == 0 or iter == max_iters - 1:
        checkpoint = {
            'model_state_dict': m.state_dict(),
            'iteration': iter,
            'config': {
                'block_size': block_size,
                'n_embd': n_embd,
                'n_head': n_head,
                'n_layer': n_layer,
                'dropout': dropout,
                'temperature': temperature,
                'top_p': top_p,
                'repetition_penalty': repetition_penalty,
            },
            'stoi': stoi,
            'itos': itos,
        }
        checkpoint_path.parent.mkdir(parents=True, exist_ok=True)
        torch.save(checkpoint, checkpoint_path)
        print(f'Saved checkpoint to {checkpoint_path} (step {iter}/{max_iters})')

checkpoint = {
    'model_state_dict': m.state_dict(),
    'config': {
        'block_size': block_size,
        'n_embd': n_embd,
        'n_head': n_head,
        'n_layer': n_layer,
        'dropout': dropout,
        'temperature': temperature,
        'top_p': top_p,
        'repetition_penalty': repetition_penalty,
    },
    'stoi': stoi,
    'itos': itos,
}
checkpoint_path.parent.mkdir(parents=True, exist_ok=True)
torch.save(checkpoint, checkpoint_path)
print(f'Saved final checkpoint to {checkpoint_path}')

# generate from the model with an explicit prompt continuation
seed_ids = [stoi[c] for c in sample_prompt if c in stoi]
if not seed_ids:
    seed_ids = torch.zeros((1, 1), dtype=torch.long, device=device).tolist()[0]

context = torch.tensor([seed_ids], dtype=torch.long, device=device)
generated_ids = m.generate(
    context,
    max_new_tokens=sample_tokens,
    temperature=temperature,
    top_p=top_p,
    repetition_penalty=repetition_penalty,
)[0].tolist()

decoded_seed = decode(seed_ids)
decoded_full = decode(generated_ids)
continuation = decoded_full[len(decoded_seed):] if decoded_full.startswith(decoded_seed) else decoded_full

print('--- SAMPLE ---')
print(sample_prompt + continuation)
#open('more.txt', 'w').write(decode(m.generate(context, max_new_tokens=10000)[0].tolist()))