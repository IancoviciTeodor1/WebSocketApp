<?php
require '../db.php';

header('Content-Type: application/json');

$groupId = $_GET['groupId'];
$excludeUserId = isset($_GET['excludeUserId']) ? $_GET['excludeUserId'] : null;

if (!$excludeUserId) {
    echo json_encode(['error' => 'excludeUserId is required']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT id, username
        FROM users
        WHERE id != :excludeUserId
        AND id NOT IN (SELECT userId FROM participants WHERE conversationId = :groupId)
    ");
    $stmt->bindValue(':excludeUserId', $excludeUserId, PDO::PARAM_INT);
    $stmt->bindValue(':groupId', $groupId, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($users) {
        echo json_encode($users);
    } else {
        echo json_encode(['error' => 'No users found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
