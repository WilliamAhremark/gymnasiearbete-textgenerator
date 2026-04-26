import json
import re
import shutil
import statistics
import subprocess
import sys
import tempfile
import time
from pathlib import Path

import requests


WEB_BASE_URL = "http://127.0.0.1:8080"
API_BASE_URL = "http://127.0.0.1:8000"
ROOT = Path(__file__).resolve().parent
AI_DIR = ROOT / "ai"


def run_sql_injection_test() -> dict:
    session = requests.Session()

    login_page = session.get(f"{WEB_BASE_URL}/login.php", timeout=15)
    login_page.raise_for_status()

    token_match = re.search(r'name="csrf_token"\s+value="([^"]+)"', login_page.text)
    if not token_match:
        raise RuntimeError("Kunde inte hitta CSRF-token i login.php")
    csrf_token = token_match.group(1)

    injection_payload = {
        "email": "admin@ai-project.com' OR '1'='1",
        "password": "anything",
        "csrf_token": csrf_token,
    }
    inj_resp = session.post(
        f"{WEB_BASE_URL}/login.php",
        data=injection_payload,
        timeout=15,
        allow_redirects=False,
    )

    dash_after_attack = session.get(
        f"{WEB_BASE_URL}/dashboard.php", timeout=15, allow_redirects=False
    )

    attack_blocked = dash_after_attack.status_code == 302 and "login.php" in dash_after_attack.headers.get("Location", "")

    control_session = requests.Session()
    control_page = control_session.get(f"{WEB_BASE_URL}/login.php", timeout=15)
    control_page.raise_for_status()
    control_token = re.search(r'name="csrf_token"\s+value="([^"]+)"', control_page.text)
    if not control_token:
        raise RuntimeError("Kunde inte hitta CSRF-token i kontrolltest")

    normal_payload = {
        "email": "admin@ai-project.com",
        "password": "password",
        "csrf_token": control_token.group(1),
    }
    normal_login = control_session.post(
        f"{WEB_BASE_URL}/login.php",
        data=normal_payload,
        timeout=15,
        allow_redirects=False,
    )
    control_dashboard = control_session.get(
        f"{WEB_BASE_URL}/dashboard.php", timeout=15, allow_redirects=False
    )

    return {
        "attack_request_status": inj_resp.status_code,
        "attack_dashboard_status": dash_after_attack.status_code,
        "attack_dashboard_location": dash_after_attack.headers.get("Location", ""),
        "sql_injection_blocked": attack_blocked,
        "control_login_status": normal_login.status_code,
        "control_login_location": normal_login.headers.get("Location", ""),
        "control_dashboard_status": control_dashboard.status_code,
    }


def run_generation_benchmark(num_tests: int = 10, length: int = 200) -> dict:
    times = []
    for i in range(num_tests):
        start = time.perf_counter()
        response = requests.post(
            f"{API_BASE_URL}/generate",
            json={"prompt": "Neural text benchmark prompt", "length": length},
            timeout=180,
        )
        elapsed = time.perf_counter() - start
        response.raise_for_status()
        data = response.json()
        times.append(elapsed)
        print(f"GEN_TEST_{i+1}: {elapsed:.4f}s (status={response.status_code}, text_len={len(data.get('text', ''))})")

    return {
        "tests": num_tests,
        "length": length,
        "avg_seconds": round(statistics.mean(times), 4),
        "median_seconds": round(statistics.median(times), 4),
        "min_seconds": round(min(times), 4),
        "max_seconds": round(max(times), 4),
        "all_seconds": [round(t, 4) for t in times],
    }


def run_training_benchmark(train_iters: int = 40, eval_interval: int = 20) -> dict:
    source_file = AI_DIR / "ai.py"
    if not source_file.exists():
        raise FileNotFoundError(f"Kunde inte hitta {source_file}")

    input_file = AI_DIR / "input.txt"
    if not input_file.exists():
        fallback = AI_DIR / "inpuut.txt"
        if fallback.exists():
            shutil.copyfile(fallback, input_file)
        else:
            raise FileNotFoundError("Varken input.txt eller inpuut.txt finns i ai/-mappen")

    source = source_file.read_text(encoding="utf-8")
    source = source.replace("max_iters = 5000", f"max_iters = {train_iters}")
    source = source.replace("eval_interval = 500", f"eval_interval = {eval_interval}")
    source = source.replace(
        "scaler = torch.amp.GradScaler('cuda', enabled=use_amp)",
        "scaler = torch.cuda.amp.GradScaler(enabled=use_amp)",
    )

    with tempfile.TemporaryDirectory(prefix="ai_train_bench_") as tmp_dir:
        temp_script = Path(tmp_dir) / "ai_train_bench.py"
        temp_script.write_text(source, encoding="utf-8")

        start = time.perf_counter()
        proc = subprocess.run(
            [sys.executable, str(temp_script)],
            cwd=str(AI_DIR),
            capture_output=True,
            text=True,
            timeout=7200,
        )
        elapsed = time.perf_counter() - start

    if proc.returncode != 0:
        raise RuntimeError(
            "Traningsbenchmark misslyckades.\n"
            f"returncode={proc.returncode}\n"
            f"STDOUT:\n{proc.stdout[-2500:]}\n"
            f"STDERR:\n{proc.stderr[-2500:]}"
        )

    return {
        "train_iters": train_iters,
        "eval_interval": eval_interval,
        "elapsed_seconds": round(elapsed, 4),
    }


def main() -> None:
    results = {
        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S"),
        "security_sql_injection": run_sql_injection_test(),
        "performance_generation": run_generation_benchmark(num_tests=10, length=200),
        "performance_training": run_training_benchmark(train_iters=40, eval_interval=20),
    }

    out_path = ROOT / "chapter3_test_results.json"
    out_path.write_text(json.dumps(results, indent=2, ensure_ascii=False), encoding="utf-8")

    print("\n=== KAPITEL 3 TESTRESULTAT ===")
    print(json.dumps(results, indent=2, ensure_ascii=False))
    print(f"\nResultat sparat i: {out_path}")


if __name__ == "__main__":
    main()
