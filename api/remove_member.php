<?php
require '../db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$groupId = $data['groupId'] ?? null;
$targetUserId = $data['userId'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$groupId || !$targetUserId || !$currentUserId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing groupId, userId, or not authenticated.']);
    exit;
}

try {
    // Verificăm rolul utilizatorului curent
    $stmt = $db->prepare("SELECT role FROM participants WHERE conversationId = ? AND userId = ?");
    $stmt->execute([$groupId, $currentUserId]);
    $currentUserRole = $stmt->fetchColumn();

    if (!$currentUserRole) {
        http_response_code(403);
        echo json_encode(['error' => 'You are not a participant of this group.']);
        exit;
    }

    // Verificăm rolul utilizatorului țintă
    $stmt = $db->prepare("SELECT role FROM participants WHERE conversationId = ? AND userId = ?");
    $stmt->execute([$groupId, $targetUserId]);
    $targetUserRole = $stmt->fetchColumn();

    if (!$targetUserRole) {
        http_response_code(404);
        echo json_encode(['error' => 'Target user not found in this group.']);
        exit;
    }

    // Verificăm permisiunile
    if ($currentUserRole === 'creator') {
        // Creatorul poate elimina pe oricine
    } elseif ($currentUserRole === 'admin' && $targetUserRole === 'member') {
        // Adminul poate elimina doar membri
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to remove this user.']);
        exit;
    }

    // Ștergem utilizatorul
    $stmt = $db->prepare("DELETE FROM participants WHERE conversationId = ? AND userId = ?");
    $stmt->execute([$groupId, $targetUserId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
