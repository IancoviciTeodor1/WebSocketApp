<?php
session_start();
require 'db.php';

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

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

    if ($user && password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            $update_stmt = $db->prepare("UPDATE users SET password = (?) WHERE id = (?)");
            $update_stmt->execute([$hashed_password, $user_id]);

            echo "Password successfully changed.";
        } else {
            echo "New passwords do not match.";
        }
    } else {
        echo "Current password is incorrect.";
    }

    echo '<a href="' . $referer . '"><button>Go Back</button></a>';
}
?>
