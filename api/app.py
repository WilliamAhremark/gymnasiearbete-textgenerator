import os
from pathlib import Path
from urllib.request import urlretrieve

import torch
import torch.nn as nn
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from torch.nn import functional as F


BASE_DIR = Path(__file__).resolve().parent.parent
DEFAULT_CHECKPOINT_PATH = BASE_DIR / "ai" / "model_checkpoint.pt"
CHECKPOINT_PATH = Path(os.getenv("CHECKPOINT_PATH", str(DEFAULT_CHECKPOINT_PATH)))
CHECKPOINT_URL = os.getenv("MODEL_CHECKPOINT_URL", "").strip()

device = "cuda" if torch.cuda.is_available() else "cpu"


class GenerateRequest(BaseModel):
	prompt: str = Field(min_length=1, max_length=2000)
	length: int = Field(default=120, ge=1, le=1000)


class Head(nn.Module):
	def __init__(self, n_embd: int, head_size: int, block_size: int, dropout: float):
		super().__init__()
		self.key = nn.Linear(n_embd, head_size, bias=False)
		self.query = nn.Linear(n_embd, head_size, bias=False)
		self.value = nn.Linear(n_embd, head_size, bias=False)
		self.register_buffer("tril", torch.tril(torch.ones(block_size, block_size)))
		self.dropout = nn.Dropout(dropout)

	def forward(self, x):
		_, t, _ = x.shape
		k = self.key(x)
		q = self.query(x)
		wei = q @ k.transpose(-2, -1) * k.shape[-1] ** -0.5
		wei = wei.masked_fill(self.tril[:t, :t] == 0, float("-inf"))
		wei = F.softmax(wei, dim=-1)
		wei = self.dropout(wei)
		v = self.value(x)
		return wei @ v


class MultiHeadAttention(nn.Module):
	def __init__(self, n_embd: int, num_heads: int, head_size: int, block_size: int, dropout: float):
		super().__init__()
		self.heads = nn.ModuleList([Head(n_embd, head_size, block_size, dropout) for _ in range(num_heads)])
		self.proj = nn.Linear(head_size * num_heads, n_embd)
		self.dropout = nn.Dropout(dropout)

	def forward(self, x):
		out = torch.cat([h(x) for h in self.heads], dim=-1)
		return self.dropout(self.proj(out))


class FeedForward(nn.Module):
	def __init__(self, n_embd: int, dropout: float):
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
	def __init__(self, n_embd: int, n_head: int, block_size: int, dropout: float):
		super().__init__()
		head_size = n_embd // n_head
		self.sa = MultiHeadAttention(n_embd, n_head, head_size, block_size, dropout)
		self.ffwd = FeedForward(n_embd, dropout)
		self.ln1 = nn.LayerNorm(n_embd)
		self.ln2 = nn.LayerNorm(n_embd)

	def forward(self, x):
		x = x + self.sa(self.ln1(x))
		x = x + self.ffwd(self.ln2(x))
		return x


class GPTLanguageModel(nn.Module):
	def __init__(self, vocab_size: int, block_size: int, n_embd: int, n_head: int, n_layer: int, dropout: float):
		super().__init__()
		self.block_size = block_size
		self.token_embedding_table = nn.Embedding(vocab_size, n_embd)
		self.position_embedding_table = nn.Embedding(block_size, n_embd)
		self.blocks = nn.Sequential(*[Block(n_embd, n_head=n_head, block_size=block_size, dropout=dropout) for _ in range(n_layer)])
		self.ln_f = nn.LayerNorm(n_embd)
		self.lm_head = nn.Linear(n_embd, vocab_size)

	def forward(self, idx, targets=None):
		_, t = idx.shape
		tok_emb = self.token_embedding_table(idx)
		pos_emb = self.position_embedding_table(torch.arange(t, device=idx.device))
		x = tok_emb + pos_emb
		x = self.blocks(x)
		x = self.ln_f(x)
		logits = self.lm_head(x)

		if targets is None:
			loss = None
		else:
			b, t, c = logits.shape
			logits = logits.view(b * t, c)
			targets = targets.view(b * t)
			loss = F.cross_entropy(logits, targets)

		return logits, loss

	def generate(self, idx, max_new_tokens, temperature=1.0, top_p=0.9, repetition_penalty=1.1):
		for _ in range(max_new_tokens):
			idx_cond = idx[:, -self.block_size :]
			logits, _ = self(idx_cond)
			logits = logits[:, -1, :] / max(temperature, 1e-6)

			for b in range(idx.shape[0]):
				unique_tokens = torch.unique(idx[b])
				logits[b, unique_tokens] = logits[b, unique_tokens] / repetition_penalty

			sorted_logits, sorted_indices = torch.sort(logits, descending=True, dim=-1)
			sorted_probs = F.softmax(sorted_logits, dim=-1)
			cumulative_probs = torch.cumsum(sorted_probs, dim=-1)
			sorted_indices_to_remove = cumulative_probs > top_p
			sorted_indices_to_remove[..., 1:] = sorted_indices_to_remove[..., :-1].clone()
			sorted_indices_to_remove[..., 0] = False
			sorted_logits = sorted_logits.masked_fill(sorted_indices_to_remove, float("-inf"))
			logits = torch.full_like(logits, float("-inf")).scatter(-1, sorted_indices, sorted_logits)

			probs = F.softmax(logits, dim=-1)
			idx_next = torch.multinomial(probs, num_samples=1)
			idx = torch.cat((idx, idx_next), dim=1)
		return idx


