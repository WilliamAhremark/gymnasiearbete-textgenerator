<?php
require_once 'config.php';

generateCSRFToken();

$errors = [];
$success = '';
$allowTestReRegistration = envValue('ALLOW_TEST_REREGISTRATION', '0') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Ogiltig session. Försök igen.";
    }
    
    $email = sanitizeInput($_POST['email'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
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
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($allowTestReRegistration) {
                // Test mode: allow recreating the same user by deleting old non-admin account.
                $stmt = $pdo->prepare("DELETE FROM users WHERE (email = ? OR username = ?) AND role = 'user'");
                $stmt->execute([$email, $username]);
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "E-post eller användarnamn finns redan.";
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                try {
                    // Prefer explicit role for DB variants without a default value.
                    $stmt = $pdo->prepare("INSERT INTO users (email, username, password, role, is_verified) VALUES (?, ?, ?, 'user', 0)");
                    $stmt->execute([$email, $username, $passwordHash]);
                } catch (PDOException $insertError) {
                    // Fallback for older schema variants where role column does not exist.
                    if (($insertError->errorInfo[1] ?? 0) === 1054) {
                        $stmt = $pdo->prepare("INSERT INTO users (email, username, password, is_verified) VALUES (?, ?, ?, 0)");
                        $stmt->execute([$email, $username, $passwordHash]);
                    } else {
                        throw $insertError;
                    }
                }

                $userId = (int)$pdo->lastInsertId();
                $verificationToken = createVerificationToken($userId);

                $stmt = $pdo->prepare("INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $verificationToken['hash'], $verificationToken['expires_at']]);

                $pdo->commit();

                [$mailOk, $mailMessage] = sendVerificationEmail($email, $username, $verificationToken['raw'], $userId);
                if ($mailOk) {
                    $success = "Kontot har skapats! Kontrollera din e-post och verifiera kontot innan du loggar in.";
                    $_POST = [];
                } else {
                    $cleanup = $pdo->prepare("DELETE FROM email_verification_tokens WHERE user_id = ?");
                    $cleanup->execute([$userId]);
                    $cleanup = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $cleanup->execute([$userId]);
                    $errors[] = $mailMessage;
                }
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (($e->errorInfo[0] ?? '') === '23000') {
                $errors[] = "E-post eller användarnamn finns redan.";
            } elseif (($e->errorInfo[0] ?? '') === '42S02') {
                $errors[] = "Databas saknar nödvändig tabell (users).";
            } elseif (($e->errorInfo[0] ?? '') === '42S22') {
                $errors[] = "Databas saknar nödvändig kolumn i users-tabellen.";
            } else {
                $errors[] = "Ett tekniskt fel uppstod. Försök igen.";
            }
        }
    }
}

$pageTitle = 'Create Account - NeuralText AI';
include 'includes/header.php';
?>
<main>
    <section class="section page-hero">
        <div class="container">
            <div style="max-width: 520px; margin: 0 auto;" class="scroll-animate">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Join the community</p>
                    <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Create Account</h1>
                    <p style="color: var(--text-secondary);">Start generating amazing text with NeuralText AI</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 12px; padding: 1.2rem; margin-bottom: 2rem;">
                        <?php foreach ($errors as $error): ?>
                            <p style="color: #ff6b6b; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 1.2rem; margin-bottom: 2rem;">
                        <p style="color: #34d399; margin-bottom: 0.8rem;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></p>
                        <a href="login.php" class="btn btn-primary" style="width: 100%; text-align: center;">Go to Login</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="scroll-animate">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group scroll-animate-item">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               placeholder="your@email.com"
                               required minlength="5">
                    </div>

                    <div class="form-group scroll-animate-item">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               placeholder="Choose your username"
                               required minlength="3">
                    </div>

                    <div class="form-group scroll-animate-item">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               required minlength="8">
                        <details style="margin-top: 0.75rem; font-size: 0.85rem;">
                            <summary style="cursor: pointer; color: var(--accent-soft);">Password requirements</summary>
                            <ul style="margin-top: 0.5rem; padding-left: 1.5rem; color: var(--text-secondary);">
                                <li id="rule-length">✓ At least 8 characters</li>
                                <li id="rule-uppercase">✓ At least one uppercase letter</li>
                                <li id="rule-lowercase">✓ At least one lowercase letter</li>
                                <li id="rule-number">✓ At least one number</li>
                                <li id="rule-special">✓ At least one special character</li>
                            </ul>
                        </details>
                    </div>

                    <div class="form-group scroll-animate-item">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="••••••••"
                               required>
                        <div id="password-match"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>

                <div style="text-align: center; margin-top: 2rem; color: var(--text-secondary);">
                    <p>Already have an account? <a href="login.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Sign in</a></p>
                    <div style="margin-top: 1rem;">
                        <a href="index.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    document.getElementById('password').addEventListener('input', function(e) {
        const password = e.target.value;
        
        const rules = {
            'rule-length': password.length >= 8,
            'rule-uppercase': /[A-Z]/.test(password),
            'rule-lowercase': /[a-z]/.test(password),
            'rule-number': /[0-9]/.test(password),
            'rule-special': /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        Object.keys(rules).forEach(id => {
            const el = document.getElementById(id);
            const isValid = rules[id];
            el.style.color = isValid ? '#34d399' : 'var(--text-secondary)';
            el.innerHTML = (isValid ? '✓' : '✗') + ' ' + el.innerHTML.replace(/^[✓✗]\s/, '');
        });
    });

    document.getElementById('confirm_password').addEventListener('input', function(e) {
        const password = document.getElementById('password').value;
        const confirm = e.target.value;
        const matchDiv = document.getElementById('password-match');

        if (confirm === '') {
            matchDiv.innerHTML = '';
            matchDiv.className = '';
        } else if (password === confirm) {
            matchDiv.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
            matchDiv.className = 'show';
            matchDiv.style.color = '#34d399';
            matchDiv.style.fontSize = '0.85rem';
            matchDiv.style.marginTop = '0.5rem';
        } else {
            matchDiv.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
            matchDiv.className = 'show';
            matchDiv.style.color = '#ff6b6b';
            matchDiv.style.fontSize = '0.85rem';
            matchDiv.style.marginTop = '0.5rem';
        }
    });
</script>