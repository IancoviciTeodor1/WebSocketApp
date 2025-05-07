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
        const selectedUsers = Array.from(document.querySelectorAll('#selectedUserList .user-card')) // Select by class
            .map(card => card.getAttribute('data-user-id'));

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
            inviteUserList.innerHTML = ''; // Curățăm lista anterioară

            users.forEach(user => {
                const card = document.createElement('div');
                card.classList.add('invite-user-card');
                card.textContent = user.username;
                card.setAttribute('data-user-id', user.id);
                card.setAttribute('data-username', user.username);
                card.onclick = () => handleInviteCardClick(card);
                inviteUserList.appendChild(card);
            });

            // După popularea listei de invitații, actualizăm starea selectată a cardurilor
            const selectedUserGrid = document.getElementById('selectedUserList');
            const currentlySelectedUserIds = Array.from(selectedUserGrid.querySelectorAll('.user-card'))
                .map(selectedCard => selectedCard.dataset.userId);

            inviteUserList.querySelectorAll('.invite-user-card').forEach(inviteCard => {
                if (currentlySelectedUserIds.includes(inviteCard.dataset.userId)) {
                    inviteCard.classList.add('selected');
                }
            });
        })
        .catch(error => {
            console.error('Error loading user list:', error);
        });
}

// Funcție pentru a gestiona click-ul pe un card de invitație
function handleInviteCardClick(clickedInviteCard) {
    const userId = clickedInviteCard.dataset.userId;
    const username = clickedInviteCard.dataset.username;
    const selectedUserGrid = document.getElementById('selectedUserList');

    clickedInviteCard.classList.toggle('selected');

    if (clickedInviteCard.classList.contains('selected')) {
        // Cardul a fost selectat, adăugăm în grila de jos
        const newSelectedCard = document.createElement('div');
        newSelectedCard.classList.add('user-card'); // Clasa pentru cardurile din grila de jos
        newSelectedCard.setAttribute('data-user-id', userId);
        newSelectedCard.textContent = username;
        // Folosim direct funcția existentă pentru eliminare, adaptată
        newSelectedCard.onclick = () => removeUserFromSelectedList(newSelectedCard, userId);
        selectedUserGrid.appendChild(newSelectedCard);
    } else {
        // Cardul a fost deselectat, eliminăm din grila de jos
        const cardToRemoveFromGrid = selectedUserGrid.querySelector(`.user-card[data-user-id="${userId}"]`);
        if (cardToRemoveFromGrid) {
            cardToRemoveFromGrid.remove();
        }
    }
}

// Elimină un utilizator din lista selectată (grila de jos) și deselectează cardul din lista de invitații (sus)
function removeUserFromSelectedList(clickedSelectedCard, userId) {
    clickedSelectedCard.remove(); // Elimină cardul din grila de jos

    // Deselectează cardul corespunzător în lista de invitații (sus)
    const inviteCardToDeselect = document.querySelector(`#inviteUserList .invite-user-card[data-user-id="${userId}"]`);
    if (inviteCardToDeselect) {
        inviteCardToDeselect.classList.remove('selected');
    }
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
    const settingsbutton = document.getElementById('GroupsettingsButton');
    settingsbutton.style.display = 'block';
    // Store the conversation ID for use in the settings
    settingsbutton.setAttribute('data-conversation-id', conversationId);
    settingsbutton.onclick = () => {
        openGroupSettingsPopup(settingsbutton.getAttribute('data-conversation-id'));
    };
}

function hideGroupSettingsButton() {
    const settingsButton = document.getElementById('GroupsettingsButton');
    settingsButton.style.display = 'none';
}


