CREATE DATABASE chat_app;

USE chat_app;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    profile_picture VARCHAR(255) DEFAULT NULL
);

CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT,
    contactUserId INT,
    FOREIGN KEY (userId) REFERENCES users(id),
    FOREIGN KEY (contactUserId) REFERENCES users(id)
);

CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    type ENUM('one-on-one', 'group', 'self') NOT NULL
);

CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversationId INT,
    userId INT,
    FOREIGN KEY (conversationId) REFERENCES conversations(id),
    FOREIGN KEY (userId) REFERENCES users(id)
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    senderId INT,
    conversationId INT,
    content TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversationId) REFERENCES conversations(id),
    FOREIGN KEY (senderId) REFERENCES users(id)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,                  -- ID-ul utilizatorului care primește notificarea
    type ENUM('message', 'invitation') NOT NULL, -- Tipul notificării: mesaj sau invitație
    referenceId INT NOT NULL,             -- ID-ul referinței (ID-ul mesajului sau invitației)
    isRead BOOLEAN DEFAULT FALSE,         -- Dacă notificarea a fost citită
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id)
);

CREATE TABLE group_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groupId INT NOT NULL,                 -- ID-ul grupului pentru care este invitația
    senderId INT NOT NULL,                -- ID-ul utilizatorului care trimite invitația
    receiverId INT NOT NULL,              -- ID-ul utilizatorului care primește invitația
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groupId) REFERENCES conversations(id),
    FOREIGN KEY (senderId) REFERENCES users(id),
    FOREIGN KEY (receiverId) REFERENCES users(id)
);



In caz ca ati initializat deja baza de date, actualizati-o:
ALTER TABLE users 
ADD COLUMN email VARCHAR(255) NOT NULL UNIQUE;

ALTER TABLE conversations 
CHANGE COLUMN subject name VARCHAR(255);

ALTER TABLE conversations
ADD COLUMN type ENUM('one-on-one', 'group') NOT NULL;


ALTER TABLE messages
DROP COLUMN receiverId;

ALTER TABLE messages
ADD COLUMN conversationId INT,
ADD FOREIGN KEY (conversationId) REFERENCES conversations(id);

ALTER TABLE conversations 
MODIFY COLUMN type ENUM('one-on-one', 'group', 'self') NOT NULL;

ALTER TABLE users
ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL;


CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,                  -- ID-ul utilizatorului care primește notificarea
    type ENUM('message', 'invitation') NOT NULL, -- Tipul notificării: mesaj sau invitație
    referenceId INT NOT NULL,             -- ID-ul referinței (ID-ul mesajului sau invitației)
    isRead BOOLEAN DEFAULT FALSE,         -- Dacă notificarea a fost citită
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id)
);

CREATE TABLE group_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groupId INT NOT NULL,                 -- ID-ul grupului pentru care este invitația
    senderId INT NOT NULL,                -- ID-ul utilizatorului care trimite invitația
    receiverId INT NOT NULL,              -- ID-ul utilizatorului care primește invitația
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groupId) REFERENCES conversations(id),
    FOREIGN KEY (senderId) REFERENCES users(id),
    FOREIGN KEY (receiverId) REFERENCES users(id)
);