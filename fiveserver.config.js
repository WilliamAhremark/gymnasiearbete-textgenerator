// Five Server Configuration
module.exports = {
  // PHP path - full Windows path with backslashes
  // Justera till din faktiska php.exe. Exempel för Apache-installationen nedan.
  // Om du kör XAMPP: "C:/xampp/php/php.exe"
  php: "C:/xampp/php/php.exe",

  // Open the PHP entry file by default
  open: "web/index.php",

  // Bind to the expected port
  port: 5500,

  // Enable verbose output for debugging
  debug: true
}
