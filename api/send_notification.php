<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validare de bază
if (empty($data['userId']) || empty($data['referenceId']) || empty($data['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data. userId, referenceId, and type are required.']);
    exit;
}

$userId = $data['userId'];
$referenceId = $data['referenceId']; // ID-ul mesajului
$type = $data['type'];

try {
    // Adaugă notificarea în baza de date
    $stmt = $db->prepare("
        INSERT INTO notifications (userId, type, referenceId, isRead, created_at)
        VALUES (?, ?, ?, FALSE, NOW())
    ");
    $stmt->execute([$userId, $type, $referenceId]);

    // Răspuns de succes
    echo json_encode(['success' => true, 'message' => 'Notification created successfully']);
    http_response_code(200);

} catch (Exception $e) {
    file_put_contents('debug.log', 'Database error: ' . $e->getMessage(), FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
