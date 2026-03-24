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
                <?php else: ?>
                    <a href="index.php#features" class="nav-text-animate">Features</a>
                    <a href="index.php#tech" class="nav-text-animate">Technology</a>
                <?php endif; ?>
            </div>
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
