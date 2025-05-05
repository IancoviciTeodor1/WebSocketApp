function connectWebSocket(conversationId) {
    if (socket === null || socket.readyState === WebSocket.CLOSED) {
        socket = new WebSocket('ws://localhost:8081');

        socket.onopen = () => {
            console.log('Connected to WebSocket server');
            joinConversation(conversationId);
        };

        socket.onmessage = event => {
            const msg = JSON.parse(event.data);

            if (msg.type === 'message' && msg.conversationId === currentConversationId) {
                // Refacem fetch-ul complet, dar afișăm doar ultimul mesaj
                fetch(`http://localhost:3000/messages?conversationId=${msg.conversationId}`, {
                        headers: {
                            'Authorization': `Bearer ${token}`
                        }
                    })
                    .then(response => response.json())
                    .then(messages => {
                        const lastMessage = messages[messages.length - 1];
                        if (lastMessage) {
                            displayMessage(lastMessage);
                        }
                    });
                loadRecentConversations();
            } else if (msg.type === 'delete-message') {
                removeMessage(msg.messageId);
                loadRecentConversations();
            } else if (msg.type === 'delete-file') {
                const deletedFileElement = document.getElementById('file-' + msg.fileId);
                if (deletedFileElement) {
                    deletedFileElement.remove();
                }
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
        socket.send(JSON.stringify({
            type: 'join',
            conversationId
        }));
        activeConversations.add(conversationId);
        currentConversationId = conversationId;
        console.log(`User joined conversation ${conversationId}`);
    } else {
        console.log(`Already joined conversation ${conversationId}`);
    }
}


function leaveConversation(conversationId) {
    if (activeConversations.has(conversationId)) {
        socket.send(JSON.stringify({
            type: 'leave',
            conversationId
        }));
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
            body: JSON.stringify({
                token
            }), // Trimite token-ul din localStorage
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