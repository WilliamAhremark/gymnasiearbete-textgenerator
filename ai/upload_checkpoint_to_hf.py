import os
from pathlib import Path

from huggingface_hub import HfApi


REPO_ID = os.getenv('HF_REPO_ID', '').strip()
HF_TOKEN = os.getenv('HF_TOKEN', '').strip() or os.getenv('HUGGINGFACE_TOKEN', '').strip()
CHECKPOINT_PATH = Path(os.getenv('CHECKPOINT_PATH', str(Path(__file__).resolve().parent / 'model_checkpoint.pt')))

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
