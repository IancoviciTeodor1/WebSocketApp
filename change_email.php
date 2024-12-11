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

    $stmt = $db->prepare("SELECT email FROM users WHERE id = (?)");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $current_email = $user['email'];
    $new_email = $_POST['new_email'];

    if ($user) {
        $update_stmt = $db->prepare("UPDATE users SET email = (?) WHERE id = (?)");
        $update_stmt->execute([$new_email, $user_id]);

        echo "Email successfully changed.";
    } else {
        echo "Error.";
    }

    echo '<a href="' . $referer . '"><button>Go Back</button></a>';
}
?>
