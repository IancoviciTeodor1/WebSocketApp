/*
document.addEventListener('DOMContentLoaded', () => {
    const createGroupButton = document.getElementById('createGroupButton');
    const groupForm = document.getElementById('groupForm');
    const submitGroupButton = document.getElementById('submitGroupButton');

    // Afișează formularul de creare grup
    createGroupButton.addEventListener('click', () => {
        groupForm.classList.toggle('hidden');
        loadUserList(''); // Încărcăm utilizatorii, inițial fără niciun filtru
    });

    // Trimite datele pentru crearea grupului
    submitGroupButton.addEventListener('click', () => {
        const groupName = document.getElementById('groupName').value.trim();
        const selectedUsers = Array.from(document.querySelectorAll('#selectedUserList li')).map(item => item.getAttribute('data-user-id'));

        if (!groupName || selectedUsers.length === 0) {
            alert('Please enter a group name and select at least one user.');
            return;
        }

        // Trimitem datele pentru crearea grupului
        fetch(`${BASE_URL}/api/create_group.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    groupName,
                    selectedUsers
                })
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
*/

// Funcție pentru căutarea utilizatorilor
function searchInvitationUsers() {
    const searchQuery = document.getElementById('groupSearchInput').value.trim();
    loadUserList(searchQuery);
}