// Funcție pentru a deschide fereastra pop-up de setări ale grupului
function openGroupSettingsPopup(conversationId) {
    const existingPopup = document.querySelector('#groupSettingsPopup');
    if (existingPopup) {
        existingPopup.remove();
    }

    // Create the popup structure with unique IDs for settings popup
    const popupOverlay = document.createElement('div');
    popupOverlay.id = 'groupSettingsPopup';
    popupOverlay.classList.add('popup-overlay');

    popupOverlay.innerHTML = `
        <div class="popup-container">
            <div class="popup-header">
                <h3>Group Settings</h3>
                <span class="close-popup material-icons">close</span>
            </div>
            <div class="popup-content">
                <div class="form-group">
                    <label for="newGroupName">Group Name:</label>
                    <input type="text" id="newGroupName" placeholder="New group name">
                    <button id="updateGroupNameButton" onclick="updateGroupName(${conversationId})">Update Group Name</button>
                </div>

                <div class="members-section">
                    <h4>Members:</h4>
                    <ul id="groupMembersList" class="members-list"></ul>
                </div>

                <div class="invite-section">
                    <h4>Invite Users:</h4>
                    <div class="search-container">
                        <input type="text" id="inviteUserSearchInputSettings" placeholder="Search users" oninput="searchUsersForInviteSettings()">
                    </div>
                    <div id="inviteUserListSettings" class="search-results"></div>
                    <div id="selectedUserListSettings" class="selected-users-grid"></div>
                    <button class="invite-button" onclick="inviteUsersToGroupSettings(${conversationId})">Invite Users</button>
                </div>
            </div>
            <div class="popup-footer">
                <button class="leave-button" onclick="leaveGroup(${conversationId})">Leave Group</button>
                <button class="close-button" onclick="closePopup()">Close</button>
            </div>
        </div>
    `;

    document.body.appendChild(popupOverlay);

    // Add event listeners for closing the popup
    popupOverlay.querySelector('.close-popup').addEventListener('click', closePopup);
    popupOverlay.addEventListener('click', (e) => {
        if (e.target === popupOverlay) {
            closePopup();
        }
    });

    // Load group details and users for invite (settings version)
    loadGroupDetails(conversationId);
    loadUserListForInviteSettings(conversationId);
}

// --- Settings popup invite logic with unique IDs ---

function loadUserListForInviteSettings(conversationId) {
    fetch(`${BASE_URL}/api/get_users_for_invite.php?groupId=${conversationId}&excludeUserId=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to fetch users for invite: ${response.status}`);
            }
            return response.json();
        })
        .then(users => {
            if (users.error) {
                console.error('Error loading users for invite:', users.error);
                alert('Failed to load users for invite.');
                return;
            }

            // Populate invite user list for settings popup
            const inviteUserList = document.getElementById('inviteUserListSettings');
            if (inviteUserList) {
                inviteUserList.innerHTML = '';
                users.forEach(user => {
                    const userCard = document.createElement('div');
                    userCard.classList.add('invite-user-card');
                    userCard.textContent = user.username;
                    userCard.setAttribute('data-user-id', user.id);
                    userCard.onclick = () => handleInviteCardClickSettings(userCard);
                    inviteUserList.appendChild(userCard);
                });
            }
        })
        .catch(error => {
            console.error('Error loading users for invite:', error);
            alert('An error occurred while loading users for invite.');
        });
}

function handleInviteCardClickSettings(clickedInviteCard) {
    const userId = clickedInviteCard.dataset.userId;
    const username = clickedInviteCard.textContent;
    const selectedUserGrid = document.getElementById('selectedUserListSettings');

    clickedInviteCard.classList.toggle('selected');

    if (clickedInviteCard.classList.contains('selected')) {
        // Add to selected grid
        const newSelectedCard = document.createElement('div');
        newSelectedCard.classList.add('user-card');
        newSelectedCard.setAttribute('data-user-id', userId);
        newSelectedCard.textContent = username;
        newSelectedCard.onclick = () => removeUserFromSelectedListSettings(newSelectedCard, userId);
        selectedUserGrid.appendChild(newSelectedCard);
    } else {
        // Remove from selected grid
        const cardToRemoveFromGrid = selectedUserGrid.querySelector(`.user-card[data-user-id="${userId}"]`);
        if (cardToRemoveFromGrid) {
            cardToRemoveFromGrid.remove();
        }
    }
}

function removeUserFromSelectedListSettings(clickedSelectedCard, userId) {
    clickedSelectedCard.remove();
    const inviteCardToDeselect = document.querySelector(`#inviteUserListSettings .invite-user-card[data-user-id="${userId}"]`);
    if (inviteCardToDeselect) {
        inviteCardToDeselect.classList.remove('selected');
    }
}

function searchUsersForInviteSettings() {
    const searchTerm = document.getElementById('inviteUserSearchInputSettings').value.toLowerCase();
    const userItems = document.querySelectorAll('#inviteUserListSettings .invite-user-card');
    userItems.forEach(item => {
        const username = item.textContent.toLowerCase();
        item.style.display = username.includes(searchTerm) ? 'block' : 'none';
    });
}

function inviteUsersToGroupSettings(conversationId) {
    const selectedUserIds = Array.from(document.querySelectorAll('#selectedUserListSettings .user-card'))
        .map(card => card.getAttribute('data-user-id'));

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
                loadGroupDetails(conversationId); // Reload group details
            })
            .catch(error => {
                console.error('Error inviting users:', error);
            });
    } else {
        alert('Please select users to invite.');
    }
}

