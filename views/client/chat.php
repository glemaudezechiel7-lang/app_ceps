<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Discuter avec CEPS</h2>

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
                <div class="ceps-message ceps-message-<?= $message['expediteur'] === 'client' ? 'moi' : 'autre' ?>">
                    <strong><?= $message['expediteur'] === 'client' ? 'Moi' : 'CEPS' ?></strong>
                    <p><?= nl2br(e($message['contenu'])) ?></p>
                    <small><?= e($message['date_envoi']) ?></small>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (empty($messages)): ?>
            <p>Aucun message pour le moment. Écrivez à CEPS pour toute question sur vos commandes.</p>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= BASE_URL ?>/index.php?page=client_chat_envoyer" class="ceps-form-chat">
        <?= csrf_field() ?>
        <textarea name="contenu" rows="2" placeholder="Votre message..." required></textarea>
        <button type="submit" class="ceps-btn">Envoyer</button>
    </form>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
