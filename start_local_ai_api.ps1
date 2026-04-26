$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectRoot

$pythonCandidates = @(
    (Join-Path $projectRoot '.venv312\Scripts\python.exe'),
    (Join-Path $projectRoot '.venv\Scripts\python.exe'),
    'C:\Users\William\AppData\Roaming\uv\python\cpython-3.12.13-windows-x86_64-none\python.exe'
)

$python = $pythonCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $python) {
    Write-Host "No Python interpreter found in expected locations." -ForegroundColor Red
    Write-Host "Expected one of: .venv312, .venv, or legacy uv path." -ForegroundColor Yellow
    exit 1
}

Write-Host 'Starting local FastAPI on http://127.0.0.1:8000 ...' -ForegroundColor Cyan
& $python -m uvicorn api.app:app --host 127.0.0.1 --port 8000
