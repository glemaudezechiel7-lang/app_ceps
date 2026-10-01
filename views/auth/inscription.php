<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section ceps-form-page">
    <h2>Créer un compte CEPS</h2>

    <form method="post" action="<?= BASE_URL ?>/index.php?page=inscription_traiter" class="ceps-form">
        <?= csrf_field() ?>
        <label>Nom
            <input type="text" name="nom" required>
        </label>
        <label>Prénom
            <input type="text" name="prenom" required>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Mot de passe (8 caractères minimum)
            <input type="password" name="mot_de_passe" minlength="8" required>
        </label>
        <label>Confirmation du mot de passe
            <input type="password" name="confirmation" minlength="8" required>
        </label>
        <button type="submit" class="ceps-btn ceps-btn-large">Créer mon compte</button>
    </form>

    <p>Déjà inscrit ? <a href="<?= BASE_URL ?>/index.php?page=login">Se connecter</a></p>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
