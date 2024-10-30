<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['message_count' => 0, 'friend_request_count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Consulta para contar mensagens não lidas
$stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM messages WHERE receiver_id = ? AND read_status = 'unread'");
$stmt->execute([$user_id]);
$message_count = $stmt->fetchColumn();

// Consulta para contar solicitações de amizade pendentes
$stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$friend_request_count = $stmt->fetchColumn();

// Limitar os contadores a 99
$message_count = min($message_count, 99);
$friend_request_count = min($friend_request_count, 99);

// Consulta para buscar detalhes das notificações (separadas por tipo, como mensagens e outras)
$stmt = $pdo->prepare("
    SELECT n.*, u.username, m.content AS message_content
    FROM notifications n
    LEFT JOIN user u ON n.sender_id = u.id
    LEFT JOIN messages m ON n.message_id = m.id
    WHERE n.user_id = ? AND n.is_read = FALSE
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$notificationData = [];
foreach ($notifications as $notification) {
    $notificationText = '';
    $link = '';

    // Processar diferentes tipos de notificações
    switch ($notification['type']) {
        case 'message':
            // Contar quantas mensagens o usuário enviou
            $messageCount = 1;
            foreach ($notifications as $n) {
                if ($n['type'] === 'message' && $n['sender_id'] === $notification['sender_id'] && $n['id'] !== $notification['id']) {
                    $messageCount++;
                }
            }
            $notificationText = $notification['username'] . " enviou " . $messageCount . " mensagem" . ($messageCount > 1 ? "ns" : "");
            $link = "messages.php?user_id=" . $notification['sender_id'];
            break;

        // Outros tipos de notificação podem ser adicionados aqui
    }

    $notificationData[] = [
        'text' => $notificationText,
        'link' => $link
    ];
}

// Retorna o total de notificações, além das contagens de mensagens e solicitações de amizade
echo json_encode([
    'message_count' => $message_count,
    'friend_request_count' => $friend_request_count,
    'notifications' => $notificationData
]);
?>