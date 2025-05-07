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

    
    $responseBody = "Hello $name, <br><br> Thank you for reaching out to us. We have received your message and will get back to you as soon as possible. <br><br> Your message: <br> $message <br><br> Best regards, <br> Wavey Support Team";

    
    $categories = [
        'login' => [
            'keywords' => ['conectare', 'logare', 'autentificare', 'nu pot intra', 'nu merge sa ma conectez'],
            'response' => "Hello $name, <br><br> We're sorry you're having trouble logging in. Please double-check your credentials and try again. If the issue persists, try resetting your password or contact our support team. <br><br> Best regards, <br> Wavey Support Team"
        ],
        'image_upload' => [
            'keywords' => ['nu merge sa trimit o imagine', 'imaginea nu se trimite', 'problema trimitere imagine', 'nu pot trimite poza'],
            'response' => "Hello $name, <br><br> We're sorry you're having trouble sending images. Please make sure the file you're trying to send has one of the following supported extensions: .xbm; .tif; .pjp; .apng; .jpeg; .heif; .ico; .tiff; .webp; .svgz; .jpg; .heic; .gif; .svg; .png; .bmp; .pjpeg; .avif. If you're still having issues, feel free to reach out to us! <br><br> Best regards, <br> Wavey Support Team"
        ],
        'messaging' => [
            'keywords' => ['trimitere mesaj', 'nu se trimite mesajul', 'mesajele nu merg', 'nu pot scrie', 'nu merge sa trimit mesaje', 'mi se trimit mesajele'],
            'response' => "Hello $name, <br><br> We're sorry you're having trouble sending messages. Please make sure you have a stable internet connection and try refreshing the page. We're working to keep everything running smoothly. If the problem persists, let us know and we'll be happy to assist you further. <br><br> Best regards, <br> Wavey Support Team"
        ],
        'notifications' => [
            'keywords' => ['nu primesc notificari', 'notificarile nu apar', 'problema notificari'],
            'response' => "Hello $name, <br><br> We're sorry you're having trouble receiving notifications. Please make sure you've allowed notifications in your browser/app settings. If the problem persists, let us know and we'll be happy to assist you further. <br><br> Best regards, <br> Wavey Support Team"
        ],
        
    ];

    
    foreach ($categories as $category) {
        foreach ($category['keywords'] as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $responseBody = $category['response'];
                break 2;  
            }
        }
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $mail_user;
        $mail->Password = $mail_pwd;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->AddReplyTo($email);
        $mail->setFrom($email, $name);
        $mail->addAddress($mail_recipient, 'Recipient Name');

        $mail->isHTML(true);
        $mail->Subject = 'New message submission!';
        $mail->Body = "<strong>Name:</strong> $name <br> <strong>Email:</strong> $email <br><strong>Message:</strong> <br> $message";
        $mail->AltBody = "Name: $name \n Email: $email \n Message: $message";

        if ($mail->send()) {
            $success = 'Message has been sent successfully!';
        } else {
            $error = 'Message could not be sent.';
        }

        
        $mail->clearAllRecipients();
        $mail->addAddress($email, $name);
        $mail->Subject = 'Thank you for contacting us!';
        $mail->Body = $responseBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $responseBody));

        $mail->send();

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
            resize: vertical;
            height: 130px;
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
        a {
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

        .whatsapp-button {
            display: inline-flex;
            align-items: center;
            background-color: #25D366;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }
        .whatsapp-button:hover {
            background-color: #1ebe5d;
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
            <textarea id="message" name="message" spellcheck="false" required></textarea>

            <label class="fkskfdfv" for="usercode"></label>
            <input class="fkskfdfv" autocomplete="off" tabindex="-1" type="text" id="usercode" name="usercode" value="">

            <input type="submit" value="Send Message">
        </form>

        <a class="whatsapp-button" href="https://wa.me/40712323123" target="_blank">
    <img src="https://img.icons8.com/color/48/000000/whatsapp--v1.png" alt="WhatsApp" style="vertical-align: middle; width: 24px; height: 24px; margin-right: 8px;">
    Contactează-ne pe WhatsApp</a>


        <a href="index.php">Go Back</a>
    </div>

</body>
</html>