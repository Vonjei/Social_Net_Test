const express = require('express');
const http = require('http');
const socketIo = require('socket.io');

const app = express();
const server = http.createServer(app);
const io = socketIo(server);

io.on('connection', (socket) => {
    console.log('Novo usuário conectado');

    socket.on('new_message', (data) => {
        // Enviar mensagem para todos os conectados, incluindo o destinatário
        io.emit('receive_message', {
            sender_name: data.sender_name, // Nome do remetente real
            message: data.message,         // Conteúdo da mensagem
            created_at: data.created_at    // Hora da mensagem
        });
    });

    socket.on('disconnect', () => {
        console.log('Usuário desconectado');
    });
});

server.listen(3000, () => {
    console.log('Servidor WebSocket rodando na porta 3000');
});