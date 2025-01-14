<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(403);
    exit;
}

$userId = $_SESSION['user_id'];
$receiverId = $_GET['receiverId'] ?? null;
$type = ($receiverId == $userId) ? 'self' : 'one-on-one';

if (!$receiverId) {
    echo json_encode(['error' => 'Receiver ID is required']);
    http_response_code(400);
    exit;
}

// Verificăm dacă există deja conversația
$query = '
    SELECT c.id, c.type 
    FROM conversations c
    INNER JOIN participants p1 ON c.id = p1.conversationId
    INNER JOIN participants p2 ON c.id = p2.conversationId
    WHERE 
        c.type = ? 
        AND p1.userId = ? 
        AND (p2.userId = ? OR c.type = "self")
';
$stmt = $db->prepare($query);
$stmt->execute([$type, $userId, $receiverId]);
$conversation = $stmt->fetch();

if ($conversation) {
    echo json_encode([
        'conversationId' => $conversation['id'],
        'type' => $conversation['type']
    ]);
    exit;
}

// Creare conversație nouă
$stmt = $db->prepare('INSERT INTO conversations (type) VALUES (?)');
$stmt->execute([$type]);
$newConversationId = $db->lastInsertId();

if ($newConversationId) {
    // Adăugăm participantul curent
    $stmt = $db->prepare('INSERT INTO participants (conversationId, userId) VALUES (?, ?)');
    $stmt->execute([$newConversationId, $userId]);

    // Adăugăm al doilea participant doar pentru "one-on-one"
    if ($type === 'one-on-one') {
        $stmt->execute([$newConversationId, $receiverId]);
    }

    echo json_encode([
        'conversationId' => $newConversationId,
        'type' => $type
    ]);
} else {
    echo json_encode(['error' => 'Failed to create conversation']);
    http_response_code(500);
}
?>
