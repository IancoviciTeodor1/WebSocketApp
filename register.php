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
            background-color: #325D75; /* Fundalul paginii */
            color: #F1E9DB; /* Culoarea textului */
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .register-container {
            background-color: #5DB7DE; /* Fundalul containerului */
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
            z-index: 1;
            position: relative;
        }
        .register-form {
            display: flex;
            flex-direction: column; /* Aliniere verticală a elementelor */
            align-items: center;
        }
        .register-form input, .register-form button, #login a {
            width: 100%;
            max-width: 300px;
        }
        .register-form input {
            display: block;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #F1E9DB; /* Fundalul input-urilor */
            color: #06020C; /* Culoarea textului input-urilor */
        }
        .register-form button, #login a {
            padding: 10px;
            border-radius: 4px;
            background-color: #DD622E; /* Fundalul butonului */
            color: #F1E9DB; /* Culoarea textului butonului */
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
            display: block;
            text-align: center;
            cursor: pointer;
        }
        .register-form button:hover, #login a:hover {
            background-color: #06020C; /* Fundalul butonului la hover */
            color: #F1E9DB; /* Culoarea textului butonului la hover */
        }
        #login {
            font-size: 14px;
            color: #06020C; /* Culoarea textului */
            margin-top: 15px;
            display: flex;
            justify-content: center;
        }
        .error {
            color: red;
        }
        .background-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Sign up</h2>
        <form class="register-form" method="POST" action="register.php">
            <?php if (isset($error)): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit">Sign up</button>
            <div id="login">
                <a href="login.php">Already have an account? Login</a>
            </div>
        </form>
    </div>

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