<?php
/**
 * UPPDATERA HISTORIKPOST - FULLSTÄNDIG CRUD (DEL AV A-NIVÅ KRAV)
 * 
 * ÄNDAMÅL: Tillåter användare att uppdatera sina genererade AI-texter
 * (t.ex. redigera prompts som skrevs felaktigt)
 * 
 * SÄKERHETSKONTROLLER:
 * 1. requireLogin() - Bara inloggade användare kan uppdatera
 * 2. CSRF-token validering - Förhindrar CSRF-attacker
 * 3. Ownership check - Du kan BARA uppdatera DIN egna texter
 * 4. Prepared statements - Förhindrar SQL-injektion
 * 5. Input sanitering - htmlspecialchars() på all user-input före lagring
 * 
 * IMPLEMENTERAR: UPDATE från CRUD-operationen
 */

require_once 'config.php';
requireLogin();

// Tillåt bara POST-requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Hämta POST-data
$text_id = isset($_POST['text_id']) ? intval($_POST['text_id']) : null;
$input_text = isset($_POST['input_text']) ? sanitizeInput($_POST['input_text']) : null;
$generated_text = isset($_POST['generated_text']) ? sanitizeInput($_POST['generated_text']) : null;
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

// VALIDERING: Kontrollera CSRF-token
if (!verifyCSRFToken($csrf_token)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'CSRF-token ogiltig. Försök igen.']);
    exit;
}

// VALIDERING: All input måste vara satt
if (!$text_id || !$input_text || !$generated_text) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Felaktig input. Alla fält är obligatoriska.']);
    exit;
}

// VALIDERING: Längdkontroller
if (strlen($input_text) > 2000 || strlen($generated_text) > 5000) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Text för lång. Max 2000 för prompt, 5000 för genererad.']);
    exit;
}

// SÄKERHET: Uppdatera ENDAST användarens egna text
/**
 * VARFÖR VI SÄKERSTÄLLER OWNERSHIP:
 * 
 * OSÄKER UPDATE:
 * UPDATE ai_texts SET input_text = ? WHERE id = ?
 * User 1 kan uppdatera User 2:s texter bara genom att skicka User 2:s ID!
 * 
 * SÄKER UPDATE:
 * UPDATE ai_texts SET input_text = ? WHERE id = ? AND user_id = ?
 * user_id hämtas från $_SESSION, kan inte manipuleras från javascript
 */
try {
    $user_id = $_SESSION['user_id'];
    
    // Först: kontrollera att detta ID tillhör inloggad användare
    $check_stmt = $pdo->prepare(
        "SELECT id FROM ai_texts WHERE id = ? AND user_id = ?"
    );
    $check_stmt->execute([$text_id, $user_id]);
    
    if ($check_stmt->rowCount() === 0) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Text hittades inte eller du har inte behörighet att uppdatera den']);
        exit;
    }
    
    // Nu uppdatera
    $update_stmt = $pdo->prepare(
        "UPDATE ai_texts 
         SET input_text = ?, generated_text = ?
         WHERE id = ? AND user_id = ?"
    );
    
    $update_stmt->execute([
        $input_text,
        $generated_text,
        $text_id,
        $user_id
    ]);
    
    // Framgång
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['success' => 'Text uppdaterad framgångsrikt']);
    
    // OPTIONAL: Logga uppdateringar för revision trail
    /**
     * I enterprise-system loggar vi ändringar för att kunna spåra vad som ändrades.
     * För gymnasiearbete kan detta behövas för att visa "revision history".
     * 
     * En revision_log tabell nästa gång:
     * CREATE TABLE revision_log (
     *     id INT AUTO_INCREMENT PRIMARY KEY,
     *     ai_text_id INT,
     *     user_id INT,
     *     old_value TEXT,
     *     new_value TEXT,
     *     timestamp TIMESTAMP,
     *     FOREIGN KEY (ai_text_id) REFERENCES ai_texts(id),
     *     FOREIGN KEY (user_id) REFERENCES users(id)
     * );
     */
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Update text error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ett fel uppstod vid uppdatering']);
    exit;
}
?>
