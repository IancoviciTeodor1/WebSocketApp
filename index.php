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

function authenticateToken($token) {
    $secretKey = 'secret_key'; // Folosește aceeași cheie ca la generare

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
    <title>WebSocket Chat App</title>
    <style>
        button {
            cursor: pointer;
        }
        #userList div {
            border: 1px solid #000;
            padding: 10px;
            margin: 5px 0;
            width: 92%;
        }
        #userList div:hover {
            background-color: #00f;
            color: #fff;
            cursor: pointer;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #f0f0f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        header h1 {
            margin: 0;
        }
        .logout-form button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            background-color: #ff4c4c;
            color: white;
        }
        .logout-form button:hover {
            background-color: #ff3333;
        }
        #content {
            display: flex;
            flex: 1;
        }
        #sidebar {
            width: 30%;
            max-width: 300px;
            border-right: 1px solid #ccc;
            padding: 10px;
            overflow-y: auto;
        }
        #main {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        #recentConversations {
            max-height: 50%;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
        }
        .conversation-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }
        .conversation-item:hover {
            background-color: #f0f0f0;
        }
        #userList {
            max-height: 50%;
            overflow-y: auto;
        }
        #conversation {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        #messages {
            flex: 1;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            overflow-y: auto;
        }
        #messageInput {
            width: calc(100% - 90px);
            padding: 10px;
            border: 1px solid #ccc;
        }
        #sendButton {
            padding: 10px;
            border: none;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        #sendButton:hover {
            background-color: #45a049;
        }

        #side {
            display: inline-flex;
        }
        .profile-btn {
            margin-right: 10px;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            background-color: #2ecc71;
            color: white;
            white-space: nowrap;
            align-self: flex-start;
            text-decoration: none;
        }

        .profile-btn:hover {
            background-color: #27ae60;
        }

        .profile-pic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 1px solid #fff;
        }
        .username {
            font-weight: bold;
        }
        .message-item {
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-bottom: 10px;
        }
        .message-header {
            display: flex;
            align-items: center;
        }
        .message-body {
            margin-left: 50px;
        }
        .timestamp {
            font-size: 0.8rem;
            color: gray;
            margin-left: 5px;
        }

        #notificationContainer {
            position: relative;
            left: 600px;
        }

        #notificationButton {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            position: relative;
        }

        #notificationButton::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 10px;
            height: 10px;
            background: red;
            border-radius: 50%;
            display: none; /* Apare doar când există notificări noi */
        }

        #notificationButton.has-notifications::after {
            display: block;
        }
        /* Ascunde lista de notificări */
        .hidden {
            display: none;
        }

        /* Stilul dropdown-ului */
        #notificationDropdown {
            position: absolute;
            top: 50px; /* Ajustează în funcție de poziția butonului */
            right: 10px; /* Ajustează în funcție de poziția dorită */
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            background-color: white;
            border: 1px solid #ddd;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            border-radius: 8px;
        }

        /* Stil pentru fiecare notificare */
        #notificationList > div {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }

        #notificationList > div:hover {
            background-color: #f0f0f0;
        }

        /* Stil pentru textul de notificare */
        #notificationList > div b {
            display: block;
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
    <header>
        <h1><a href="index.php" style="text-decoration: none; color: black;">WebSocket Chat App</a></h1>
        <div id="notificationContainer">
            <!-- Panoul pentru notificări -->
            <button id="notificationButton">🔔</button>
            <div id="notificationDropdown" class="hidden">
                <div id="notificationList"></div>
            </div>
        </div>
        <div id="side">
            <a href="profile.php" class="profile-btn">Profile</a>
            <form class="logout-form" method="POST" action="">
                <button type="submit" name="logout">Logout</button>
            </form>
        </div>
    </header>
        <div id="content">
            <!-- Sidebar -->
            <div id="sidebar">
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
            <!-- Main Chat Area -->
            <div id="main">
                <div id="conversation">
                    <div id="messages"></div>
                    <div>
                        <input type="text" id="messageInput" placeholder="Type a message">
                        <button id="sendButton" onclick="sendMessage()">Send</button>
                    </div>
                </div>
            </div>
        </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('token');
            if (!token) {
                window.location.href = 'login.php';
            }
        });
    </script>

    <script>
        let socket;
        let token = localStorage.getItem('token');
        let username = '<?php echo $_SESSION['username']; ?>';
        const userId = localStorage.getItem('userId');
        if (!userId || !token) {
            alert('Session expired. Please log in again.');
            window.location.href = 'login.php';
        }
        let currentConversationId;
        let currentReceiverId;
        console.log('User ID after login:', localStorage.getItem('userId'));
        console.log('Token after login:', localStorage.getItem('token'));
        
        const currentUsername = <?php echo json_encode($currentUsername); ?>;

        // Function to search users
        async function searchUsers() {
            console.log('searchUsers function called'); // Verificăm că funcția este apelată
            const query = document.getElementById('searchInput').value;
            const token = localStorage.getItem('token'); // Fetch token each time

            if (!token) {
                alert('Session expired. Please log in again.');
                localStorage.removeItem('token');
                
                // Trimite o cerere către server pentru a șterge sesiunea
                await fetch('logout.php').then(() => {
                    window.location.href = 'login.php';
                });
                return;
            }

            try {
                const response = await fetch('http://localhost:3000/search?q=' + encodeURIComponent(query), {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    const users = await response.json();
                    console.log('Rezultate căutare:', users); // Afișează rezultatele în consolă
                    
                    // Golim și populăm lista de utilizatori
                    const userList = document.getElementById('userList');
                    userList.innerHTML = '';
                    users.forEach(user => {
                        const userItem = document.createElement('div');
                        userItem.textContent = user.username;
                        userItem.onclick = () => openUserConversation(user.id);
                        userList.appendChild(userItem);
                    });
                } else {
                    console.error('Eroare la răspuns:', response.status);
                }
            } catch (error) {
                console.error('Request failed:', error);
            }
        }

        // Adăugare eveniment pentru butonul de căutare
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('searchButton').addEventListener('click', searchUsers);
        });

        // Function to open or create a conversation
        function openUserConversation(receiverId) {
            currentReceiverId = receiverId;
            console.log('Receiver ID set to:', currentReceiverId);
            document.getElementById('conversation').style.display = 'block';

            // Verificăm dacă există deja conversația
            fetch(`conversations.php?receiverId=${receiverId}`, {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
            })
            .then(response => {
                if (response.ok) {
                    return response.json(); // Continuă dacă răspunsul este valid
                } else if (response.status === 404) {
                    // Nu există conversația, dar vom crea conversația când trimitem un mesaj
                    console.log('No conversation found, will create on message send.');
                    return { conversationId: null }; // Conversația nu există, setăm `conversationId` pe `null`
                } else {
                    throw new Error('Failed to open conversation');
                }
            })
            .then(data => {
                if (data.conversationId) {
                    currentConversationId = data.conversationId;
                    console.log(`Conversatie deschisă: ID=${data.conversationId}, Tip=${data.type}`);
                    loadMessages(currentConversationId);
                } else {
                    console.log('Conversation will be created on first message send');
                    currentConversationId = null; // Setează pe null dacă nu există conversația
                }
            })
            .catch(error => {
                console.error('Failed to open conversation:', error);
                alert('Failed to open conversation.');
            });
        }
        function openConversation(conversationId, type = null, participants = null) {
            currentConversationId = conversationId; // Setăm ID-ul conversației curente
            document.getElementById('conversation').style.display = 'block';

            // Încarcă mesajele pentru conversația selectată
            if (conversationId) {
                console.log(`Loading conversation ID=${conversationId}`);
                loadMessages(conversationId);
                return;
            }

            // Dacă nu există ID-ul conversației, tratăm cazurile diferite
            if (type === 'one-on-one') {
                console.log('No existing conversation. Will create when sending a message.');
                currentConversationId = null; // Conversația va fi creată ulterior
            } else if (type === 'group') {
                console.error('Group conversations cannot be created dynamically.');
                alert('Cannot open this type of conversation.');
            } else {
                console.error('Invalid conversation type or ID.');
                alert('Cannot open this conversation.');
            }
        }



        // Function to create a new conversation
        function createConversation(receiverId, message, userId) {
            fetch('conversations.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({
                    userId: userId,
                    receiverId: receiverId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.conversationId) {
                    currentConversationId = data.conversationId;
                    sendMessageToConversation(currentConversationId, message, userId);
                } else {
                    console.error('Failed to create conversation, conversationId missing');
                    alert('Failed to create conversation: conversationId not returned from server');
                }
            })
            .catch(error => {
                console.error('Error creating conversation:', error);
                alert('Error creating conversation');
            });
        }

        function findOrCreateConversation(receiverId, message, userId) {
            // Verifică mai întâi dacă există conversația
            const url = `conversations.php?userId=${userId}&receiverId=${receiverId}`;

            fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error searching for conversation');
                }
                return response.json();
            })
            .then(data => {
                if (data.conversationId) {
                    // Dacă există conversație, setează `currentConversationId`
                    currentConversationId = data.conversationId;
                    sendMessageToConversation(currentConversationId, message, userId);
                } else {
                    // Dacă nu există conversație, o creează
                    createConversation(receiverId, message, userId);
                }
            })
            .catch(error => {
                console.error('Failed to find or create conversation:', error);
                alert('Failed to find or create conversation.');
            });
        }

        function sendMessageToConversation(conversationId, message, userId) {
            if (!conversationId) {
                console.log("Conversation not found; message cannot be sent.");
                return;
            }

            fetch(`messages.php?conversationId=${conversationId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({
                    content: message
                })
            })
            .then(response => {
                console.log("Raw response:", response);
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(text); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayMessage(data.message);
                } else {
                    console.error('Error in response data:', data.error || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Failed to send message: ' + error.message);
            });
        }

        function displayMessage(message) {
            const messagesDiv = document.getElementById('messages');

            const messageItem = document.createElement('div');
            messageItem.classList = "message-item";

            messageItem.innerHTML = `
                <div class="message-header">
                    <img src="${message.profile_picture}" alt="Profile Picture" class="profile-pic">
                    <span class="username">${message.username || 'Unknown'}</span>
                    <div class="timestamp">
                        ${new Date(message.timestamp).toLocaleString()}
                    </div>
                </div>
                <div class="message-body">
                    ${message.content || ''}
                </div>
            `;

            messagesDiv.appendChild(messageItem);
        }

            function handleSessionExpiry() {
            console.log('Redirecting to login page');
            alert('Session expired. Please log in again.');
            localStorage.removeItem('token');
            window.location.href = 'login.php';
        }



        // Function to load messages of a conversation
        function loadMessages(conversationId) {
            fetch(`http://localhost:3000/messages?conversationId=${conversationId}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            })
            .then(response => response.json())
            .then(messages => {
                const messagesDiv = document.getElementById('messages');
                messagesDiv.innerHTML = '';
                messages.forEach(message => {
                    displayMessage(message);
                });
            })
            .catch(error => {
                console.error('Failed to load messages:', error);
                alert('Failed to load messages: ' + error.message);
            });
        }

        // Function to connect WebSocket
        function connectWebSocket(username) {
            socket = new WebSocket('ws://localhost:8081');
            socket.onopen = () => {
                console.log('Connected to server');
            };
            socket.onmessage = event => {
                const message = JSON.parse(event.data);
                displayMessage(message);
            };
            socket.onclose = () => {
                console.log('Disconnected from server');
            };
        }

        // Function to send a message
        /*function sendMessage() {
            const message = document.getElementById('messageInput').value;
            const receiverId = currentReceiverId;
            const userId = localStorage.getItem('userId');
            const token = localStorage.getItem('token');

            if (!token || !message || !receiverId) {
                alert('Message or receiver is missing.');
                return;
            }

            // Verifică dacă `currentConversationId` este definit
            if (!currentConversationId) {
                findOrCreateConversation(receiverId, message, userId);
            } else {
                sendMessageToConversation(currentConversationId, message, userId);
            }
        }*/
        function sendMessage() {
            const userId = localStorage.getItem('userId');
            const messageInput = document.getElementById('messageInput');
            const messageContent = messageInput.value.trim();

            if (!messageContent) {
                alert('Message content cannot be empty.');
                return;
            }

            const payload = {
                content: messageContent,
                conversationId: currentConversationId || null, // Pentru conversații existente
                receiverId: currentConversationId ? null : currentReceiverId, // Doar pentru conversații noi
                senderId: userId, // ID-ul utilizatorului curent
            };

            console.log('Payload sent to server:', payload);

            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(err => {
                        console.error('Error response text:', err);
                        throw new Error(`HTTP Error: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Message sent successfully:', data);
                messageInput.value = ''; // Golește câmpul de text
                loadMessages(data.conversationId); // Reîncarcă mesajele
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Failed to send message.');
            });
        }



        let offset = 0; // Offset pentru paginare
        const limit = 20; // Număr de conversații per cerere
        let loading = false; // Indicator pentru a preveni cererile multiple
        let allLoaded = false; // Indicator dacă toate conversațiile au fost încărcate

        // Funcție pentru a încărca conversațiile recente
        function loadRecentConversations(offset = 0, limit = 20) {
            fetch(`recent_conversations.php?offset=${offset}&limit=${limit}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            })
            .then(response => {
                if (!response.ok) {
                    console.error('HTTP Error:', response.status);
                    return response.text().then(err => {
                        throw new Error(`Server Error: ${err}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                if (Array.isArray(data)) {
                    const recentList = document.getElementById('recentConversations');
                    recentList.innerHTML = '';
                    data.forEach(conversation => {
                console.log('Conversation data:', conversation);

                const conversationItem = document.createElement('div');
                conversationItem.classList.add('conversation-item');

                // Afișează numele conversației folosind "conversationName"
                conversationItem.textContent = conversation.conversationName || 'Unnamed conversation';

                // Configurăm acțiunea la click pentru conversație
                conversationItem.onclick = () =>
                    openConversation(conversation.conversationId, conversation.conversationType);

                recentList.appendChild(conversationItem);
            });
                } else {
                    console.error('Unexpected data format:', data);
                }
            })
            .catch(error => {
                console.error('Error loading recent conversations:', error);
                alert('Failed to load recent conversations.');
            });
        }


        let notificationsVisible = false;

    // Funcția pentru a comuta vizibilitatea listei de notificări
    async function loadNotifications() {
    try {
        const response = await fetch('notifications.php', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch notifications');
        }

        const data = await response.json();
        const notificationList = document.getElementById('notificationList');
        notificationList.innerHTML = '';

        if (data.length === 0) {
            notificationList.innerHTML = '<p>No new notifications</p>';
            document.getElementById('notificationButton').classList.remove('has-notifications');
        } else {
            data.forEach(notification => {
                const item = document.createElement('div');
                item.style.padding = '10px';
                item.style.borderBottom = '1px solid #ddd';

                let notificationContent = '';

                if (notification.conversation_type === 'one-on-one') {
                    // Notificare pentru conversații individuale
                    notificationContent = `<b>${notification.sender_name}:</b> ${notification.message_content}`;
                } else if (notification.conversation_type === 'group') {
                    // Notificare pentru conversații de grup
                    notificationContent = `<b>${notification.group_name}<br>${notification.sender_name}:</b> ${notification.message_content}`;
                }

                item.innerHTML = notificationContent;

                // Adaugă un eveniment de click pentru a deschide conversația
                item.style.cursor = 'pointer';
                item.onclick = () => openConversation(notification.conversation_id);

                notificationList.appendChild(item);
            });

            document.getElementById('notificationButton').classList.add('has-notifications');
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');

    // Eveniment pentru afișarea/ascunderea notificărilor
    notificationButton.addEventListener('click', () => {
        notificationDropdown.classList.toggle('hidden'); // Toggle visibility
        if (!notificationDropdown.classList.contains('hidden')) {
            loadNotifications(); // Încarcă notificările doar când dropdown-ul este deschis
        }
    });

    // Închide dropdown-ul când se face click în afara lui
    document.addEventListener('click', (event) => {
        if (!notificationButton.contains(event.target) && !notificationDropdown.contains(event.target)) {
            notificationDropdown.classList.add('hidden');
        }
    });
});



    // Funcția pentru a gestiona acceptarea/refuzarea invitațiilor
    async function handleInvitation(notificationId, action) {
        try {
            const response = await fetch('handle_invitation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({ notificationId, action })
            });

            const result = await response.json();
            if (result.success) {
                alert(`Invitation ${action}ed successfully`);
                loadNotifications(); // Reîncărcăm notificările
            } else {
                alert('Failed to handle invitation');
            }
        } catch (error) {
            console.error('Error handling invitation:', error);
        }
    }

    // Încarcă notificările periodic
    setInterval(loadNotifications, 10000);
    loadNotifications(); // Încarcă notificările imediat ce se încarcă pagina

        console.log(currentUsername);

        // Ascultă evenimentul de scroll pentru a încărca mai multe conversații
        document.getElementById('recentConversations').addEventListener('scroll', function () {
            const { scrollTop, scrollHeight, clientHeight } = this;
            if (scrollTop + clientHeight >= scrollHeight - 10) {
                loadRecentConversations(); // Încarcă mai multe conversații
            }
        });

        // Încarcă primele conversații la inițializare
        loadRecentConversations();


        // Connect WebSocket on page load
        connectWebSocket(username);
    </script>

</body>
</html>