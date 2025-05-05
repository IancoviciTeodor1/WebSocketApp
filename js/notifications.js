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


let notificationsVisible = false;

// Funcția pentru a comuta vizibilitatea listei de notificări
async function loadNotifications() {
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

        const {
            messages,
            invitations
        } = await response.json();
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
                });

                item.innerHTML = notificationContent;
                item.style.cursor = 'pointer';
                item.onclick = () => {
                    openConversation(notification.conversationId, notification.conversationType);
                    notificationDropdown.classList.add('hidden');
                };

                notificationList.appendChild(item);
            });
        }

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
            body: JSON.stringify({
                groupId
            })
        });

        if (response.ok) {
            alert(`Invitation ${action}ed successfully!`);
            loadNotifications(); // Refresh notifications after action
            location.reload();
        } else {
            const errorData = await response.json();
            alert(`Error: ${errorData.error}`);
        }
    } catch (error) {
        console.error('Error handling invitation:', error);
    }
}


document.addEventListener('DOMContentLoaded', () => {
    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');

    // Eveniment pentru afișarea/ascunderea notificărilor
    notificationButton.addEventListener('click', () => {
        notificationDropdown.classList.toggle('hidden');
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

// Încarcă notificările periodic
setInterval(loadNotifications, 1000);
loadNotifications(); // Încarcă notificările imediat ce se încarcă pagina