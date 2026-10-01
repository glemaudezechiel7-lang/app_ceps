<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Mon historique de commandes</h2>

    <?php if (empty($historique)): ?>
        <p>Vous n'avez pas encore passé de commande.</p>
    <?php else: ?>
        <?php
        // Regroupe les lignes (une par produit) par commande, pour n'afficher qu'un seul
        // tableau et un seul bouton d'action par commande.
        $commandesGroupees = [];
        foreach ($historique as $ligne) {
            $commandesGroupees[$ligne['id_commande']]['infos'] = $ligne;
            $commandesGroupees[$ligne['id_commande']]['lignes'][] = $ligne;
        }
        ?>
        <?php foreach ($commandesGroupees as $idCommande => $groupe): $infos = $groupe['infos']; ?>
            <div class="ceps-commande-bloc">
                <div class="ceps-commande-entete">
                    <h3>Commande #<?= (int)$idCommande ?></h3>
                    <span class="ceps-statut ceps-statut-<?= e($infos['statut']) ?>"><?= e(str_replace('_', ' ', $infos['statut'])) ?></span>
                </div>
                <table class="ceps-table">
                    <thead>
                    <tr><th>Produit</th><th>Quantité</th><th>Prix</th><th>Sous-total</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($groupe['lignes'] as $ligne): ?>
                        <tr>
                            <td><?= e($ligne['nom_produit']) ?></td>
                            <td><?= (int)$ligne['quantite'] ?></td>
                            <td><?= number_format((float)$ligne['prix_unitaire_negocie'], 2) ?> $</td>
                            <td><?= number_format((float)$ligne['sous_total'], 2) ?> $</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="ceps-total">Total : <?= number_format((float)$infos['montant_total'], 2) ?> $ — <span class="ceps-note-inline"><?= e($infos['date_commande']) ?></span></p>

                <?php if ($infos['statut'] === 'modifiee_adm'): ?>
                    <div class="ceps-alert ceps-alert-info">
                        Le prix de cette commande a été modifié par un administrateur CEPS.
                        <a href="<?= BASE_URL ?>/index.php?page=client_commande_depot&id=<?= (int)$idCommande ?>" class="ceps-btn ceps-btn-petit">💰 Faire un dépôt</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <p class="ceps-note">Une commande "modifiée admin" signifie que le prix a été ajusté suite à une négociation. Discutez-en via le chat, et vous pouvez y envoyer un dépôt avant l'approbation finale.</p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
