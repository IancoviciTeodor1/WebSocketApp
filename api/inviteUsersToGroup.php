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
    session_start();
    $senderId = $_SESSION['user_id'];

    // Pregătim interogările
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM group_invitations WHERE groupId = ? AND receiverId = ?");
    $stmtInvite = $db->prepare("INSERT INTO group_invitations (groupId, senderId, receiverId) VALUES (?, ?, ?)");
    $stmtNotify = $db->prepare("INSERT INTO notifications (userId, type, referenceId) VALUES (?, 'invitation', ?)");

    foreach ($userIds as $userId) {
        // Verifică dacă deja există o invitație
        $stmtCheck->execute([$conversationId, $userId]);
        $invitationExists = $stmtCheck->fetchColumn();

        if ($invitationExists) {
            continue; // Sari peste acest utilizator dacă există deja o invitație
        }

        // Adăugăm invitația
        $stmtInvite->execute([$conversationId, $senderId, $userId]);
        $invitationId = $db->lastInsertId();

        // Trimitem notificarea
        $stmtNotify->execute([$userId, $invitationId]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Users invited successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
