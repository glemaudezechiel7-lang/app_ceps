<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Caisse CEPS</h2>

    <div class="ceps-stats-grid">
        <div class="ceps-stat-card">
            <span class="ceps-stat-valeur"><?= number_format($gainDuJour, 2) ?> $</span>
            <span class="ceps-stat-label">Gain aujourd'hui</span>
        </div>
    </div>

    <div class="ceps-caisse-layout">

        <!-- Liste des produits -->
        <div class="ceps-caisse-produits">
            <h3>Produits disponibles</h3>
            <table class="ceps-table">
                <thead><tr><th>Produit</th><th>Prix</th><th>Stock</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($produits as $produit): ?>
                    <tr>
                        <td><?= e($produit['nom_produit']) ?></td>
                        <td><?= number_format((float)$produit['prix_unitaire'], 2) ?> $</td>
                        <td><?= (int)$produit['quantite_disponible'] ?></td>
                        <td>
                            <form method="post" action="<?= BASE_URL ?>/index.php?page=caissier_panier_ajouter" class="ceps-form-inline"
                                  onsubmit="return confirm('Ajouter ce produit au panier de vente ?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_produit" value="<?= (int)$produit['id_produit'] ?>">
                                <input type="number" name="quantite" value="1" min="1" max="<?= (int)$produit['quantite_disponible'] ?>" class="ceps-input-qte">
                                <button type="submit" class="ceps-btn ceps-btn-petit" <?= (int)$produit['quantite_disponible'] === 0 ? 'disabled' : '' ?>>Ajouter</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Panier de vente en cours -->
        <div class="ceps-caisse-panier">
            <h3>🛒 Panier de vente</h3>
            <?php if (empty($panierCaisse)): ?>
                <p>Aucun produit sélectionné.</p>
            <?php else: ?>
                <table class="ceps-table">
                    <thead><tr><th>Produit</th><th>Qté</th><th>Sous-total</th><th></th></tr></thead>
                    <tbody>
                    <?php $totalPanier = 0; foreach ($panierCaisse as $idProduit => $ligne):
                        $sousTotal = $ligne['quantite'] * $ligne['prix']; $totalPanier += $sousTotal; ?>
                        <tr>
                            <td><?= e($ligne['nom']) ?></td>
                            <td><?= (int)$ligne['quantite'] ?></td>
                            <td><?= number_format($sousTotal, 2) ?> $</td>
                            <td>
                                <form method="post" action="<?= BASE_URL ?>/index.php?page=caissier_panier_retirer" class="ceps-form-inline"
                                      onsubmit="return confirm('Retirer ce produit du panier de vente ?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$idProduit ?>">
                                    <button type="submit" class="ceps-lien-danger ceps-btn-lien">Retirer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="ceps-total">Total : <?= number_format($totalPanier, 2) ?> $</p>

                <form method="post" action="<?= BASE_URL ?>/index.php?page=caissier_valider"
                      onsubmit="return confirm('Confirmer et valider cette vente ? Cette action est définitive.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="ceps-btn ceps-btn-large">✔ Valider la vente</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <h3>Ventes par jour</h3>
    <canvas id="graphJour" height="90"></canvas>
    <h3>Ventes par semaine</h3>
    <canvas id="graphSemaine" height="90"></canvas>
    <h3>Ventes par mois</h3>
    <canvas id="graphMois" height="90"></canvas>

    <h3>Mes ventes (réimprimables à tout moment)</h3>
    <table class="ceps-table">
        <thead><tr><th>N°</th><th>Montant</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($mesVentes as $v): ?>
            <tr>
                <td>#<?= (int)$v['id_commande'] ?></td>
                <td><?= number_format((float)$v['montant_total'], 2) ?> $</td>
                <td><?= e($v['date_approbation']) ?></td>
                <td><a href="<?= BASE_URL ?>/index.php?page=caissier_commande_recu&id=<?= (int)$v['id_commande'] ?>" class="ceps-btn ceps-btn-petit" target="_blank">🧾 Reçu</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($mesVentes)): ?>
            <tr><td colspan="4">Aucune vente pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const optionsCommunes = { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } };
new Chart(document.getElementById('graphJour'), { type: 'bar', data: { labels: <?= json_encode(array_column($parJour, 'periode')) ?>, datasets: [{ label: 'Ventes ($)', data: <?= json_encode(array_map('floatval', array_column($parJour, 'total'))) ?>, backgroundColor: '#0f8a5f' }] }, options: optionsCommunes });
new Chart(document.getElementById('graphSemaine'), { type: 'line', data: { labels: <?= json_encode(array_column($parSemaine, 'periode')) ?>, datasets: [{ label: 'Ventes ($)', data: <?= json_encode(array_map('floatval', array_column($parSemaine, 'total'))) ?>, borderColor: '#12c98a', backgroundColor: 'rgba(18,201,138,.2)', fill: true, tension: .3 }] }, options: optionsCommunes });
new Chart(document.getElementById('graphMois'), { type: 'bar', data: { labels: <?= json_encode(array_column($parMois, 'periode')) ?>, datasets: [{ label: 'Ventes ($)', data: <?= json_encode(array_map('floatval', array_column($parMois, 'total'))) ?>, backgroundColor: '#0a6644' }] }, options: optionsCommunes });
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
