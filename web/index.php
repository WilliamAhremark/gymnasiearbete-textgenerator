<?php
require_once 'config.php';

// If already logged in, go directly to dashboard
// if (isLoggedIn()) {
//     header("Location: dashboard.php");
//     exit;
// }

// Demo is now unlimited for all visitors.
$ai_test_used = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text Generator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #030716;
            --bg-section: #060d22;
            --card-bg: rgba(9, 18, 44, 0.72);
            --card-border: rgba(120, 153, 255, 0.22);
            --text-primary: #eef3ff;
            --text-secondary: #9caed8;
            --accent: #4f7cff;
            --accent-soft: #7fa0ff;
            --accent-gradient: linear-gradient(135deg, #3165ff 0%, #8e7eff 100%);
            --shadow-soft: 0 24px 55px rgba(9, 16, 39, 0.45);
            --nav-height: 78px;
            --intro-delay: 0s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: radial-gradient(circle at 20% 15%, rgba(68, 117, 255, 0.22), transparent 32%),
                        radial-gradient(circle at 80% 20%, rgba(99, 84, 255, 0.18), transparent 34%),
                        radial-gradient(circle at 50% 100%, rgba(20, 49, 135, 0.28), transparent 48%),
                        var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            width: min(1180px, 92%);
            margin: 0 auto;
        }

        main {
            width: min(1240px, 96%);
            margin: 0 auto 2rem;
            border-radius: 0;
            border: none;
            background: transparent;
            box-shadow: none;
            overflow: hidden;
        }

        .site-nav {
            position: sticky;
            top: 0;
            z-index: 999;
            backdrop-filter: blur(14px);
            background: rgba(3, 7, 22, 0.7);
            border-bottom: 1px solid rgba(140, 166, 255, 0.18);
            opacity: 0;
            animation: fadeInOnly 0.55s ease var(--intro-delay) forwards;
        }

        .nav-wrap {
            min-height: 78px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            color: var(--text-primary);
        }

        .logo strong {
            color: var(--accent-soft);
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.93rem;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: var(--text-primary);
        }

        .nav-cta {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .btn {
            border: 1px solid rgba(129, 159, 255, 0.28);
            border-radius: 12px;
            padding: 0.72rem 1.2rem;
            text-decoration: none;
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            font-weight: 600;
            font-size: 0.92rem;
            transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
            cursor: pointer;
            background: rgba(16, 30, 74, 0.45);
        }

        .btn:hover {
            transform: translateY(-1px);
            border-color: rgba(145, 173, 255, 0.7);
        }

        .btn-primary {
            background: var(--accent-gradient);
            border-color: transparent;
            box-shadow: 0 12px 28px rgba(61, 94, 255, 0.38);
        }

        .btn-icon-video {
            width: 3em;
            height: 3em;
            object-fit: contain;
            display: block;
            flex-shrink: 0;
        }

        .hero {
            position: relative;
            min-height: calc(100vh - var(--nav-height));
            display: flex;
            align-items: center;
            padding: 24px 0;
        }

        .hero-shell {
            position: relative;
            border: none;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            overflow: hidden;
            padding: 2.8rem 1.2rem 2.2rem;
        }

        @keyframes fadeInOnly {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes heroFadeUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-intro {
            opacity: 0;
            animation: heroFadeUp 0.7s cubic-bezier(.21,1.02,.73,1) forwards;
        }

        .hero-intro-title {
            opacity: 0;
            animation: heroFadeUp 0.85s cubic-bezier(.21,1.02,.73,1) var(--intro-delay) forwards;
        }

        .hero-intro-badge { animation-delay: calc(var(--intro-delay) + 0.22s); }
        .hero-intro-text { animation-delay: calc(var(--intro-delay) + 0.42s); }
        .hero-intro-cta { animation-delay: calc(var(--intro-delay) + 0.62s); }
        .hero-intro-stats { animation-delay: calc(var(--intro-delay) + 0.82s); }

        @keyframes navTextCenterOut {
            from {
                opacity: 0;
                clip-path: inset(0 50% 0 50%);
                filter: blur(6px);
            }
            to {
                opacity: 1;
                clip-path: inset(0 0 0 0);
                filter: blur(0);
            }
        }

        .nav-text-animate {
            opacity: 0;
            animation: navTextCenterOut 0.72s cubic-bezier(.21,1.02,.73,1) var(--intro-delay) forwards;
        }

        .nav-links .nav-text-animate:nth-child(2) { animation-delay: calc(var(--intro-delay) + 0.06s); }
        .nav-links .nav-text-animate:nth-child(3) { animation-delay: calc(var(--intro-delay) + 0.12s); }
        .nav-links .nav-text-animate:nth-child(4) { animation-delay: calc(var(--intro-delay) + 0.18s); }
        .nav-cta .btn:nth-child(1) .nav-text-animate { animation-delay: calc(var(--intro-delay) + 0.10s); }
        .nav-cta .btn:nth-child(2) .nav-text-animate { animation-delay: calc(var(--intro-delay) + 0.18s); }

        .hero-content {
            max-width: 760px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid rgba(116, 145, 255, 0.35);
            border-radius: 999px;
            padding: 0.45rem 0.95rem;
            font-size: 0.8rem;
            color: #b7c8ff;
            margin-bottom: 1.3rem;
            background: rgba(25, 45, 107, 0.4);
        }

        .hero h1 {
            font-size: clamp(2.2rem, 5.4vw, 4.2rem);
            line-height: 1.06;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
        }

        .hero h1 span {
            color: #91abff;
        }

        .hero-type-cursor::after {
            content: '|';
            margin-left: 0.08em;
            opacity: 1;
            animation: blinkCursor 0.9s steps(1, end) infinite;
        }

        @keyframes blinkCursor {
            0%, 50% { opacity: 1; }
            50.01%, 100% { opacity: 0; }
        }

        .hero p {
            max-width: 670px;
            margin: 0 auto 1.8rem;
            color: var(--text-secondary);
            font-size: 1.03rem;
        }

        .hero-cta {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .stats-row {
            margin-top: 2rem;
            display: grid;
            grid-template-columns: repeat(3, minmax(120px, 1fr));
            gap: 0.8rem;
            max-width: 760px;
            margin-left: auto;
            margin-right: auto;
        }

        .stat-card {
            border: none;
            border-radius: 0;
            background: transparent;
            padding: 0.5rem 0.75rem;
            text-align: center;
        }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #d8e3ff;
            margin-bottom: 0.2rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.84rem;
        }

        .hero-bg-left,
        .hero-bg-right {
            position: absolute;
            width: min(270px, 30vw);
            height: min(300px, 30vw);
            bottom: 0.5rem;
            z-index: 1;
            pointer-events: none;
            opacity: 0.18;
            background: radial-gradient(circle at center, rgba(96, 127, 255, 0.35), transparent 68%);
            animation: fadeInOnly 0.9s ease calc(var(--intro-delay) + 0.18s) both;
        }

        .hero-bg-left {
            left: 1rem;
        }

        .hero-bg-right {
            right: 1rem;
        }

        .section {
            padding: 56px 0;
        }

        .section + .section {
            border-top: none;
        }

        .section-dark {
            background: transparent;
        }

        .section-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .section-label {
            color: #8ba6ff;
            font-size: 0.82rem;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            margin-bottom: 0.6rem;
        }

        .section-title {
            font-size: clamp(1.7rem, 4vw, 2.8rem);
            margin-bottom: 0.8rem;
        }

        .section-description {
            color: var(--text-secondary);
            max-width: 760px;
            margin: 0 auto;
        }

        .demo-layout {
            display: grid;
            gap: 1.2rem;
            max-width: 980px;
            margin: 0 auto;
        }

        .demo-card {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 1.4rem;
        }

        .demo-card h2 {
            margin-bottom: 0.55rem;
            font-size: 1.45rem;
        }

        .demo-card > p {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .ai-form {
            display: grid;
            gap: 0.85rem;
        }

        .ai-form label {
            color: #cad8ff;
            font-size: 0.9rem;
        }

        .ai-form textarea,
        .ai-form input[type="number"] {
            border-radius: 11px;
            border: 1px solid rgba(128, 158, 255, 0.28);
            background: rgba(15, 28, 68, 0.65);
            color: var(--text-primary);
            padding: 0.75rem 0.85rem;
        }

        .ai-form textarea {
            min-height: 120px;
            resize: vertical;
        }

        .ai-actions {
            display: flex;
            align-items: flex-end;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .ai-status {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .ai-output {
            border: 1px solid rgba(128, 158, 255, 0.22);
            border-radius: 12px;
            background: rgba(8, 18, 46, 0.7);
            min-height: 90px;
            padding: 0.9rem;
            white-space: pre-wrap;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(200px, 1fr));
            gap: 0.8rem;
            padding: 1.2rem;
        }

        .feature-card,
        .tech-item,
        .mini-card {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 1.2rem 0.6rem;
            box-shadow: none;
        }

        .feature-card {
            position: relative;
            min-height: 220px;
            overflow: hidden;
            padding: 1.05rem 0.8rem;
        }

        .feature-card h3 {
            font-size: 1.08rem;
            margin: 0.65rem 0 0.4rem;
        }

        .feature-card p,
        .mini-card p,
        .tech-item p {
            color: var(--text-secondary);
            font-size: 0.88rem;
        }

        .feature-icon {
            width: 32px;
            height: 32px;
            border-radius: 0;
            background: transparent;
            border: none;
            display: grid;
            place-items: center;
            color: #bad0ff;
            font-size: 0.92rem;
            padding: 0;
        }

        .tech-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(120px, 1fr));
            gap: 0.8rem;
        }

        .tech-item {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 0.45rem;
        }

        .tech-item .feature-icon {
            width: 44px;
            height: 44px;
            font-size: 1.2rem;
            margin: 0 auto;
        }

        .tech-name {
            font-weight: 600;
            color: #dde7ff;
            margin-top: 0;
        }

        .cta-shell {
            max-width: 980px;
            margin: 0 auto;
            border-radius: 0;
            border: none;
            background: transparent;
            padding: 2.3rem;
            text-align: center;
        }

        .cta-shell h2 {
            font-size: clamp(1.6rem, 4vw, 2.4rem);
            margin-bottom: 0.7rem;
        }

        .cta-shell p {
            color: #cad7ff;
            max-width: 700px;
            margin: 0 auto 1.25rem;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        footer {
            border-top: none;
            padding: 2.4rem 0 1.6rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr repeat(3, 1fr);
            gap: 1.2rem;
            margin-bottom: 1.4rem;
        }

        .footer-col h4 {
            margin-bottom: 0.7rem;
        }

        .footer-col p,
        .footer-col a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.92rem;
        }

        .footer-col ul {
            list-style: none;
            display: grid;
            gap: 0.5rem;
        }

        .footer-col a:hover {
            color: #d9e4ff;
        }

        .footer-bottom {
            border-top: none;
            padding-top: 1rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .scroll-animate {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.6s ease, transform 0.6s cubic-bezier(.21,1.02,.73,1);
            will-change: opacity, transform;
        }

        .scroll-animate.show {
            opacity: 1;
            transform: translateY(0);
        }

        .scroll-animate-item {
            transform: translateY(24px);
            transition-duration: 0.5s;
        }

        .cards-grid .scroll-animate:nth-child(2),
        .tech-grid .scroll-animate:nth-child(2),
        .footer-grid .scroll-animate:nth-child(2) {
            transition-delay: 0.08s;
        }

        .cards-grid .scroll-animate:nth-child(3),
        .tech-grid .scroll-animate:nth-child(3),
        .footer-grid .scroll-animate:nth-child(3) {
            transition-delay: 0.16s;
        }

        .cards-grid .scroll-animate:nth-child(4),
        .tech-grid .scroll-animate:nth-child(4),
        .footer-grid .scroll-animate:nth-child(4) {
            transition-delay: 0.24s;
        }

        .cards-grid .scroll-animate:nth-child(5),
        .tech-grid .scroll-animate:nth-child(5) {
            transition-delay: 0.32s;
        }

        .cards-grid .scroll-animate:nth-child(6),
        .tech-grid .scroll-animate:nth-child(6) {
            transition-delay: 0.40s;
        }

        @media (max-width: 1280px) {
            .cards-grid {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .cards-grid {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
            }

            .tech-grid {
                grid-template-columns: repeat(3, minmax(120px, 1fr));
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            main {
                width: 100%;
                margin: 0;
                border-radius: 0;
                border-left: none;
                border-right: none;
            }

            .nav-links {
                display: none;
            }

            .nav-wrap {
                min-height: 68px;
            }

            :root {
                --nav-height: 68px;
            }

            .hero-shell {
                padding: 2.2rem 0.2rem 1.8rem;
            }

            .hero-bg-left,
            .hero-bg-right {
                display: none;
            }

            .stats-row,
            .cards-grid,
            .tech-grid,
            .footer-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
            }

            .hero-cta,
            .cta-buttons,
            .nav-cta {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="site-nav">
        <div class="container nav-wrap">
            <div class="logo nav-text-animate">Neural<strong>Text</strong> AI</div>
            <div class="nav-links">
                <a href="#home" class="nav-text-animate">Home</a>
                <a href="#features" class="nav-text-animate">Features</a>
                <a href="#tech" class="nav-text-animate">Technology</a>
                <a href="#about" class="nav-text-animate">About</a>
            </div>
            <div class="nav-cta">
                <a href="login.php" class="btn"><span class="nav-text-animate">Sign In</span></a>
                <a href="register.php" class="btn btn-primary"><span class="nav-text-animate">Get Started</span></a>
            </div>
        </div>
    </nav>

    <main id="home">
        <section class="hero">
            <div class="container">
                <div class="hero-shell">
                    <div class="hero-content">
                        <h1 class="hero-intro-title"><span id="hero-line-1"></span><br><span id="hero-line-2"></span></h1>
                        <div class="hero-badge hero-intro hero-intro-badge"><i class="fas fa-sparkles"></i> Built with simple AI</div>
                        <p class="hero-intro hero-intro-text">Try a basic AI that can generate text. It is simple and built for a high school project and demo.</p>
                        <div class="hero-cta hero-intro hero-intro-cta">
                            <a href="register.php" class="btn btn-primary"><i class="fas fa-rocket"></i> Start Creating Free</a>
                            <a href="#features" class="btn">
                                <video class="btn-icon-video" autoplay loop muted playsinline>
                                    <source src="assets/Videos/chroma-keyed-video.webm" type="video/webm">
                                </video>
                                See How It Works
                            </a>
                        </div>
                        <div class="stats-row hero-intro hero-intro-stats">
                            <div class="stat-card">
                                <div class="stat-value">500</div>
                                <div class="stat-label">Words per generation</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">99.9%</div>
                                <div class="stat-label">Uptime</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">1 min</div>
                                <div class="stat-label">Response time</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-dark" id="ai-demo">
            <div class="container">
                <div class="demo-layout scroll-animate">
                    <div class="demo-card">
                        <h2>Try simple AI text</h2>
                        <p>Send a prompt and generate text directly via this site's AI endpoint.</p>
                        <div class="ai-form" id="ai-form">
                            <label for="ai-prompt">Prompt</label>
                            <textarea id="ai-prompt" placeholder="Write your starting text here...">This is a test</textarea>

                            <div class="ai-actions">
                                <div>
                                    <label for="ai-length">Length</label>
                                    <input id="ai-length" type="number" min="10" max="500" value="120">
                                </div>
                                <button id="ai-generate" class="btn btn-primary" type="button">
                                    <i class="fas fa-magic"></i> Generate
                                </button>
                                <span class="ai-status" id="ai-status"></span>
                            </div>

                            <div class="ai-output" id="ai-output">The result will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="features">
            <div class="container">
                <div class="section-header scroll-animate">
                    <div class="section-label">Capabilities</div>
                    <h2 class="section-title">Text generation with simple AI</h2>
                    <p class="section-description">This page demonstrates a simple AI that can generate text. The model is basic and trained on character level.</p>
                </div>
                <div class="cards-grid">
                    <article class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-brain"></i></div>
                        <h3>Neural Language Model</h3>
                        <p>Custom-built bigram language model trained on extensive datasets. Understands context and generates coherent, natural-sounding text.</p>
                    </article>
                    <article class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                        <h3>Not super fast Processing</h3>
                        <p>Optimized algorithms deliver results in under 1 minute. Real-time generation with decent latency.</p>
                    </article>
                    <article class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h3>Enterprise Security</h3>
                        <p>Basic encryption, simple authentication, and minimal compliance measures. Your data and generated content have limited protection.</p>
                    </article>
                    <article class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <h3>Analytics Dashboard</h3>
                        <p>Track your usage, monitor performance metrics, and optimize your content generation strategy with detailed insights.</p>
                    </article>
                    <article class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-code"></i></div>
                        <h3>API Integration</h3>
                        <p>RESTful API for integration into your existing workflow. Comprehensive documentation.</p>
                    </article>
                    <article class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-users"></i></div>
                        <h3>Solo usage</h3>
                        <p>Built for individual users without collaborative features.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section-dark" id="tech">
            <div class="container">
                <div class="section-header scroll-animate">
                    <div class="section-label">Technology</div>
                    <h2 class="section-title">Built with simple technology</h2>
                    <p class="section-description">Simple demo showing basic AI and web technology. Everything is built to be easy to understand.</p>
                </div>
                <div class="tech-grid">
                    <div class="tech-item scroll-animate"><div class="feature-icon"><i class="fab fa-python"></i></div><div class="tech-name">Python</div></div>
                    <div class="tech-item scroll-animate"><div class="feature-icon"><i class="fas fa-network-wired"></i></div><div class="tech-name">Neural Network</div></div>
                    <div class="tech-item scroll-animate"><div class="feature-icon"><i class="fab fa-php"></i></div><div class="tech-name">PHP</div></div>
                    <div class="tech-item scroll-animate"><div class="feature-icon"><i class="fas fa-database"></i></div><div class="tech-name">MySQL</div></div>
                    <div class="tech-item scroll-animate"><div class="feature-icon"><i class="fab fa-js"></i></div><div class="tech-name">JavaScript</div></div>
                    <div class="tech-item scroll-animate"><div class="feature-icon"><i class="fab fa-css3-alt"></i></div><div class="tech-name">Modern CSS</div></div>
                </div>
            </div>
        </section>

        <section class="section" id="about">
            <div class="container">
                <div class="cta-shell scroll-animate">
                    <h2>Try simple AI text</h2>
                    <p>This page is a simple demo to show how basic AI can generate text.</p>
                    <div class="cta-buttons">
                        <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create Free Account</a>
                        <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Sign In</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col scroll-animate">
                    <h4>Simple AI Demo</h4>
                    <p>Simple AI text generator built for a high school project. Everything is basic and for demo/fun purposes.</p>
                </div>
                <div class="footer-col scroll-animate">
                    <h4>Product</h4>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#tech">Technology</a></li>
                        <li><a href="register.php">Get Started</a></li>
                        <li><a href="login.php">Sign In</a></li>
                    </ul>
                </div>
                <div class="footer-col scroll-animate">
                    <h4>About</h4>
                    <ul>
                        <li><a href="#about">About Project</a></li>
                        <li><a href="#tech">Tech Stack</a></li>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">GitHub</a></li>
                    </ul>
                </div>
                <div class="footer-col scroll-animate">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">GDPR</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">© <?php echo date('Y'); ?> NeuralText AI - High School Project. Developed with precision and passion.</div>
        </div>
    </footer>

    <script>
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        window.addEventListener('load', () => {
            window.scrollTo(0, 0);

            const line1 = document.getElementById('hero-line-1');
            const line2 = document.getElementById('hero-line-2');

            if (line1 && line2) {
                const textLine1 = 'Simple text generation';
                const textLine2 = 'with basic AI';
                const typeSpeed = 42;

                line1.classList.add('hero-type-cursor');
                line2.style.color = '#91abff';

                let index1 = 0;
                const typeFirstLine = setInterval(() => {
                    if (index1 < textLine1.length) {
                        line1.textContent += textLine1.charAt(index1);
                        index1 += 1;
                    } else {
                        clearInterval(typeFirstLine);
                        line1.classList.remove('hero-type-cursor');
                        line2.classList.add('hero-type-cursor');

                        let index2 = 0;
                        const typeSecondLine = setInterval(() => {
                            if (index2 < textLine2.length) {
                                line2.textContent += textLine2.charAt(index2);
                                index2 += 1;
                            } else {
                                clearInterval(typeSecondLine);
                                line2.classList.remove('hero-type-cursor');
                            }
                        }, typeSpeed);
                    }
                }, typeSpeed);
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Scroll-animation: grupper animeras tillsammans, mindre element var för sig
        const groupedSelectors = [
            '#ai-demo .demo-layout',
            '#features .section-header',
            '#tech .section-header',
            '#about .cta-shell',
            '.footer-bottom'
        ];

        const itemSelectors = [
            '.demo-card h2',
            '.demo-card > p',
            '.ai-form > label',
            '.ai-form textarea',
            '.ai-actions > div',
            '.ai-actions > button',
            '.ai-actions > span',
            '.ai-output',
            '.cards-grid .feature-card',
            '.tech-grid .tech-item',
            '.footer-grid .footer-col'
        ];

        groupedSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                el.classList.add('scroll-animate');
            });
        });

        itemSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                el.classList.add('scroll-animate', 'scroll-animate-item');
            });
        });

        // Auto-stagger för små element inom samma parent (premium-känsla)
        document.querySelectorAll('.cards-grid, .tech-grid, .footer-grid, .stats-row, .hero-cta, .nav-links, .nav-cta, .ai-actions').forEach(group => {
            const items = group.querySelectorAll('.scroll-animate-item');
            items.forEach((item, index) => {
                item.style.transitionDelay = `${Math.min(index * 0.08, 0.48)}s`;
            });
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                } else {
                    const rect = entry.target.getBoundingClientRect();
                    const completelyOut = rect.bottom < 0 || rect.top > window.innerHeight;

                    if (completelyOut) {
                        entry.target.classList.remove('show');
                    }
                }
            });
        }, {
            threshold: 0.2
        });

        document.querySelectorAll('.scroll-animate').forEach(el => {
            observer.observe(el);
        });

        // Same-origin endpoint works both locally and on Railway.
        const apiUrl = 'api_generate.php';
        const promptEl = document.getElementById('ai-prompt');
        const lengthEl = document.getElementById('ai-length');
        const outputEl = document.getElementById('ai-output');
        const statusEl = document.getElementById('ai-status');
        const btn = document.getElementById('ai-generate');
        const aiTestUsed = false;

        async function generateText() {
            const prompt = promptEl.value.trim();
            const length = parseInt(lengthEl.value, 10) || 80;
            
            if (!prompt) {
                statusEl.textContent = 'Please enter a prompt first.';
                return;
            }
            statusEl.textContent = 'Generating...';
            btn.disabled = true;
            btn.style.opacity = '0.6';
            outputEl.textContent = '';
            try {
                const res = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt, length })
                });
                if (!res.ok) {
                    let errorMessage = `API error ${res.status}`;
                    try {
                        const errData = await res.json();
                        if (errData?.error) {
                            errorMessage = errData.error;
                        }
                        if (Array.isArray(errData?.attempts) && errData.attempts.length > 0) {
                            const compactAttempts = errData.attempts.slice(0, 4).join('\n- ');
                            outputEl.textContent = `Diagnostics:\n- ${compactAttempts}`;
                        }
                    } catch (_) {
                        // Keep fallback status-based message when error body is not JSON.
                    }
                    throw new Error(errorMessage);
                }
                const raw = await res.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (_) {
                    data = { text: raw };
                }
                outputEl.textContent = data.text || '(empty response)';
                statusEl.textContent = 'Done!';
                if (data._source === 'local-fallback') {
                    statusEl.textContent = 'Done (generic fallback text)';
                } else if (data._source === 'custom-api') {
                    statusEl.textContent = 'Done (Shakespeare model)';
                }
            } catch (err) {
                statusEl.textContent = 'Error during request: ' + err.message;
            } finally {
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        }

        if (btn) {
            btn.addEventListener('click', generateText);
        }
    </script>
</body>
</html>