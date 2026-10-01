<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Détails de la commande #<?= (int)$idCommande ?></h2>

    <table class="ceps-table">
        <thead><tr><th>Produit</th><th>Quantité</th><th>Prix négocié</th><th>Sous-total</th><th>Modifier le prix</th></tr></thead>
        <tbody>
        <?php foreach ($details as $ligne): ?>
            <tr>
                <td><?= e($ligne['nom_produit']) ?></td>
                <td><?= (int)$ligne['quantite'] ?></td>
                <td><?= number_format((float)$ligne['prix_unitaire_negocie'], 2) ?> $</td>
                <td><?= number_format((float)$ligne['sous_total'], 2) ?> $</td>
                <td>
                    <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_commande_prix" class="ceps-form-inline" onsubmit="return confirm('Confirmer la modification du prix ?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id_commande" value="<?= (int)$idCommande ?>">
                        <input type="hidden" name="id_produit" value="<?= (int)$ligne['id_produit'] ?>">
                        <input type="number" step="0.01" name="nouveau_prix" placeholder="Nouveau prix" required>
                        <button type="submit" class="ceps-btn ceps-btn-petit">Négocier</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ceps-note">Le total de la commande est recalculé automatiquement par un trigger SQL après chaque modification de prix.</p>

    <?php
    // $infosCommande est fourni par le contrôleur (informationsCommande) pour afficher un éventuel dépôt client.
    if (!empty($infosCommande['montant_acompte'])):
    ?>
        <div class="ceps-alert ceps-alert-info">
            <span>
                💰 Le client a envoyé un dépôt de <strong><?= number_format((float)$infosCommande['montant_acompte'], 2) ?> $</strong>
                via <?= e(strtoupper($infosCommande['mode_paiement_acompte'])) ?> le <?= e($infosCommande['date_acompte']) ?>.
            </span>
            <?php if (!empty($infosCommande['preuve_acompte'])): ?>
                <a href="<?= BASE_URL ?>/uploads/preuves/<?= e($infosCommande['preuve_acompte']) ?>" target="_blank" class="ceps-btn ceps-btn-petit">Voir la preuve</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/index.php?page=admin_commande_approuver" class="ceps-form-inline"
          onsubmit="return confirm('Approuver cette commande ?')">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$idCommande ?>">
        <button type="submit" class="ceps-btn">✔ Approuver la commande</button>
    </form>
    <a href="<?= BASE_URL ?>/index.php?page=admin_commandes" class="ceps-btn">Retour à la liste</a>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
