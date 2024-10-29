<?php
$host = 'localhost';
$dbname = 'chat_app';
$username = 'chat_app_user'; // MySQL username
$password = 'pass'; // MySQL password

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>