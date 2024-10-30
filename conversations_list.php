<div id="conversations-list">
    <h2>Suas Conversas</h2>
    <?php if (!empty($conversations)): ?>
        <ul>
            <?php foreach ($conversations as $conversation): ?>
                <li>
                    <a href="messages.php?user_id=<?php echo htmlspecialchars($conversation['id']); ?>">
                        <?php echo htmlspecialchars($conversation['username']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Você ainda não iniciou nenhuma conversa.</p>
    <?php endif; ?>
</div>