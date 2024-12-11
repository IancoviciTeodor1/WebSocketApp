<?php
session_start();
require 'db.php';

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

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

    if ($user) {
        $update_stmt = $db->prepare("UPDATE users SET username = (?) WHERE id = (?)");
        $update_stmt->execute([$new_username, $user_id]);

        echo "Username successfully changed.";
    } else {
        echo "Error.";
    }

    echo '<a href="' . $referer . '"><button>Go Back</button></a>';
}
?>