// Încărcăm utilizatorii din baza de date, excluzând utilizatorul care creează grupul
function loadUserList(query) {
    fetch(`${BASE_URL}/api/users.php?search=${query}&excludeUserId=${userId}`)
        .then(response => response.json())
        .then(users => {
            const inviteUserList = document.getElementById('inviteUserList');
            inviteUserList.innerHTML = ''; // Curăță lista anterioară

            // Se obtine lista ID-urilor deja selectate
            const selectedUserIds = Array.from(document.querySelectorAll('#selectedUserList li'))
                .map(li => li.getAttribute('data-user-id'));

            users.forEach(user => {
                const isChecked = selectedUserIds.includes(user.id.toString());
                const userDiv = document.createElement('div');
                userDiv.innerHTML = `
                    <label>
                        <input type="checkbox" value="${user.id}" onclick="toggleUserSelection(this)" ${isChecked ? 'checked' : ''}> ${user.username}
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
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            groupId,
            selectedUsers
        })
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


// Funcție pentru a arăta butonul de setări pentru grup
function showGroupSettingsButton(conversationId) {
    // Verificăm dacă butonul există deja
    const existingButton = document.getElementById('groupSettingsButton');
    if (existingButton) {
        return; // Dacă butonul există deja, nu îl mai adăugăm
    }

    const settingsButton = document.createElement('button');
    settingsButton.id = 'groupSettingsButton'; // Atribuim un ID pentru a-l identifica ușor
    settingsButton.textContent = 'Group Settings';
    settingsButton.onclick = function() {
        openGroupSettingsPopup(conversationId);
    };

    // Plasează butonul lângă cel de creare a grupului
    document.getElementById('sidebar').appendChild(settingsButton);
}

function hideGroupSettingsButton() {
    const settingsButton = document.getElementById('groupSettingsButton');
    if (settingsButton) {
        settingsButton.remove(); // Elimină butonul din DOM
    }
}


// Funcție pentru a deschide fereastra pop-up de setări ale grupului
function openGroupSettingsPopup(conversationId) {
    const existingPopup = document.querySelector('.popup');
    if (existingPopup) {
        existingPopup.remove();
    }

    // Deschide fereastra pop-up
    const popup = document.createElement('div');
    popup.classList.add('popup');
    popup.innerHTML = `
    <div id="groupSettingsContainer">
        <h3>Group Settings</h3>
        <label for="newGroupName">Group Name:</label>
        <input type="text" id="newGroupName" placeholder="New group name">
        <button id="updateGroupNameButton" onclick="updateGroupName(${conversationId})">Update Group Name</button>

        <h4>Members:</h4>
        <ul id="groupMembersList" onclick="handleMemberClick(event)"></ul>

        <div id="inviteUserSearch">
            <h4>Invite Users:</h4>
            <input type="text" id="inviteUserSearchInput" placeholder="Search users">
            <div id="inviteUserList2"></div>
        </div>

        <button onclick="inviteUsersToGroup(${conversationId})">Invite Users</button>
        <button onclick="leaveGroup(${conversationId})" style="background-color: red; color: white; margin-top: 20px;">Leave Group</button>
        <button onclick="closePopup()">Close</button>
    </div>
    `;

    document.body.appendChild(popup);

    if (currentUserRole === 'member') {
        document.getElementById('newGroupName').setAttribute('readonly', 'readonly');
        document.getElementById('updateGroupNameButton').style.display = 'none';
    }

    // Eveniment pentru căutarea utilizatorilor
    document.getElementById('inviteUserSearchInput').addEventListener('input', searchUsersForInvite);

    // Încarcă detaliile grupului și utilizatorii de invitat
    loadGroupDetails(currentConversationId);
    loadUserListForInvite(currentConversationId);
}

function handleMemberClick(event) {
    const clickedItem = event.target.closest('li');
    if (!clickedItem) return;

    const userId = clickedItem.getAttribute('data-user-id');
    const userRole = clickedItem.getAttribute('data-user-role');
    console.log(userRole);

    // Evită auto-promovarea sau promovarea creatorului
    if (userRole === 'creator' || parseInt(userId) === parseInt(localStorage.getItem('userId'))) return;

    // Verifică rolul tău propriu
    const myRole = localStorage.getItem('myRoleInCurrentGroup');
    if (myRole !== 'creator' && myRole !== 'admin') return;

    // Confirmare și cerere către server
    if (confirm('Do you want to promote this member to admin?')) {
        promoteUserToAdmin(userId);
    }
}

function promoteUserToAdmin(userId) {
    fetch(`${BASE_URL}/api/promote_to_admin.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                groupId: currentConversationId,
                userId: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User promoted to admin successfully!');
                loadGroupDetails(currentConversationId); // Reîncarcă membrii
            } else {
                throw new Error(JSON.stringify(data));
            }
        })
        .catch(error => {
            console.error('Failed to promote user:', error);
            alert('Failed to promote user.');
        });
}

function leaveGroup(groupId) {
    if (!confirm('Are you sure you want to leave this group?')) {
        return;
    }

    fetch(`${BASE_URL}/api/leave_group.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                groupId: groupId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('You have left the group.');
                closePopup();
                loadRecentConversations();
                document.getElementById('conversation').style.display = 'none'; // Ascunde conversația
                return;
            } else {
                throw new Error(data.error || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error leaving group:', error);
            alert('Failed to leave group.');
        });
}

function removeMemberFromGroup(userId) {
    if (!confirm('Are you sure you want to remove this member from the group?')) {
        return;
    }

    fetch(`${BASE_URL}/api/remove_member.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                groupId: currentConversationId,
                userId: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Member removed successfully.');
                loadGroupDetails(currentConversationId); // Actualizăm lista de membri
            } else {
                throw new Error(data.error || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Failed to remove member:', error);
            alert('Failed to remove member.');
        });
}

// Funcție pentru a închide pop-up-ul
function closePopup() {
    const popup = document.querySelector('.popup');
    if (popup) {
        popup.remove();
    }
}


function loadGroupDetails(conversationId) {
    fetch(`${BASE_URL}/api/groupDetails.php?groupId=${conversationId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('newGroupName').value = data.groupName;

            // Salvăm membrii pentru comenzi ulterioare
            currentConversationMembers = data.members;

            const groupMembersList = document.getElementById('groupMembersList');
            groupMembersList.innerHTML = '';

            data.members.forEach(member => {
                const memberItem = document.createElement('li');
                memberItem.textContent = `${member.username} (${member.role})`;
                memberItem.style.cursor = 'pointer';
                memberItem.onclick = (e) => {
                    showMemberActionsMenu(e, member);
                };
                groupMembersList.appendChild(memberItem);
            });
        })
        .catch(error => {
            console.error('Error loading group details:', error);
        });
}



function showMemberActionsMenu(event, member) {
    // Elimină un meniu vechi dacă există
    const existingMenu = document.getElementById('memberActionsMenu');
    if (existingMenu) {
        existingMenu.remove();
    }

    // Creăm meniul nou
    const menu = document.createElement('div');
    menu.id = 'memberActionsMenu';
    menu.style.position = 'absolute';
    menu.style.background = '#fff';
    menu.style.border = '1px solid #ccc';
    menu.style.padding = '5px';
    menu.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
    menu.style.zIndex = 1000;

    // Poziționează meniul sub elementul click-uit
    const rect = event.target.getBoundingClientRect();
    menu.style.top = `${rect.bottom + window.scrollY}px`;
    menu.style.left = `${rect.left + window.scrollX}px`;

    // Adaugă opțiunile din meniu
    if (member.role === 'member' && ['admin', 'creator'].includes(currentUserRole)) {
        const promoteBtn = document.createElement('button');
        promoteBtn.textContent = 'Promote to Admin';
        promoteBtn.onclick = () => {
            promoteUserToAdmin(member.userId);
            menu.remove();
        };
        menu.appendChild(promoteBtn);
    }

    if (['admin', 'creator'].includes(currentUserRole)) {
        const isSelf = member.userId == userId; // Verifică dacă este utilizatorul curent
        const isProtected = member.role === 'admin' || member.role === 'creator'; // Protejează adminii și creatorul

        if (!isSelf && (!isProtected || currentUserRole === 'creator')) {
            const removeBtn = document.createElement('button');
            removeBtn.textContent = 'Remove from Group';
            removeBtn.style.display = 'block';
            removeBtn.style.marginTop = '5px';
            removeBtn.onclick = () => {
                removeMemberFromGroup(member.userId);
                menu.remove();
            };
            menu.appendChild(removeBtn);
        }
    }

    const openChatBtn = document.createElement('button');
    openChatBtn.textContent = 'Message User';
    openChatBtn.style.display = 'block';
    openChatBtn.style.marginTop = '5px';
    openChatBtn.onclick = () => {
        openUserConversation(member.userId);
        menu.remove();
        closePopup();
    };
    menu.appendChild(openChatBtn);

    document.body.appendChild(menu);

    // Închide meniul dacă utilizatorul face click altundeva
    setTimeout(() => {
        document.addEventListener('click', function handlerOutsideClick(e) {
            if (!menu.contains(e.target)) {
                menu.remove();
                document.removeEventListener('click', handlerOutsideClick);
            }
        });
    }, 0);
}


// Funcție pentru a actualiza numele grupului
function updateGroupName(conversationId) {
    if (!['creator', 'admin'].includes(currentUserRole)) {
        alert('You do not have permission to change the group name.');
        return;
    }

    const newGroupName = document.getElementById('newGroupName').value;
    if (newGroupName) {
        fetch(`${BASE_URL}/api/updateGroupName.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    groupId: conversationId,
                    newGroupName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Group name updated successfully');
                    loadGroupDetails(conversationId);
                } else {
                    // Dacă serverul răspunde dar există eroare
                    alert('Failed to update group name: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                // Dacă există o eroare de rețea sau altceva grav
                console.error('Error updating group name:', error);
                alert('An error occurred while updating the group name.');
            });
    } else {
        alert('Please enter a valid group name.');
    }
}



