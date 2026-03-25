<?php
require_once 'config.php';
requireLogin();

$aiApiUrl = 'api_generate.php';
$hfApiToken = getenv('HF_API_TOKEN') ?: '';
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Text</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --darker-bg: #050810;
            --card-bg: rgba(255, 255, 255, 0.03);
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --accent-blue: #667eea;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #050810 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }

        nav {
            padding: 1.5rem 5%;
            background: rgba(10, 14, 39, 0.6);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--text-primary);
        }

        .btn-logout {
            padding: 0.5rem 1rem;
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 6px;
            color: #ff6b6b;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        .container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 5%;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
        }

        textarea, input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-primary);
            font-family: inherit;
            transition: all 0.2s;
        }

        textarea:focus, input:focus {
            outline: none;
            border-color: rgba(102, 126, 234, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }

        textarea {
            resize: vertical;
            min-height: 200px;
        }

        input[type="number"] {
            max-width: 150px;
        }

        .char-count {
            text-align: right;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .control-row {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .control-group {
            flex: 1;
            min-width: 150px;
        }

        .control-group label {
            margin-bottom: 0.5rem;
        }

        .control-group input {
            margin-bottom: 0;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #ai-output {
            margin-top: 1.5rem;
            padding: 1.5rem;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-height: 100px;
            white-space: pre-wrap;
            line-height: 1.8;
            word-break: break-word;
        }

        .status-message {
            font-size: 0.95rem;
            margin-left: 1rem;
            min-width: 150px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 6px;
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: rgba(102, 126, 234, 0.2);
            border-color: rgba(102, 126, 234, 0.3);
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .control-row {
                flex-direction: column;
                align-items: stretch;
            }

            .control-group {
                width: 100%;
            }

            .control-group input {
                width: 100%;
            }

            .status-message {
                margin-left: 0;
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-container">
            <a href="index.php" class="logo">TextGenerator</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="generate.php">Generate</a>
                <a href="history.php">History</a>
                <a href="profile.php">Profile</a>
                <?php if (isAdmin()): ?>
                <a href="admin.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>Text Generator</h1>
            <p>Generate text using neural network model</p>
        </div>

        <div class="card">
            <h2>Generate New Text</h2>
            
            <div class="form-group">
                <label for="ai-prompt">Text Prompt (1-2000 characters)</label>
                <textarea id="ai-prompt" maxlength="2000" placeholder="Enter your text prompt. More text = better style. Example: ANTONIO: Long live Gonzalo!" style="resize: vertical;"></textarea>
                <div class="char-count"><span id="char-count">0</span> / 2000 characters</div>
            </div>

            <div class="control-row">
                <div class="control-group">
                    <label for="ai-length">Generated Length (1-1000 characters)</label>
                    <input id="ai-length" type="number" min="1" max="1000" value="120">
                </div>
                <button id="ai-generate" class="btn">Generate</button>
                <span id="ai-status" class="status-message"></span>
            </div>

            <div id="ai-output">Waiting for input...</div>

            <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>

        <script>
            const apiUrl = <?php echo json_encode($aiApiUrl, JSON_UNESCAPED_SLASHES); ?>;
            const hfApiToken = <?php echo json_encode($hfApiToken); ?>;
            const promptEl = document.getElementById('ai-prompt');
            const lengthEl = document.getElementById('ai-length');
            const outputEl = document.getElementById('ai-output');
            const statusEl = document.getElementById('ai-status');
            const btn = document.getElementById('ai-generate');
            const charCountEl = document.getElementById('char-count');

            // Update character counter
            promptEl.addEventListener('input', () => {
                charCountEl.textContent = promptEl.value.length;
            });

            // Validate length input
            lengthEl.addEventListener('input', () => {
                let val = parseInt(lengthEl.value, 10);
                if (val < 1) lengthEl.value = 1;
                if (val > 1000) lengthEl.value = 1000;
            });

            async function generateText() {
                const prompt = promptEl.value.trim();
                const length = parseInt(lengthEl.value, 10) || 100;
                
                // Validation
                if (!prompt) {
                    statusEl.textContent = 'Enter a prompt first.';
                    statusEl.style.color = '#ff6b6b';
                    return;
                }
                if (prompt.length > 2000) {
                    statusEl.textContent = 'Prompt too long (max 2000).';
                    statusEl.style.color = '#ff6b6b';
                    return;
                }
                if (length < 1 || length > 1000) {
                    statusEl.textContent = 'Length must be 1-1000.';
                    statusEl.style.color = '#ff6b6b';
                    return;
                }

                statusEl.textContent = 'Generating...';
                statusEl.style.color = 'var(--text-secondary)';
                btn.disabled = true;
                outputEl.textContent = '';
                try {
                    if (!hfApiToken) {
                        throw new Error('HF_API_TOKEN is not set. Add your Hugging Face API token as an environment variable in Railway.');
                    }
                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + hfApiToken
                        },
                        body: JSON.stringify({ inputs: prompt, parameters: { max_new_tokens: length } })
                    });
                    if (res.status === 401) {
                        throw new Error('Invalid or expired HF_API_TOKEN. Check your Hugging Face API token in Railway environment variables.');
                    }
                    if (res.status === 503) {
                        throw new Error('Model is loading on Hugging Face servers. Please wait a moment and try again.');
                    }
                    if (!res.ok) {
                        throw new Error(`Hugging Face API error (HTTP ${res.status})`);
                    }
                    const data = await res.json();
                    const generated = Array.isArray(data) ? data[0]?.generated_text : data?.generated_text;
                    outputEl.textContent = generated || '(empty response)';
                    statusEl.textContent = 'Done!';
                    statusEl.style.color = '#10b981';
                } catch (err) {
                    statusEl.textContent = 'Error';
                    statusEl.style.color = '#ff6b6b';
                    outputEl.textContent = err.message;
                } finally {
                    btn.disabled = false;
                }
            }

            btn.addEventListener('click', generateText);
            promptEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.ctrlKey) {
                    generateText();
                }
            });
        </script>
    </div>
</body>
</html>