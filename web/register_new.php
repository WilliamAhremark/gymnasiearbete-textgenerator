<?php
require_once 'config.php';

generateCSRFToken();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-skydd
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Ogiltig session. Försök igen.";
    }
    
    $email = sanitizeInput($_POST['email'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validering
    if (empty($email) || empty($username) || empty($password)) {
        $errors[] = "Alla fält måste fyllas i.";
    }
    
    if (!validateEmail($email)) {
        $errors[] = "Ogiltig e-postadress.";
    }
    
    if (strlen($username) < 3) {
        $errors[] = "Användarnamn måste vara minst 3 tecken.";
    }
    
    
    $passwordErrors = checkPasswordStrength($password);
    if (!empty($passwordErrors)) {
        foreach ($passwordErrors as $error) {
            $errors[] = "Lösenordet saknar: " . $error;
        }
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Lösenorden matchar inte.";
    }
    
    // Om inga fel, registrera användaren
    if (empty($errors)) {
        try {
            // Kolla om e-post eller användarnamn redan finns
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "E-post eller användarnamn finns redan.";
            } else {
             
                $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Spara användare
                $stmt = $pdo->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
                $stmt->execute([$email, $username, $passwordHash]);
                
                $userId = $pdo->lastInsertId();
                
                // Skapa välkomstnotis utan att blockera registrering om notifications-tabellen saknas.
                try {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $stmt->execute([$userId, "Välkommen till AI-projektet! Du kan nu börja generera text med AI:n."]);
                } catch (PDOException $notificationError) {
                    error_log("Registration notification error: " . $notificationError->getMessage());
                }
                
                $success = "Registrering lyckades! Du kan nu logga in.";
                
                // Rensa POST-data
                $_POST = [];
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            if (($e->errorInfo[0] ?? '') === '23000') {
                $errors[] = "E-post eller användarnamn finns redan.";
            } else {
                $errors[] = "Ett tekniskt fel uppstod. Försök igen.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - NeuralText AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --dark-bg: #0a0e27;
            --darker-bg: #050810;
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --accent-blue: #667eea;
            --success-color: #10b981;
            --error-color: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--darker-bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: var(--darker-bg);
        }

        .animated-bg::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(118, 75, 162, 0.15) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(30px, -30px) rotate(180deg); }
        }

        .register-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 3rem;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-size: 1.75rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .logo p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: var(--text-secondary);
        }

        .error-message, .success-message {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-left: 4px solid var(--error-color);
            color: #ff6b6b;
        }

        .success-message {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-left: 4px solid var(--success-color);
            color: #34d399;
        }

        .error-message p, .success-message p {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .error-message p:last-child, .success-message p:last-child {
            margin-bottom: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-blue);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input::placeholder {
            color: var(--text-secondary);
        }

        .password-rules {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 0.75rem;
            font-size: 0.875rem;
        }

        .password-rules strong {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .password-rules ul {
            list-style: none;
            padding: 0;
        }

        .password-rules li {
            padding: 0.25rem 0;
            color: var(--text-secondary);
        }

        .password-rules li.valid {
            color: var(--success-color);
        }

        .password-rules li.invalid {
            color: var(--error-color);
        }

        .password-rules li::before {
            margin-right: 0.5rem;
        }

        .password-rules li.valid::before {
            content: '✓';
        }

        .password-rules li.invalid::before {
            content: '✗';
        }

        #password-match {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        #password-match.valid {
            color: var(--success-color);
        }

        #password-match.invalid {
            color: var(--error-color);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .form-footer {
            margin-top: 1.5rem;
            text-align: center;
            color: var(--text-secondary);
        }

        .form-footer a {
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: var(--text-secondary);
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .divider span {
            padding: 0 1rem;
            font-size: 0.875rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--text-primary);
        }

        @media (max-width: 640px) {
            .register-container {
                padding: 2rem 1.5rem;
            }

            .auth-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>

    <div class="register-container">
        <div class="logo">
            <h1>NeuralText AI</h1>
            <p>Neural Language Processing Platform</p>
        </div>

        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Join the future of content creation</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <p><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></p>
                <p><a href="login.php" style="color: var(--success-color); font-weight: 600;">Sign in here</a></p>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com"
                       required autofocus>
            </div>
            
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Choose a username"
                       required minlength="3">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" 
                       placeholder="Create a strong password"
                       required minlength="8">
                <div class="password-rules">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li id="rule-length">At least 8 characters</li>
                        <li id="rule-uppercase">One uppercase letter</li>
                        <li id="rule-lowercase">One lowercase letter</li>
                        <li id="rule-number">One number</li>
                        <li id="rule-special">One special character</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       placeholder="Confirm your password"
                       required>
                <div id="password-match"></div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="divider">
            <span>or</span>
        </div>
        
        <div class="form-footer">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>

        <div class="divider"></div>

        <div style="text-align: center;">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <script>
        // Real-time password validation
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            
            document.getElementById('rule-length').className = 
                password.length >= 8 ? 'valid' : 'invalid';
            
            document.getElementById('rule-uppercase').className = 
                /[A-Z]/.test(password) ? 'valid' : 'invalid';
            
            document.getElementById('rule-lowercase').className = 
                /[a-z]/.test(password) ? 'valid' : 'invalid';
            
            document.getElementById('rule-number').className = 
                /[0-9]/.test(password) ? 'valid' : 'invalid';
            
            document.getElementById('rule-special').className = 
                /[!@#$%^&*(),.?":{}|<>]/.test(password) ? 'valid' : 'invalid';
        });
        
        // Check password match
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const password = document.getElementById('password').value;
            const confirm = e.target.value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirm === '') {
                matchDiv.innerHTML = '';
                matchDiv.className = '';
            } else if (password === confirm) {
                matchDiv.innerHTML = '✓ Passwords match';
                matchDiv.className = 'valid';
            } else {
                matchDiv.innerHTML = '✗ Passwords do not match';
                matchDiv.className = 'invalid';
            }
        });
    </script>
</body>
</html>
