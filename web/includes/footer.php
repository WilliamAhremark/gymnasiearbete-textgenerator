    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col scroll-animate">
                    <h4>NeuralText AI</h4>
                    <p>Simple AI text generator built for a high school project. Creating with code and creativity.</p>
                </div>
                <div class="footer-col scroll-animate">
                    <h4>Product</h4>
                    <ul>
                        <li><a href="index.php#features">Features</a></li>
                        <li><?php if (isLoggedIn()): ?><a href="generate.php">Generate Text</a><?php else: ?><a href="register.php">Get Started</a><?php endif; ?></li>
                        <li><a href="index.php#tech">Technology</a></li>
                        <li><?php if (isLoggedIn()): ?><a href="dashboard.php">Dashboard</a><?php else: ?><a href="login.php">Sign In</a><?php endif; ?></li>
                    </ul>
                </div>
                <div class="footer-col scroll-animate">
                    <h4>Documentation</h4>
                    <ul>
                        <li><a href="../API_DOCUMENTATION.md">API Docs</a></li>
                        <li><a href="../TESTING.md">Testing</a></li>
                        <li><a href="../README.md">About</a></li>
                        <li><a href="#">GitHub</a></li>
                    </ul>
                </div>
                <div class="footer-col scroll-animate">
                    <h4>Links</h4>
                    <ul>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="profile.php">Profile</a></li>
                            <li><a href="history.php">Your History</a></li>
                            <li><a href="logout.php">Sign Out</a></li>
                            <li><a href="#">Settings</a></li>
                        <?php else: ?>
                            <li><a href="register.php">Register</a></li>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="#">Privacy</a></li>
                            <li><a href="#">Terms</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">© <?php echo date('Y'); ?> NeuralText AI - High School Project. Built with passion and code.</div>
        </div>
    </footer>

    <script>
        // Scroll animation handler
        const groupedSelectors = [
            'main > .section:first-child',
            'main > .section:nth-child(2)',
            'main > .section:nth-child(3)',
            '.section-header',
            '.cta-shell',
        ];

        const itemSelectors = [
            '.cards-grid .feature-card',
            '.tech-grid .tech-item',
            '.footer-grid .footer-col',
            '.form-group',
            'table tbody tr',
            '.history-item',
            '.profile-section'
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

        // Auto-stagger för små element
        document.querySelectorAll('.cards-grid, .tech-grid, .footer-grid, .form-group, table tbody').forEach(group => {
            const items = group.querySelectorAll('.scroll-animate-item, .scroll-animate');
            items.forEach((item, index) => {
                item.style.transitionDelay = `${Math.min(index * 0.08, 0.48)}s`;
            });
        });

        // Intersection observer för scroll animations
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

        // Smooth scroll för anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
