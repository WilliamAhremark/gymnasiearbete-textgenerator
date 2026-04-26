<?php
require_once 'config.php';

$pageTitle = 'Verify Email - NeuralText AI';
$message = 'Ogiltig verifieringslänk.';
$messageType = 'error';

$userId = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$token = trim((string)($_GET['token'] ?? ($_GET['amp;token'] ?? '')));

// Some mail clients may keep HTML entities in query params (amp;token).
// Fallback: decode the raw query string and parse again.
if (($userId <= 0 || $token === '') && !empty($_SERVER['QUERY_STRING'])) {
    $decodedQuery = html_entity_decode((string)$_SERVER['QUERY_STRING'], ENT_QUOTES, 'UTF-8');
    parse_str($decodedQuery, $queryValues);

    if ($userId <= 0 && isset($queryValues['uid'])) {
        $userId = (int)$queryValues['uid'];
    }

    if ($token === '') {
        $token = trim((string)($queryValues['token'] ?? ($queryValues['amp;token'] ?? '')));
    }
}

if ($userId > 0 && $token !== '') {
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare('SELECT id FROM email_verification_tokens WHERE user_id = ? AND token_hash = ? AND expires_at >= NOW() LIMIT 1');
    $stmt->execute([$userId, $tokenHash]);
    $verification = $stmt->fetch();

    if ($verification) {
        try {
            $pdo->beginTransaction();

            $updateStmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
            $updateStmt->execute([$userId]);

            $deleteStmt = $pdo->prepare('DELETE FROM email_verification_tokens WHERE user_id = ?');
            $deleteStmt->execute([$userId]);

            $pdo->commit();

            $message = 'Din e-postadress har verifierats. Du kan nu logga in.';
            $messageType = 'success';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Email verification error: ' . $e->getMessage());
            $message = 'Verifieringen misslyckades. Försök igen senare.';
        }
    } elseif ($userId > 0) {
        $message = 'Verifieringslänken är ogiltig eller har gått ut.';
    }
}

include 'includes/header.php';
?>

<main>
    <section class="section page-hero">
        <div class="container" style="max-width: 760px; margin: 0 auto;">
            <div class="section-header scroll-animate">
                <div class="section-label">Verification</div>
                <h1 class="section-title">Email verification</h1>
                <p class="section-description">Confirm your account before signing in.</p>
            </div>

            <div style="padding: 1.4rem; border-radius: 16px; border: 1px solid <?= $messageType === 'success' ? 'rgba(34, 197, 94, 0.35)' : 'rgba(220, 53, 69, 0.35)' ?>; background: <?= $messageType === 'success' ? 'rgba(34, 197, 94, 0.1)' : 'rgba(220, 53, 69, 0.1)' ?>; color: <?= $messageType === 'success' ? '#86efac' : '#ff8a8a' ?>;">
                <p style="margin-bottom: 1rem; font-size: 1.05rem;"><?= htmlspecialchars($message) ?></p>
                <div style="display:flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Go to login</a>
                    <a href="index.php" class="btn"><i class="fas fa-house"></i> Back to home</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>