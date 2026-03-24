<?php
require_once 'config.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM ai_texts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$texts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generation History - TextGenerator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0a0e27;
            --bg-secondary: #141829;
            --bg-tertiary: #1a1f3a;
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --accent-blue: #667eea;
            --accent-purple: #764ba2;
            --border-color: rgba(255, 255, 255, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-tertiary) 100%);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            min-height: 100vh;
        }

        nav {
            background: rgba(10, 14, 39, 0.95);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5%;
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
            max-width: 1000px;
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

        .empty-state {
            text-align: center;
            background: rgba(79, 124, 255, 0.08);
            border: 1px solid rgba(120, 153, 255, 0.22);
            border-radius: 12px;
            padding: 3rem 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            display: block;
        }

        .empty-state h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .history-item {
            background: rgba(79, 124, 255, 0.08);
            border: 1px solid rgba(120, 153, 255, 0.22);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s ease;
        }

        .history-item:hover {
            border-color: rgba(120, 153, 255, 0.4);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .item-date {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .item-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.6rem 1rem;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 6px;
            color: var(--accent-blue);
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: rgba(102, 126, 234, 0.2);
            border-color: rgba(102, 126, 234, 0.3);
        }

        .btn-danger {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }

        .btn-danger:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .prompt-preview {
            margin-bottom: 0.8rem;
        }

        .prompt-preview h3 {
            word-break: break-word;
        }

        .result-box {
            background: rgba(15, 28, 68, 0.5);
            border-left: 3px solid var(--accent-blue);
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            max-height: 100px;
            overflow-y: auto;
        }

        .result-text {
            color: #b7c8ff;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .char-count {
            display: inline-block;
            background: rgba(79, 124, 255, 0.2);
            border: 1px solid rgba(120, 153, 255, 0.3);
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #b7c8ff;
        }

        .center-text {
            text-align: center;
            margin-top: 3rem;
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

            .item-header {
                flex-direction: column;
            }

            .item-actions {
                width: 100%;
            }

            .item-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-container">
            <a href="index.php" class="logo">TextGenerator</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="generate.php">Generate</a>
                <a href="history.php">History</a>
                <a href="profile.php">Profile</a>
                <?php if (isAdmin()): ?>
                <a href="admin.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <h1>Generation History</h1>
            <p>View, edit & manage all your generated texts</p>
        </div>

        <?php if (empty($texts)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h2>No History Yet</h2>
                <p>You haven't generated any text yet. Create your first text now!</p>
                <a href="generate.php" class="btn btn-primary"><i class="fas fa-wand-magic-sparkles"></i> Start Generating</a>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($texts as $text): ?>
                    <div class="history-item">
                        <div class="item-header">
                            <div style="flex: 1;">
                                <p class="item-date"><i class="fas fa-calendar"></i> <?php echo date('M d, Y • H:i', strtotime($text['created_at'])); ?></p>
                            </div>
                            <div class="item-actions">
                                <a href="update_text.php?id=<?php echo $text['id']; ?>" class="btn"><i class="fas fa-edit"></i> Edit</a>
                                <a href="delete_text.php?id=<?php echo $text['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this text?');"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </div>

                        <div class="prompt-preview">
                            <h3><?php echo htmlspecialchars(substr($text['prompt'], 0, 100)); ?><?php echo strlen($text['prompt']) > 100 ? '...' : ''; ?></h3>
                        </div>

                        <div class="result-box">
                            <p class="result-text"><?php echo htmlspecialchars(substr($text['result'], 0, 200)); ?><?php echo strlen($text['result']) > 200 ? '...' : ''; ?></p>
                        </div>

                        <span class="char-count"><?php echo strlen($text['result']); ?> chars</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="center-text">
                <a href="generate.php" class="btn btn-primary"><i class="fas fa-wand-magic-sparkles"></i> Create More</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>