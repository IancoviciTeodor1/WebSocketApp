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
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("SELECT password FROM users WHERE id = (?)");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $password_pattern = '/^(?=(.*[A-Z]))(?=(.*[a-z]))(?=(.*\d))(?=(.*[!@#$%^&*(),.?":{}|<>]))[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/';

    if ($user && password_verify($current_password, $user['password'])) {

        if ($new_password === $current_password) {
            $message = "New password cannot be the same as the current password.";
            $error = 1;
        } elseif (!preg_match($password_pattern, $new_password)) {
            $message = "New password does not meet the required complexity (at least 8 characters, one uppercase, one lowercase, one digit, one special character).";
            $error = 1;
        } elseif ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            $update_stmt = $db->prepare("UPDATE users SET password = (?) WHERE id = (?)");
            $update_stmt->execute([$hashed_password, $user_id]);

            $message = "Password successfully changed.";
        } else {
            $message = "New passwords do not match.";
            $error = 1;
        }
    } else {
        $message = "Current password is incorrect.";
        $error = 1;
    }

    $_SESSION['passwordMessage'] = $message;
    $_SESSION['passwordError'] = $error;

    header( 'Location: ' . $referer);
}
?>
