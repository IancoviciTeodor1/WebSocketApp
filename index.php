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
    $secretKey = 'secretkey'; // Folosește aceeași cheie ca la generare

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
<<<<<<< Updated upstream
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
        #content {
            display: flex;
            flex: 1;
        }
        #side {
            display: inline-flex;
            align-items: center;
        }
        .profile-btn {
            margin-right: 10px;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            background-color: #2ecc71;
            color: white;
            white-space: nowrap;
            text-decoration: none;
        }
        .profile-btn:hover {
            background-color: #27ae60;
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
        .logout-form {
            margin: 0;
        }

        #main {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        #sidebar {
            width: 30%;
            max-width: 300px;
            border-right: 1px solid #ccc;
            padding: 10px;
            overflow-y: auto;
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
        }
        #sendButton:hover {
            background-color: #45a049;
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
            display: flex;
            align-items: center;
            margin-right: 20px;
        }

        #notificationButton {
            background: none;
            border: none;
            font-size: 24px;
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

        #groupForm {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

    </style>
=======
    <link href="/WebSocketApp/css/main_page_style.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css"/>
>>>>>>> Stashed changes
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
        <div id="sidebar">
            <div id="createGroupContainer">
                <button id="createGroupButton">Create Group</button>
                <div id="groupForm" class="hidden">
                    <h3>Create New Group</h3>
                    <label for="groupName">Group Name:</label>
                    <input type="text" id="groupName" placeholder="Enter group name">

                    <div id="groupUserSearch">
                        <input type="text" id="groupSearchInput" placeholder="Search users" oninput="searchUsers()">
                        <div id="inviteUserList"></div>
                    </div>
                    <div id="selectedUsers">
                        <h4>Selected Users</h4>
                        <ul id="selectedUserList"></ul>
                    </div>

                    <button id="submitGroupButton">Create Group</button>
                </div>
            </div>
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
                    <input type="file" id="fileInput" multiple style="display: none;">
                    <input type="button" value="Browse..." onclick="document.getElementById('fileInput').click();" />
                    <div id="filePreview" style="margin-top: 10px;"></div>
                </div>
            </div>
        </div>
    </div>

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
        let currentReceiverId;
        console.log('User ID after login:', localStorage.getItem('userId'));
        console.log('Token after login:', localStorage.getItem('token'));
        
        const currentUsername = <?php echo json_encode($currentUsername); ?>;
        const BASE_URL = `${window.location.origin}/WebSocketApp`;

        let socket = null;
        let activeConversations = new Set(); // Set pentru a ține evidența conversațiilor active

        function connectWebSocket(conversationId) {
            if (socket === null || socket.readyState === WebSocket.CLOSED) {
                socket = new WebSocket('ws://localhost:8081');
                
                socket.onopen = () => {
                    console.log('Connected to WebSocket server');
                    joinConversation(conversationId);
                };
                
                socket.onmessage = event => {
                    const msg = JSON.parse(event.data);

                    if (msg.conversationId === currentConversationId) {
                        // Refacem fetch-ul complet, dar afișăm doar ultimul mesaj
                        fetch(`http://localhost:3000/messages?conversationId=${msg.conversationId}`, {
                            headers: { 'Authorization': `Bearer ${token}` }
                        })
                        .then(response => response.json())
                        .then(messages => {
                            const lastMessage = messages[messages.length - 1];
                            if (lastMessage) {
                                displayMessage(lastMessage);
                            }
                        });
                    }
                };

                socket.onclose = () => {
                    console.log('Disconnected from WebSocket server');
                    activeConversations.clear(); // Golim conversațiile active la deconectare
                };
            } else {
                // Dacă conexiunea este deja activă, părăsește conversația curentă și alătură-te uneia noi
                if (currentConversationId && currentConversationId !== conversationId) {
                    leaveConversation(currentConversationId);
                }
                joinConversation(conversationId);
            }
        }


        function joinConversation(conversationId) {
            if (!activeConversations.has(conversationId)) {
                socket.send(JSON.stringify({ type: 'join', conversationId }));
                activeConversations.add(conversationId);
                currentConversationId = conversationId;
                console.log(`User joined conversation ${conversationId}`);
            } else {
                console.log(`Already joined conversation ${conversationId}`);
            }
        }


        function leaveConversation(conversationId) {
    if (activeConversations.has(conversationId)) {
        socket.send(JSON.stringify({ type: 'leave', conversationId }));
        activeConversations.delete(conversationId);
        console.log(`User left conversation ${conversationId}`);
    } else {
        console.log(`Conversation ${conversationId} was not active`);
    }
}



        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('token');
            if (!token) {
                window.location.href = 'login.php';
            }
        });

        function clearToken() {
            localStorage.removeItem('token'); // Eliminăm token-ul din localStorage
            localStorage.removeItem('userId'); // Dacă există și userId, îl ștergem
            console.log('Token and userId removed from localStorage');
        }


        function checkTokenValidity() {
            const token = localStorage.getItem('token'); // Obține token-ul curent din localStorage

            if (!token) {
                alert('Session expired. Redirecting to login.');
                logoutAndRedirect(); // Apelează funcția de logout
                return;
            }

            console.log('Checking token validity:', token); // Debugging

            fetch(BASE_URL + '/api/validate_token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ token }), // Trimite token-ul din localStorage
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Server response:', data); // Debugging răspuns server
                    if (data.status === 'error') {
                        alert(data.message || 'Session expired. Redirecting to login.');
                        logoutAndRedirect(); // Apelează funcția de logout
                    }
                })
                .catch(error => {
                    console.error('Error validating token:', error); // Debugging erori
                });
        }

        // Funcția pentru a șterge token-ul, sesiunea și a redirecționa
        function logoutAndRedirect() {
            // Șterge token-ul și userId-ul din localStorage
            localStorage.removeItem('token');
            localStorage.removeItem('userId');

            // Apelează endpoint-ul de logout pe server pentru a distruge sesiunea
            fetch(`${BASE_URL}/api/logout.php`, {
                method: 'POST',
            })
                .then(() => {
                    window.location.href = 'login.php'; // Redirecționează la login
                })
                .catch(error => {
                    console.error('Error during logout:', error); // Debugging erori
                    window.location.href = 'login.php'; // Redirecționează chiar și în caz de eroare
                });
        }

        // Verifică token-ul o dată la 10 secunde
        setInterval(checkTokenValidity, 90000);



        function checkTokenChanged() {
            const storedToken = localStorage.getItem('token'); // Token-ul actual din localStorage

            if (storedToken !== token) {
                alert('Session changed. Redirecting to login.');
                window.location.href = 'login.php';
            }
        }

        // Verifică dacă token-ul s-a schimbat o dată la 5 secunde
        setInterval(checkTokenChanged, 5000);


        // Function to search users
        async function searchUsers() {
            console.log('searchUsers function called'); // Verificăm că funcția este apelată
            const query = document.getElementById('searchInput').value;
            const token = localStorage.getItem('token'); // Fetch token each time

            if (!token) {
                alert('Session expired. Please log in again.');
                localStorage.removeItem('token');
                
                // Trimite o cerere către server pentru a șterge sesiunea
                await fetch(`${BASE_URL}/api/logout.php`).then(() => {
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


        function markMessagesAsRead(conversationId, lastReadMessageId) {
            const userId = localStorage.getItem('userId');
            console.log({ userId, conversationId, lastReadMessageId });


            fetch(`${BASE_URL}/api/mark_messages_as_read.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ userId, conversationId, lastReadMessageId }),
            })
            .then(response => {
                console.log(response);  // Log
                if (!response.ok) {
                    return response.text().then(err => {
                        console.error('Error response text:', err);
                        throw new Error(`HTTP Error: ${response.status}`);
                    });
                }
            })
            .catch(error => {
                console.error('Error marking messages as read:', error);
            });
        }


        // Function to open or create a conversation
        function openUserConversation(receiverId) {
            currentReceiverId = receiverId;
            console.log('Receiver ID set to:', currentReceiverId);
            document.getElementById('conversation').style.display = 'block';

            // Verificăm dacă există deja conversația
            fetch(`${BASE_URL}/api/conversations.php?receiverId=${receiverId}`, {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
            })
            .then(response => {
                if (response.ok) {
                    return response.json(); // Continuă dacă răspunsul este valid
                } else if (response.status === 404) {
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
                    loadMessages(currentConversationId); // Încărcăm mesajele conversației

                    // Conectare la WebSocket pentru această conversație
                    connectWebSocket(data.conversationId);
                } else {
                    console.log('Conversation will be created on first message send');
                    currentConversationId = null;
                }
            })
            .catch(error => {
                console.error('Failed to open conversation:', error);
                alert('Failed to open conversation.');
            });
        }

        function openConversation(conversationId, type = null, participants = null) {
            //currentConversationId = conversationId;  Setăm ID-ul conversației curente
            document.getElementById('conversation').style.display = 'block';

            // Încarcă mesajele pentru conversația selectată
            if (conversationId) {
                console.log(`Loading conversation ID=${conversationId}`);
                
                loadMessages(conversationId).then(lastMessageId => {
                    // Marchează mesajele ca citite
                    if (lastMessageId) {
                        markMessagesAsRead(conversationId, lastMessageId);
                    }
                    else {
                        // Dacă nu sunt mesaje, folosim un ID fix pentru a testa funcția
                        markMessagesAsRead(conversationId, 1); // ID-ul 1 ca test
                    }
                });

                // Conectare la WebSocket pentru această conversație
                connectWebSocket(conversationId);
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

        function displayMessage(message) {
    const messagesDiv = document.getElementById('messages');

    const messageItem = document.createElement('div');
    messageItem.classList.add('message-item');

    // Vom construi toate fișierele într-un container separat
    let fileContent = '';
    if (Array.isArray(message.files) && message.files.length > 0) {
        fileContent += `<div class="file-container">`;

        message.files.forEach(file => {
            if (file.type === 'image') {
                fileContent += `<img src="uploads/user_files/${file.path}" alt="Attached Image" class="message-file">`;
            } else if (file.type === 'audio') {
                fileContent += `<audio controls><source src="uploads/user_files/${file.path}" type="audio/mpeg"></audio>`;
            } else if (file.type === 'video') {
                fileContent += `<video controls><source src="uploads/user_files/${file.path}" type="video/mp4"></video>`;
            } else if (file.type === 'document') {
                fileContent += `<div class="file-preview">
                                    <img src="icons/pdf-icon.png" class="file-icon">
                                    <a href="uploads/user_files/${file.path}" target="_blank">View File</a>
                                </div>`;
            }
        });

        fileContent += `</div>`;
    }

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
            ${fileContent}
        </div>
    `;

    messagesDiv.appendChild(messageItem);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}


function base64ToBlob(base64, type) {
    const binary = atob(base64);
    const array = Uint8Array.from(binary, char => char.charCodeAt(0));
    return new Blob([array], { type });
}


        function handleSessionExpiry() {
            console.log('Redirecting to login page');
            alert('Session expired. Please log in again.');
            localStorage.removeItem('token');
            window.location.href = 'login.php';
        }



        // Function to load messages of a conversation
        function loadMessages(conversationId) {
            return new Promise((resolve, reject) => {
                fetch(`http://localhost:3000/messages?conversationId=${conversationId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                })
                .then(response => response.json())
                .then(messages => {
                    const messagesDiv = document.getElementById('messages');
                    messagesDiv.innerHTML = ''; // Curățăm mesajele existente
                    
                    messages.forEach(message => {
                        displayMessage(message); // Afișăm fiecare mesaj existent
                    });

                    console.log("Messages loaded:", messages); // Adaugă logul pentru debug

                    // Returnăm ID-ul ultimului mesaj
                    if (messages.length > 0) {
                        console.log("Last message ID:", messages[messages.length - 1].id);
                        resolve(messages[messages.length - 1].id);
                    } else {
                        console.log("No messages found.");
                        resolve(null);
                    }

                })
                .catch(error => {
                    console.error('Failed to load messages:', error);
                    reject(error);
                });
            });
        }



        async function sendMessage() {
            const userId = localStorage.getItem('userId');
            const messageInput = document.getElementById('messageInput');
            const messageContent = messageInput.value.trim();

            if (!messageContent && selectedFiles.length === 0) {
                alert('Please write a message or attach a file.');
                return;
            }

            const filePayloads = [];

            // Convertim fișierele în base64
            for (let file of selectedFiles) {
                const base64 = await fileToBase64(file);
                filePayloads.push({
                    name: file.name,
                    type: file.type,
                    base64
                });
            }

            const wsPayload = {
                type: 'message',
                content: messageContent || null,
                conversationId: currentConversationId,
                senderId: userId,
                username: localStorage.getItem('username'),
                files: filePayloads
            };

            // Trimitem mesajul prin WebSocket
            if (socket && socket.readyState === WebSocket.OPEN) {
                console.log('Sending WebSocket message:', wsPayload);
                socket.send(JSON.stringify(wsPayload));

                // Curățăm câmpurile
                messageInput.value = '';
                selectedFiles = [];
                updateFilePreview();
            } else {
                console.warn('WebSocket is not connected. Falling back to REST API.');
                
                fetch(`${BASE_URL}/api/send_message.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(wsPayload),
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
                    messageInput.value = '';
                    selectedFiles = [];
                    updateFilePreview();
                    loadMessages(data.conversationId); // Doar fallback
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    alert('Failed to send message.');
                });
            }
        }


        function fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result.split(',')[1]);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        let selectedFiles = [];

        document.getElementById('fileInput').addEventListener('change', event => {
            const newFiles = Array.from(event.target.files);

            // Adăugăm doar fișiere care nu sunt deja în listă (după nume + dimensiune)
            newFiles.forEach(file => {
                if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                }
            });

            // Resetăm inputul pentru a permite reselectarea aceluiași fișier
            event.target.value = '';

            updateFilePreview();
        });

        function updateFilePreview() {
            const previewContainer = document.getElementById('filePreview');
            previewContainer.innerHTML = ''; // Golește preview-ul anterior

            selectedFiles.forEach((file, index) => {
                const fileElement = document.createElement('div');
                fileElement.className = 'file-preview';
                fileElement.innerHTML = `
                    <span>${file.name}</span>
                    <button onclick="removeFile(${index})" style="margin-left: 10px;">✖</button>
                `;
                previewContainer.appendChild(fileElement);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1); // Eliminăm fișierul
            updateFilePreview();
        }



        // Functia pentru trimiterea notificărilor
        function sendNotification(messageId) {
            const participants = getParticipantsForMessage(messageId); // Funcție care obține participanții
            participants.forEach(participant => {
                // Trimite notificarea pentru fiecare participant
                fetch(`${BASE_URL}/api/send_notification.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        userId: participant.userId,
                        type: 'message',
                        referenceId: messageId, // Folosim acum ID-ul mesajului
                    }),
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(err => {
                            console.error('Error response text:', err);
                            throw new Error(`Notification error: ${response.status}`);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error sending notification:', error);
                });
            });
        }


        let offset = 0; // Offset pentru paginare
        const limit = 20; // Număr de conversații per cerere
        let loading = false; // Indicator pentru a preveni cererile multiple
        let allLoaded = false; // Indicator dacă toate conversațiile au fost încărcate

        // Funcție pentru a încărca conversațiile recente
        function loadRecentConversations(offset = 0, limit = 20) {
            fetch(`${BASE_URL}/api/recent_conversations.php?offset=${offset}&limit=${limit}`, {
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
<<<<<<< Updated upstream
            try {
                const response = await fetch(`${BASE_URL}/api/notifications.php`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
=======
    try {
        const response = await fetch(`${BASE_URL}/api/notifications.php`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch notifications');
        }

        const { messages, invitations } = await response.json();
        const notificationList = document.getElementById('notificationList');
        notificationList.innerHTML = '';

        // Afișare notificări mesaje
        if (messages.length > 0) {
            messages.forEach(notification => {
                const item = document.createElement('div');
                item.style.padding = '10px';
                item.style.borderBottom = '1px solid #ddd';

                let notificationContent = `<b style="font-size: 18px">${notification.conversationName}</b><br>`;
                notification.unreadMessages.forEach(msg => {
                    notificationContent += `<b>${msg.username}:</b> ${msg.content} <br>`;
>>>>>>> Stashed changes
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch notifications');
                }

                const notifications = await response.json();
                const notificationList = document.getElementById('notificationList');
                notificationList.innerHTML = '';

                // Filtrăm notificările pentru a include doar cele cu mesaje necitite
                const filteredNotifications = notifications.filter(notification => 
                    notification.unreadMessages && notification.unreadMessages.length > 0
                );

                if (filteredNotifications.length === 0) {
                    notificationList.innerHTML = '<p align="center">No new notifications</p>';
                    document.getElementById('notificationButton').classList.remove('has-notifications');
                } else {
                    filteredNotifications.forEach(notification => {
                        const item = document.createElement('div');
                        item.style.padding = '10px';
                        item.style.borderBottom = '1px solid #ddd';

                        let notificationContent = `<b>Conversation ${notification.conversationName}:</b><br>`;
                        notification.unreadMessages.forEach(msg => {
                            notificationContent += `<b>${msg.username}:</b> ${msg.content} <br>`;
                        });

                        item.innerHTML = notificationContent;

                        // Adaugă un eveniment de click pentru a deschide conversația
                        item.style.cursor = 'pointer';
                        item.onclick = () => {
                            openConversation(notification.conversationId);
                            notificationDropdown.classList.add('hidden');
                        };

                        notificationList.appendChild(item);
                    });

                    document.getElementById('notificationButton').classList.add('has-notifications');
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }

<<<<<<< Updated upstream
=======
        // Afișare notificări invitații
        if (invitations.length > 0) {
            invitations.forEach(invitation => {
                const item = document.createElement('div');
                item.style.padding = '10px';
                item.style.borderBottom = '1px solid #ddd';

                item.innerHTML = `
                    <b>Group Invitation:</b><br>
                    <b>Group:</b> ${invitation.groupName}<br>
                    <b>From:</b> ${invitation.senderName}<br>
                    <button style="background-color: green; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;" onclick="handleInvitation(${invitation.groupId}, 'accept')">Accept</button>
                    <button style="background-color: red; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;" onclick="handleInvitation(${invitation.groupId}, 'decline')">Decline</button>
                `;

                notificationList.appendChild(item);
            });
        }

        if (messages.length === 0 && invitations.length === 0) {
            notificationList.innerHTML = '<p align="center">No new notifications</p>';
            document.getElementById('notificationButton').classList.remove('has-notifications');
        } else {
            document.getElementById('notificationButton').classList.add('has-notifications');
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

async function handleInvitation(groupId, action) {
    const endpoint = action === 'accept' ? 'accept_invitation.php' : 'decline_invitation.php';
    try {
        const response = await fetch(`${BASE_URL}/api/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({ groupId })
        });

        if (response.ok) {
            alert(`Invitation ${action}ed successfully!`);
            loadNotifications();  // Refresh notifications after action
            location.reload();
        } else {
            const errorData = await response.json();
            alert(`Error: ${errorData.error}`);
        }
    } catch (error) {
        console.error('Error handling invitation:', error);
    }
}



>>>>>>> Stashed changes

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


        document.addEventListener('DOMContentLoaded', () => {
    const createGroupButton = document.getElementById('createGroupButton');
    const groupForm = document.getElementById('groupForm');
    const submitGroupButton = document.getElementById('submitGroupButton');

    // Afișează formularul de creare grup
    createGroupButton.addEventListener('click', () => {
        console.log("Butonul a fost apăsat");
        groupForm.classList.toggle('hidden');
        loadUserList(''); // Încărcăm utilizatorii, inițial fără niciun filtru
    });

    // Trimite datele pentru crearea grupului
    submitGroupButton.addEventListener('click', () => {
        const groupName = document.getElementById('groupName').value.trim();
        const selectedUsers = Array.from(document.querySelectorAll('#selectedUserList li')).map(item => item.getAttribute('data-user-id'));

<<<<<<< Updated upstream
        if (!groupName || selectedUsers.length === 0) {
            alert('Please enter a group name and select at least one user.');
            return;
=======
                if (!groupName || selectedUsers.length === 0) {
                    alert('Please enter a group name and select at least one user.');
                    return;
                }

                // Trimitem datele pentru crearea grupului
                fetch(`${BASE_URL}/api/create_group.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ groupName, selectedUsers })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Group created successfully.');
                            // Trimite invitațiile către toți utilizatorii selectați
                            // sendInvitations(data.groupId, selectedUsers);
                            groupForm.classList.add('hidden');
                            // location.reload();
                        } else {
                            alert('Error creating group: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error creating group:', error);
                    });
            });
        });

        // Funcție pentru căutarea utilizatorilor
        function searchInvitationUsers() {
            const searchQuery = document.getElementById('groupSearchInput').value.trim();
            loadUserList(searchQuery);
>>>>>>> Stashed changes
        }

        // Trimitem datele pentru crearea grupului
        fetch(`${BASE_URL}/api/create_group.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ groupName, selectedUsers })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Group created successfully.');
                    // Trimite invitațiile către toți utilizatorii selectați
                    sendInvitations(data.groupId, selectedUsers);
                    groupForm.classList.add('hidden');
                } else {
                    alert('Error creating group: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error creating group:', error);
            });
    });
});

// Funcție pentru căutarea utilizatorilor
function searchUsers() {
    const searchQuery = document.getElementById('groupSearchInput').value.trim();
    loadUserList(searchQuery);
}

// Încărcăm utilizatorii din baza de date
function loadUserList(query) {
    fetch(`${BASE_URL}/api/users.php?search=${query}`)
        .then(response => response.json())
        .then(users => {
            const inviteUserList = document.getElementById('inviteUserList');
            inviteUserList.innerHTML = ''; // Curățăm lista anterioară
            users.forEach(user => {
                const userDiv = document.createElement('div');
                userDiv.innerHTML = `
                    <label>
                        <input type="checkbox" value="${user.id}" onclick="toggleUserSelection(this)"> ${user.username}
                    </label>
                `;
                inviteUserList.appendChild(userDiv);
            });
        })
        .catch(error => {
            console.error('Error loading user list:', error);
        });
}

// Funcție pentru a adăuga sau elimina utilizatori din lista selectată
function toggleUserSelection(checkbox) {
    const userId = checkbox.value;
    const username = checkbox.parentElement.textContent.trim();

    const selectedUserList = document.getElementById('selectedUserList');

    if (checkbox.checked) {
        // Adăugăm utilizatorul în lista selectată
        const li = document.createElement('li');
        li.setAttribute('data-user-id', userId);
        li.textContent = username;
        const removeButton = document.createElement('button');
        removeButton.textContent = 'Remove';
        removeButton.onclick = () => removeUserFromList(li, userId);
        li.appendChild(removeButton);
        selectedUserList.appendChild(li);
    } else {
        // Eliminăm utilizatorul din lista selectată
        const userItem = selectedUserList.querySelector(`[data-user-id="${userId}"]`);
        if (userItem) {
            selectedUserList.removeChild(userItem);
        }
    }
}

// Elimină un utilizator din lista selectată
function removeUserFromList(userItem, userId) {
    userItem.remove();
    // De-selectează checkbox-ul corespunzător
    const checkbox = document.querySelector(`input[value="${userId}"]`);
    if (checkbox) checkbox.checked = false;
}

// Trimite invitațiile pentru utilizatorii selectați
function sendInvitations(groupId, selectedUsers) {
    fetch(`${BASE_URL}/api/send_invitations.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ groupId, selectedUsers })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Invitations sent successfully!');
        } else {
            alert('Error sending invitations: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error sending invitations:', error);
    });
}




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
        setInterval(loadNotifications, 1000);
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
    </script>
</body>
</html>