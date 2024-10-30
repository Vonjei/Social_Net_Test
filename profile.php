<?php
session_start();
require_once 'config.php';

$page_title = 'Seu Perfil';
include 'header.php';

include 'image_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Definir $profile_id com base no parâmetro GET ou usar o $user_id como padrão
$profile_id = isset($_GET['user_id']) ? $_GET['user_id'] : $user_id;

// Buscar informações do usuário do perfil
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$profile_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    // Se o perfil não for encontrado, redirecionar para o próprio perfil
    header('Location: profile.php');
    exit;
}

// Processar atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile_id == $user_id) {
    if (isset($_POST['update_profile'])) {
        $description = sanitize_input($_POST['description']);
        $stmt = $pdo->prepare("UPDATE user SET description = ? WHERE id = ?");
        $stmt->execute([$description, $user_id]);
        
        // Processar upload de foto de perfil
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_picture']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            if (in_array(strtolower($filetype), $allowed)) {
                $newname = "profile_".$user_id.".".$filetype;
                move_uploaded_file($_FILES['profile_picture']['tmp_name'], 'uploads/'.$newname);
                $stmt = $pdo->prepare("UPDATE user SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$newname, $user_id]);
            }
        }
    }
    
    // Processar nova postagem
    if (isset($_POST['new_post'])) {
        $content = sanitize_input($_POST['content']);
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, is_profile_post) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $content, 1]);
    }
    
    // Redirecionar após o POST para evitar reenvio do formulário
    header("Location: profile.php" . ($profile_id != $user_id ? "?user_id=".$profile_id : ""));
    exit;
}

// Buscar postagens do usuário do perfil
$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? AND is_profile_post = 1 ORDER BY created_at DESC");
$stmt->execute([$profile_id]);
$profile_posts = $stmt->fetchAll();


