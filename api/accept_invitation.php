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
$data = json_decode(file_get_contents('php://input'), true);
$groupId = $data['groupId'];

try {
    $db->beginTransaction();

    // Obține ID-ul invitației
    $stmt = $db->prepare(
        'SELECT id FROM group_invitations WHERE groupId = ? AND receiverId = ?'
    );
    $stmt->execute([$groupId, $userId]);
    $invitationId = $stmt->fetchColumn();

    if (!$invitationId) {
        throw new Exception('Invitation not found');
    }

    // Adaugă participantul la grup
    $stmt = $db->prepare(
        'INSERT INTO participants (conversationId, userId) VALUES (?, ?)'
    );
    $stmt->execute([$groupId, $userId]);

    // Șterge notificarea pentru invitație
    $stmt = $db->prepare(
        'DELETE FROM notifications WHERE userId = ? AND referenceId = ? AND type = "invitation"'
    );
    $stmt->execute([$userId, $invitationId]);

    // Actualizează statusul invitației la "accepted"
    $stmt = $db->prepare(
        'UPDATE group_invitations SET status = "accepted" WHERE id = ?'
    );
    $stmt->execute([$invitationId]);

    $db->commit();

    echo json_encode(['message' => 'Invitation accepted']);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
