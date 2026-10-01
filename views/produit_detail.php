<?php require __DIR__ . '/partials/header.php'; ?>

<section class="ceps-section ceps-detail-produit">
    <div class="ceps-detail-image">
        <?php if ($produit['image']): ?>
            <img src="<?= BASE_URL ?>/uploads/produits/<?= e($produit['image']) ?>" alt="<?= e($produit['nom_produit']) ?>">
        <?php else: ?>
            <div class="ceps-card-placeholder" style="height:320px; font-size:4rem;">✚</div>
        <?php endif; ?>
    </div>

    <div class="ceps-detail-infos">
        <h2><?= e($produit['nom_produit']) ?></h2>
        <p class="ceps-detail-description"><?= nl2br(e($produit['description'])) ?></p>
        <p class="ceps-prix ceps-detail-prix"><?= number_format((float)$produit['prix_unitaire'], 2) ?> $</p>

        <?php if ((int)$produit['quantite_disponible'] > 0): ?>
            <p class="ceps-badge">En stock (<?= (int)$produit['quantite_disponible'] ?> disponibles)</p>
        <?php else: ?>
            <p class="ceps-badge" style="background:#fdeaea; color:#d64545;">Rupture de stock</p>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/index.php?page=panier_ajouter" class="ceps-detail-form">
            <input type="hidden" name="id_produit" value="<?= (int)$produit['id_produit'] ?>">
            <input type="hidden" name="retour" value="index.php?page=produit_detail&id=<?= (int)$produit['id_produit'] ?>">

            <label>Quantité</label>
            <div class="ceps-qte-selecteur">
                <button type="button" onclick="ajusterQte(-1)">−</button>
                <input type="number" name="quantite" id="qteProduit" value="1" min="1" max="<?= (int)$produit['quantite_disponible'] ?>">
                <button type="button" onclick="ajusterQte(1)">+</button>
            </div>

            <button type="submit" class="ceps-btn ceps-btn-large" <?= (int)$produit['quantite_disponible'] === 0 ? 'disabled' : '' ?>>
                Ajouter au panier
            </button>
        </form>

        <a href="<?= BASE_URL ?>/index.php?page=galerie" class="ceps-lien-retour">← Retour à la galerie</a>
    </div>
</section>

<script>
function ajusterQte(delta) {
    const champ = document.getElementById('qteProduit');
    const max = parseInt(champ.max || 999, 10);
    let valeur = parseInt(champ.value || 1, 10) + delta;
    if (valeur < 1) valeur = 1;
    if (valeur > max) valeur = max;
    champ.value = valeur;
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
