<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function authenticateToken($token)
{
    $secretKey = 'secretkey'; // Foloseste aceeasi cheie ca la generare

    try {
        $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
        return $decoded; // Returnează payload-ul decodat
    } catch (Exception $e) {
        // Loghează eroarea pentru depanare
        error_log('Token invalid: ' . $e->getMessage());
        return false; // Token invalid
    }
}

// Înainte de a genera JavaScript-ul
$currentUsername = $_SESSION['username'] ?? null; // Sau cum este definit username-ul utilizatorului conectat
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wavey</title>
    <link href="/WebSocketApp/css/main_page_style.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet">
</head>

<body>
    <header>
        <h1><a href="index.php" style="text-decoration: none; color: black;">Wavey</a></h1>
        <div id="side">
            <div id="notificationContainer">
                <!-- Panoul pentru notificări -->
                <button id="notificationButton">🔔</button>
                <div id="notificationDropdown" class="hidden">
                    <div id="notificationList"></div>
                </div>
            </div>
            <a href="profile.php" class="profile-btn">Profile</a>
            <form class="logout-form" method="POST" action="" onsubmit="clearToken()">
                <button type="submit" name="logout">Logout</button>
            </form>
        </div>
    </header>
    <div id="content">
        <!-- Sidebar -->
        <div id="leftside">
            <div id="sidebar">
                <button id="createGroupButton">Create Group</button>
                <div id="userSearch">
                    <input type="text" id="searchInput" placeholder="Search users">
                    <button onclick="searchUsers()">Search</button>
                    <div id="userList"></div>
                </div>
                <div id="recentConversations">
                    <h3>Conversații recente</h3>
                    <div id="conversationList"></div>
                </div>
            </div>
            <footer>

                <div class="footer-content">
                    <a href="contact.php">Contact Us</a>
                    |
                    <a href="faq.php">FAQ</a>
                </div>
                <div class="footer-content">
                    &copy; 2025 Wavey
                </div>
        </div>

        <!-- Main Chat Area -->
        <div id="main">
            <div id="conversation">
                <div id="messages"></div>
                <button class="join" type="button" id="call-button" onclick="call()">Call</button>
                <input type="file" id="fileInput" multiple style="display: none;" accept="image/*" onchange="previewSelectedImages(event)">
                <div id="filePreview" style="margin-top: 10px;"></div>
                <div style="display: flex; align-items: center; gap: 10px; background-color:#325D75">
                    <button onclick="document.getElementById('fileInput').click();" style="display: flex; align-items: center;">
                        <span class="material-icons">attach_file</span>
                    </button>
                    <input type="text" id="messageInput" placeholder="Type a message" style="flex: 1; height: 40px; padding: 5px;">
                    <button id="sendButton" onclick="sendMessage()" style="height: 40px;">Send</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/WebSocketApp/js/websocket.js?v=<?php echo time(); ?>"></script>
    <script src="/WebSocketApp/js/conversations.js?v=<?php echo time(); ?>"></script>
    <script src="/WebSocketApp/js/messages.js?v=<?php echo time(); ?>"></script>
    <script src="/WebSocketApp/js/notifications.js?v=<?php echo time(); ?>"></script>
    <script src="/WebSocketApp/js/group.js?v=<?php echo time(); ?>"></script>
    <script src="/WebSocketApp/js/lib/confetti.browser.min.js"></script>

    <script>
        let token = localStorage.getItem('token');
        let username = '<?php echo $_SESSION['username']; ?>';
        localStorage.setItem('username', username); // Salvează username în localStorage
        const userId = localStorage.getItem('userId');
        if (!userId || !token) {
            alert('Session expired. Please log in again.');
            window.location.href = 'login.php';
        }
        let currentConversationId;
        let currentConversationType = null;
        let currentReceiverId;
        console.log('User ID after login:', localStorage.getItem('userId'));
        console.log('Token after login:', localStorage.getItem('token'));

        const currentUsername = <?php echo json_encode($currentUsername); ?>;
        const BASE_URL = `${window.location.origin}/WebSocketApp`;
        let currentUserRole = null;

        let socket = null;
        let activeConversations = new Set(); // Set pentru a ține evidența conversațiilor active

        console.log(currentUsername);

        function call() {
            location.href= `http:\/\/localhost:3001/#${currentConversationId}?user=${localStorage.getItem('username')}`;
        }

        // Încarcă primele conversații la inițializare
        loadRecentConversations();
        let currentConversationMembers = [];

        loadNotifications(); // Încarcă notificările imediat ce se încarcă pagina
    </script>

    <div id="popupOverlay" class="hidden">
        <div id="groupFormPopup">
            <div class="popup-header">
                <h3>Create New Group</h3>
                <span class="close-popup material-icons">close</span>
            </div>
            <div class="popup-content">
                <label for="groupName">Group Name:</label>
                <input type="text" id="groupName" placeholder="Enter group name">

                <div id="groupUserSearch">
                    <input type="text" id="groupSearchInput" placeholder="Search users" oninput="searchInvitationUsers()">
                    <div id="inviteUserList"></div>
                </div>
                <div id="selectedUsers">
                    <h4>Selected Users</h4>
                    <ul id="selectedUserList"></ul>
                </div>

                <button id="submitGroupButton">Create Group</button>
            </div>
        </div>

    </div>
</body>
</html>