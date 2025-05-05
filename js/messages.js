function displayMessage(message) {
    const messagesDiv = document.getElementById('messages');

    const messageItem = document.createElement('div');
    messageItem.classList.add('message-item');
    messageItem.id = 'message-' + message.id;

    // Vom construi toate fișierele într-un container separat
    let fileContent = '';
    if (Array.isArray(message.files) && message.files.length > 0) {
        fileContent += `<div class="file-container" id="files-container-${message.id}">`;

        message.files.forEach(file => {
            if (file.type === 'image') {
                fileContent += `<img id="file-${file.id}" data-file-id="${file.id}" src="uploads/user_files/${file.path}" alt="Attached Image" class="message-file">`;
            } else if (file.type === 'audio') {
                fileContent += `<audio controls id="file-${file.id}" data-file-id="${file.id}">
                                    <source src="uploads/user_files/${file.path}" type="audio/mpeg">
                                </audio>`;
            } else if (file.type === 'video') {
                fileContent += `<video controls id="file-${file.id}" data-file-id="${file.id}">
                                    <source src="uploads/user_files/${file.path}" type="video/mp4">
                                </video>`;
            } else if (file.type === 'document') {
                fileContent += `<div class="file-preview" id="file-${file.id}" data-file-id="${file.id}">
                                    <img src="icons/pdf-icon.png" class="file-icon">
                                    <a href="uploads/user_files/${file.path}" target="_blank">View File</a>
                                </div>`;
            }
        });

        fileContent += `</div>`;
    }

    // Acum putem construi mesajul
    messageItem.innerHTML = `
        <div class="message-header">
            <img src="${message.profile_picture}" alt="Profile Picture" class="profile-pic">
            <span class="username">${message.username || 'Unknown'}</span>
            <div class="timestamp">
                ${new Date(message.timestamp).toLocaleString()}
            </div>
        </div>
        <div class="message-body">
            <div class="message-text">${message.content || ''}</div>
            ${fileContent}
        </div>
    `;

    messagesDiv.appendChild(messageItem);

    // - click dreapta pe mesaj
    const textElement = messageItem.querySelector('.message-text');
    if (textElement && parseInt(message.senderId) === parseInt(userId)) {
        textElement.addEventListener('contextmenu', (e) => {
            showMessageActionsMenu(e, 'message', message.id);
        });
    }

    // - click dreapta pe fisiere
    const fileElements = messageItem.querySelectorAll('[data-file-id]');
    fileElements.forEach(fileEl => {
        const fileId = fileEl.getAttribute('data-file-id');
        fileEl.addEventListener('contextmenu', (e) => {
            showMessageActionsMenu(e, 'file', fileId);
        });
    });

    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}


function removeMessage(messageId) {
    // Ștergerea mesajului
    const messageElement = document.getElementById('message-' + messageId);
    if (messageElement) {
        messageElement.remove();
    }

    // Ștergerea fișierelor atașate
    const fileContainer = document.getElementById('files-container-' + messageId);
    if (fileContainer) {
        fileContainer.remove();
    }
}


