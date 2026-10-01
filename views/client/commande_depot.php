<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Dépôt pour la commande #<?= (int)$idCommande ?></h2>

    <p class="ceps-note">
        Un administrateur CEPS a proposé un nouveau prix pour cette commande. Vous pouvez envoyer un
        <strong>dépôt (acompte)</strong> dès maintenant, avant même que la commande soit définitivement approuvée.
    </p>

    <table class="ceps-table">
        <thead>
        <tr><th>Produit</th><th>Quantité</th><th>Prix négocié</th><th>Sous-total</th></tr>
        </thead>
        <tbody>
        <?php foreach ($details as $ligne): ?>
            <tr>
                <td><?= e($ligne['nom_produit']) ?></td>
                <td><?= (int)$ligne['quantite'] ?></td>
                <td><?= number_format((float)$ligne['prix_unitaire_negocie'], 2) ?> $</td>
                <td><?= number_format((float)$ligne['sous_total'], 2) ?> $</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="ceps-total">Total de la commande : <?= number_format((float)$infos['montant_total'], 2) ?> $</p>

    <?php if (!empty($infos['montant_acompte'])): ?>
        <div class="ceps-alert ceps-alert-succes">
            Un dépôt de <?= number_format((float)$infos['montant_acompte'], 2) ?> $
            via <?= e(strtoupper($infos['mode_paiement_acompte'])) ?> a déjà été envoyé le
            <?= e($infos['date_acompte']) ?>. Vous pouvez envoyer un nouveau dépôt ci-dessous pour le remplacer
            tant que la commande n'est pas encore approuvée.
        </div>
    <?php endif; ?>

    <div class="ceps-paiement-moyens">
        <div class="ceps-paiement-carte">
            <h4>📱 MonCash</h4>
            <p><?= e(NUMERO_MONCASH) ?></p>
        </div>
        <div class="ceps-paiement-carte">
            <h4>📱 NatCash</h4>
            <p><?= e(NUMERO_NATCASH) ?></p>
        </div>
        <div class="ceps-paiement-carte">
            <h4>🏦 BNC</h4>
            <p><?= e(NUMERO_BNC) ?></p>
        </div>
    </div>

    <form method="post" action="<?= BASE_URL ?>/index.php?page=client_commande_depot_enregistrer"
          enctype="multipart/form-data" class="ceps-form"
          onsubmit="return confirm('Confirmer l\'envoi de ce dépôt ?')">
        <?= csrf_field() ?>
        <input type="hidden" name="id_commande" value="<?= (int)$idCommande ?>">

        <label>Montant du dépôt ($)
            <input type="number" step="0.01" min="0.01" max="<?= (float)$infos['montant_total'] ?>" name="montant" required>
        </label>

        <label>Moyen de paiement utilisé
            <select name="mode_paiement" required>
                <option value="moncash">MonCash</option>
                <option value="natcash">NatCash</option>
                <option value="bnc">BNC (virement bancaire)</option>
            </select>
        </label>

        <label>Photo de la preuve du dépôt
            <input type="file" name="preuve" accept="image/*" required>
        </label>

        <button type="submit" class="ceps-btn ceps-btn-large">Envoyer le dépôt</button>
    </form>

    <a href="<?= BASE_URL ?>/index.php?page=client_historique" class="ceps-btn ceps-btn-secondaire">Retour à mon historique</a>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
