<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

$mail_user = getenv('MAIL_USER');
$mail_pwd = getenv('MAIL_PWD');
$mail_recipient = getenv('MAIL_RECIPIENT');



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['usercode'])) {
        die('Spam detected');
    }

    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        // Your email address (the one that sends the email)
        $mail->Username = $mail_user; 
        // Enable 2FA and use an App Password
        $mail->Password = $mail_pwd; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email, $name);
        // The email address that receives all the emails
        $mail->addAddress($mail_recipient, 'Recipient Name');

        $mail->isHTML(true);
        $mail->Subject = 'New message submission!';
        $mail->Body    = "<strong>Name:</strong> $name <br> <strong>Email:</strong> $email <br><strong>Message:</strong> <br> $message";
        $mail->AltBody = "Name: $name \n Email: $email \n Message: $message";

        if ($mail->send()) {
            $success = 'Message has been sent successfully!';
        } else {
            $error = 'Message could not be sent.';
        }
    } catch (Exception $e) {
        echo "Mailer Error: {$mail->ErrorInfo}";
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            background-color: white;
            padding: 40px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            font-size: 1.1em;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        textarea {
            resize: none;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .message {
            text-align: center;
            padding: 10px;
            margin: 10px 0;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .fkskfdfv {
            opacity: 0;
            position: absolute;
            top: 0;
            left: 0;
            height: 0;
            width: 0;
            z-index: -1;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Send Us A Message</h1>

        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php elseif (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="contact.php" method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="message">Message:</label>
            <textarea id="message" name="message" required></textarea>

            <label class="fkskfdfv" for="usercode"></label>
            <input class="fkskfdfv" autocomplete="off" tabindex="-1" type="text" id="usercode" name="usercode" value="">

            <input type="submit" value="Send Message">
        </form>
    </div>

</body>
</html>
