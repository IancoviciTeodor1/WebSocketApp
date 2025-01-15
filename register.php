<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use Firebase\JWT\JWT;

// Verifică dacă este o cerere POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obține conținutul JSON trimis prin fetch
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $username = $data['username'];
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];
    $email = $data['email'];

    if ($password !== $confirm_password) {
        echo json_encode(['error' => 'Passwords do not match']);
        exit();
    }

    // Verifică dacă email-ul există deja
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Email already registered']);
        exit();
    }

    try {
        // Introdu utilizatorul în baza de date
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO users (username, password, email) VALUES (?, ?, ?)');
        $stmt->execute([$username, $hashedPassword, $email]);

        // Setează sesiunea și creează un token JWT
        $userId = $db->lastInsertId();
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;

        $secretKey = 'secretkey';
        $payload = [
            'user_id' => $userId,
            'username' => $username,
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $secretKey, 'HS256');

        // Răspunde cu token-ul și userId
        header('Content-Type: application/json');
        echo json_encode([
            'token' => $token,
            'userId' => $userId
        ]);
        exit();

    } catch (Exception $e) {
        echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
        exit();
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
    <script>
        document.querySelector('.register-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = {
                username: formData.get('username'),
                password: formData.get('password'),
                confirm_password: formData.get('confirm_password'),
                email: formData.get('email')
            };

            const response = await fetch('register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.token) {
                // Salvează tokenul și userId în localStorage
                localStorage.setItem('token', result.token);
                localStorage.setItem('userId', result.userId);
                window.location.href = 'index.php'; // Redirecționează la pagina principală
            } else {
                alert(result.error || 'Registration failed');
            }
        });
    </script>
</body>
</html>