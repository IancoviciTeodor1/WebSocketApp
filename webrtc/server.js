'use strict';

const createError = require('http-errors');
const express = require('express');
const path = require('path');
const logger = require('morgan');
const io = require('socket.io')();
const debug = require('debug')("signaling-server");
const fs = require('fs');
const ifaces = require('os').networkInterfaces();

const key_path = process.env.LOCALHOST_SSL_KEY;
const cert_path = process.env.LOCALHOST_SSL_CERT;

const app = express();
const public_dir = './';
app.use(logger('dev'));
app.use(express.static(path.join(__dirname, public_dir)));

app.use(function(req, res, next) {
  next(createError(404));
});

app.use(function(err, req, res, next) {
  res.status(err.status || 500);
  res.sendFile(`error/${err.status}.html`, { root: __dirname });
});



const namespaces = io.of(/^\/[0-9]{7}$/);

namespaces.on('connect', function(socket) {
  const namespace = socket.nsp;
  console.log(`    Socket namespace: ${namespace.name}`);

  socket.broadcast.emit('connected peer');

  socket.on('signal', function(data) {
    socket.broadcast.emit('signal', data);
  });

  socket.on('disconnect', function() {
    namespace.emit('disconnected peer');
  });
});

const mp_namespaces = io.of(/^\/[0-9]+$/);

mp_namespaces.on('connect', function(socket) {
  const namespace = socket.nsp;
  const peers = [];

  for (let peer of namespace.sockets.keys()) {
    peers.push(peer);
  }
  console.log(`    Socket namespace: ${namespace.name}`);

  socket.emit('connected peers', peers);

  socket.broadcast.emit('connected peer', socket.id);

  socket.on('signal', function({ recipient, sender, signal }) {
    socket.to(recipient).emit('signal', { recipient, sender, signal });
  });

  socket.on('disconnect', function() {
    namespace.emit('disconnected peer', socket.id);
  });
});

function selectServer(k, c) {
  const config = {};
  if (k && c) {
    try {
      const key = fs.readFileSync(k);
      const cert = fs.readFileSync(c);
      config.protocol = 'https';
      config.server = require(config.protocol).createServer({key: key, cert: cert }, app);
    } catch(e) {
      console.error(e);
      process.exit(1);
    }
  } else {
    debug(' WARNING: Your server is using \'http\', not \'https\'.\n Some things might not work as expected.\n');
    config.protocol = 'http';
    config.server = require(config.protocol).createServer(app);
  }
  return config;
}

const port = process.env.PORT || '3001';
app.set('port', port);

const {server, protocol} = selectServer(key_path, cert_path);

io.attach(server);

function handleError(error) {
  if (error.syscall !== 'listen') {
    throw error;
  }

  switch (error.code) {
  case 'EADDRINUSE':
    console.error(`Port ${port} is already being used`);
    process.exit(1);
    break;
  case 'EACCES':
    console.error(`Port ${port} requires elevated user privileges (sudo)`);
    process.exit(1);
    break;
  default:
    throw error;
  }
}

function handleListening() {
  const address = server.address();
  const interfaces = [];
  Object.keys(ifaces).forEach(function(dev) {
    ifaces[dev].forEach(function(details) {
      if (details.family.toString().endsWith('4')) {
        interfaces.push(`-> ${protocol}://${details.address}:${address.port}/`);
      }
    });
  });
  console.log(
    `  Hold CTRL + C to stop the server.`
  );
}

server.listen(port);
server.on('error', handleError);
server.on('listening', handleListening);

console.log(`Starting server on port ${port}...`);

module.exports = {app, io, public_dir};
