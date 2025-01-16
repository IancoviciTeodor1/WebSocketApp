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
        .login-container {
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
        .login-form {
            display: flex;
            flex-direction: column; /* Aliniere verticală a elementelor */
            align-items: center;
        }
        .login-form input, .login-form button, #signup a {
            width: 100%;
            max-width: 300px;
        }
        .login-form input {
            display: block;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #F1E9DB; /* Fundalul input-urilor */
            color: #06020C; /* Culoarea textului input-urilor */
        }
        .login-form button, #signup a {
            padding: 10px;
            border-radius: 4px;
            background-color: #DD622E; /* Fundalul butonului */
            color: #F1E9DB; /* Culoarea textului butonului */
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
            display: block;
            text-align: center;
            margin-bottom: 10px; /* Adaugă un spațiu între butoane */
            cursor: pointer;
        }
        .login-form button:hover, #signup a:hover {
            background-color: #06020C; /* Fundalul butonului la hover */
            color: #F1E9DB; /* Culoarea textului butonului la hover */
        }
        #signup a:hover {
            content: "SIGN UP"; /* Textul butonului când cursorul este deasupra */
        }
        
        #signup {
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
    <div class="login-container">
        <h1>Login</h1>
        <form class="login-form" method="POST" action="login.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">LOGIN</button>
            <div style="height: 10px;"></div>
        </form>
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <div id="signup">
            <a href="register.php" onmouseover="this.innerText='SIGN UP'" onmouseout="this.innerText='NO ACCOUNT?'">NO ACCOUNT?</a>
        </div>
    </div>
    

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
                localStorage.setItem('token', result.token);
                localStorage.setItem('userId', result.userId);
                window.location.href = 'index.php';
            } else {
                alert(result.error || 'Login failed');
            }
        });

    
    
    </script>
</body>
</html>