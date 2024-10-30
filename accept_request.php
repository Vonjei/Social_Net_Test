<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$request_id = $_POST['request_id'];

// Verificar se a solicitação existe e se o usuário é o destinatário
$stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE id = ? AND receiver_id = ?");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if ($request) {
    // Atualizar o status da solicitação para aceito
    $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
    $stmt->execute([$request_id]);

    header('Location: profile.php?user_id=' . $request['sender_id']);
    exit;
} else {
    echo "Solicitação inválida ou você não tem permissão para aceitá-la.";
}
?>