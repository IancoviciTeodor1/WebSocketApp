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
        let currentReceiverId;

        function register() {
            const usernameInput = document.getElementById('usernameInput').value;
            const passwordInput = document.getElementById('passwordInput').value;

            if (!usernameInput || !passwordInput) {
                alert('Please enter both username and password');
                return;
            }

            fetch('http://localhost:3000/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: usernameInput, password: passwordInput })
            }).then(response => response.text().then(text => {
                if (response.ok) {
                    alert('Registration successful');
                } else {
                    alert('Registration failed: ' + text);
                }
            })).catch(error => {
                alert('Registration failed: ' + error.message);
            });
        }

        function login() {
    const usernameInput = document.getElementById('usernameInput').value;
    const passwordInput = document.getElementById('passwordInput').value;

    if (!usernameInput || !passwordInput) {
        alert('Please enter both username and password');
        return;
    }

    fetch('http://localhost:3000/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: usernameInput, password: passwordInput })
    }).then(response => response.json().then(data => {
        if (data.token) {
            token = data.token;
            username = usernameInput;
            userId = data.id; // Se asigură că 'id' este returnat de server
            console.log('User ID:', userId); // Log pentru verificare
            localStorage.setItem('token', token);
            document.getElementById('auth').style.display = 'none';
            document.getElementById('chat').style.display = 'block';
            connectWebSocket(username);
        } else {
            alert('Login failed');
        }
    })).catch(error => {
        alert('Login failed: ' + error.message);
    });
}

function openConversation(receiverId) {
    currentReceiverId = receiverId; 
    document.getElementById('conversation').style.display = 'block'; 
    fetch(`http://localhost:3000/messages?senderId=${userId}&receiverId=${receiverId}`, { 
        headers: { 'Authorization': `Bearer ${token}` } 
    }).then(response => response.json().then(messages => { 
        const messagesDiv = document.getElementById('messages'); 
        messagesDiv.innerHTML = ''; 
        messages.forEach(message => { 
            displayMessage(message); 
        }); 
    })).catch(error => { 
        alert('Failed to load messages: ' + error.message); 
    });
}

        function searchUsers() {
            const query = document.getElementById('searchInput').value;
            fetch(`http://localhost:3000/search?q=${query}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            }).then(response => response.json().then(users => {
                const userList = document.getElementById('userList');
                userList.innerHTML = '';
                users.forEach(user => {
                    const userItem = document.createElement('div');
                    userItem.textContent = user.username;
                    userItem.onclick = () => openConversation(user.id); // Ensure user.id is passed correctly
                    userList.appendChild(userItem);
                });
            })).catch(error => {
                alert('Search failed: ' + error.message);
            });
        }


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

function displayMessage(message) {
    const messagesDiv = document.getElementById('messages');
    const messageItem = document.createElement('p');
    messageItem.textContent = `${message.username || 'Unknown'}: ${message.content || ''}`;
    messagesDiv.appendChild(messageItem);
}

function sendMessage() {
    const message = document.getElementById('messageInput').value;
    if (message && currentReceiverId) {  // Check if currentReceiverId is set
        const messageData = {
            username: username,
            content: message,
            senderId: userId,
            receiverId: currentReceiverId
        };
        socket.send(JSON.stringify(messageData));
        document.getElementById('messageInput').value = '';
    } else {
        alert('Please select a user to send a message to.');
    }
}

connectWebSocket(username);

    </script>
</body>
</html>
