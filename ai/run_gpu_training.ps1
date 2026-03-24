param(
    [string]$PythonExe = ".\\.venv_local\\Scripts\\python.exe"
)

$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
$aiDir = Join-Path $repoRoot "ai"
$requirements = Join-Path $aiDir "requirements.txt"

if (-not (Test-Path -LiteralPath $PythonExe)) {
    Write-Host "Python interpreter not found at: $PythonExe" -ForegroundColor Red
    Write-Host "Create environment first: py -3.13 -m venv .venv_local" -ForegroundColor Yellow
    exit 1
}

$PythonExe = (Resolve-Path -LiteralPath $PythonExe).Path

Push-Location $repoRoot
try {
    & $PythonExe -m pip install --upgrade pip wheel
    & $PythonExe -m pip install "setuptools<82"
    & $PythonExe -m pip install -r $requirements

    Write-Host "Running CUDA diagnostics..." -ForegroundColor Cyan
    & $PythonExe -c "import torch; print('torch', torch.__version__); print('cuda runtime', torch.version.cuda); print('cuda available', torch.cuda.is_available()); print('gpu', torch.cuda.get_device_name(0) if torch.cuda.is_available() else 'none')"

    if ($LASTEXITCODE -ne 0) {
        Write-Host "CUDA diagnostics failed." -ForegroundColor Red
        exit $LASTEXITCODE
    }

    Write-Host "`n========================================" -ForegroundColor Cyan
    Write-Host "GPU TRAINING STARTING" -ForegroundColor Green
    Write-Host "Expected duration: 2-4 minutes" -ForegroundColor Yellow
    Write-Host "System will NOT sleep during training" -ForegroundColor Yellow
    Write-Host "========================================`n" -ForegroundColor Cyan
    Push-Location $aiDir
    try {
        $stamp = Get-Date -Format "yyyyMMdd_HHmmss"
        $outLog = "training_gpu_$stamp.log"
        $errLog = "training_gpu_$stamp.err.log"

        & $PythonExe -u "ai.py" 1> $outLog 2> $errLog

        Write-Host "`n========================================" -ForegroundColor Green
        Write-Host "TRAINING COMPLETE" -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Green
        Write-Host "Stdout log: ai/$outLog"
        Write-Host "Stderr log: ai/$errLog"
    }
    finally {
        Pop-Location
    }
}
finally {
    Pop-Location
}
