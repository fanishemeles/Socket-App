require('dotenv').config();
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const path = require('path');

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: '*', // Adjust as needed for security
  }
});

// Serve static files if needed
app.use(express.static(path.join(__dirname, 'public')));

io.on('connection', (socket) => {
  console.log(`A user connected: ${socket.id}`);

  socket.on('message', (msg) => {
    // Broadcast message to all clients
    io.emit('message', msg);
  });

  socket.on('disconnect', () => {
    console.log(`User disconnected: ${socket.id}`);
  });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`Server listening on port ${PORT}`);
});
