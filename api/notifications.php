<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Selectăm notificările utilizatorului
    $stmt = $db->prepare(
        'SELECT n.referenceId AS conversationId, n.created_at AS lastUpdated, 
                c.type AS conversationType, c.name AS conversationName,
                (SELECT MAX(id) FROM messages WHERE conversationId = n.referenceId) AS latestMessageId
         FROM notifications n
         JOIN conversations c ON n.referenceId = c.id
         WHERE n.userId = ? AND n.isRead = FALSE'
    );
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Adăugăm cele mai recente 3 mesaje necitite pentru fiecare conversație
    foreach ($notifications as &$notification) {
        $conversationId = $notification['conversationId'];
        $conversationType = $notification['conversationType'];

        // Verificăm tipul conversației
        if ($conversationType === 'one-on-one') {
            // Obținem numele celuilalt utilizator în conversație
            $stmt = $db->prepare(
                'SELECT u.username 
                 FROM participants p
                 JOIN users u ON p.userId = u.id
                 WHERE p.conversationId = ? AND p.userId != ?'
            );
            $stmt->execute([$conversationId, $userId]);
            $otherUser = $stmt->fetch(PDO::FETCH_ASSOC);
            $notification['conversationName'] = $otherUser['username'] ?? 'Unknown User';
        } elseif ($conversationType === 'group') {
            // Pentru grup, păstrăm numele conversației așa cum este
            $notification['conversationName'] = $notification['conversationName'];
        } else {
            continue;
        }

        // Obține ultimul mesaj citit
        $stmt = $db->prepare(
            'SELECT lastReadMessageId FROM last_read_messages WHERE userId = ? AND conversationId = ?'
        );
        $stmt->execute([$userId, $conversationId]);
        $lastReadMessageId = $stmt->fetchColumn() ?: 0;

        // Selectează cele mai recente 3 mesaje necitite
        $stmt = $db->prepare(
            'SELECT m.id, m.content, m.timestamp, u.username 
             FROM messages m
             JOIN users u ON m.senderId = u.id
             WHERE m.conversationId = ? AND m.id > ?
             ORDER BY m.timestamp DESC
             LIMIT 3'
        );
        $stmt->execute([$conversationId, $lastReadMessageId]);
        $unreadMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mesajele sunt inversate pentru a fi afisate cronologic
        $notification['unreadMessages'] = array_reverse($unreadMessages);
    }

    echo json_encode($notifications);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
