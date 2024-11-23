<?php
require 'db.php';

ob_start(); // Începe buffer-ul de ieșire

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
file_put_contents('debug.log', "Payload received: " . print_r($data, true), FILE_APPEND); // Loghează payload-ul primit

// Validare de bază
if (empty($data['content'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Message content is required.']);
    exit;
}

$content = $data['content'];
$conversationId = $data['conversationId'] ?? null;
$receiverId = $data['receiverId'] ?? null;
$senderId = $data['senderId'] ?? $_SESSION['user_id']; // Asigură-te că există un senderId valid

if (!$senderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Sender ID is missing.']);
    exit;
}

try {
    if ($conversationId) {
        // Dacă există o conversație, doar trimitem mesajul
        $stmt = $db->prepare("INSERT INTO messages (content, conversationId, senderId, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$content, $conversationId, $senderId]);
    } elseif ($receiverId) {
        // Dacă nu există conversație, creăm una nouă
        $stmt = $db->prepare("INSERT INTO conversations (type) VALUES ('one-on-one')");
        $stmt->execute();
        $conversationId = $db->lastInsertId();

        // Adaugă participanții în conversație
        $stmt = $db->prepare("INSERT INTO participants (conversationId, userId) VALUES (?, ?), (?, ?)");
        $stmt->execute([$conversationId, $senderId, $conversationId, $receiverId]);

        // Inserează mesajul în conversația nouă
        $stmt = $db->prepare("INSERT INTO messages (content, conversationId, senderId, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$content, $conversationId, $senderId]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID or Receiver ID is required.']);
        exit;
    }

    // Răspuns de succes
    echo json_encode(['success' => true, 'message' => 'Message sent successfully', 'conversationId' => $conversationId]);
    http_response_code(200);

} catch (Exception $e) {
    file_put_contents('debug.log', 'Database error: ' . $e->getMessage(), FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$output = ob_get_clean(); // Golește buffer-ul
if (!empty($output)) {
    file_put_contents('debug.log', "Unexpected output: " . $output, FILE_APPEND);
}

// Răspuns JSON valid
header('Content-Type: application/json'); // Specifică tipul de răspuns
echo json_encode(['success' => true, 'message' => 'Message sent successfully', 'conversationId' => $conversationId]);
http_response_code(200);
exit;
?>
