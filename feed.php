<?php
session_start();
require_once 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Funções para obter votos
function getVoteCount($pdo, $post_id) {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN vote_type = 'upvote' THEN 1 ELSE -1 END), 0) as vote_count
        FROM votes
        WHERE post_id = ?
    ");
    $stmt->execute([$post_id]);
    return $stmt->fetchColumn();
}

function getUserVote($pdo, $user_id, $post_id) {
    $stmt = $pdo->prepare("SELECT vote_type FROM votes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    return $stmt->fetchColumn();
}

function resizeImage($file, $max_width, $max_height) {
    list($width, $height) = getimagesize($file);
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = $width * $ratio;
    $new_height = $height * $ratio;
    
    $src = imagecreatefromstring(file_get_contents($file));
    $dst = imagecreatetruecolor($max_width, $max_height);
    
    // Preencher com fundo branco
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
    
    // Copiar e redimensionar parte da imagem
    imagecopyresampled($dst, $src, ($max_width - $new_width) / 2, ($max_height - $new_height) / 2, 0, 0, $new_width, $new_height, $width, $height);
    
    return $dst;
}

// Buscar postagens de todos os usuários com contagem de votos
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_picture 
    FROM posts p 
    JOIN user u ON p.user_id = u.id 
    WHERE p.is_profile_post = 0
    ORDER BY p.created_at DESC 
    LIMIT 50
");
$stmt->execute();
$posts = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed de Postagens</title>
    <style>
        .post-image {
            width: 382px;
            height: 382px;
            object-fit: contain;
            background-color: white;
        }
    </style>
</head>
<body>
    <main>
        <h1>Feed de Postagens</h1>

        <section id="new-post">
            <h2>Nova Postagem</h2>
            <form action="create_post.php" method="POST" enctype="multipart/form-data">
                <textarea name="content" placeholder="O que você está pensando?" required></textarea>
                <input type="file" name="image" accept="image/*">
                <button type="submit">Postar</button>
            </form>
        </section>

        <section id="feed">
            <?php foreach ($posts as $post): 
                $vote_count = getVoteCount($pdo, $post['id']);
                $user_vote = getUserVote($pdo, $user_id, $post['id']);
            ?>
                <article class="post" data-post-id="<?php echo $post['id']; ?>">
                    <a href="profile.php?user_id=<?php echo $post['user_id']; ?>">
                        <img src="<?php echo isset($post['profile_picture']) && $post['profile_picture'] ? 'uploads/'.$post['profile_picture'] : 'default_profile.png'; ?>" alt="Foto de perfil" class="profile-pic-small">
                        <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                    </a>
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    
                    <?php if (isset($post['image']) && $post['image']): ?>
                        <?php 
                        $image_path = 'uploads/' . $post['image'];
                        $resized_image = resizeImage($image_path, 382, 382);
                        ob_start();
                        imagepng($resized_image);
                        $image_data = ob_get_clean();
                        $base64_image = base64_encode($image_data);
                        ?>
                        <img src="data:image/png;base64,<?php echo $base64_image; ?>" alt="Imagem da postagem" class="post-image">
                    <?php endif; ?>

                    <small>Postado em: <?php echo $post['created_at']; ?></small>

                    <?php if ($post['user_id'] == $user_id): ?>
                            <form action="delete_posts.php" method="POST" style="display: inline;">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" onclick="return confirm('Tem certeza que deseja excluir este post?');">X</button>
                            </form>
                        <?php endif; ?>
                        
                    <div class="vote-section">
                        <form method="post" class="vote-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" name="vote" value="upvote" class="vote-button <?php echo $user_vote === 'upvote' ? 'active' : ''; ?>">👍</button>
                            <span class="vote-count"><?php echo $vote_count; ?></span>
                            <button type="submit" name="vote" value="downvote" class="vote-button <?php echo $user_vote === 'downvote' ? 'active' : ''; ?>">👎</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <script src="script.js"></script>
</body>
</html>