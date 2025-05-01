<?php
require '../db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$groupId = $data['groupId'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if (!$groupId || !$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing groupId or userId.']);
    exit;
}

try {
    // Verificăm dacă utilizatorul este participant în grup
    $stmt = $db->prepare("SELECT * FROM participants WHERE conversationId = ? AND userId = ?");
    $stmt->execute([$groupId, $userId]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participant) {
        http_response_code(403);
        echo json_encode(['error' => 'You are not a member of this group.']);
        exit;
    }

    // Ștergem utilizatorul din grup
    $stmt = $db->prepare("DELETE FROM participants WHERE conversationId = ? AND userId = ?");
    $stmt->execute([$groupId, $userId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
