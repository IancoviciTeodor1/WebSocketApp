<?php
require '../db.php'; // Se presupune că fișierul db.php este inclus corect

// Verificăm dacă cererea este de tip POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decodăm corpul cererii
    $data = json_decode(file_get_contents('php://input'), true);

    $userId = $data['userId'] ?? null;
    $conversationId = $data['conversationId'] ?? null;
    $lastReadMessageId = $data['lastReadMessageId'] ?? null;

    // Validăm parametrii primiți
    if (!$userId || !$conversationId || !$lastReadMessageId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data.']);
        exit;
    }

    try {
        // Folosim PDO pentru a verifica dacă există deja o intrare
        $query = 'SELECT id FROM last_read_messages WHERE userId = :userId AND conversationId = :conversationId';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':conversationId', $conversationId, PDO::PARAM_INT);
        $stmt->execute();

        // Dacă există deja o intrare, o actualizăm
        if ($stmt->rowCount() > 0) {
            $updateQuery = 'UPDATE last_read_messages SET lastReadMessageId = :lastReadMessageId, updated_at = NOW() WHERE userId = :userId AND conversationId = :conversationId';
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':lastReadMessageId', $lastReadMessageId, PDO::PARAM_INT);
            $updateStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $updateStmt->bindParam(':conversationId', $conversationId, PDO::PARAM_INT);
            $updateStmt->execute();
        } else {
            // Creăm o intrare nouă
            $insertQuery = 'INSERT INTO last_read_messages (userId, conversationId, lastReadMessageId) VALUES (:userId, :conversationId, :lastReadMessageId)';
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $insertStmt->bindParam(':conversationId', $conversationId, PDO::PARAM_INT);
            $insertStmt->bindParam(':lastReadMessageId', $lastReadMessageId, PDO::PARAM_INT);
            $insertStmt->execute();
        }

        // Răspundem cu un succes
        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Gestionăm erorile
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    // Răspundem cu o eroare dacă metoda HTTP nu este POST
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}
?>
