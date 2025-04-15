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

        .profile-container {
            display: flex;
            background-color: #5DB7DE; /* Fundalul containerului */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 80%;
            max-width: 1000px;
        }

        .profile-left {
            width: 30%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin-right: 20px; /* Adaugă spațiu între zona de butoane și zona de formulare */
        }

        .profile-right {
            width: 70%;
            display: none;
            flex-direction: column;
            justify-content: center;
            background-color: #A7D0DD; /* Fundalul zonei de formulare */
            padding: 20px;
            border-radius: 10px;
        }

        .profile-right.active {
            display: flex;
        }

        .profile-img-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
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

        button, a {
            padding: 10px;
            background-color: #DD622E; /* Fundalul butonului */
            color: #F1E9DB; /* Culoarea textului butonului */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 10px;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
            width: 100%; /* Asigură-te că butoanele au aceeași lățime */
            max-width: 300px; /* Setează o lățime maximă pentru butoane */
        }

        button:hover, a:hover {
            background-color: #06020C; /* Fundalul butonului la hover */
            color: #F1E9DB; /* Culoarea textului butonului la hover */
        }

        .small-button {
            width: 90%; /* Setează lățimea la 90% pentru butonul mic */
            max-width: 270px; /* Ajustează lățimea maximă pentru butonul mic */
            padding: 10px; /* Ajustează padding-ul pentru butonul mic */
            font-size: 16px; /* Ajustează dimensiunea fontului pentru butonul mic */
            margin: 0 auto; /* Centrează butonul */
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 16px;
            margin-bottom: 5px;
            color: #06020C; /* Culoarea textului */
        }

        input {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            background-color: #F1E9DB; /* Fundalul input-urilor */
            color: #06020C; /* Culoarea textului input-urilor */
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }

        .error-message {
            margin-top: 5px;
            color: red;
        }

        .success-message {
            margin-top: 5px;
            color: green;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-left">
            <div class="profile-img-container">
                <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-img">
                <button onclick="showForm('profile-pic')">Upload Profile Picture</button>
            </div>
            <button onclick="showForm('username')">Change Username</button>
            <button onclick="showForm('email')">Change Email</button>
            <button onclick="showForm('password')">Change Password</button>
            <a href="index.php" class="small-button">Go Back</a>
        </div>

        <div class="profile-right" id="profile-pic-form">
            <form action="upload_profile_pic.php" method="POST" enctype="multipart/form-data" target="_self">
                <label for="profile_pic">Upload Profile Picture:</label>
                <input type="file" name="profile_pic" id="profile_pic" accept="image/*" onchange="validateFileType()">
                <button type="submit" name="submit">Upload</button>
                <p id="pfp_status" class="<?php
                        if (isset($_SESSION['pfpError'])) {
                            if ($_SESSION['pfpError']) {
                                echo "error-message";
                            } else {
                                echo "success-message";
                            }
                            unset($_SESSION['pfpError']);
                        }
                        ?>">
                        <?php
                            if (isset($_SESSION['pfpMessage'])) {
                                echo $_SESSION['pfpMessage'];
                                unset($_SESSION['pfpMessage']);
                            }
                        ?>
                    </p>
            </form>
        </div>

        <div class="profile-right" id="username-form">
            <form action="change_username.php" method="POST" onsubmit="return validateUsername()" target="_self">
                <label for="new_username">Username: </label>
                <input type="text" id="new_username" name="new_username" value="<?php echo htmlspecialchars($username); ?>" required><br>
                <button type="submit" name="submit">Change username</button>
                <p id="username_status" class="<?php
                        if (isset($_SESSION['usernameError'])) {
                            if ($_SESSION['usernameError']) {
                                echo "error-message";
                            } else {
                                echo "success-message";
                            }
                            unset($_SESSION['usernameError']);
                        }
                        ?>">
                    <?php
                        if (isset($_SESSION['usernameMessage'])) {
                            echo $_SESSION['usernameMessage'];
                            unset($_SESSION['usernameMessage']);
                        }
                    ?>
                </p>
            </form>
        </div>

        <div class="profile-right" id="email-form">
            <form action="change_email.php" method="POST" target="_self">
                <label for="new_email">Email: </label>
                <input type="email" id="new_email" name="new_email" value="<?php echo htmlspecialchars($email); ?>" required><br>
                <button type="submit" name="submit">Change email</button>
                <p class="<?php
                        if (isset($_SESSION['emailError'])) {
                            if ($_SESSION['emailError']) {
                                echo "error-message";
                            } else {
                                echo "success-message";
                            }
                            unset($_SESSION['emailError']);
                        }
                        ?>">
                    <?php
                        if (isset($_SESSION['emailMessage'])) {
                            echo $_SESSION['emailMessage'];
                            unset($_SESSION['emailMessage']);
                        }
                    ?>
                </p>
            </form>
        </div>

        <div class="profile-right" id="password-form">
            <form action="change_password.php" method="POST" onsubmit="return validatePasswordChangeForm()" target="_self">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required><br>

                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required><br>

                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required><br>

                <button type="submit" name="submit">Change Password</button>

                <p id="password_status" class="<?php
                        if (isset($_SESSION['passwordError'])) {
                            if ($_SESSION['passwordError']) {
                                echo "error-message";
                            } else {
                                echo "success-message";
                            }
                            unset($_SESSION['passwordError']);
                        }
                        ?>">
                    <?php
                        if (isset($_SESSION['passwordMessage'])) {
                            echo $_SESSION['passwordMessage'];
                            unset($_SESSION['passwordMessage']);
                        }
                    ?>
                </p>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const activeForm = localStorage.getItem('activeForm');
            if (activeForm) {
                showForm(activeForm);
            }
        });

        function showForm(formId) {
            document.querySelectorAll('.profile-right').forEach(form => {
                form.classList.remove('active');
            });
            document.getElementById(formId + '-form').classList.add('active');
            localStorage.setItem('activeForm', formId);
        }
        
        function validateFileType() {
            const fileInput = document.getElementById('profile_pic');
            const filePath = fileInput.value;

            const pfpStatus = document.getElementById("pfp_status");

            const allowedExtensions = /(\.jpg|\.jpeg|\.png)$/i;

            if (!allowedExtensions.exec(filePath)) {
                pfpStatus.className = "error-message";
                pfpStatus.textContent = "Please upload an image file (JPG, JPEG, PNG).";
                fileInput.value = '';
            } else {
                pfpStatus.className = "";
                pfpStatus.textContent = "";
            }
        }

        function validateUsername() {
            const username = document.getElementById("new_username").value;

            const usernameStatus = document.getElementById("username_status");

            const usernamePattern = /^[0-9A-Za-z]{3,16}$/;

            if (username.trim() === "") {
                usernameStatus.className = "error-message";
                usernameStatus.textContent = "New username is required.";
                return false;
            }

            if (!usernamePattern.test(username)) {
                usernameStatus.className = "error-message";
                usernameStatus.textContent = "New username must be between 3 and 16 characters long and contain a mix of numbers and letters";
                return false;
            }


            return true;
        }

        function validatePasswordChangeForm() {
            const currentPassword = document.getElementById("current_password").value;
            const newPassword = document.getElementById("new_password").value;
            const confirmPassword = document.getElementById("confirm_password").value;

            const passwordStatus = document.getElementById("password_status");

            const passwordPattern = /^(?=(.*[A-Z]))(?=(.*[a-z]))(?=(.*\d))(?=(.*[!@#$%^&*(),.?":{}|<>]))[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/;

            if (currentPassword.trim() === "") {
                passwordStatus.className = "error-message";
                passwordStatus.textContent = "Current password is required.";
                return false;
            }

            if (!passwordPattern.test(newPassword)) {
                passwordStatus.className = "error-message";
                passwordStatus.textContent = "New password must be at least 8 characters long and contain a mix of uppercase, lowercase, numbers, and special characters.";
                return false;
            }

            if (newPassword !== confirmPassword) {
                passwordStatus.className = "error-message";
                passwordStatus.textContent = "New passwords do not match.";
                
                return false;
            }

            return true;
        }   
    </script>
</body>
</html>