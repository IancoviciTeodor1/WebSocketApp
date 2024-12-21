
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
const SECRET_KEY = 'secretkey'; // Definește cheia ta secretă constantă pentru JWT

// Middleware pentru autentificare cu token JWT
function authenticateToken(req, res, next) {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];
    //console.log('Token primit:', token);
    
    if (!token) {
        console.log('Unauthorized request: Missing token');
        return res.sendStatus(403); // Forbidden
    }

    jwt.verify(token, SECRET_KEY, (err, user) => { // Folosește aceeași cheie secretă ca la generare
        if (err) {
            console.log('Unauthorized request: Invalid token');
            return res.sendStatus(403); // Forbidden
        }
        req.user = user; // Salvează informațiile decodate în obiectul req
        next();
    });
}

app.use(express.json());
app.use(cors({
    origin: 'http://localhost', // Adjust this to match your frontend's address
    credentials: true
}));
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
 
    app.get('/search', authenticateToken, async (req, res) => {
        const authHeader = req.headers['authorization'];
        if (!authHeader || !authHeader.startsWith('Bearer ')) {
            console.log('Unauthorized request: Missing or invalid token');
            return res.sendStatus(403); // Forbidden
        }
    
        const token = authHeader.split(' ')[1];
        try {
            const decoded = jwt.verify(token, SECRET_KEY); // Folosește `SECRET_KEY` aici
            const query = req.query.q;
            const [rows] = await db.execute('SELECT id, username FROM users WHERE username LIKE ?', [`%${query}%`]);
            res.json(rows);
        } catch (error) {
            console.log('Unauthorized request: Invalid token');
            res.sendStatus(403);
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
                `SELECT m.*, u.username, u.profile_picture FROM messages m
                 JOIN users u ON m.senderId = u.id
                 WHERE m.conversationId = ?
                 ORDER BY m.timestamp`,
                [conversationId]
            );

            const formattedMessages = rows.map(msg => ({
                username: msg.username,
                content: msg.content,
                timestamp: msg.timestamp,
                profile_picture: msg.profile_picture || 'default.jpg'
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
    
    
    const clients = new Map();

server.on('connection', (socket) => {
    console.log('A new WebSocket connection was established');

    socket.on('message', async (message) => {
        const data = JSON.parse(message);

        console.log('Message received:', data);

        if (data.type === 'join') {
            clients.set(socket, data.conversationId);
            console.log(`User joined conversation ${data.conversationId}`);
        } else if (data.type === 'message') {
            const { conversationId, content, senderId, username } = data;

            // Logăm trimiterea mesajului
            console.log(`Message sent in conversation ${conversationId}: ${content}`);

            try {
                // Salvează mesajul în baza de date și obține timestamp-ul generat
                const [result] = await db.execute(
                    'INSERT INTO messages (conversationId, senderId, content) VALUES (?, ?, ?)',
                    [conversationId, senderId, content]
                );

                const messageId = result.insertId;

                // Obține informațiile utilizatorului și mesajului
                const [rows] = await db.execute(
                    `SELECT m.id, m.timestamp, u.profile_picture 
                     FROM messages m 
                     JOIN users u ON m.senderId = u.id 
                     WHERE m.id = ?`,
                    [messageId]
                );

                const enrichedMessage = {
                    username,
                    content,
                    conversationId,
                    profile_picture: rows[0]?.profile_picture || 'default_profile_picture.png',
                    timestamp: rows[0]?.timestamp || new Date().toISOString(),
                };

                // Trimite mesajul către utilizatorii din aceeași conversație
                clients.forEach((clientConversationId, clientSocket) => {
                    if (
                        clientConversationId === conversationId &&
                        clientSocket.readyState === WebSocket.OPEN
                    ) {
                        console.log(`Sending message to client in conversation ${conversationId}`);
                        clientSocket.send(JSON.stringify(enrichedMessage));
                    }
                });
            } catch (err) {
                console.error('Error processing message:', err);
            }
        }
    });

    socket.on('close', () => {
        console.log('A WebSocket connection was closed');
        clients.delete(socket);
    });
});

    
    app.listen(3000, () => {
        console.log('HTTP server is running on http://localhost:3000');
    });

    const socket = new WebSocket('ws://localhost:8081');

    socket.onopen = () => {
        console.log('Connected to WebSocket server');
        //socket.send(JSON.stringify({ type: 'join', conversationId: 12 }));
    };

    socket.onmessage = (event) => {
        const message = JSON.parse(event.data);
        console.log('Message received:', message);
    };

    socket.onclose = () => {
        console.log('Disconnected from WebSocket server');
    };

    
    console.log('WebSocket server is running on ws://localhost:8081');
}).catch(err => {
    console.error('Error connecting to the database:', err);
});