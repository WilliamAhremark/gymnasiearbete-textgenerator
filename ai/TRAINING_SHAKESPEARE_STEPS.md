Shakespeare v2 Training and Deploy (PowerShell)

1. Open terminal in repo root.
2. Activate venv if needed:
   .\.venv\Scripts\Activate.ps1

3. Quick smoke test (1 iteration):
   powershell -ExecutionPolicy Bypass -File ai/train_and_publish.ps1 -MaxIters 1 -EvalInterval 1 -EvalIters 1 -BatchSize 8 -BlockSize 64 -NEmbd 64 -NHead 2 -NLayer 2 -SamplePrompt "look outside"

4. Real training run (example):
   powershell -ExecutionPolicy Bypass -File ai/train_and_publish.ps1 -MaxIters 5000 -EvalInterval 250 -EvalIters 50 -BatchSize 64 -BlockSize 256 -NEmbd 384 -NHead 6 -NLayer 6 -SamplePrompt "look outside"

5. Set Hugging Face token in current shell:
   $env:HF_TOKEN = "hf_xxxxxxxxxxxxxxxxx"

6. Upload trained checkpoint to a NEW HF model repo:
   powershell -ExecutionPolicy Bypass -File ai/train_and_publish.ps1 -MaxIters 5000 -EvalInterval 250 -EvalIters 50 -BatchSize 64 -BlockSize 256 -NEmbd 384 -NHead 6 -NLayer 6 -SamplePrompt "look outside" -Upload -HfRepoId "YOUR_USERNAME/neuraltext-shakespeare-v2"

7. Copy the printed URL, example:
   https://huggingface.co/YOUR_USERNAME/neuraltext-shakespeare-v2/resolve/main/model_checkpoint.pt

8. In Railway API service set env var:
   MODEL_CHECKPOINT_URL = <the resolve/main URL>

9. In Railway web service set env var (fallback to your API):
   AI_API_URL = https://<your-api-service>.railway.app/generate

10. Redeploy both services.

11. Verify:
   - Generate with prompt: look outside
   - Output should start with exactly: look outside
