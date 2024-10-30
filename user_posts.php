<section id="user-posts">
    <h2>Postagens de <?php echo htmlspecialchars($profileUser['username']); ?></h2>
    <div class="tabs">
        <button class="tab-btn active" onclick="openTab(event, 'profile-posts')">Postagens do Perfil</button>
        <button class="tab-btn" onclick="openTab(event, 'community-posts')">Postagens em Comunidades</button>
    </div>

    <div id="profile-posts" class="tab-content active">
        <?php foreach ($profilePosts as $post): ?>
            <article class="post">
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <small>Postado em: <?php echo $post['created_at']; ?></small>
                <?php if ($post['user_id'] == $userId): ?>
                    <form action="delete_post.php" method="POST" class="delete-post-form">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" onclick="return confirm('Tem certeza que deseja excluir este post?');">X</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <div id="community-posts" class="tab-content">
        <?php foreach ($communityPosts as $post): ?>
            <article class="post">
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <small>Postado em: <?php echo $post['created_at']; ?> na comunidade <?php echo htmlspecialchars($post['community_name']); ?></small>
                <?php if ($post['user_id'] == $userId): ?>
                    <form action="delete_post.php" method="POST" class="delete-post-form">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" onclick="return confirm('Tem certeza que deseja excluir este post?');">X</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>