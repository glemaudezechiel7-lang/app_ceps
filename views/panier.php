<?php require __DIR__ . '/partials/header.php'; ?>

<section class="ceps-section">
    <h2>Mon panier</h2>

    <?php if (empty($panier)): ?>
        <p>Votre panier est vide. <a href="<?= BASE_URL ?>/index.php?page=galerie">Voir la galerie</a></p>
    <?php else: ?>
        <table class="ceps-table">
            <thead>
            <tr><th>Produit</th><th>Quantité</th><th>Prix unitaire</th><th>Sous-total</th><th></th></tr>
            </thead>
            <tbody>
            <?php $total = 0; foreach ($panier as $idProduit => $ligne):
                $sousTotal = $ligne['quantite'] * $ligne['prix'];
                $total += $sousTotal; ?>
                <tr>
                    <td><?= e($ligne['nom']) ?></td>
                    <td><?= (int)$ligne['quantite'] ?></td>
                    <td><?= number_format($ligne['prix'], 2) ?> $</td>
                    <td><?= number_format($sousTotal, 2) ?> $</td>
                    <td>
                        <form method="post" action="<?= BASE_URL ?>/index.php?page=panier_retirer" class="ceps-form-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$idProduit ?>">
                            <button type="submit" class="ceps-lien-danger ceps-btn-lien">Retirer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="ceps-total">Total : <?= number_format($total, 2) ?> $</p>

        <?php if (utilisateur_connecte()): ?>
            <form method="post" action="<?= BASE_URL ?>/index.php?page=client_commander"
                  onsubmit="return confirm('Continuer vers le paiement pour cette commande ?')">
                <?= csrf_field() ?>
                <button type="submit" class="ceps-btn ceps-btn-large">Valider ma commande</button>
            </form>
            <p class="ceps-note">Vous choisirez ensuite de payer tout de suite ou d'envoyer la commande pour négociation.</p>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/index.php?page=login" class="ceps-btn ceps-btn-large">Se connecter pour commander</a>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