// Verificar se já foi enviada uma solicitação de amizade
$stmt = $pdo->prepare("
    SELECT * FROM friend_requests 
    WHERE (sender_id = ? AND receiver_id = ?) 
    OR (sender_id = ? AND receiver_id = ?)
");
$stmt->execute([$user_id, $profile_id, $profile_id, $user_id]);
$friend_request = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT * FROM posts 
    WHERE user_id = ? AND community_id IS NULL 
    ORDER BY created_at DESC
");
$stmt->execute([$profile_id]);
$profile_posts = $stmt->fetchAll();

// Fetch user's community posts
$stmt = $pdo->prepare("
    SELECT p.*, c.name as community_name 
    FROM posts p
    JOIN communities c ON p.community_id = c.id
    WHERE p.user_id = ? AND p.community_id IS NOT NULL
    ORDER BY p.created_at DESC
");
$stmt->execute([$profile_id]);
$community_posts = $stmt->fetchAll();

// Fetch friends of the profile user
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.profile_picture 
    FROM user u
    JOIN friend_requests fr ON (fr.sender_id = u.id OR fr.receiver_id = u.id)
    WHERE (fr.sender_id = ? OR fr.receiver_id = ?) 
    AND fr.status = 'accepted'
    AND u.id != ?
");
$stmt->execute([$profile_id, $profile_id, $profile_id]);
$friends = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - <?php echo htmlspecialchars($profile_user['username']); ?></title>
    <link rel="stylesheet" href="profile.css">
</head>
<body>
    <main>
        <h1>Perfil de <?php echo htmlspecialchars($profile_user['username']); ?></h1>
        
        <section id="profile-info">
            <img src="<?php echo $profile_user['profile_picture'] ? 'uploads/'.$profile_user['profile_picture'] : 'default_profile.png'; ?>" alt="Foto de perfil">
            <p><?php echo htmlspecialchars($profile_user['description'] ?? 'Sem descrição.'); ?></p>
            
            <?php if ($profile_id == $user_id): ?>
                <h2>Alterar Nome de Usuário</h2>
                <form action="change_username.php" method="POST">
                    <input type="text" name="new_username" placeholder="Novo nome de usuário" required>
                    <button type="submit">Alterar Nome de Usuário</button>
                </form>
            <?php endif; ?>

            <?php if ($profile_id == $user_id): ?>
                <h2>Atualizar Perfil</h2>
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <textarea name="description" placeholder="Sua descrição"><?php echo htmlspecialchars($profile_user['description'] ?? ''); ?></textarea>
                    <input type="file" name="profile_picture">
                    <button type="submit" name="update_profile">Atualizar Perfil</button>
                </form>
            <?php else: ?>
                <?php if ($profile_id != $user_id): ?>
                    <?php if (!$friend_request): ?>
                        <form action="send_request.php" method="POST">
                            <input type="hidden" name="receiver_id" value="<?php echo $profile_id; ?>">
                            <button type="submit">Enviar solicitação de amizade</button>
                        </form>
                    <?php elseif ($friend_request['status'] === 'pending'): ?>
                        <p>Solicitação de amizade pendente</p>
                    <?php elseif ($friend_request['status'] === 'accepted'): ?>
                        <p>Amigos</p>
                    <?php endif; ?>
                    <a href="messages.php?user_id=<?php echo $profile_id; ?>">Enviar mensagem</a>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- Amigos do usuário -->
        <section id="user-friends" class="friends-section">
            <h2>Amigos de <?php echo htmlspecialchars($profile_user['username']); ?></h2>
            <div class="friends-container">
                <?php if (count($friends) > 0): ?>
                    <?php foreach ($friends as $friend): ?>
                        <div class="friend-card">
                            <div class="friend-info">
                                <a href="profile.php?user_id=<?php echo $friend['id']; ?>">
                                    <img src="<?php echo $friend['profile_picture'] ? 'uploads/'.$friend['profile_picture'] : 'default_profile.png'; ?>" alt="Foto de perfil" class="profile-pic-small">
                                </a>
                                <a href="profile.php?user_id=<?php echo $friend['id']; ?>" class="friend-name"><?php echo htmlspecialchars($friend['username']); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhum amigo ainda.</p>
                <?php endif; ?>
            </div>
        </section>

        

        <?php if ($profile_id == $user_id): ?>
            <section id="new-post">
                <h2>Nova Postagem</h2>
                <form action="profile.php" method="POST">
                    <textarea name="content" placeholder="O que você está pensando?"></textarea>
                    <button type="submit" name="new_post">Postar</button>
                </form>
            </section>
        <?php endif; ?>

        <section id="user-posts">
            <h2>Postagens de <?php echo htmlspecialchars($profile_user['username']); ?></h2>
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab(event, 'profile-posts')">Postagens do Perfil</button>
                <button class="tab-btn" onclick="openTab(event, 'community-posts')">Postagens em Comunidades</button>
            </div>

            <div id="profile-posts" class="tab-content active">
                <?php foreach ($profile_posts as $post): ?>
                    <article class="post">
                        <p><?php echo htmlspecialchars($post['content']); ?></p>
                        <small>Postado em: <?php echo $post['created_at']; ?></small>
                        <?php if ($post['user_id'] == $user_id): ?>
                            <form action="delete_posts.php" method="POST" style="display: inline;">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" onclick="return confirm('Tem certeza que deseja excluir este post?');">X</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="community-posts" class="tab-content">
                <?php foreach ($community_posts as $post): ?>
                    <article class="post">
                        <p><?php echo htmlspecialchars($post['content']); ?></p>
                        <small>Postado em: <?php echo $post['created_at']; ?> na comunidade <?php echo htmlspecialchars($post['community_name']); ?></small>
                        <?php if ($post['user_id'] == $user_id): ?>
                            <form action="delete_posts.php" method="POST" style="display: inline;">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" onclick="return confirm('Tem certeza que deseja excluir este post?');">X</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

    </main>
    <script>
    function openTab(evt, tabName) {
        var i, tabContent, tabButtons;
        tabContent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabContent.length; i++) {
            tabContent[i].style.display = "none";
        }
        tabButtons = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tabButtons.length; i++) {
            tabButtons[i].className = tabButtons[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    </script>

    <script src="script.js"></script>
</body>
</html>