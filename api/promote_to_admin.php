<?php
require '../db.php';
session_start();

$currentUserId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$groupId = $data['groupId'] ?? null;
$targetUserId = $data['userId'] ?? null;

if (!$groupId || !$targetUserId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing groupId or userId.']);
    exit;
}

try {
    // Verifică dacă userul curent este admin sau creator
    $stmt = $db->prepare("SELECT role FROM participants WHERE conversationId = ? AND userId = ?");
    $stmt->execute([$groupId, $currentUserId]);
    $userRole = $stmt->fetchColumn();

    if (!$userRole) {
        http_response_code(403);
        echo json_encode(['error' => 'Your role was not found in this group.']);
        exit;
    }

    if ($userRole !== 'admin' && $userRole !== 'creator') {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to promote users.']);
        exit;
    }

    // Update rolul target user-ului
    $stmt = $db->prepare("UPDATE participants SET role = 'admin' WHERE conversationId = ? AND userId = ?");
    $stmt->execute([$groupId, $targetUserId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
