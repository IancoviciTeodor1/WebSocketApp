<?php
require '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$groupName = $data['groupName'] ?? null;
$selectedUsers = $data['selectedUsers'] ?? [];

if (!$groupName || empty($selectedUsers)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid group name or user selection.']);
    exit;
}

try {
    // Creare conversație de tip grup
    $stmt = $db->prepare("INSERT INTO conversations (name, type) VALUES (?, 'group')");
    $stmt->execute([$groupName]);
    $groupId = $db->lastInsertId();

    // Adaugă creatorul grupului ca participant
    session_start();
    $creatorId = $_SESSION['user_id'];
    $stmt = $db->prepare("INSERT INTO participants (conversationId, userId) VALUES (?, ?)");
    $stmt->execute([$groupId, $creatorId]);

    // Adaugă participanții selectați și trimite invitații
    $stmt = $db->prepare("INSERT INTO group_invitations (groupId, senderId, receiverId) VALUES (?, ?, ?)");
    foreach ($selectedUsers as $userId) {
        $stmt->execute([$groupId, $creatorId, $userId]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
