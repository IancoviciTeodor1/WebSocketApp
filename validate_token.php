<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

session_start();
header('Content-Type: application/json');

// Debugging: logăm cererea primită
file_put_contents('debug.log', "Request received: " . file_get_contents('php://input') . PHP_EOL, FILE_APPEND);

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

if (!isset($data['token'])) {
    file_put_contents('debug.log', "Token missing in request." . PHP_EOL, FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Token is missing']);
    exit();
}

$token = $data['token'];
$secretKey = 'secretkey';

try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

    // Verificăm expirarea
    if ($decoded->exp < time()) {
        file_put_contents('debug.log', "Token expired." . PHP_EOL, FILE_APPEND);
        echo json_encode(['status' => 'error', 'message' => 'Token expired']);
        exit();
    }

    echo json_encode(['status' => 'success', 'message' => 'Token is valid']);
} catch (Exception $e) {
    file_put_contents('debug.log', "Invalid token: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
}
?>
