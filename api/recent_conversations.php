<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

// Verificăm dacă utilizatorul este autentificat
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(403);
    exit;
}

$userId = $_SESSION['user_id'];

// Validăm și sanitizăm limitele pentru paginare
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Asigurăm limite rezonabile pentru LIMIT și OFFSET
$limit = max(1, min($limit, 100)); // LIMIT între 1 și 100
$offset = max(0, $offset);

try {
    // Construim interogarea SQL pentru a obține conversațiile recente
    $query = '
    SELECT 
        c.id AS conversationId,
        c.type AS conversationType,
        CASE
            WHEN c.type = "group" THEN c.name
            WHEN c.type = "self" THEN (SELECT username FROM users WHERE id = :userId)
            WHEN c.type = "one-on-one" THEN (
                SELECT u.username 
                FROM users u 
                INNER JOIN participants p ON u.id = p.userId
                WHERE p.conversationId = c.id AND u.id != :userId
                LIMIT 1
            )
            ELSE NULL
        END AS conversationName,
        MAX(m.timestamp) AS lastMessageTime
    FROM conversations c
    LEFT JOIN messages m ON c.id = m.conversationId
    WHERE c.id IN (
        SELECT conversationId 
        FROM participants 
        WHERE userId = :userId
    )
    GROUP BY c.id
    ORDER BY lastMessageTime DESC
    LIMIT :limit OFFSET :offset';

    // Pregătim interogarea
    $stmt = $db->prepare($query);

    // Legăm parametrii
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    // Executăm interogarea
    $stmt->execute();

    // Obținem rezultatele
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificăm dacă avem conversații
    if (!$conversations) {
        $conversations = [];
    }

    // Returnăm rezultatele
    echo json_encode($conversations);

} catch (Exception $e) {
    // În caz de eroare, returnăm un mesaj
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
