import os
import argparse
from pathlib import Path

from huggingface_hub import HfApi


parser = argparse.ArgumentParser(description='Upload model checkpoint to Hugging Face model repo.')
parser.add_argument('--repo_id', default='', help='Hugging Face repo id, e.g. user/neuraltext-model')
parser.add_argument('--checkpoint_path', default='', help='Path to model_checkpoint.pt')
parser.add_argument('--hf_token', default='', help='HF token; if omitted, reads HF_TOKEN/HUGGINGFACE_TOKEN env var')
args = parser.parse_args()


REPO_ID = (args.repo_id or os.getenv('HF_REPO_ID', '')).strip()
HF_TOKEN = (args.hf_token or os.getenv('HF_TOKEN', '') or os.getenv('HUGGINGFACE_TOKEN', '')).strip()
checkpoint_env = args.checkpoint_path or os.getenv('CHECKPOINT_PATH', str(Path(__file__).resolve().parent / 'model_checkpoint.pt'))
CHECKPOINT_PATH = Path(checkpoint_env)

if not REPO_ID:
    raise ValueError('Missing HF_REPO_ID env var, example: yourname/neuraltext-shakespeare-v2')

if not HF_TOKEN:
    raise ValueError('Missing HF_TOKEN env var (or HUGGINGFACE_TOKEN)')

if not CHECKPOINT_PATH.exists():
    raise FileNotFoundError(f'Checkpoint file not found: {CHECKPOINT_PATH}')

api = HfApi(token=HF_TOKEN)
api.create_repo(repo_id=REPO_ID, repo_type='model', exist_ok=True)

api.upload_file(
    path_or_fileobj=str(CHECKPOINT_PATH),
    path_in_repo='model_checkpoint.pt',
    repo_id=REPO_ID,
    repo_type='model',
)

resolve_url = f'https://huggingface.co/{REPO_ID}/resolve/main/model_checkpoint.pt'
print('Upload complete')
print(resolve_url)