function base64ToBlob(base64, type) {
    const binary = atob(base64);
    const array = Uint8Array.from(binary, char => char.charCodeAt(0));
    return new Blob([array], {
        type
    });
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
                headers: {
                    'Authorization': `Bearer ${token}`
                }
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

function showMessageActionsMenu(event, type, id) {
    event.preventDefault();

    // Elimină orice alt meniu deschis
    const existingMenu = document.getElementById('messageActionsMenu');
    if (existingMenu) {
        existingMenu.remove();
    }

    // Creează meniul
    const menu = document.createElement('div');
    menu.id = 'messageActionsMenu';
    menu.classList.add('actions-menu');
    menu.style.position = 'absolute';
    menu.style.background = '#fff';
    menu.style.border = '1px solid #ccc';
    menu.style.padding = '8px';
    menu.style.borderRadius = '8px';
    menu.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
    menu.style.zIndex = 1000;

    // Buton pentru ștergere mesaj
    if (type === 'message') {
        const deleteBtn = document.createElement('button');
        deleteBtn.textContent = 'Delete Message';
        deleteBtn.style.display = 'block';
        deleteBtn.onclick = () => {
            deleteMessage(id);
            menu.remove();
        };
        menu.appendChild(deleteBtn);
    }

    // Buton pentru ștergere fișier
    if (type === 'file') {
        const deleteFileBtn = document.createElement('button');
        deleteFileBtn.textContent = 'Delete File';
        deleteFileBtn.style.display = 'block';
        deleteFileBtn.onclick = () => {
            deleteFile(id);
            menu.remove();
        };
        menu.appendChild(deleteFileBtn);
    }

    // Adaugă meniul în body
    document.body.appendChild(menu);

    // ⚡️ Pozitionăm meniul sub mesajul pe care ai dat click
    const messageElement = event.currentTarget; // Elementul pe care s-a dat click dreapta
    const rect = messageElement.getBoundingClientRect();

    menu.style.top = `${rect.bottom + window.scrollY}px`;
    menu.style.left = `${rect.left + window.scrollX}px`;

    // Închidem meniul când se dă click în altă parte
    document.addEventListener('click', function closeMenu(e) {
        if (!menu.contains(e.target)) {
            menu.remove();
            document.removeEventListener('click', closeMenu);
        }
    });
}


function deleteMessage(messageId) {
    const userId = localStorage.getItem('userId');
    const payload = {
        type: 'delete-message',
        messageId,
        userId,
        conversationId: currentConversationId
    };

    if (socket && socket.readyState === WebSocket.OPEN) {

        // Trimitem payload-ul pentru ștergerea mesajului
        socket.send(JSON.stringify(payload));

        // Ștergem mesajul din DOM imediat ce am trimis cererea
        const deletedMessageElement = document.getElementById('message-' + messageId);
        if (deletedMessageElement) {
            deletedMessageElement.remove();
        }
    } else {
        console.error('WebSocket not connected.');
    }
}



function deleteFile(fileId) {
    const userId = localStorage.getItem('userId');
    const fileEl = document.getElementById(`file-${fileId}`);
    if (!fileEl) return;

    // Găsim containerul de fișiere și mesajul părinte
    const filesContainer = fileEl.closest('.file-container');
    const messageItem = fileEl.closest('.message-item');

    // Ștergem fișierul din DOM
    fileEl.remove();

    // Verificăm dacă mai sunt alte fișiere
    const remainingFiles = filesContainer.querySelectorAll('[data-file-id]');
    const hasFiles = remainingFiles.length > 0;

    // Verificăm dacă mesajul are text
    const messageText = messageItem.querySelector('.message-text');
    const hasText = messageText && messageText.textContent.trim().length > 0;

    const messageId = parseInt(messageItem.id.replace('message-', ''));

    // Dacă nu mai are nici fișiere, nici text — ștergem întreg mesajul
    if (!hasFiles && !hasText) {
        const deletePayload = {
            type: 'delete-message',
            messageId,
            userId,
            conversationId: currentConversationId
        };

        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify(deletePayload));
        } else {
            console.error('WebSocket not connected.');
        }
    } else {
        // Altfel, trimitem doar comanda pentru a șterge fișierul
        const deleteFilePayload = {
            type: 'delete-file',
            fileId,
            userId,
            conversationId: currentConversationId
        };

        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify(deleteFilePayload));
        } else {
            console.error('WebSocket not connected.');
        }
    }
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
        const reader = new FileReader();

        reader.onload = (e) => {
            const previewContent = file.type.startsWith('image/') ?
                `<img src="${e.target.result}" alt="${file.name}" style="max-width: 100px; max-height: 100px; margin-right: 10px;">` :
                `<span>${file.name}</span>`;

            fileElement.innerHTML = `
                ${previewContent}
                <button onclick="removeFile(${index})" style="margin-left: 10px;">✖</button>
            `;
            previewContainer.appendChild(fileElement);
        };

        reader.readAsDataURL(file);
    });
}

function removeFile(index) {
    selectedFiles.splice(index, 1); // Eliminăm fișierul
    updateFilePreview();
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