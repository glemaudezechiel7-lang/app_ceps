<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Commentaires des clients</h2>

    <?php foreach ($commentaires as $com):
        $contenu = json_decode($com['contenu_json'], true); ?>
        <div class="ceps-card ceps-commentaire">
            <strong><?= e($com['prenom'] . ' ' . $com['nom']) ?></strong>
            <span class="ceps-badge">⭐ <?= (int)($contenu['note'] ?? 0) ?>/5</span>
            <p><?= e($contenu['texte'] ?? '') ?></p>
            <small><?= e($com['date_commentaire']) ?> — <?= (int)$com['publie_par_admin'] ? 'Réponse publiée' : 'En attente de réponse' ?></small>

            <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_commentaire_publier" class="ceps-form-inline" onsubmit="return confirm('Publier cette réponse dans le chat du client ?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id_commentaire" value="<?= (int)$com['id_commentaire'] ?>">
                <input type="hidden" name="id_utilisateur" value="<?= (int)$com['id_utilisateur'] ?>">
                <input type="text" name="reponse" placeholder="Répondre (envoyé dans le chat du client)...">
                <button type="submit" class="ceps-btn ceps-btn-petit">Publier la réponse</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if (empty($commentaires)): ?>
        <p>Aucun commentaire reçu pour le moment.</p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
