<?php
require 'db.php';

session_start();

$userId = $_SESSION['user_id'];

// Verifică dacă utilizatorul este autentificat
if (!$userId) {
    echo json_encode([]); // Trimite un array gol dacă utilizatorul nu este autentificat
    exit();
}

try {
    // Obține notificările pentru utilizatorul curent
    $stmt = $db->prepare("
        SELECT 
            n.id, 
            n.userId, 
            n.type, 
            n.referenceId, 
            n.created_at, 
            m.content AS message_content, 
            m.conversationId AS conversation_id, 
            u.username AS sender_name,
            c.name AS group_name, -- Include numele grupului, dacă este o conversație de grup
            c.type AS conversation_type -- Tipul conversației: 'one-on-one' sau 'group'
        FROM notifications n
        LEFT JOIN messages m ON n.referenceId = m.id
        LEFT JOIN users u ON m.senderId = u.id
        LEFT JOIN conversations c ON m.conversationId = c.id
        WHERE n.userId = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Asigură-te că răspunsul este de tip JSON
    header('Content-Type: application/json');
    echo json_encode($notifications ?: []); // Trimite array gol dacă nu există notificări

} catch (Exception $e) {
    file_put_contents('debug.log', 'Error loading notifications: ' . $e->getMessage(), FILE_APPEND);
    echo json_encode([]); // Trimite un array gol în caz de eroare
}
?>
