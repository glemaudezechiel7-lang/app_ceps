<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Paiement de votre commande</h2>

    <?php $total = array_sum(array_map(fn($l) => $l['quantite'] * $l['prix'], $_SESSION['panier'])); ?>
    <p class="ceps-total">Total à payer : <?= number_format($total, 2) ?> $</p>

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

    <?php
    $panierActuel  = $_SESSION['panier'];
    $nbDistincts   = panier_nombre_produits_distincts($panierActuel);
    $nbTotalArt    = panier_nombre_articles_total($panierActuel);
    $eligibleNego  = panier_eligible_negociation($panierActuel);
    ?>

    <div class="ceps-paiement-choix">

        <!-- Choix 1 : envoyer la commande d'abord, négocier avec l'admin, payer plus tard -->
        <div class="ceps-paiement-option<?= $eligibleNego ? '' : ' ceps-paiement-option-verrouillee' ?>">
            <h3>1. Envoyer sans payer maintenant (négociation)</h3>
            <?php if ($eligibleNego): ?>
                <p>Votre commande est transmise à un administrateur CEPS pour discuter du prix ou des modalités avant paiement.</p>
                <form method="post" action="<?= BASE_URL ?>/index.php?page=client_commander_sans_paiement"
                      onsubmit="return confirm('Envoyer cette commande sans paiement immédiat ?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="ceps-btn ceps-btn-large">Envoyer pour négociation</button>
                </form>
            <?php else: ?>
                <p class="ceps-note">
                    🔒 La négociation est réservée aux commandes d'au moins <strong>10 produits différents</strong>,
                    ou <strong>10 articles au total</strong> (même produit en plusieurs exemplaires).
                    Votre panier actuel contient <?= $nbDistincts ?> produit(s) différent(s) pour
                    <?= $nbTotalArt ?> article(s) au total.
                    Ajoutez des produits ou choisissez l'option 2 ci-contre.
                </p>
                <a href="<?= BASE_URL ?>/index.php?page=galerie" class="ceps-btn ceps-btn-large ceps-btn-secondaire">Ajouter d'autres produits</a>
            <?php endif; ?>
        </div>

        <!-- Choix 2 : j'ai déjà payé, j'envoie la preuve -->
        <div class="ceps-paiement-option">
            <h3>2. J'ai déjà payé</h3>
            <p>Envoyez une photo de votre preuve de paiement (capture MonCash/NatCash ou reçu de virement BNC). Un admin vérifiera avant d'approuver.</p>
            <form method="post" action="<?= BASE_URL ?>/index.php?page=client_commander_preuve" enctype="multipart/form-data"
                  onsubmit="return confirm('Confirmer l\'envoi de cette preuve de paiement ?')" class="ceps-form">
                <?= csrf_field() ?>
                <label>Moyen de paiement utilisé
                    <select name="mode_paiement" required>
                        <option value="moncash">MonCash</option>
                        <option value="natcash">NatCash</option>
                        <option value="bnc">BNC (virement bancaire)</option>
                    </select>
                </label>
                <label>Photo de la preuve de paiement
                    <input type="file" name="preuve" accept="image/*" required>
                </label>
                <button type="submit" class="ceps-btn ceps-btn-large">Envoyer la preuve de paiement</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
