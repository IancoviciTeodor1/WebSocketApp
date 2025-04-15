<?php
require_once '../db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$conversationId = $data['groupId'] ?? null;
$userIds = $data['userIds'] ?? [];

if (!$conversationId || empty($userIds)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid group ID or user selection']);
    exit;
}

try {
    // Obține ID-ul utilizatorului curent
    session_start();
    $senderId = $_SESSION['user_id'];

    $stmtInvite = $db->prepare("INSERT INTO group_invitations (groupId, senderId, receiverId) VALUES (?, ?, ?)");
    $stmtNotify = $db->prepare("INSERT INTO notifications (userId, type, referenceId) VALUES (?, 'invitation', ?)");

    // Trimite invitațiile și notificările pentru fiecare utilizator
    foreach ($userIds as $userId) {
        // Adăugăm invitația
        $stmtInvite->execute([$conversationId, $senderId, $userId]);

        // Obține ID-ul invitației
        $invitationId = $db->lastInsertId();

        // Trimitem notificarea pentru utilizatorul invitat
        $stmtNotify->execute([$userId, $invitationId]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Users invited successfully']);
} catch (PDOException $e) {

    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
