<?php
require '../db.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    if ($search) {
        $stmt = $db->prepare("SELECT id, username FROM users WHERE username LIKE :search");
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    } else {
        $stmt = $db->prepare("SELECT id, username FROM users LIMIT 30");
    }
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($users);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
