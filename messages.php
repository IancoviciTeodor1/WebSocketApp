<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

// Verificăm dacă utilizatorul este autentificat
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$conversationId = $_GET['conversationId'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);
$content = $data['content'] ?? null;

if (!$conversationId || !$content) {
    http_response_code(400);
    echo json_encode(['error' => 'conversationId and content are required']);
    exit;
}

// Verificăm dacă utilizatorul este participant în conversația respectivă
$stmt = $db->prepare("
    SELECT id FROM participants WHERE conversationId = ? AND userId = ?
");
$stmt->execute([$conversationId, $userId]);
$isParticipant = $stmt->fetch();

if (!$isParticipant) {
    http_response_code(403);
    echo json_encode(['error' => 'User is not a participant in this conversation']);
    exit;
}

// Inserăm mesajul
$stmt = $db->prepare("
    INSERT INTO messages (senderId, conversationId, content) 
    VALUES (?, ?, ?)
");
$success = $stmt->execute([$userId, $conversationId, $content]);

if ($success) {
    // Obținem numele utilizatorului care a trimis mesajul
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    echo json_encode([
        'success' => true, 
        'message' => [
            'senderId' => $userId, 
            'username' => $user['username'] ?? 'Unknown', 
            'content' => $content
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
}
?>
