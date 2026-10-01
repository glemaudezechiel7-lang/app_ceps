<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Conversation</h2>

    <div class="ceps-chat-fenetre">
        <?php foreach ($messages as $message): ?>
            <?php if ($message['expediteur'] === 'system'): ?>
                <div class="ceps-message ceps-message-system">
                    <p><?= nl2br(e($message['contenu'])) ?></p>
                    <?php if (!empty($message['preuve_paiement'])): ?>
                        <a href="<?= BASE_URL ?>/uploads/preuves/<?= e($message['preuve_paiement']) ?>" target="_blank">
                            <img src="<?= BASE_URL ?>/uploads/preuves/<?= e($message['preuve_paiement']) ?>" alt="Preuve de paiement" class="ceps-preuve-img">
                        </a>
                    <?php endif; ?>
                    <small><?= e($message['date_envoi']) ?></small>
                </div>
            <?php else: ?>
                <div class="ceps-message ceps-message-<?= $message['expediteur'] === 'admin' ? 'moi' : 'autre' ?>">
                    <strong><?= $message['expediteur'] === 'admin' ? 'CEPS (Moi)' : 'Client' ?></strong>
                    <p><?= nl2br(e($message['contenu'])) ?></p>
                    <small><?= e($message['date_envoi']) ?></small>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_chat_repondre" class="ceps-form-chat">
        <?= csrf_field() ?>
        <input type="hidden" name="id_conversation" value="<?= (int)$idConversation ?>">
        <textarea name="contenu" rows="2" placeholder="Votre réponse..." required></textarea>
        <button type="submit" class="ceps-btn">Répondre</button>
    </form>

    <a href="<?= BASE_URL ?>/index.php?page=admin_chat" class="ceps-btn">Retour aux conversations</a>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
