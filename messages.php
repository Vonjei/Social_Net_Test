<?php
session_start();
require_once 'config.php';

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

requireLogin(); // Garante que o usuário esteja logado antes de acessar a página.

$userId = $_SESSION['user_id']; // Agora $_SESSION['user_id'] estará acessível
$otherUserId = $_GET['user_id'] ?? null;

if ($otherUserId) {
    // Verifica se a função getMessages() está definida
    $messages = getMessages($userId, $otherUserId);
    $otherUser = getUserById($otherUserId);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
        $messageContent = sanitizeInput($_POST['message_content']);
        sendMessage($userId, $otherUserId, $messageContent);
        redirect("messages.php?user_id=$otherUserId");
    }
} else {
    $conversations = getConversations($userId);
}

$pageTitle = $otherUserId ? "Conversa com " . htmlspecialchars($otherUser['username']) : "Mensagens Privadas";
include 'header.php';

function getUserById($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getConversations($userId) {
    global $pdo;

    // Consulta para buscar as conversas com base nas mensagens trocadas
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.username
        FROM messages m
        JOIN user u ON (m.sender_id = u.id OR m.receiver_id = u.id)
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
          AND u.id != ?
    ");
    $stmt->execute([$userId, $userId, $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMessages($userId, $otherUserId) {
    global $pdo;

    // Busca as mensagens entre os dois usuários (usuário atual e outro usuário)
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender_name
        FROM messages m
        JOIN user u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
  
    <link rel="stylesheet" href="messages.css">
</head>
<body>
    <main>
        <h1><?php echo $pageTitle; ?></h1>

        <?php if (!$otherUserId): ?>
            <?php include 'conversations_list.php'; ?>
        <?php else: ?>
            <?php include 'chat_window.php'; ?>
        <?php endif; ?>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.3.2/socket.io.js"></script>
    <script src="messages.js"></script>
</body>
</html>