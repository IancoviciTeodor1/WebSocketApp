<?php
require '../db.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$excludeUserId = isset($_GET['excludeUserId']) ? (int)$_GET['excludeUserId'] : 0;

try {
    if ($search) {
        // Exclude utilizatorul curent din rezultatele interogării
        $stmt = $db->prepare("SELECT id, username FROM users WHERE username LIKE :search AND id != :excludeUserId");
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':excludeUserId', $excludeUserId, PDO::PARAM_INT);
    } else {
        // Exclude utilizatorul curent din rezultatele interogării
        $stmt = $db->prepare("SELECT id, username FROM users WHERE id != :excludeUserId LIMIT 30");
        $stmt->bindValue(':excludeUserId', $excludeUserId, PDO::PARAM_INT);
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
