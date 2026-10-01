<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Gestion du stock</h2>

    <div class="ceps-stats-grid">
        <div class="ceps-stat-card">
            <span class="ceps-stat-valeur"><?= number_format($gainDuJour, 2) ?> $</span>
            <span class="ceps-stat-label">Gain aujourd'hui</span>
        </div>
    </div>

    <form method="get" action="<?= BASE_URL ?>/index.php" class="ceps-form-inline">
        <input type="hidden" name="page" value="admin_stock">
        <input type="text" name="recherche" placeholder="Rechercher par nom ou ID..." value="<?= e($_GET['recherche'] ?? '') ?>">
        <button type="submit" class="ceps-btn">Rechercher</button>
    </form>

    <h3>Ajouter un produit</h3>
    <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_produit_ajouter" enctype="multipart/form-data" class="ceps-form">
        <?= csrf_field() ?>
        <label>Nom <input type="text" name="nom_produit" required></label>
        <label>Description <textarea name="description" rows="2"></textarea></label>
        <label>Catégorie
            <select name="id_categorie" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id_categorie'] ?>"><?= e($cat['nom_categorie']) ?> (<?= e($cat['type']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Prix unitaire ($) <input type="number" step="0.01" name="prix_unitaire" required></label>
        <label>Quantité initiale en stock <input type="number" name="quantite_initiale" value="0" required></label>
        <label>Image <input type="file" name="image" accept="image/*"></label>
        <button type="submit" class="ceps-btn">Ajouter</button>
    </form>

    <h3>Produits enregistrés (répartis par catégorie)</h3>
    <?php
    // Regroupement par catégorie pour un affichage organisé (répond au besoin de répartition par catégorie)
    $produitsParCategorie = [];
    foreach ($produits as $produit) {
        $produitsParCategorie[$produit['nom_categorie']][] = $produit;
    }
    ?>
    <?php foreach ($produitsParCategorie as $nomCategorie => $produitsDeCetteCategorie): ?>
        <h4 class="ceps-categorie-titre"><?= e($nomCategorie) ?> (<?= count($produitsDeCetteCategorie) ?>)</h4>
        <table class="ceps-table">
            <thead><tr><th>ID</th><th>Nom</th><th>Prix</th><th>Stock</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($produitsDeCetteCategorie as $produit): ?>
                <tr class="<?= $produit['quantite_disponible'] <= ($produit['seuil_alerte'] ?? 10) ? 'ceps-stock-faible' : '' ?>">
                    <td>#<?= (int)$produit['id_produit'] ?></td>
                    <td><?= e($produit['nom_produit']) ?></td>
                    <td><?= number_format((float)$produit['prix_unitaire'], 2) ?> $</td>
                    <td><?= (int)$produit['quantite_disponible'] ?></td>
                    <td>
                        <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_produit_supprimer" class="ceps-form-inline"
                              onsubmit="return confirm('Supprimer ce produit ?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$produit['id_produit'] ?>">
                            <button type="submit" class="ceps-lien-danger ceps-btn-lien">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td colspan="5">
                        <details>
                            <summary>Modifier</summary>
                            <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_produit_modifier" enctype="multipart/form-data" class="ceps-form-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_produit" value="<?= (int)$produit['id_produit'] ?>">
                                <input type="text" name="nom_produit" value="<?= e($produit['nom_produit']) ?>" required>
                                <input type="number" step="0.01" name="prix_unitaire" value="<?= e($produit['prix_unitaire']) ?>" required>
                                <input type="number" name="quantite_disponible" value="<?= (int)$produit['quantite_disponible'] ?>">
                                <select name="id_categorie">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int)$cat['id_categorie'] ?>" <?= $cat['id_categorie'] == $produit['id_categorie'] ? 'selected' : '' ?>><?= e($cat['nom_categorie']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea name="description" rows="1"><?= e($produit['description']) ?></textarea>
                                <input type="file" name="image" accept="image/*">
                                <button type="submit" class="ceps-btn">Enregistrer</button>
                            </form>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
    <?php if (empty($produits)): ?>
        <p>Aucun produit enregistré.</p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
