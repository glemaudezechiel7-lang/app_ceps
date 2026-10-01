<?php require __DIR__ . '/partials/header.php'; ?>

<section class="ceps-section">
    <h2>Résultats pour « <?= e($_GET['q'] ?? '') ?> »</h2>

    <div class="ceps-grid">
        <?php foreach ($produits as $produit): ?>
            <div class="ceps-card">
                <a href="<?= BASE_URL ?>/index.php?page=produit_detail&id=<?= (int)$produit['id_produit'] ?>" class="ceps-image-cliquable">
                <?php if ($produit['image']): ?>
                    <img src="<?= BASE_URL ?>/uploads/produits/<?= e($produit['image']) ?>" alt="<?= e($produit['nom_produit']) ?>">
                <?php else: ?>
                    <div class="ceps-card-placeholder">✚</div>
                <?php endif; ?>
                </a>
                <h3><?= e($produit['nom_produit']) ?></h3>
                <p><?= e($produit['description']) ?></p>
                <p class="ceps-prix"><?= number_format((float)$produit['prix_unitaire'], 2) ?> $</p>
                <span class="ceps-badge"><?= e($produit['nom_categorie']) ?></span>

                <form method="post" action="<?= BASE_URL ?>/index.php?page=panier_ajouter" class="ceps-form-inline">
                    <input type="hidden" name="id_produit" value="<?= (int)$produit['id_produit'] ?>">
                    <input type="hidden" name="retour" value="index.php?page=recherche_produit&q=<?= urlencode($_GET['q'] ?? '') ?>">
                    <input type="number" name="quantite" value="1" min="1" class="ceps-input-qte">
                    <button type="submit" class="ceps-btn">Ajouter au panier</button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if (empty($produits)): ?>
            <p>Aucun produit trouvé pour cette recherche. <a href="<?= BASE_URL ?>/index.php?page=galerie">Voir tous les produits</a></p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
