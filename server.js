
const express = require('express');
const mysql = require('mysql2/promise');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const WebSocket = require('ws');
const cors = require('cors');
const session = require('express-session');
const cookieParser = require('cookie-parser');
const crypto = require('crypto');

const app = express();
const server = new WebSocket.Server({ port: 8081 });
const messages = [];

const secret = crypto.randomBytes(64).toString('hex');

app.use(express.json());
app.use(cors());
app.use(cookieParser());
app.use(session({
    secret: secret,
    resave: false,
    saveUninitialized: true,
    cookie: { secure: false } // Set to true if using HTTPS
}));

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
        const { username, password, email } = req.body;
        try {
            const hashedPassword = await bcrypt.hash(password, 10);
            await db.execute('INSERT INTO users (username, password, email) VALUES (?, ?, ?)', [username, hashedPassword, email]);
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
            req.session.token = token;
            req.session.user_id = user.id;
            res.json({ token, id: user.id });
        } else {
            res.status(400).send('Invalid credentials');
        }
    });

    const authenticateToken = (req, res, next) => {
        const token = req.headers['authorization'] && req.headers['authorization'].split(' ')[1];
        if (!token) return res.sendStatus(401);
        
        jwt.verify(token, 'secretkey', (err, user) => {
            if (err) return res.sendStatus(403);
            req.user = user;
            next();
        });
    };
    
    // Apply middleware to routes
    app.get('/search', authenticateToken, async (req, res) => {
        const query = req.query.q;
        try {
            const [rows] = await db.execute('SELECT id, username FROM users WHERE username LIKE ?', [`%${query}%`]);
            res.json(rows);
        } catch (error) {
            console.error('Error searching users:', error);
            res.status(500).json({ error: 'Internal server error' });
        }
    });
    

    app.get('/conversations', authenticateToken, async (req, res) => {
        const { userId1, userId2 } = req.query;
        try {
            const [rows] = await db.execute(
                `SELECT c.id as conversationId FROM conversations c
                 JOIN participants p1 ON c.id = p1.conversationId
                 JOIN participants p2 ON c.id = p2.conversationId
                 WHERE p1.userId = ? AND p2.userId = ? AND c.type = 'one-on-one'`,
                [userId1, userId2]
            );

            if (rows.length > 0) {
                res.json({ conversationId: rows[0].conversationId });
            } else {
                res.json({ conversationId: null });
            }
        } catch (error) {
            console.error('Error fetching conversation:', error);
            res.status(500).json({ error: 'Internal server error' });
        }
    });

    app.post('/conversations', authenticateToken, async (req, res) => {
        const { participants, type } = req.body;
        try {
            const [result] = await db.execute('INSERT INTO conversations (type) VALUES (?)', [type]);
            const conversationId = result.insertId;

            const participantPromises = participants.map(userId =>
                db.execute('INSERT INTO participants (conversationId, userId) VALUES (?, ?)', [conversationId, userId])
            );
            await Promise.all(participantPromises);

            res.json({ conversationId });
        } catch (error) {
            console.error('Error creating conversation:', error);
            res.status(500).json({ error: 'Internal server error' });
        }
    });

    app.get('/messages', authenticateToken, async (req, res) => {
        const { conversationId } = req.query;
        try {
            const [rows] = await db.execute(
                `SELECT m.*, u.username FROM messages m
                 JOIN users u ON m.senderId = u.id
                 WHERE m.conversationId = ?
                 ORDER BY m.timestamp`,
                [conversationId]
            );

            const formattedMessages = rows.map(msg => ({
                username: msg.username,
                content: msg.content,
                timestamp: msg.timestamp
            }));
            res.json(formattedMessages);
        } catch (error) {
            console.error('Error fetching messages:', error);
            res.status(500).json({ error: 'Internal server error' });
        }
    });

    app.post('/messages', authenticateToken, async (req, res) => {
        const { conversationId, senderId, content } = req.body;
        try {
            await db.execute('INSERT INTO messages (conversationId, senderId, content) VALUES (?, ?, ?)', [conversationId, senderId, content]);
            res.status(201).send('Message sent');
        } catch (error) {
            console.error('Error sending message:', error);
            res.status(500).json({ error: 'Internal server error' });
        }
    });
    
    
    server.on('connection', socket => {
        socket.on('message', async message => {
            const data = JSON.parse(message);
            const { conversationId, senderId, content, username } = data;
    
            console.log(`Message from ${username} (id: ${senderId}) in conversation ${conversationId}: ${content}`);
    
            if (!conversationId || !senderId || !content || !username) {
                console.error('Undefined parameter detected', { conversationId, senderId, content, username });
                return;
            }
    
            try {
                await db.execute('INSERT INTO messages (conversationId, senderId, content) VALUES (?, ?, ?)', [conversationId, senderId, content]);
            } catch (err) {
                console.error('Error saving message:', err);
            }
    
            const formattedMessage = { username: username, content: content };
            server.clients.forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(JSON.stringify(formattedMessage));
                }
            });
        });
    });
    
    app.listen(3000, () => {
        console.log('HTTP server is running on http://localhost:3000');
    });
    
    console.log('WebSocket server is running on ws://localhost:8081');
}).catch(err => {
    console.error('Error connecting to the database:', err);
});