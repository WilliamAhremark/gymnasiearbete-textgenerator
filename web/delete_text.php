<?php
/**
 * DELETE HISTORIKPOST - FULLSTÄNDIG CRUD (DEL AV A-NIVÅ KRAV)
 * 
 * ÄNDAMÅL: Tillåter användare att radera sina genererade AI-texter
 * 
 * SÄKERHETSKONTROLLER:
 * 1. requireLogin() - Bara inloggade användare kan radera
 * 2. CSRF-token validering - Förhindrar CSRF-attacker
 * 3. Ownership check - Du kan BARA radera DIN egna texter
 *    SQL: DELETE FROM ai_texts WHERE id = ? AND user_id = ?
 *    Användar-ID från $_SESSION kan inte manipuleras från client-side
 * 4. Prepared statements - Förhindrar SQL-injektion från text_id
 * 
 * IMPLEMENTERAR: DELETE från CRUD-operationen
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

// Hämta text_id från POST-data
$text_id = isset($_POST['text_id']) ? intval($_POST['text_id']) : null;
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

// VALIDERING: Kontrollera CSRF-token
if (!verifyCSRFToken($csrf_token)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'CSRF-token ogiltig. Försök igen.']);
    exit;
}

// VALIDERING: text_id måste vara satt och vara ett nummer
if (!$text_id || $text_id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ogiltigt text-ID']);
    exit;
}

// SÄKERHET: Radera ENDAST användarens egna text
// WHERE-clause kräver att BÅDA id och user_id matchar
/**
 * VARFÖR DETTA ÄR VIKTIGT:
 * 
 * OSÄKER DELETE:
 * DELETE FROM ai_texts WHERE id = ?
 * User 1 kan radera User 2:s texter genom att skicka User 2:s ID!
 * 
 * SÄKER DELETE:
 * DELETE FROM ai_texts WHERE id = ? AND user_id = ?
 * user_id hämtas från $_SESSION['user_id'] (kan inte manipuleras från browser)
 * Så User 1 kan ALDRIG radera någon annans texter
 */
try {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare(
        "DELETE FROM ai_texts 
         WHERE id = ? AND user_id = ?"
    );
    
    // execute() kör prepared statement säkert
    $stmt->execute([$text_id, $user_id]);
    
    // Kontrollera om något raderades (rowCount() = antal påverkade rader)
    if ($stmt->rowCount() === 0) {
        // Antingen existerar ID:t inte, eller så tillhör det inte denna användare
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Text hittades inte eller du har inte behörighet att radera den']);
        exit;
    }
    
    // Framgång: Text raderad
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['success' => 'Text raderad framgångsrikt']);
    
} catch (PDOException $e) {
    /**
     * SÄKERHET: Visa INTE detaljerade fel-meddelanden till client
     * En angripare kan använda detta för att lära sig om din databas-struktur
     * 
     * Vi loggar det sanna felet internt, men säger bara "something went wrong"
     */
    http_response_code(500);
    error_log("Delete text error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ett fel uppstod vid radering']);
    exit;
}
?>
