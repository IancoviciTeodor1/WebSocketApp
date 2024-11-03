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
    let socket;
    let token = localStorage.getItem('token');
    let username = '<?php echo $_SESSION['username']; ?>';
    let userId = <?php echo $_SESSION['user_id']; ?>;
    let currentConversationId;
    let currentReceiverId;

    // Function to search users
    function searchUsers() {
    const query = document.getElementById('searchInput').value;
    fetch(`http://localhost:3000/search?q=${query}`, {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(response => {
        console.log('Raw response:', response);
        return response.json();
    })
    .then(users => {
        const userList = document.getElementById('userList');
        userList.innerHTML = '';
        users.forEach(user => {
            const userItem = document.createElement('div');
            userItem.textContent = user.username;
            userItem.onclick = () => openConversation(user.id); 
            userList.appendChild(userItem);
        });
    })
    .catch(error => {
        console.error('Search failed:', error);
        alert('Search failed: ' + error.message);
    });
}

    // Function to open or create a conversation
    function openConversation(receiverId) {
        currentReceiverId = receiverId;
        document.getElementById('conversation').style.display = 'block';

        fetch(`http://localhost:3000/conversations?userId1=${userId}&userId2=${receiverId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(response => response.json())
        .then(data => {
            if (data.conversationId) {
                // Conversation exists
                currentConversationId = data.conversationId;
                loadMessages(currentConversationId);
            } else {
                // Conversation does not exist, create it
                createConversation(receiverId);
            }
        })
        .catch(error => {
            console.error('Failed to load conversation:', error);
            alert('Failed to load conversation: ' + error.message);
        });
    }

    // Function to create a new conversation
    function createConversation(receiverId) {
        fetch('http://localhost:3000/conversations', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ participants: [userId, receiverId], type: 'one-on-one' })
        })
        .then(response => response.json())
        .then(data => {
            currentConversationId = data.conversationId;
            loadMessages(currentConversationId);
        })
        .catch(error => {
            console.error('Failed to create conversation:', error);
            alert('Failed to create conversation: ' + error.message);
        });
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

    // Function to display a message
    function displayMessage(message) {
        const messagesDiv = document.getElementById('messages');
        const messageItem = document.createElement('p');
        messageItem.textContent = `${message.username || 'Unknown'}: ${message.content || ''}`;
        messagesDiv.appendChild(messageItem);
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
        if (message && currentReceiverId) {
            const messageData = {
                username: username,
                content: message,
                senderId: userId,
                conversationId: currentConversationId
            };
            socket.send(JSON.stringify(messageData));
            document.getElementById('messageInput').value = '';
        } else {
            alert('Please select a user to send a message to.');
        }
    }

    // Connect WebSocket on page load
    connectWebSocket(username);
</script>

</body>
</html>
