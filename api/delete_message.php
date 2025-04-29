<?php
require '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$messageId = $data['messageId'] ?? null;

if (!$messageId) {
    echo json_encode(['error' => 'Missing messageId']);
    exit;
}

try {
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
