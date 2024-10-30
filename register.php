<?php
session_start();
require 'db.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Query the database to check if the email already exists
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            try {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare('INSERT INTO users (username, password, email) VALUES (?, ?, ?)');
                $stmt->execute([$username, $hashedPassword, $email]);

                // Automatically log in the user
                $_SESSION['user_id'] = $db->lastInsertId();
                $_SESSION['username'] = $username;

                header('Location: index.php');
                exit();
            } catch (Exception $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh; /* Full height for better centering */
            background-color: #f0f0f0;
            margin: 0;
        }
        .register-form {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column; /* Align items vertically */
            align-items: center; /* Center align items horizontally */
        }
        .register-form input,
        .register-form button {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            max-width: 300px; /* Limit max width for better alignment */
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
        }
        .register-form button {
            border: none;
            background-color: #007BFF;
            color: white;
            font-size: 16px;
        }
        #login {
            font-size: 14px;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <form class="register-form" method="POST" action="register.php">
        <h2 align="center">Sign up</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit">Sign up</button>
        <p id="login">Already have an account? <a href="login.php">Login</a></p>
    </form>
</body>
</html>