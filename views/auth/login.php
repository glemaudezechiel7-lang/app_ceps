<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section ceps-form-page">
    <h2>Connexion</h2>

    <form method="post" action="<?= BASE_URL ?>/index.php?page=login_traiter" class="ceps-form">
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Mot de passe
            <input type="password" name="mot_de_passe" required>
        </label>
        <button type="submit" class="ceps-btn ceps-btn-large">Se connecter</button>
    </form>

    <div class="ceps-separateur">ou</div>

    <!-- Connexion via Google (Google Identity Services) -->
    <div id="g_id_onload"
         data-client_id="<?= e(GOOGLE_CLIENT_ID) ?>"
         data-callback="gererReponseGoogle"
         data-auto_prompt="false">
    </div>
    <div class="g_id_signin" data-type="standard" data-size="large" data-width="100%"></div>

    <form id="formGoogle" method="post" action="<?= BASE_URL ?>/index.php?page=login_google" style="display:none;">
        <input type="hidden" name="credential" id="googleCredential">
    </form>

    <script>
        function gererReponseGoogle(reponse) {
            document.getElementById('googleCredential').value = reponse.credential;
            document.getElementById('formGoogle').submit();
        }
    </script>

    <p>Pas encore de compte ? <a href="<?= BASE_URL ?>/index.php?page=inscription">Créer un compte</a></p>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
