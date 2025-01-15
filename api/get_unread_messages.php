<?php
require_once 'db.php';
header('Content-Type: application/json');

$conversationId = $_GET['conversationId'];
$userId = $_SESSION['user_id']; // Obține ID-ul utilizatorului autentificat

try {
    // Obține ultimul mesaj citit
    $stmt = $db->prepare('SELECT lastReadMessageId FROM last_read_messages WHERE userId = ? AND conversationId = ?');
    $stmt->execute([$userId, $conversationId]);
    $lastReadMessageId = $stmt->fetchColumn() ?: 0;

    // Selectează cele mai recente 3 mesaje necitite
    $stmt = $db->prepare(
        'SELECT m.id, m.content, m.timestamp, u.username, u.profile_picture
         FROM messages m
         JOIN users u ON m.senderId = u.id
         WHERE m.conversationId = ? AND m.id > ?
         ORDER BY m.timestamp DESC
         LIMIT 3'
    );
    $stmt->execute([$conversationId, $lastReadMessageId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($messages);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>