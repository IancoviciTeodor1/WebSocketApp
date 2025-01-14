<?php
session_start();
require 'db.php';

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
$message = "";
$error = 0;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['submit'])) {    
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("SELECT username FROM users WHERE id = (?)");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $current_username = $user['username'];
    $new_username = $_POST['new_username'];

    if (strlen($new_username) < 3 || strlen($new_username) > 16) {
        $message = "Username must be between 3 and 16 characters.";
        $error = 1;
    } elseif (!ctype_alnum($new_username)) {
        $message = "Username must only contain alphanumeric characters.";
        $error = 1;
    } else {
        if ($user) {
            $update_stmt = $db->prepare("UPDATE users SET username = (?) WHERE id = (?)");
            $update_stmt->execute([$new_username, $user_id]);

            $message = "Username successfully changed.";
        } else {
            $message = "Error.";
            $error = 1;
        }
    }

    $_SESSION['usernameMessage'] = $message;
    $_SESSION['usernameError'] = $error;

    header( 'Location: ' . $referer);
}
?>
