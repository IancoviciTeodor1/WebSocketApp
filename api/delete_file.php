<?php
require '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$fileId = $data['fileId'] ?? null;

if (!$fileId) {
    echo json_encode(['error' => 'Missing fileId']);
    exit;
}

try {
    $stmt = $db->prepare("DELETE FROM media_files WHERE id = ?");
    $stmt->execute([$fileId]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
