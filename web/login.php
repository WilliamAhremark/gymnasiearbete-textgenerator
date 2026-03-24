<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Alla fält måste fyllas i.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                setcookie('ai_demo_tested', '', time() - 3600, '/');

                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                $redirect = 'dashboard.php';
                if (isset($_SESSION['redirect_url'])) {
                    $redirect = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                }

                header("Location: " . $redirect);
                exit;
            } else {
                $errors[] = "Felaktig e-post eller lösenord.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "Ett tekniskt fel uppstod. Försök igen.";
        }
    }
}

$pageTitle = 'Sign In - NeuralText AI';
include 'includes/header.php';
?>

<main>
    <section class="section" style="min-height: 70vh; display: flex; align-items: center;">
        <div class="container">
            <div style="max-width: 480px; margin: 0 auto;" class="scroll-animate">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Welcome back</p>
                    <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Sign In</h1>
                    <p style="color: var(--text-secondary);">Access your NeuralText AI account and create amazing text</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 12px; padding: 1.2rem; margin-bottom: 2rem; color: #ff6b6b;">
                        <?php foreach ($errors as $error): ?>
                            <p style="margin-bottom: 0.5rem;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="scroll-animate">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="form-group scroll-animate-item">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               placeholder="your@email.com"
                               required autofocus>
                    </div>

                    <div class="form-group scroll-animate-item">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" 
                               placeholder="••••••••"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>

                <div style="text-align: center; margin-top: 2rem; color: var(--text-secondary);">
                    <p>Don't have an account? <a href="register.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Create one</a></p>
                    <div style="margin-top: 1rem;">
                        <a href="index.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
