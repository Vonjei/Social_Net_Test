document.getElementById('message-form').addEventListener('submit', function(e) {
    e.preventDefault(); // Evita o reload da página

    const messageContent = document.getElementById('message-content').value;
    const receiverId = document.querySelector('input[name="receiver_id"]').value;

    if (messageContent.trim() !== '') {
        fetch('send_messages.php', { 
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                message_content: messageContent,
                receiver_id: receiverId,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Se a mensagem for enviada com sucesso, limpar o campo de texto
                document.getElementById('message-content').value = '';

                // Adicionar a nova mensagem ao chat
                const messageList = document.getElementById('messages-list');
                const newMessage = document.createElement('div');
                newMessage.classList.add('message', 'sent');
                newMessage.innerHTML = `<p>${data.message.message}</p><span class="message-time">${data.message.created_at}</span>`;
                messageList.appendChild(newMessage);

                // Rolagem automática para a última mensagem
                messageList.scrollTop = messageList.scrollHeight;

                // Enviar a mensagem para o servidor via Socket.IO
                socket.emit('new_message', data.message);
            } else {
                alert('Erro ao enviar a mensagem');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
        });
    }
});

// Receber novas mensagens via Socket.IO
socket.on('receive_message', function(data) {
    // Adicionar a nova mensagem recebida ao chat
    const messageList = document.getElementById('messages-list');
    const newMessage = document.createElement('div');
    newMessage.classList.add('message', 'received');
    newMessage.innerHTML = `<p>${data.message}</p><span class="message-time">${data.created_at}</span>`;
    messageList.appendChild(newMessage);

    // Rolagem automática para a última mensagem
    messageList.scrollTop = messageList.scrollHeight;
});