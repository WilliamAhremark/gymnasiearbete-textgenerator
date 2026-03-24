<?php
require_once 'config.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Hämta statistik
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ai_texts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$text_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT MAX(created_at) FROM ai_texts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$last_generated = $stmt->fetchColumn();

$pageTitle = 'Dashboard - NeuralText AI';
include 'includes/header.php';
?>

<main>
    <section class="section" style="min-height: 80vh;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 3rem;" class="scroll-animate">
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Welcome back</p>
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Dashboard</h1>
                <p style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['username']); ?>'s control center</p>
            </div>

            <!-- Quick Stats -->
            <div class="cards-grid" style="margin-bottom: 3rem;">
                <div class="scroll-animate" style="text-align: center; padding: 2rem;">
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--accent-soft); margin-bottom: 0.5rem;">
                        <?php echo $text_count; ?>
                    </div>
                    <div style="color: var(--text-secondary);">Generated Texts</div>
                </div>
                <div class="scroll-animate" style="text-align: center; padding: 2rem;">
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--accent-soft); margin-bottom: 0.5rem;">
                        <?php echo date('M d', strtotime($user['created_at'])); ?>
                    </div>
                    <div style="color: var(--text-secondary);">Member Since</div>
                </div>
                <div class="scroll-animate" style="text-align: center; padding: 2rem;">
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--accent-soft); margin-bottom: 0.5rem;">
                        <?php echo ucfirst($user['role']); ?>
                    </div>
                    <div style="color: var(--text-secondary);">Account Type</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <a href="generate.php" class="scroll-animate" style="background: var(--accent-gradient); border: none; border-radius: 12px; padding: 2rem; text-decoration: none; color: white; text-align: center; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-wand-magic-sparkles"></i></div>
                    <strong style="display: block; margin-bottom: 0.3rem;">Generate Text</strong>
                    <small style="color: rgba(255,255,255,0.8);">Create content with AI</small>
                </a>

                <a href="history.php" class="scroll-animate" style="background: rgba(79, 124, 255, 0.15); border: 1px solid rgba(120, 153, 255, 0.22); border-radius: 12px; padding: 2rem; text-decoration: none; color: white; text-align: center; transition: transform 0.2s ease, border-color 0.2s ease;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-history"></i></div>
                    <strong style="display: block; margin-bottom: 0.3rem;">View History</strong>
                    <small style="color: var(--text-secondary);">See your generated texts</small>
                </a>

                <a href="profile.php" class="scroll-animate" style="background: rgba(79, 124, 255, 0.15); border: 1px solid rgba(120, 153, 255, 0.22); border-radius: 12px; padding: 2rem; text-decoration: none; color: white; text-align: center; transition: transform 0.2s ease, border-color 0.2s ease;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-user"></i></div>
                    <strong style="display: block; margin-bottom: 0.3rem;">My Profile</strong>
                    <small style="color: var(--text-secondary);">Manage your account</small>
                </a>
            </div>

            <!-- Info Card -->
            <div class="scroll-animate" style="background: rgba(79, 124, 255, 0.08); border: 1px solid rgba(120, 153, 255, 0.22); border-radius: 12px; padding: 2rem;">
                <h2 style="margin-bottom: 1rem;">Quick Guide</h2>
                <ul style="color: var(--text-secondary); line-height: 1.8; padding-left: 1.5rem;">
                    <li>Go to <strong style="color: var(--text-primary);">Generate</strong> to create new text</li>
                    <li>Check <strong style="color: var(--text-primary);">History</strong> to see your past generations</li>
                    <li>Edit or delete texts from your history anytime</li>
                    <li>Update your profile information in <strong style="color: var(--text-primary);">Settings</strong></li>
                </ul>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
