<?php
session_start();
require_once 'config.php';

// Verifique se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifique se o formulário foi enviado corretamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $user_id = $_SESSION['user_id'];
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];

    // Verifique se a solicitação pertence ao usuário logado
    $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$request_id, $user_id]);
    $friend_request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$friend_request) {
        // Se a solicitação não for encontrada ou não pertencer ao usuário, redireciona
        header('Location: friend_request.php?error=invalid_request');
        exit;
    }

    // Tratamento das ações "accept" e "reject"
    if ($action === 'accept') {
        // Atualiza o status para "accepted"
        $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$request_id]);

        // Aqui você também pode adicionar a lógica para inserir a amizade na tabela de amigos (se houver uma)
        $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)");
        $stmt->execute([$user_id, $friend_request['sender_id'], $friend_request['sender_id'], $user_id]);

        header('Location: friend_request.php?success=accepted');
        exit;

    } elseif ($action === 'reject') {
        // Atualiza o status para "rejected"
        $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$request_id]);

        header('Location: friend_request.php?success=rejected');
        exit;

    } else {
        // Ação inválida
        header('Location: friend_request.php?error=invalid_action');
        exit;
    }
} else {
    // Redireciona se o formulário não foi enviado corretamente
    header('Location: friend_request.php?error=invalid_request');
    exit;
}