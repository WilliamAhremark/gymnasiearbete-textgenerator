<?php
require_once 'config.php';
requireLogin();

$textId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['text_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM ai_texts WHERE id = ? AND user_id = ?');
$stmt->execute([$textId, $_SESSION['user_id']]);
$text = $stmt->fetch();

if (!$text) {
    http_response_code(404);
    die('Texten hittades inte eller du har inte behörighet att radera den.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ogiltig session. Försök igen.');
    }

    $deleteStmt = $pdo->prepare('DELETE FROM ai_texts WHERE id = ? AND user_id = ?');
    $deleteStmt->execute([$textId, $_SESSION['user_id']]);

    header('Location: history.php');
    exit;
}

$pageTitle = 'Delete text - NeuralText AI';
include 'includes/header.php';
?>

<main>
    <section class="section page-hero">
        <div class="container" style="max-width: 720px; margin: 0 auto;">
            <div class="section-header scroll-animate">
                <div class="section-label">Delete</div>
                <h1 class="section-title">Delete saved text</h1>
                <p class="section-description">This action cannot be undone.</p>
            </div>

            <div style="background: rgba(220, 53, 69, 0.08); border: 1px solid rgba(220, 53, 69, 0.25); border-radius: 16px; padding: 1.2rem; margin-bottom: 1.2rem;">
                <p style="color: #ff8a8a; margin-bottom: 0.6rem;"><strong>Prompt:</strong></p>
                <p style="white-space: pre-wrap; color: #dbe6ff;"><?= htmlspecialchars($text['input_text']) ?></p>
            </div>

            <form method="POST" class="scroll-animate" onsubmit="return confirm('Do you really want to delete this text?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                <input type="hidden" name="text_id" value="<?= (int)$text['id'] ?>">

                <div style="display:flex; gap: 0.75rem; flex-wrap: wrap;">
                    <button type="submit" class="btn history-btn history-btn-danger"><i class="fas fa-trash"></i> Delete permanently</button>
                    <a href="history.php" class="btn"><i class="fas fa-arrow-left"></i> Cancel</a>
                </div>
            </form>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
