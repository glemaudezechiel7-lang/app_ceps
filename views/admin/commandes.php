<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Commandes à approuver</h2>

    <table class="ceps-table">
        <thead><tr><th>N°</th><th>Client</th><th>Montant total</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($commandes as $commande): ?>
            <tr>
                <td>#<?= (int)$commande['id_commande'] ?></td>
                <td><?= e($commande['prenom'] . ' ' . $commande['nom']) ?> (<?= e($commande['email']) ?>)</td>
                <td><?= number_format((float)$commande['montant_total'], 2) ?> $</td>
                <td><span class="ceps-statut ceps-statut-<?= e($commande['statut']) ?>"><?= e(str_replace('_', ' ', $commande['statut'])) ?></span>
                    <?php if (!empty($commande['montant_acompte'])): ?>
                        <br><span class="ceps-note-inline">💰 Dépôt reçu : <?= number_format((float)$commande['montant_acompte'], 2) ?> $</span>
                    <?php endif; ?>
                </td>
                <td><?= e($commande['date_commande']) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/index.php?page=admin_commande_details&id=<?= (int)$commande['id_commande'] ?>" class="ceps-btn ceps-btn-petit">Examiner</a>

                    <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_commande_approuver" class="ceps-form-inline"
                          onsubmit="return confirm('Approuver cette commande ?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$commande['id_commande'] ?>">
                        <button type="submit" class="ceps-btn ceps-btn-petit">Approuver</button>
                    </form>

                    <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_commande_refuser" class="ceps-form-inline"
                          onsubmit="return confirm('Refuser cette commande ?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$commande['id_commande'] ?>">
                        <button type="submit" class="ceps-lien-danger ceps-btn-lien">Refuser</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($commandes)): ?>
            <tr><td colspan="6">Aucune commande en attente.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
