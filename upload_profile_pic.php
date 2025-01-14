<?php
session_start();
include('db.php');

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
$message = "";
$error = 0;

if(isset($_POST['submit'])) {

    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {

        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_name = $_FILES['profile_pic']['name'];
        $file_size = $_FILES['profile_pic']['size'];
        $file_type = $_FILES['profile_pic']['type'];

        $allowed_types = ['image/jpeg', 'image/png'];

        if(in_array($file_type, $allowed_types)) {

            $max_size = 5 * 1024 * 1024;
            if($file_size > $max_size) {
                $message = "File size is too large. Maximum size is 5MB.";
                $error = 1;
                $_SESSION['pfpMessage'] = $message;
                $_SESSION['pfpError'] = $error;
                header( 'Location: ' . $referer);
                exit();
            }

            $unique_name = uniqid() . '-' . basename($file_name);

            $upload_dir = 'uploads/profile_pics/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_path = $upload_dir . $unique_name;

            if(move_uploaded_file($file_tmp, $file_path)) {
                
                $user_id = $_SESSION['user_id'];

                $stmt = $db->prepare("UPDATE users SET profile_picture = (?) WHERE id = (?)");

                if($stmt->execute([$file_path, $user_id])) {
                    $message = "Profile picture uploaded successfully!";
                } else {
                    $message = "Error updating profile picture.";
                    $error = 1;
                }
            } else {
                $message = "Failed to upload the image.";
                $error = 1;
            }
        } else {
            $message = "Invalid file type. Only JPEG and PNG are allowed.";
            $error = 1;
        }
    } else {
        $message = "No file uploaded or there was an error with the upload.";
        $error = 1;
    }

    $_SESSION['pfpMessage'] = $message;
    $_SESSION['pfpError'] = $error;

    header( 'Location: ' . $referer);
}
?>
