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

window.addEventListener('DOMContentLoaded', () => {
    document.getElementById('searchInput').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchUsers();
        }
    });
});


function markMessagesAsRead(conversationId, lastReadMessageId) {
    const userId = localStorage.getItem('userId');
    console.log({
        userId,
        conversationId,
        lastReadMessageId
    });


    fetch(`${BASE_URL}/api/mark_messages_as_read.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                userId,
                conversationId,
                lastReadMessageId
            }),
        })
        .then(response => {
            console.log(response); // Log
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
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json(); // Continuă dacă răspunsul este valid
            } else if (response.status === 404) {
                console.log('No conversation found, will create on message send.');
                return {
                    conversationId: null
                }; // Conversația nu există, setăm `conversationId` pe `null`
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
    currentConversationType = type;
    document.getElementById('conversation').style.display = 'block';
    if (type === 'group') {
        // Obține rolul curent al utilizatorului în grup
        fetch(`${BASE_URL}/api/getUserRole.php?conversationId=${conversationId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            })
            .then(response => response.json())
            .then(data => {
                currentUserRole = data.role; // poate fi 'member', 'admin', 'creator'
                console.log('User role in this group:', currentUserRole);

                showGroupSettingsButton(conversationId);
            })
            .catch(error => {
                console.error('Error getting user role:', error);
                currentUserRole = null;
                alert('Could not determine your role in this group.');
            });
    } else {
        hideGroupSettingsButton();
        closePopup();
    }

    // Încarcă mesajele pentru conversația selectată
    if (conversationId) {
        console.log(`Loading conversation ID=${conversationId}`);

        loadMessages(conversationId).then(lastMessageId => {
            // Marchează mesajele ca citite
            if (lastMessageId) {
                markMessagesAsRead(conversationId, lastMessageId);
            } else {
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
                // Adaugă o pictogramă diferită în funcție de tipul conversației
                const icon = document.createElement('span');
                icon.classList.add('material-icons');
                if (conversation.conversationType === 'group') {
                    icon.textContent = 'group';
                } else if (conversation.conversationType === 'one-on-one') {
                    icon.textContent = 'person';
                } else {
                    icon.textContent = 'chat';
                }
                conversationItem.prepend(icon);
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

// Ascultă evenimentul de scroll pentru a încărca mai multe conversații
document.getElementById('recentConversations').addEventListener('scroll', function() {
    const {
        scrollTop,
        scrollHeight,
        clientHeight
    } = this;
    if (scrollTop + clientHeight >= scrollHeight - 10) {
        loadRecentConversations(); // Încarcă mai multe conversații
    }
});