// Funcție pentru a încărca utilizatorii disponibili pentru invitație
function loadUserListForInvite(conversationId) {
    fetch(`${BASE_URL}/api/get_users_for_invite.php?groupId=${conversationId}&excludeUserId=${userId}`)
        .then(response => response.json())
        .then(users => {
            if (users.error) {
                console.log(users.error);
                return;
            }
            displayInviteUserList(users); // Afișează lista de utilizatori
        })
        .catch(error => {
            console.error('Error loading user list for invite:', error);
        });
}


// Funcție pentru a afișa lista de utilizatori în div-ul de invitație
function displayInviteUserList(users) {
    const inviteUserList2 = document.getElementById('inviteUserList2');
    inviteUserList2.innerHTML = ''; // Curăță lista anterioară

    users.forEach(user => {
        const userDiv = document.createElement('div');
        userDiv.classList.add('user-invite-item');
        userDiv.innerHTML = `
            <label style="display: inline-flex; align-items: center; margin: 0;">
                <input type="checkbox" value="${user.id}" onclick="toggleUserSelectionForInvite(this)" style="margin-right: 5px;">${user.username}
            </label>
        `;
        inviteUserList2.appendChild(userDiv);
    });
}

// Funcție pentru a căuta utilizatorii în lista de invitație
function searchUsersForInvite() {
    const searchTerm = document.getElementById('inviteUserSearchInput').value.toLowerCase();
    const userItems = document.querySelectorAll('#inviteUserList2 .user-invite-item');

    userItems.forEach(item => {
        const username = item.textContent.toLowerCase();
        if (username.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Funcție pentru a gestiona selecția utilizatorilor
function toggleUserSelectionForInvite(checkbox) {
    // Puteți salva selecțiile într-o variabilă globală sau le trimiteți direct la invitație
    console.log(`User ${checkbox.value} selected: ${checkbox.checked}`);
}

// Funcție pentru a invita utilizatori selectați în grup
function inviteUsersToGroup(conversationId) {
    const selectedUserIds = Array.from(document.querySelectorAll('#inviteUserList2 input[type="checkbox"]:checked'))
        .map(checkbox => checkbox.value);

    if (selectedUserIds.length > 0) {
        fetch(`${BASE_URL}/api/inviteUsersToGroup.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    groupId: conversationId,
                    userIds: selectedUserIds
                })
            })
            .then(response => response.json())
            .then(data => {
                alert('Users invited successfully');
                loadGroupDetails(conversationId); // Reîncarcă detaliile grupului
            })
            .catch(error => {
                console.error('Error inviting users:', error);
            });
    } else {
        alert('Please select users to invite.');
    }
}


document.addEventListener('DOMContentLoaded', () => {
    const createGroupButton = document.getElementById('createGroupButton');
    const popupOverlay = document.getElementById('popupOverlay');
    const closePopup = document.querySelector('.close-popup');
    const submitGroupButton = document.getElementById('submitGroupButton');

    // Show popup when Create Group button is clicked
    createGroupButton.addEventListener('click', () => {
        popupOverlay.classList.remove('hidden');
        loadUserList(''); // Load users when popup opens
    });

    // Close popup when X is clicked
    closePopup.addEventListener('click', () => {
        popupOverlay.classList.add('hidden');
        // Clear form fields
        document.getElementById('groupName').value = '';
        document.getElementById('groupSearchInput').value = '';
        document.getElementById('inviteUserList').innerHTML = '';
        document.getElementById('selectedUserList').innerHTML = '';
    });

    // Close popup when clicking outside the form
    popupOverlay.addEventListener('click', (e) => {
        if (e.target === popupOverlay) {
            popupOverlay.classList.add('hidden');
        }
    });

    // Submit group form
    submitGroupButton.addEventListener('click', () => {
        const groupName = document.getElementById('groupName').value.trim();
        const selectedUsers = Array.from(document.querySelectorAll('#selectedUserList li'))
            .map(item => item.getAttribute('data-user-id'));

        if (!groupName || selectedUsers.length === 0) {
            alert('Please enter a group name and select at least one user.');
            return;
        }

        // Submit data for group creation
        fetch(`${BASE_URL}/api/create_group.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    groupName,
                    selectedUsers
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Group created successfully.');
                    popupOverlay.classList.add('hidden');
                    loadRecentConversations(); // Refresh conversations list
                } else {
                    alert('Error creating group: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error creating group:', error);
            });
    });
});