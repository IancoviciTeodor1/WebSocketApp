<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT username, email, profile_picture FROM users WHERE id = (?)');
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if (!$row) {
    echo "User not found!";
    exit();
}

$username = $row['username'];
$email = $row['email'];
$profile_picture = $row['profile_picture'] ? $row['profile_picture'] : 'uploads/profile_pics/default.jpg';
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            grid-gap: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 1000px;
        }

        .profile-img-container {
            display: flex;
            justify-content: center;
            align-items: start;
        }

        .profile-left {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease-in-out;
        }

        .profile-right {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 16px;
            margin-bottom: 5px;
            color: #555;
        }

        input {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            padding: 10px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #0056b3;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }

        a {
            align-self: flex-start;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #007BFF;
            color: white;
            white-space: nowrap;
            text-decoration: none;
            font-size: 16px;
        }

        a:hover {
            background-color: #0056b3;
        }

    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-left">            
            <div class="profile-img-container">
                <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-img">
                <form action="upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                    <label for="profile_pic">Upload Profile Picture:</label>
                    <input type="file" name="profile_pic" id="profile_pic" accept="image/*">
                    <button type="submit" name="submit">Upload</button>
                </form>
            </div>

            <a href="index.php">Go Back</a>
        </div>

        <div class="profile-right">
            <form action="change_username.php" method="POST">
                <label for="new_username">Username: </label>
                <input type="text" id="new_username" name="new_username" value="<?php echo htmlspecialchars($username); ?>" required><br>
                <button type="submit" name="submit">Change username</button>
            </form>

            <hr>
    
            <form action="change_email.php" method="POST">
                <label for="new_email">Email: </label>
                <input type="email" id="new_email" name="new_email" value="<?php echo htmlspecialchars($email); ?>" required><br>
                <button type="submit" name="submit">Change email</button>
            </form>

            <hr>
    
            <form action="change_password.php" method="POST">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required><br>
    
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required><br>
    
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required><br>
    
                <button type="submit" name="submit">Change Password</button>
            </form>
        </div>
    </div>
</body>
</html>
