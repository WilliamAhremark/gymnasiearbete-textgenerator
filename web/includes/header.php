<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'NeuralText AI' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/shared.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <nav class="site-nav">
        <div class="container nav-wrap">
            <a href="index.php" class="logo nav-text-animate">Neural<strong>Text</strong> AI</a>
            <div class="nav-links">
                <a href="index.php" class="nav-text-animate">Home</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="nav-text-animate">Dashboard</a>
                    <a href="history.php" class="nav-text-animate">History</a>
                    <a href="generate.php" class="nav-text-animate">Generate</a>
                    <a href="profile.php" class="nav-text-animate">Profile</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin.php" class="nav-text-animate">Admin</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="index.php#features" class="nav-text-animate">Features</a>
                    <a href="index.php#tech" class="nav-text-animate">Technology</a>
                    <a href="index.php#about" class="nav-text-animate">About</a>
                <?php endif; ?>
            </div>
            <div class="nav-right">
                <div class="nav-cta">
                    <?php if (isLoggedIn()): ?>
                        <span style="color: var(--text-secondary); font-size: 0.9rem;" class="nav-text-animate">
                            Welcome, <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>
                        </span>
                        <a href="logout.php" class="btn"><span class="nav-text-animate">Sign Out</span></a>
                    <?php else: ?>
                        <a href="login.php" class="btn"><span class="nav-text-animate">Sign In</span></a>
                        <a href="register.php" class="btn btn-primary"><span class="nav-text-animate">Get Started</span></a>
                    <?php endif; ?>
                </div>
                <button class="nav-menu-toggle" type="button" aria-label="Open menu" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <style>
        .nav-text-animate {
            opacity: 0;
            animation: navTextCenterOut 0.72s cubic-bezier(.21,1.02,.73,1) var(--intro-delay) forwards;
        }

        .nav-links .nav-text-animate:nth-child(1) { animation-delay: calc(var(--intro-delay) + 0.06s); }
        .nav-links .nav-text-animate:nth-child(2) { animation-delay: calc(var(--intro-delay) + 0.12s); }
        .nav-links .nav-text-animate:nth-child(3) { animation-delay: calc(var(--intro-delay) + 0.18s); }
        .nav-links .nav-text-animate:nth-child(4) { animation-delay: calc(var(--intro-delay) + 0.24s); }
        .nav-links .nav-text-animate:nth-child(5) { animation-delay: calc(var(--intro-delay) + 0.30s); }
        .nav-cta .btn:nth-child(1) .nav-text-animate,
        .nav-cta > span { animation-delay: calc(var(--intro-delay) + 0.36s); }
        .nav-cta .btn:nth-child(2) .nav-text-animate { animation-delay: calc(var(--intro-delay) + 0.42s); }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const nav = document.querySelector('.site-nav');
            const toggle = nav ? nav.querySelector('.nav-menu-toggle') : null;
            const links = nav ? nav.querySelectorAll('.nav-links a') : [];

            if (!nav || !toggle) {
                return;
            }

            const closeMenu = () => {
                nav.classList.remove('nav-open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.innerHTML = '<i class="fas fa-bars"></i>';
            };

            toggle.addEventListener('click', () => {
                const isOpen = nav.classList.toggle('nav-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                toggle.innerHTML = isOpen ? '<i class="fas fa-xmark"></i>' : '<i class="fas fa-bars"></i>';
            });

            links.forEach(link => link.addEventListener('click', closeMenu));

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeMenu();
                }
            });

            document.addEventListener('click', (e) => {
                if (!nav.contains(e.target)) {
                    closeMenu();
                }
            });
        });
    </script>
