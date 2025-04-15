<?php
require '../db.php';
$groupId = $_GET['groupId'];

try {
    // Obține detalii despre grup
    $stmt = $db->prepare("SELECT name FROM conversations WHERE id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obține membrii grupului din tabelul participants
    $stmt = $db->prepare("SELECT u.username 
                          FROM users u
                          JOIN participants p ON p.userId = u.id
                          WHERE p.conversationId = ?");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Răspunde cu datele grupului și membrii acestuia
    echo json_encode(['groupName' => $group['name'], 'members' => $members]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
