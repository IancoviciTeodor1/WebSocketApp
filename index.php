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
            width: 10%;
        }
        #userList div:hover {
            background-color: #00f;
            color: #fff;
            cursor: pointer;
        }
        body {
            font-family: Arial, sans-serif;
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
        #chat {
            padding: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>WebSocket Chat App</h1>
        <form class="logout-form" method="POST" action="">
            <button type="submit" name="logout">Logout</button>
        </form>
    </header>
    <div id="chat">
        <input type="text" id="searchInput" placeholder="Search users">
        <button onclick="searchUsers()">Search</button>
        <div id="userList"></div>
        <div id="conversation" style="display:none;">
            <input type="text" id="messageInput" placeholder="Type a message">
            <button onclick="sendMessage()">Send</button>
            <div id="messages"></div>
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
                    userItem.onclick = () => openConversation(user.id);
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
    function openConversation(receiverId) {
        currentReceiverId = receiverId;
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
    const messageItem = document.createElement('p');
    messageItem.textContent = `${message.username || 'Unknown'}: ${message.content || ''}`;
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
    function sendMessage() {
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
}

    // Connect WebSocket on page load
    connectWebSocket(username);
</script>

</body>
</html>
