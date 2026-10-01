</main>

<footer class="ceps-footer">
    <div class="ceps-container ceps-footer-flex">

        <div class="ceps-footer-marque">
            <img src="<?= BASE_URL ?>/assets/images/logo_ceps.jpeg" alt="Logo CEPS" class="ceps-footer-logo">
            <span><?= e(NOM_ENTREPRISE) ?></span>
        </div>

        <ul class="ceps-footer-liens">
            <li><a href="<?= BASE_URL ?>/index.php?page=accueil">Accueil</a></li>
            <li><a href="<?= BASE_URL ?>/index.php?page=galerie">Produits</a></li>
            <li><a href="<?= BASE_URL ?>/index.php?page=panier">Panier</a></li>
            <li><a href="<?= BASE_URL ?>/index.php?page=apropos">À propos</a></li>
            <li><a href="<?= BASE_URL ?>/index.php?page=contact">Contact</a></li>
            <?php if (!utilisateur_connecte()): ?>
                <li><a href="<?= BASE_URL ?>/index.php?page=login">Connexion</a></li>
                <li><a href="<?= BASE_URL ?>/index.php?page=inscription">Inscription</a></li>
            <?php elseif (role_utilisateur() === 'caissier'): ?>
                <li><a href="<?= BASE_URL ?>/index.php?page=caissier_dashboard">Caisse</a></li>
            <?php endif; ?>
        </ul>

        <div class="ceps-footer-contact">
            <p>Email : <a href="mailto:centreequipementproduitsante@gmail.com">centreequipementproduitsante@gmail.com</a></p>
            <p>Téléphone : +509 41-10-36-02</p>
            <div class="ceps-footer-social">
                <a href="https://www.facebook.com" target="_blank" rel="noopener" title="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="https://www.instagram.com" target="_blank" rel="noopener" title="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="https://wa.me/50941103602" target="_blank" rel="noopener" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                <a href="https://twitter.com" target="_blank" rel="noopener" title="X (Twitter)"><i class="bi bi-twitter-x"></i></a>
            </div>
        </div>
    </div>

    <p class="ceps-footer-copy">&copy; <?= date('Y') ?> <?= e(NOM_ENTREPRISE) ?> — Tous droits réservés</p>
</footer>

<!-- Bootstrap JS : indispensable pour que le carrousel glisse/tourne automatiquement -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
