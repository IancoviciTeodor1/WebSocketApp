const express = require('express');
const mysql = require('mysql2/promise');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const WebSocket = require('ws');
const cors = require('cors');

const app = express();
const server = new WebSocket.Server({ port: 8081 });
const messages = [];

app.use(express.json());
app.use(cors());

async function initializeDatabase() {
    return await mysql.createConnection({
        host: 'localhost',
        user: 'chat_app_user',
        password: 'pass',
        database: 'chat_app'
    });
}

let db;
initializeDatabase().then(connection => {
    db = connection;

    app.post('/register', async (req, res) => {
        const { username, password } = req.body;
        try {
            const hashedPassword = await bcrypt.hash(password, 10);
            const [result] = await db.execute('INSERT INTO users (username, password) VALUES (?, ?)', [username, hashedPassword]);
            res.status(201).send('User registered');
        } catch (error) {
            res.status(400).send('Error registering user: ' + error.message);
        }
    });

    app.post('/login', async (req, res) => {
        const { username, password } = req.body;
        const [rows] = await db.execute('SELECT * FROM users WHERE username = ?', [username]);
        const user = rows[0];
        if (user && await bcrypt.compare(password, user.password)) {
            const token = jwt.sign({ username: user.username, id: user.id }, 'secretkey');
            res.json({ token, id: user.id });
        } else {
            res.status(400).send('Invalid credentials');
        }
    });

    app.get('/search', async (req, res) => {
        const query = req.query.q;
        const [rows] = await db.execute('SELECT id, username FROM users WHERE username LIKE ?', [`%${query}%`]);
        res.json(rows);
    });

    app.get('/messages', async (req, res) => {
    const { senderId, receiverId } = req.query;
    const [rows] = await db.execute(
        `SELECT messages.*, users.username 
         FROM messages 
         JOIN users ON messages.senderId = users.id 
         WHERE (senderId = ? AND receiverId = ?) 
         OR (senderId = ? AND receiverId = ?) 
         ORDER BY timestamp`,
        [senderId, receiverId, receiverId, senderId]
    );
    const formattedMessages = rows.map(msg => ({
        username: msg.username,
        content: msg.content,
        timestamp: msg.timestamp
    }));
    res.json(formattedMessages);
});

    app.post('/messages', async (req, res) => {
        const { senderId, receiverId, content } = req.body;
        await db.execute('INSERT INTO messages (senderId, receiverId, content) VALUES (?, ?, ?)', [senderId, receiverId, content]);
        res.status(201).send('Message sent');
    });

    server.on('connection', socket => {
        console.log('Client connected');

        socket.on('message', async message => {
            const data = JSON.parse(message);
            const { senderId, receiverId, content, username } = data;

            // Log the message details to the terminal
            console.log(`Message from ${username} (id: ${senderId}) to id:${receiverId}: ${content}`);
            
            // Check if any of the parameters are undefined
            if (!senderId || !receiverId || !content || !username) {
                console.error('Undefined parameter detected', { senderId, receiverId, content, username });
                return;
            }
            
            // Insert message into the database without waiting
            db.execute('INSERT INTO messages (senderId, receiverId, content) VALUES (?, ?, ?)', [senderId, receiverId, content])
              .catch(err => console.error('Error saving message:', err));
    
            // Immediately send the message to clients
            const formattedMessage = { username: username, content: content };
            server.clients.forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(JSON.stringify(formattedMessage));
                }
            });
        });

        socket.on('close', () => {
            console.log('Client disconnected');
        });
    });
    
    app.listen(3000, () => {
        console.log('HTTP server is running on http://localhost:3000');
    });

    console.log('WebSocket server is running on ws://localhost:8081');
}).catch(err => {
    console.error('Error connecting to the database:', err);
});
