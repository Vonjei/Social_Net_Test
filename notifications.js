function updateNotificationCount() {
    fetch('check_notifications.php')
        .then(response => response.json())
        .then(data => {
            // Atualizar o contador de mensagens
            const notificationCount = document.getElementById('notification-count');
            const messageCount = data.message_count > 99 ? '+99' : data.message_count;
            notificationCount.textContent = messageCount;
            notificationCount.style.display = data.message_count > 0 ? 'inline' : 'none';

            // Atualizar o contador de solicitações de amizade
            const friendRequestCount = document.getElementById('friend-request-count');
            const requestCount = data.friend_request_count > 99 ? '+99' : data.friend_request_count;
            friendRequestCount.textContent = requestCount;
            friendRequestCount.style.display = data.friend_request_count > 0 ? 'inline' : 'none';
        })
        .catch(error => console.error('Erro ao carregar notificações:', error));
}

// Atualizar a contagem de notificações a cada 60 segundos
setInterval(updateNotificationCount, 60000);

// Atualizar a contagem de notificações imediatamente ao carregar a página
document.addEventListener('DOMContentLoaded', updateNotificationCount);