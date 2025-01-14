<?php
require '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$groupId = $data['groupId'];
$newGroupName = $data['newGroupName'];

try {
    $stmt = $db->prepare("UPDATE conversations SET name = ? WHERE id = ?");
    $stmt->execute([$newGroupName, $groupId]);

    echo json_encode(['message' => 'Group name updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
