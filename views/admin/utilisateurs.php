<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Utilisateurs de la plateforme</h2>

    <form method="get" action="<?= BASE_URL ?>/index.php" class="ceps-form-inline">
        <input type="hidden" name="page" value="admin_utilisateurs">
        <input type="text" name="recherche" placeholder="Rechercher par nom ou email..." value="<?= e($_GET['recherche'] ?? '') ?>">
        <button type="submit" class="ceps-btn">Rechercher</button>
    </form>

    <h3>Créer un compte caissier</h3>
    <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_caissier_creer" class="ceps-form">
        <?= csrf_field() ?>
        <label>Nom <input type="text" name="nom" required></label>
        <label>Prénom <input type="text" name="prenom" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label>Mot de passe <input type="password" name="mot_de_passe" minlength="8" required></label>
        <button type="submit" class="ceps-btn">Créer le caissier</button>
    </form>

    <?php if (!empty($caissiers)): ?>
    <h3>Caissiers existants</h3>
    <table class="ceps-table">
        <thead><tr><th>Nom</th><th>Email</th><th>Créé le</th></tr></thead>
        <tbody>
        <?php foreach ($caissiers as $c): ?>
            <tr><td><?= e($c['prenom'] . ' ' . $c['nom']) ?></td><td><?= e($c['email']) ?></td><td><?= e($c['date_inscription']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h3>Tous les utilisateurs</h3>

    <table class="ceps-table">
        <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Bloqué</th><th>Localisation</th><th>Inscrit le</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($utilisateurs as $u): ?>
            <tr>
                <td><?= e($u['nom'] . ' ' . $u['prenom']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['nom_role']) ?></td>
                <td><?= (int)$u['compte_bloque'] === 1 ? '🔒 Oui' : 'Non' ?></td>
                <td>
                    <?php if ($u['latitude'] && $u['longitude']): ?>
                        <a href="https://www.google.com/maps?q=<?= e($u['latitude']) ?>,<?= e($u['longitude']) ?>" target="_blank">Voir sur la carte</a>
                    <?php else: ?>
                        Non partagée
                    <?php endif; ?>
                </td>
                <td><?= e($u['date_inscription']) ?></td>
                <td>
                    <?php if ($u['nom_role'] === 'client'): ?>
                        <a href="<?= BASE_URL ?>/index.php?page=admin_chat_demarrer&id=<?= (int)$u['id_utilisateur'] ?>" class="ceps-btn ceps-btn-petit">💬 Chat</a>
                    <?php endif; ?>
                    <?php if ((int)$u['compte_bloque'] === 1): ?>
                        <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_utilisateur_debloquer" class="ceps-form-inline" onsubmit="return confirm('Débloquer ce compte ?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id_utilisateur'] ?>">
                            <button type="submit" class="ceps-btn ceps-btn-petit">Débloquer</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($utilisateurs)): ?>
            <tr><td colspan="7">Aucun utilisateur trouvé.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
