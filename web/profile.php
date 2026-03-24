<?php
require_once 'config.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$pageTitle = 'My Profile - NeuralText AI';
include 'includes/header.php';
?>

<main>
    <section class="section">
        <div class="container">
            <div style="text-align: center; margin-bottom: 3rem;" class="scroll-animate">
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Account</p>
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">My Profile</h1>
                <p style="color: var(--text-secondary);">Manage your account settings</p>
            </div>

            <div style="max-width: 600px; margin: 0 auto;">
                <!-- Profile Info Card -->
                <div class="scroll-animate" style="background: rgba(79, 124, 255, 0.08); border: 1px solid rgba(120, 153, 255, 0.22); border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
                    <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Profile Information</h2>
                    
                    <div style="display: grid; gap: 1rem;">
                        <div class="scroll-animate-item">
                            <label style="display: block; color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.3rem;">Username</label>
                            <div style="background: rgba(15, 28, 68, 0.5); border: 1px solid rgba(128, 158, 255, 0.28); border-radius: 11px; padding: 0.75rem 0.85rem; color: var(--text-primary);">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </div>
                        </div>

                        <div class="scroll-animate-item">
                            <label style="display: block; color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.3rem;">Email Address</label>
                            <div style="background: rgba(15, 28, 68, 0.5); border: 1px solid rgba(128, 158, 255, 0.28); border-radius: 11px; padding: 0.75rem 0.85rem; color: var(--text-primary);">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                        </div>

                        <div class="scroll-animate-item">
                            <label style="display: block; color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.3rem;">Account Type</label>
                            <div style="background: rgba(15, 28, 68, 0.5); border: 1px solid rgba(128, 158, 255, 0.28); border-radius: 11px; padding: 0.75rem 0.85rem; color: var(--text-primary);">
                                <span style="text-transform: capitalize;"><?php echo htmlspecialchars($user['role']); ?></span>
                                <span style="background: rgba(79, 124, 255, 0.3); color: #b7c8ff; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem; margin-left: 0.5rem;">verified</span>
                            </div>
                        </div>

                        <div class="scroll-animate-item">
                            <label style="display: block; color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.3rem;">Member Since</label>
                            <div style="background: rgba(15, 28, 68, 0.5); border: 1px solid rgba(128, 158, 255, 0.28); border-radius: 11px; padding: 0.75rem 0.85rem; color: var(--text-primary);">
                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Section -->
                <div class="scroll-animate" style="background: rgba(79, 124, 255, 0.08); border: 1px solid rgba(120, 153, 255, 0.22); border-radius: 12px; padding: 2rem;">
                    <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Security</h2>
                    
                    <div style="display: grid; gap: 1rem;">
                        <div class="scroll-animate-item">
                            <a href="change-password.php" class="btn btn-primary" style="width: 100%; text-align: center; display: block;">
                                <i class="fas fa-lock"></i> Change Password
                            </a>
                        </div>
                        <div class="scroll-animate-item">
                            <p style="color: var(--text-secondary); font-size: 0.9rem; background: rgba(102, 126, 234, 0.1); padding: 1rem; border-radius: 8px;">
                                <i class="fas fa-shield-alt"></i> Your password is encrypted using bcrypt with cost factor 12 for maximum security.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="scroll-animate" style="background: rgba(79, 124, 255, 0.08); border: 1px solid rgba(120, 153, 255, 0.22); border-radius: 12px; padding: 1.5rem; margin-top: 2rem; text-align: center;">
                    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Need to delete your account? <a href="delete-account.php" style="color: #ff6b6b; font-weight: 600; text-decoration: none;">Contact support</a>
                    </p>
                </div>

                <!-- Back button -->
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="dashboard.php" class="btn" style="display: inline-flex; gap: 0.5rem; padding: 0.75rem 1.5rem;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
            align-items: center;
        }

        .logo {
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

        .main-content {
            max-width: 600px;
            margin: 3rem auto;
            padding: 0 5%;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .info-section {
            margin-bottom: 2rem;
        }

        .info-section:last-child {
            margin-bottom: 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .value {
            color: var(--text-primary);
            font-weight: 600;
        }

        .back-link {
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

        .back-link:hover {
            background: rgba(102, 126, 234, 0.2);
            border-color: rgba(102, 126, 234, 0.3);
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .main-content {
                margin: 2rem auto;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }