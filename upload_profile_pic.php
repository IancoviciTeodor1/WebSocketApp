<?php
session_start();
include('db.php');

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

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
                echo "File size is too large. Maximum size is 5MB.";
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
                    echo "Profile picture uploaded successfully!";
                } else {
                    echo "Error updating profile picture.";
                }
            } else {
                echo "Failed to upload the image.";
            }
        } else {
            echo "Invalid file type. Only JPEG and PNG are allowed.";
        }
    } else {
        echo "No file uploaded or there was an error with the upload.";
    }

    echo '<a href="' . $referer . '"><button>Go Back</button></a>';
}
?>
