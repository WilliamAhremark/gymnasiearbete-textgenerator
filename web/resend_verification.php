<?php
require_once 'config.php';

generateCSRFToken();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Ogiltig session. Försök igen.';
    }

    $email = sanitizeInput($_POST['email'] ?? '');

    if ($email === '' || !validateEmail($email)) {
        $errors[] = 'Ange en giltig e-postadress.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, username, is_verified FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'Ingen användare hittades med den adressen.';
        } elseif (!empty($user['is_verified'])) {
            $errors[] = 'Kontot är redan verifierat.';
        } else {
            try {
                $pdo->beginTransaction();

                $deleteStmt = $pdo->prepare('DELETE FROM email_verification_tokens WHERE user_id = ?');
                $deleteStmt->execute([(int)$user['id']]);

                $verificationToken = createVerificationToken((int)$user['id']);
                $insertStmt = $pdo->prepare('INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
                $insertStmt->execute([(int)$user['id'], $verificationToken['hash'], $verificationToken['expires_at']]);

                $pdo->commit();

                [$mailOk, $mailMessage] = sendVerificationEmail($email, $user['username'], $verificationToken['raw'], (int)$user['id']);
                if ($mailOk) {
                    $success = 'Ett nytt verifieringsmejl har skickats.';
                } else {
                    $errors[] = $mailMessage;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Resend verification error: ' . $e->getMessage());
                $errors[] = 'Det gick inte att skicka verifieringsmejlet.';
            }
        }
    }
}

$pageTitle = 'Resend verification - NeuralText AI';
include 'includes/header.php';
?>

<main>
    <section class="section page-hero">
        <div class="container">
            <div style="max-width: 520px; margin: 0 auto;" class="scroll-animate">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Account verification</p>
                    <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Resend verification email</h1>
                    <p style="color: var(--text-secondary);">Enter the email address used for registration.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 12px; padding: 1.2rem; margin-bottom: 2rem; color: #ff6b6b;">
                        <?php foreach ($errors as $error): ?>
                            <p style="margin-bottom: 0.5rem;"><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 1.2rem; margin-bottom: 2rem; color: #86efac;">
                        <p style="margin-bottom: 0.75rem;"><?= htmlspecialchars($success) ?></p>
                        <a href="login.php" class="btn btn-primary" style="width: 100%; text-align: center;">Back to login</a>
                    </div>
                <?php endif; ?>

                <form method="POST" class="scroll-animate">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">

                    <div class="form-group scroll-animate-item">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Send verification email</button>
                </form>

                <div style="text-align: center; margin-top: 2rem; color: var(--text-secondary);">
                    <a href="login.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Back to login</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>