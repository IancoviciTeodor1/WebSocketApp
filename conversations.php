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
$receiverId = $_GET['receiverId'] ?? $_POST['receiverId'] ?? null;

if (!$receiverId) {
    http_response_code(400);
    echo json_encode(['error' => 'Receiver ID is required']);
    exit;
}

// Verificăm dacă există deja o conversație între cei doi utilizatori
$stmt = $db->prepare("
    SELECT c.id 
    FROM conversations c
    JOIN participants p1 ON c.id = p1.conversationId
    JOIN participants p2 ON c.id = p2.conversationId
    WHERE c.type = 'one-on-one' AND p1.userId = ? AND p2.userId = ?
");
$stmt->execute([$userId, $receiverId]);
$conversation = $stmt->fetch();

if ($conversation) {
    // Conversația există deja
    echo json_encode(['conversationId' => $conversation['id']]);
} else {
    // Dacă nu există, creăm o conversație nouă
    $stmt = $db->prepare("INSERT INTO conversations (type) VALUES ('one-on-one')");
    $stmt->execute();
    $newConversationId = $db->lastInsertId();

    if ($newConversationId) {
        // Adăugăm participanții
        $stmt = $db->prepare("INSERT INTO participants (conversationId, userId) VALUES (?, ?), (?, ?)");
        $stmt->execute([$newConversationId, $userId, $newConversationId, $receiverId]);
        
        echo json_encode(['conversationId' => $newConversationId]);
    } else {
        echo json_encode(['error' => 'Failed to create conversation']);
        http_response_code(500);
    }
}
?>