app = FastAPI(title="NeuralText API", version="1.0.0")

origins_env = os.getenv("CORS_ORIGINS", "*").strip()
if origins_env == "*":
	allow_origins = ["*"]
else:
	allow_origins = [origin.strip() for origin in origins_env.split(",") if origin.strip()]

app.add_middleware(
	CORSMiddleware,
	allow_origins=allow_origins,
	allow_credentials=True,
	allow_methods=["*"],
	allow_headers=["*"],
)

model = None
stoi = None
itos = None
gen_cfg = None


def ensure_checkpoint_present():
	if CHECKPOINT_PATH.exists():
		return

	if not CHECKPOINT_URL:
		return

	CHECKPOINT_PATH.parent.mkdir(parents=True, exist_ok=True)
	urlretrieve(CHECKPOINT_URL, str(CHECKPOINT_PATH))


def load_model():
	global model, stoi, itos, gen_cfg
	ensure_checkpoint_present()
	if not CHECKPOINT_PATH.exists():
		return

	checkpoint = torch.load(CHECKPOINT_PATH, map_location=device)
	stoi = checkpoint["stoi"]
	itos = checkpoint["itos"]
	cfg = checkpoint["config"]

	model = GPTLanguageModel(
		vocab_size=len(stoi),
		block_size=cfg["block_size"],
		n_embd=cfg["n_embd"],
		n_head=cfg["n_head"],
		n_layer=cfg["n_layer"],
		dropout=cfg["dropout"],
	).to(device)
	model.load_state_dict(checkpoint["model_state_dict"])
	model.eval()
	gen_cfg = cfg


load_model()


def encode_prompt(text: str):
	if not text:
		return [0]

	encoded = []
	for ch in text:
		if ch in stoi:
			encoded.append(stoi[ch])

	if encoded:
		return encoded

	first_id = next(iter(stoi.values()))
	return [first_id]


def decode_tokens(tokens):
	return "".join(itos.get(int(i), "") for i in tokens)


@app.get("/")
def health():
	return {
		"status": "ok" if model is not None else "error",
		"message": "POST /generate med JSON: {'prompt': 'text', 'length': 100}",
		"checkpoint": str(CHECKPOINT_PATH),
		"checkpoint_exists": CHECKPOINT_PATH.exists(),
		"device": device,
	}


@app.post("/generate")
def generate(req: GenerateRequest):
	if model is None:
		raise HTTPException(
			status_code=503,
			detail="Modell ej laddad. Kor ai/ai.py lokalt for att skapa checkpoint och deploya filen, eller satt MODEL_CHECKPOINT_URL.",
		)

	prompt = req.prompt.strip()
	if not prompt:
		raise HTTPException(status_code=400, detail="Prompt kan inte vara tom")

	idx = torch.tensor([encode_prompt(prompt)], dtype=torch.long, device=device)

	with torch.no_grad():
		out = model.generate(
			idx,
			max_new_tokens=req.length,
			temperature=gen_cfg.get("temperature", 0.9),
			top_p=gen_cfg.get("top_p", 0.9),
			repetition_penalty=gen_cfg.get("repetition_penalty", 1.1),
		)

	text = decode_tokens(out[0].tolist())
	return {"text": text}

@app.get("/")
def railway_health():
    return {"status": "ok"}
