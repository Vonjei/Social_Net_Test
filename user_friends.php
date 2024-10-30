<section id="user-friends">
    <h2>Amigos de <?php echo htmlspecialchars($profileUser['username']); ?></h2>
    <?php if (count($friends) > 0): ?>
        <?php foreach ($friends as $friend): ?>
            <div class="friend">
                <img src="<?php echo $friend['profile_picture'] ? 'uploads/'.$friend['profile_picture'] : 'default_profile.png'; ?>" alt="Foto de perfil">
                <a href="profile.php?user_id=<?php echo $friend['id']; ?>"><?php echo htmlspecialchars($friend['username']); ?></a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Nenhum amigo ainda.</p>
    <?php endif; ?>
</section>