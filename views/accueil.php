<?php require __DIR__ . '/partials/header.php'; ?>

<section class="ceps-hero">
    <h1><?= e(NOM_ENTREPRISE) ?></h1>
    <p>Votre partenaire de confiance en produits pharmaceutiques et cosmétiques.</p>
    <a href="<?= BASE_URL ?>/index.php?page=galerie" class="ceps-btn ceps-btn-large">Voir la galerie ➜</a>
</section>

<!-- Section bienvenue : texte + image côte à côte, comme sur la maquette -->






    <div class="image">
      <img src="<?= BASE_URL ?>/assets/images/ecran2.jpeg"  width="100%" height="100%" alt="COMPLET HOME">
    </div>



  

<section class="ceps-section">
    <h2>Nos produits phares</h2>
    <div class="ceps-grid">
        <?php foreach ($produitsAccueil as $produit): ?>
            <div class="ceps-card">
                <a href="<?= BASE_URL ?>/index.php?page=produit_detail&id=<?= (int)$produit['id_produit'] ?>" class="ceps-image-cliquable">
                <?php if ($produit['image']): ?>
                    <img src="<?= BASE_URL ?>/uploads/produits/<?= e($produit['image']) ?>" alt="<?= e($produit['nom_produit']) ?>">
                <?php else: ?>
                    <div class="ceps-card-placeholder">✚</div>
                <?php endif; ?>
                </a>
                <h3><?= e($produit['nom_produit']) ?></h3>
                <p class="ceps-prix"><?= number_format((float)$produit['prix_unitaire'], 2) ?> $</p>
                <span class="ceps-badge"><?= e($produit['nom_categorie']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="text-align:center; margin-top: 2rem;">
        <a href="<?= BASE_URL ?>/index.php?page=galerie" class="ceps-btn">+ Voir toute la galerie</a>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
