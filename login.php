<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use Firebase\JWT\JWT;

// Verificăm dacă utilizatorul este deja autentificat
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obține conținutul JSON trimis prin fetch
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $username = $data['username'];
    $password = $data['password'];

    // Verificare utilizator în baza de date
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        $secretKey = 'secretkey';
        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $secretKey, 'HS256');

        header('Content-Type: application/json');
        echo json_encode([
            'token' => $token,
            'userId' => $user['id']  // Include `userId` în răspuns
        ]);
        exit();
    } else {
        echo json_encode(['error' => 'Invalid username or password']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
        .login-form {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column; /* Align items vertically */
            align-items: center;
        }
        .login-form input {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            max-width: 300px;
        }
        .login-form button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #007BFF;
            color: white;
            font-size: 16px;
            max-width: 300px;
        }
        #signup {
            font-size: 14px;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <form class="login-form" method="POST" action="login.php">
        <h2 align="center">Login</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <p id="signup">Don't have an account? <a href="register.php">Sign up</a></p>
    </form>

    <script>
        let token = localStorage.getItem('token');
        console.log('Current Token:', localStorage.getItem('token'));

    document.querySelector('.login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = {
            username: formData.get('username'),
            password: formData.get('password')
        };
        
        const response = await fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.token) {
            // Stochează tokenul în localStorage
            localStorage.setItem('token', result.token);
            localStorage.setItem('userId', result.userId); // Salvează userId
            window.location.href = 'index.php'; // Redirecționează la pagina principală
        } else {
            alert(result.error || 'Authentication failed');
        }
    });
    </script>

</body>
</html>
