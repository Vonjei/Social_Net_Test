<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['new_username'])) {
    header('Location: profile.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$new_username = sanitize_input($_POST['new_username']);

// Verificar se o novo nome de usuário já existe
$stmt = $pdo->prepare("SELECT id FROM user WHERE username = ? AND id != ?");
$stmt->execute([$new_username, $user_id]);
if ($stmt->fetch()) {
    $_SESSION['error'] = "Usuário existente, selecione outro";
    header('Location: profile.php');
    exit;
}

// Verificar se o novo nome é "criador" ou "O Criador"
if (strtolower($new_username) === "criador" || strtolower($new_username) === "o criador") {
    $_SESSION['error'] = "Heresia!!";
    header('Location: profile.php');
    exit;
}

// Atualizar o nome de usuário
$stmt = $pdo->prepare("UPDATE user SET username = ? WHERE id = ?");
$stmt->execute([$new_username, $user_id]);

$_SESSION['success'] = "Nome alterado!";
header('Location: profile.php');
exit;
?>