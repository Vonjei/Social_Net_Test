<div id="chat-window">
    <script src="messages.js"></script>
    <div id="messages-list">
        <?php foreach ($messages as $message): ?>
            <div class="message <?php echo $message['sender_id'] == $userId ? 'sent' : 'received'; ?>">
                <strong><?php echo htmlspecialchars($message['sender_name']); ?>:</strong>
                <p><?php echo htmlspecialchars($message['message']); ?></p>
                <span class="message-time"><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <form id="message-form">
        <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($otherUserId); ?>">
        <textarea id="message-content" name="message_content" rows="3" placeholder="Digite sua mensagem..."></textarea>
        <button type="submit">Enviar</button>
    </form>
</div>
