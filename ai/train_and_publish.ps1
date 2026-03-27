param(
    [string]$InputFile = "ai/inpuut.txt",
    [int]$MaxIters = 5000,
    [int]$EvalInterval = 250,
    [int]$EvalIters = 50,
    [int]$BatchSize = 64,
    [int]$BlockSize = 256,
    [int]$NEmbd = 384,
    [int]$NHead = 6,
    [int]$NLayer = 6,
    [string]$SamplePrompt = "look outside",
    [string]$HfRepoId = "",
    [switch]$Upload
)

$python = "g:/Min enhet/Allt/Webserverprogrameringx/GYMNASIEARBETE1/.venv/Scripts/python.exe"

if (-not (Test-Path $python)) {
    throw "Python env not found at $python"
}

$env:INPUT_FILE = $InputFile
$env:MAX_ITERS = "$MaxIters"
$env:EVAL_INTERVAL = "$EvalInterval"
$env:EVAL_ITERS = "$EvalIters"
$env:BATCH_SIZE = "$BatchSize"
$env:BLOCK_SIZE = "$BlockSize"
$env:N_EMBD = "$NEmbd"
$env:N_HEAD = "$NHead"
$env:N_LAYER = "$NLayer"
$env:SAMPLE_PROMPT = $SamplePrompt

Write-Host "Starting training..." -ForegroundColor Cyan
& $python ai/ai.py
if ($LASTEXITCODE -ne 0) {
    throw "Training failed"
}

Write-Host "Training complete. Checkpoint saved to ai/model_checkpoint.pt" -ForegroundColor Green

if ($Upload) {
    if ([string]::IsNullOrWhiteSpace($HfRepoId)) {
        throw "Use -HfRepoId 'username/repo' when -Upload is enabled"
    }

    if (-not $env:HF_TOKEN -and -not $env:HUGGINGFACE_TOKEN) {
        throw "Set HF_TOKEN (or HUGGINGFACE_TOKEN) in environment before upload"
    }

    $env:HF_REPO_ID = $HfRepoId
    Write-Host "Uploading checkpoint to Hugging Face repo $HfRepoId..." -ForegroundColor Cyan
    & $python ai/upload_checkpoint_to_hf.py
    if ($LASTEXITCODE -ne 0) {
        throw "Upload failed"
    }

    Write-Host "Upload complete." -ForegroundColor Green
}