function closePopup() {
    const popup = document.getElementById('groupSettingsPopup');
    if (popup) {
        popup.remove();
    }
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
                document.getElementById('GroupsettingsButton').style.display = 'none'; // Ascunde butonul de setări
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



function loadGroupDetails(conversationId) {
    fetch(`${BASE_URL}/api/groupDetails.php?groupId=${conversationId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to fetch group details: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('Error loading group details:', data.error);
                alert('Failed to load group details.');
                return;
            }

            // Populate group name
            const groupNameInput = document.getElementById('newGroupName');
            if (groupNameInput) {
                groupNameInput.value = data.groupName || '';
            }

            // Populate members list
            const groupMembersList = document.getElementById('groupMembersList');
            if (groupMembersList) {
                groupMembersList.innerHTML = '';
                data.members.forEach(member => {
                    const memberItem = document.createElement('li');
                    memberItem.textContent = `${member.username} (${member.role})`;
                    memberItem.setAttribute('data-user-id', member.userId);
                    memberItem.setAttribute('data-user-role', member.role);
                    memberItem.style.cursor = 'pointer';
                    memberItem.onclick = (e) => showMemberActionsMenu(e, member);
                    groupMembersList.appendChild(memberItem);
                });
            }

            // Save members for later use
            currentConversationMembers = data.members;
        })
        .catch(error => {
            console.error('Error loading group details:', error);
            alert('An error occurred while loading group details.');
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
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to fetch users for invite: ${response.status}`);
            }
            return response.json();
        })
        .then(users => {
            if (users.error) {
                console.error('Error loading users for invite:', users.error);
                alert('Failed to load users for invite.');
                return;
            }

            // Populate invite user list
            const inviteUserList = document.getElementById('inviteUserList');
            if (inviteUserList) {
                inviteUserList.innerHTML = '';
                users.forEach(user => {
                    const userCard = document.createElement('div');
                    userCard.classList.add('invite-user-card');
                    userCard.textContent = user.username;
                    userCard.setAttribute('data-user-id', user.id);
                    userCard.onclick = () => handleInviteCardClick(userCard);
                    inviteUserList.appendChild(userCard);
                });
            }
        })
        .catch(error => {
            console.error('Error loading users for invite:', error);
            alert('An error occurred while loading users for invite.');
        });
}


// Funcție pentru a afișa lista de utilizatori în div-ul de invitație
function displayInviteUserList(users) {
    const inviteUserList2 = document.getElementById('selectedUserList');
    inviteUserList2.innerHTML = ''; // Curăță lista anterioară

    users.forEach(user => {
        const userDiv = document.createElement('div');
        userDiv.classList.add('user-invite-item');
        userDiv.innerHTML = `
            <label style="display: inline-flex; align-items: center; margin: 0;">
                <input type="checkbox" value="${user.id}" onclick="toggleUserSelectionForInvite(this)" style="margin-right: 5px;">${user.username}
            </label>
        `;
        selectedUserList.appendChild(userDiv);
    });
}

// Funcție pentru a căuta utilizatorii în lista de invitație
function searchUsersForInvite() {
    const searchTerm = document.getElementById('inviteUserSearchInput').value.toLowerCase();
    const userItems = document.querySelectorAll('#inviteUserList .user-invite-item');

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
    const selectedUserIds = Array.from(document.querySelectorAll('#inviteUserList input[type="checkbox"]:checked'))
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
// document.addEventListener('DOMContentLoaded', () => {
//     const GroupsettingsButton = document.getElementById('GroupsettingsButton');
    
//     GroupsettingsButton.addEventListener('click', () => {

//         console.log('Group settings button clicked');
//     });
// },

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
        document.getElementById('inviteUserList').innerHTML = ''; // Clear invite cards
        document.getElementById('selectedUserList').innerHTML = ''; // Clear selected user cards
    });

    // Close popup when clicking outside the form
    popupOverlay.addEventListener('click', (e) => {
        if (e.target === popupOverlay) {
            popupOverlay.classList.add('hidden');
            // Clear form fields as above
            document.getElementById('groupName').value = '';
            document.getElementById('groupSearchInput').value = '';
            document.getElementById('inviteUserList').innerHTML = ''; // Clear invite cards
            document.getElementById('selectedUserList').innerHTML = ''; // Clear selected user cards
        }
    });

    // Submit group form
    submitGroupButton.addEventListener('click', () => {
        const groupName = document.getElementById('groupName').value.trim();
        const selectedUsers = Array.from(document.querySelectorAll('#selectedUserList .user-card')) // Select by class
            .map(card => card.getAttribute('data-user-id'));

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