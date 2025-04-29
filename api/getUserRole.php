<?php
require '../db.php';
session_start();

$userId = $_SESSION['user_id'];
$conversationId = $_GET['conversationId'];

try {
    $stmt = $db->prepare("SELECT role FROM participants WHERE conversationId = ? AND userId = ?");
    $stmt->execute([$conversationId, $userId]);
    $role = $stmt->fetchColumn();

    if ($role) {
        echo json_encode(['role' => $role]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Role not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
