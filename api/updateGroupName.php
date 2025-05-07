<?php
require '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$groupId = $data['groupId'] ?? null;
$newGroupName = $data['newGroupName'] ?? null;

if (!$groupId || !$newGroupName) {
    echo json_encode(['success' => false, 'error' => 'Missing groupId or newGroupName']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE conversations SET name = ? WHERE id = ?");
    $stmt->execute([$newGroupName, $groupId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}