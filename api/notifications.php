<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Notificări mesaje necitite excluzând conversațiile de tip self și verificând existența mesajelor necitite
    $stmt = $db->prepare(
        'SELECT n.referenceId AS conversationId, c.type AS conversationType, c.name AS conversationName
         FROM notifications n
         JOIN conversations c ON n.referenceId = c.id
         WHERE n.userId = ? 
           AND n.isRead = FALSE 
           AND n.type = "message" 
           AND c.type != "self"
           AND EXISTS (
               SELECT 1 
               FROM messages m 
               JOIN last_read_messages lrm ON lrm.conversationId = m.conversationId AND lrm.userId = ?
               WHERE m.conversationId = n.referenceId 
                 AND m.id > lrm.lastReadMessageId
           )'
    );
    $stmt->execute([$userId, $userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($notifications as &$notification) {
        $conversationId = $notification['conversationId'];
        $conversationType = $notification['conversationType'];

        // Verificăm și ajustăm numele conversației
        if ($conversationType === 'one-on-one') {
            $stmt = $db->prepare(
                'SELECT u.username 
                 FROM participants p
                 JOIN users u ON p.userId = u.id
                 WHERE p.conversationId = ? AND p.userId != ?'
            );
            $stmt->execute([$conversationId, $userId]);
            $otherUser = $stmt->fetch(PDO::FETCH_ASSOC);
            $notification['conversationName'] = $otherUser['username'] ?? 'Unknown User';
        }

        // Selectează cele mai recente 3 mesaje necitite
        $stmt = $db->prepare(
            'SELECT m.id, m.content, m.timestamp, u.username 
             FROM messages m
             JOIN users u ON m.senderId = u.id
             WHERE m.conversationId = ? AND m.id > (
                 SELECT COALESCE(MAX(lastReadMessageId), 0) 
                 FROM last_read_messages 
                 WHERE userId = ? AND conversationId = ?
             )
             ORDER BY m.timestamp DESC
             LIMIT 3'
        );
        $stmt->execute([$conversationId, $userId, $conversationId]);
        $notification['unreadMessages'] = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Notificări invitații necitite
    $stmt = $db->prepare(
        'SELECT gi.groupId, gi.senderId, gi.created_at, g.name AS groupName, u.username AS senderName
         FROM notifications n
         JOIN group_invitations gi ON gi.id = n.referenceId
         JOIN conversations g ON gi.groupId = g.id
         JOIN users u ON gi.senderId = u.id
         WHERE n.userId = ? AND n.isRead = FALSE AND n.type = "invitation"'
    );
    $stmt->execute([$userId]);
    $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Combinați notificările
    $response = [
        'messages' => $notifications,
        'invitations' => $invitations
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
