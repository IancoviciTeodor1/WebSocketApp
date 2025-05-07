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
    } elseif ($new_username === $current_username) {
        $message = "New username must be different from the current one.";
        $error = 1;
    } else {
        $check_stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->execute([$new_username, $user_id]);
        $existing_user = $check_stmt->fetch();

        if ($existing_user) {
            $message = "This username is already taken.";
            $error = 1;
        } else {
            $update_stmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
            $update_stmt->execute([$new_username, $user_id]);
            $message = "Username successfully changed.";
        }
    }

    $_SESSION['usernameMessage'] = $message;
    $_SESSION['usernameError'] = $error;

    header( 'Location: ' . $referer);
}
?>
