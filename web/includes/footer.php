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
                            <?php if (isAdmin()): ?><li><a href="admin.php">Admin Panel</a></li><?php endif; ?>
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
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
