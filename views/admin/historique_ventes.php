<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Historique de toutes les ventes</h2>
    <p class="ceps-note">Chaque vente (commande client approuvée ou vente en caisse) reste enregistrée ici en permanence et peut être réimprimée à tout moment.</p>

    <table class="ceps-table">
        <thead><tr><th>N°</th><th>Vendu par</th><th>Type</th><th>Montant</th><th>Paiement</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($ventes as $v): ?>
            <tr>
                <td>#<?= (int)$v['id_commande'] ?></td>
                <td><?= e($v['prenom'] . ' ' . $v['nom']) ?></td>
                <td><?= $v['nom_role'] === 'caissier' ? '🏪 Vente en caisse' : '🛒 Commande en ligne' ?></td>
                <td><?= number_format((float)$v['montant_total'], 2) ?> $</td>
                <td><?= e(strtoupper($v['mode_paiement'])) ?></td>
                <td><?= e($v['date_approbation'] ?? $v['date_commande']) ?></td>
                <td><a href="<?= BASE_URL ?>/index.php?page=admin_commande_recu&id=<?= (int)$v['id_commande'] ?>" class="ceps-btn ceps-btn-petit" target="_blank">🧾 Reçu</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($ventes)): ?>
            <tr><td colspan="7">Aucune vente enregistrée pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
