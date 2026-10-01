<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Tableau de bord CEPS Admin</h2>

    <div class="ceps-stats-grid">
        <div class="ceps-stat-card">
            <span class="ceps-stat-valeur"><?= number_format($gainDuJour, 2) ?> $</span>
            <span class="ceps-stat-label">Gain total aujourd'hui</span>
        </div>
        <div class="ceps-stat-card">
            <span class="ceps-stat-valeur"><?= count($commandes) ?></span>
            <span class="ceps-stat-label">Commandes en attente</span>
        </div>
        <div class="ceps-stat-card">
            <span class="ceps-stat-valeur"><?= count($utilisateurs) ?></span>
            <span class="ceps-stat-label">Utilisateurs enregistrés</span>
        </div>
    </div>

    <div class="ceps-admin-menu">
        <a href="<?= BASE_URL ?>/index.php?page=admin_stock" class="ceps-btn">📦 Gestion du stock</a>
        <a href="<?= BASE_URL ?>/index.php?page=admin_commandes" class="ceps-btn">🧾 Commandes à approuver</a>
        <a href="<?= BASE_URL ?>/index.php?page=admin_chat" class="ceps-btn">💬 Chat clients</a>
        <a href="<?= BASE_URL ?>/index.php?page=admin_utilisateurs" class="ceps-btn">👥 Utilisateurs</a>
        <a href="<?= BASE_URL ?>/index.php?page=admin_commentaires" class="ceps-btn">⭐ Commentaires</a>
        <a href="<?= BASE_URL ?>/index.php?page=admin_historique_ventes" class="ceps-btn">🧾 Historique des ventes</a>
    </div>

    <h3>Ventes par jour (détail par produit)</h3>
    <table class="ceps-table">
        <thead><tr><th>Jour</th><th>Produit</th><th>Quantité vendue</th><th>Gain</th></tr></thead>
        <tbody>
        <?php foreach ($ventesJour as $ligne): ?>
            <tr>
                <td><?= e($ligne['jour_vente']) ?></td>
                <td><?= e($ligne['nom_produit']) ?></td>
                <td><?= (int)$ligne['total_quantite'] ?></td>
                <td><?= number_format((float)$ligne['total_gain'], 2) ?> $</td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($ventesJour)): ?>
            <tr><td colspan="4">Aucune vente enregistrée pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h3>Ventes par jour</h3>
    <canvas id="graphJour" height="90"></canvas>

    <h3>Ventes par semaine</h3>
    <canvas id="graphSemaine" height="90"></canvas>

    <h3>Ventes par mois</h3>
    <canvas id="graphMois" height="90"></canvas>

    <h3>Modifier mon mot de passe (Admin CEPS)</h3>
    <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_mdp_modifier" class="ceps-form" onsubmit="return confirm('Confirmer le changement de mot de passe ?')">
        <?= csrf_field() ?>
        <label>Ancien mot de passe <input type="password" name="ancien_mot_de_passe" required></label>
        <label>Nouveau mot de passe <input type="password" name="nouveau_mot_de_passe" minlength="8" required></label>
        <label>Confirmation <input type="password" name="confirmation" minlength="8" required></label>
        <button type="submit" class="ceps-btn">Modifier</button>
    </form>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const optionsCommunes = { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } };

new Chart(document.getElementById('graphJour'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($parJour, 'periode')) ?>,
        datasets: [{ label: 'Ventes ($)', data: <?= json_encode(array_map('floatval', array_column($parJour, 'total'))) ?>, backgroundColor: '#0f8a5f' }]
    },
    options: optionsCommunes
});

new Chart(document.getElementById('graphSemaine'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($parSemaine, 'periode')) ?>,
        datasets: [{ label: 'Ventes ($)', data: <?= json_encode(array_map('floatval', array_column($parSemaine, 'total'))) ?>, borderColor: '#12c98a', backgroundColor: 'rgba(18,201,138,.2)', fill: true, tension: .3 }]
    },
    options: optionsCommunes
});

new Chart(document.getElementById('graphMois'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($parMois, 'periode')) ?>,
        datasets: [{ label: 'Ventes ($)', data: <?= json_encode(array_map('floatval', array_column($parMois, 'total'))) ?>, backgroundColor: '#0a6644' }]
    },
    options: optionsCommunes
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
