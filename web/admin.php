<?php
require_once 'config.php';
requireAdmin();

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Ogiltig session. Försök igen.';
    } else {
        $action = $_POST['action'] ?? '';
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        if ($targetUserId <= 0) {
            $errors[] = 'Ogiltigt användar-ID.';
        } elseif ($targetUserId === (int)$_SESSION['user_id'] && $action === 'toggle_role') {
            $errors[] = 'Du kan inte ändra din egen administratörsroll här.';
        } else {
            if ($action === 'toggle_role') {
                $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$targetUserId]);
                $currentRole = $stmt->fetchColumn();

                if ($currentRole === false) {
                    $errors[] = 'Användaren hittades inte.';
                } else {
                    $newRole = $currentRole === 'admin' ? 'user' : 'admin';
                    $updateStmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
                    $updateStmt->execute([$newRole, $targetUserId]);
                    $message = 'Roll uppdaterad.';
                }
            }

            if ($action === 'toggle_verification') {
                $stmt = $pdo->prepare('SELECT is_verified FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$targetUserId]);
                $currentVerified = $stmt->fetchColumn();

                if ($currentVerified === false) {
                    $errors[] = 'Användaren hittades inte.';
                } else {
                    $newValue = (int)!((bool)$currentVerified);
                    $updateStmt = $pdo->prepare('UPDATE users SET is_verified = ? WHERE id = ?');
                    $updateStmt->execute([$newValue, $targetUserId]);
                    $message = 'Verifieringsstatus uppdaterad.';
                }
            }
        }
    }
}

$usersStmt = $pdo->query('SELECT id, email, username, role, is_verified, created_at, last_login FROM users ORDER BY created_at DESC');
$users = $usersStmt->fetchAll();

$statsStmt = $pdo->query('SELECT COUNT(*) AS total_users, SUM(role = "admin") AS admins, SUM(is_verified = 1) AS verified_users FROM users');
$stats = $statsStmt->fetch() ?: ['total_users' => 0, 'admins' => 0, 'verified_users' => 0];

$textsStmt = $pdo->query(
    'SELECT a.id, a.input_text, a.generated_text, a.created_at, u.username 
     FROM ai_texts a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT 10'
);
$recentTexts = $textsStmt->fetchAll();

$pageTitle = 'Admin panel - NeuralText AI';
include 'includes/header.php';
?>

<main>
    <section class="section page-hero">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            <div class="section-header scroll-animate">
                <div class="section-label">Admin</div>
                <h1 class="section-title">Administration panel</h1>
                <p class="section-description">Manage users, verification and roles.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1rem; color: #ff8a8a;">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($message !== ''): ?>
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1rem; color: #86efac;">
                    <p><?= htmlspecialchars($message) ?></p>
                </div>
            <?php endif; ?>

            <div class="dash-stats scroll-animate" style="margin-bottom: 1.5rem;">
                <div>
                    <div class="dash-stat-label">Users</div>
                    <div class="dash-stat-value"><?= (int)($stats['total_users'] ?? 0) ?></div>
                </div>
                <div>
                    <div class="dash-stat-label">Admins</div>
                    <div class="dash-stat-value"><?= (int)($stats['admins'] ?? 0) ?></div>
                </div>
                <div>
                    <div class="dash-stat-label">Verified</div>
                    <div class="dash-stat-value"><?= (int)($stats['verified_users'] ?? 0) ?></div>
                </div>
            </div>

            <div class="section-header scroll-animate" style="margin-top: 2rem;">
                <div class="section-label">Users</div>
                <h2 class="section-title" style="font-size: 1.5rem;">User management</h2>
            </div>

            <div style="overflow-x: auto;" class="scroll-animate">
                <table style="width:100%; border-collapse: collapse; min-width: 860px; background: rgba(6, 13, 34, 0.65); border: 1px solid rgba(128, 158, 255, 0.2); border-radius: 16px; overflow: hidden;">
                    <thead>
                        <tr style="text-align:left; color:#cfe0ff; background: rgba(255,255,255,0.03);">
                            <th style="padding: 0.9rem;">User</th>
                            <th style="padding: 0.9rem;">Email</th>
                            <th style="padding: 0.9rem;">Role</th>
                            <th style="padding: 0.9rem;">Verified</th>
                            <th style="padding: 0.9rem;">Created</th>
                            <th style="padding: 0.9rem;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr style="border-top: 1px solid rgba(128, 158, 255, 0.15);">
                                <td style="padding: 0.9rem;"><?= htmlspecialchars($user['username']) ?></td>
                                <td style="padding: 0.9rem;"><?= htmlspecialchars($user['email']) ?></td>
                                <td style="padding: 0.9rem;"><?= htmlspecialchars($user['role']) ?></td>
                                <td style="padding: 0.9rem;"><?= !empty($user['is_verified']) ? 'Yes' : 'No' ?></td>
                                <td style="padding: 0.9rem;"><?= htmlspecialchars($user['created_at']) ?></td>
                                <td style="padding: 0.9rem;">
                                    <div style="display:flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                            <input type="hidden" name="action" value="toggle_role">
                                            <button type="submit" class="btn" style="padding: 0.55rem 0.8rem;"><?= $user['role'] === 'admin' ? 'Demote' : 'Promote' ?></button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                            <input type="hidden" name="action" value="toggle_verification">
                                            <button type="submit" class="btn" style="padding: 0.55rem 0.8rem;"><?= !empty($user['is_verified']) ? 'Unverify' : 'Verify' ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="section-header scroll-animate" style="margin-top: 2.5rem;">
                <div class="section-label">Content</div>
                <h2 class="section-title" style="font-size: 1.5rem;">Recent generated texts</h2>
            </div>

            <div class="scroll-animate" style="display:grid; gap: 0.9rem;">
                <?php foreach ($recentTexts as $text): ?>
                    <article style="padding: 1rem; border-radius: 14px; border: 1px solid rgba(128, 158, 255, 0.18); background: rgba(6, 13, 34, 0.65);">
                        <div style="display:flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.86rem;">
                            <span><?= htmlspecialchars($text['username'] ?? 'Unknown') ?></span>
                            <span><?= htmlspecialchars($text['created_at']) ?></span>
                        </div>
                        <p style="margin-bottom: 0.45rem; color:#dbe6ff;"><strong>Prompt:</strong> <?= htmlspecialchars(mb_strimwidth((string)$text['input_text'], 0, 120, '...')) ?></p>
                        <p style="color: #c5d3f7;"><strong>Result:</strong> <?= htmlspecialchars(mb_strimwidth((string)$text['generated_text'], 0, 180, '...')) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>