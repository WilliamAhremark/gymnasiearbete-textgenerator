require_once 'config.php';
requireLogin();

\$textId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['text_id'] ?? 0);

\$stmt = $pdo->prepare('SELECT * FROM ai_texts WHERE id = ? AND user_id = ?');
\$stmt->execute([$textId, $_SESSION['user_id']]);
\$text = $stmt->fetch();

if (!\$text) {
    http_response_code(404);
    die('Texten hittades inte eller du har inte behörighet att ändra den.');
}

\$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        \$errors[] = 'Ogiltig session. Försök igen.';
    }

    \$inputText = trim((string)($_POST['input_text'] ?? ''));
    \$generatedText = trim((string)($_POST['generated_text'] ?? ''));

    if (\$inputText === '' || \$generatedText === '') {
        \$errors[] = 'Alla fält måste fyllas i.';
    }

    if (strlen(\$inputText) > 2000 || strlen(\$generatedText) > 5000) {
        \$errors[] = 'Texten är för lång.';
    }

    if (empty(\$errors)) {
        \$updateStmt = $pdo->prepare('UPDATE ai_texts SET input_text = ?, generated_text = ? WHERE id = ? AND user_id = ?');
        \$updateStmt->execute([\$inputText, \$generatedText, \$textId, $_SESSION['user_id']]);

        header('Location: history.php');
        exit;
    }
}

\$pageTitle = 'Edit text - NeuralText AI';
include 'includes/header.php';
?>

<main>
    <section class="section page-hero">
        <div class="container" style="max-width: 900px; margin: 0 auto;">
            <div class="section-header scroll-animate">
                <div class="section-label">Edit</div>
                <h1 class="section-title">Edit saved text</h1>
                <p class="section-description">Update the prompt and result for this saved AI entry.</p>
            </div>

            <?php if (!empty(\$errors)): ?>
                <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: #ff8a8a;">
                    <?php foreach (\$errors as \$error): ?>
                        <p><?= htmlspecialchars(\$error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="scroll-animate">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                <input type="hidden" name="text_id" value="<?= (int)\$text['id'] ?>">

                <div class="form-group">
                    <label for="input_text">Prompt</label>
                    <textarea id="input_text" name="input_text" rows="6" maxlength="2000"><?= htmlspecialchars($_POST['input_text'] ?? \$text['input_text']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="generated_text">Generated text</label>
                    <textarea id="generated_text" name="generated_text" rows="10" maxlength="5000"><?= htmlspecialchars($_POST['generated_text'] ?? \$text['generated_text']) ?></textarea>
                </div>

                <div style="display:flex; gap: 0.75rem; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save changes</button>
                    <a href="history.php" class="btn"><i class="fas fa-arrow-left"></i> Back to history</a>
                </div>
            </form>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
