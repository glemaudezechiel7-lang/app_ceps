<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Mon profil</h2>

    <div class="ceps-profil-photo">
        <?php if (!empty($profil['photo_profil'])): ?>
            <img src="<?= BASE_URL ?>/uploads/profils/<?= e($profil['photo_profil']) ?>" alt="Photo de profil">
        <?php else: ?>
            <div class="ceps-avatar-defaut">👤</div>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= BASE_URL ?>/index.php?page=client_profil_modifier" enctype="multipart/form-data" class="ceps-form">
        <?= csrf_field() ?>
        <label>Nom
            <input type="text" name="nom" value="<?= e($profil['nom'] ?? '') ?>" required>
        </label>
        <label>Prénom
            <input type="text" name="prenom" value="<?= e($profil['prenom'] ?? '') ?>" required>
        </label>
        <label>Téléphone
            <input type="text" name="telephone" value="<?= e($profil['telephone'] ?? '') ?>">
        </label>
        <label>Photo de profil (facultatif)
            <input type="file" name="photo" accept="image/*">
        </label>
        <button type="submit" class="ceps-btn">Enregistrer</button>
    </form>

    <h3>Changer mon mot de passe</h3>
    <form method="post" action="<?= BASE_URL ?>/index.php?page=client_mdp_modifier" class="ceps-form" onsubmit="return confirm('Confirmer le changement de mot de passe ?')">
        <?= csrf_field() ?>
        <label>Ancien mot de passe
            <input type="password" name="ancien_mot_de_passe" required>
        </label>
        <label>Nouveau mot de passe
            <input type="password" name="nouveau_mot_de_passe" minlength="8" required>
        </label>
        <label>Confirmation
            <input type="password" name="confirmation" minlength="8" required>
        </label>
        <button type="submit" class="ceps-btn">Modifier le mot de passe</button>
    </form>

    <h3>Localisation pour livraison</h3>
    <p>Autorisez CEPS à accéder à votre position pour une meilleure livraison.</p>
    <button type="button" class="ceps-btn" onclick="demanderLocalisation()">Partager ma position</button>
    <p id="statutLocalisation"></p>

    <h3>Laisser un commentaire</h3>
    <form method="post" action="<?= BASE_URL ?>/index.php?page=client_commentaire" class="ceps-form">
        <?= csrf_field() ?>
        <label>Votre avis
            <textarea name="texte" rows="3" required></textarea>
        </label>
        <label>Note (1 à 5)
            <input type="number" name="note" min="1" max="5" value="5">
        </label>
        <button type="submit" class="ceps-btn">Envoyer</button>
    </form>
</section>

<script>
function demanderLocalisation() {
    // Les navigateurs bloquent silencieusement la géolocalisation hors HTTPS
    // (seule exception : http://localhost). Sans ce test, l'utilisateur ne voit
    // alors AUCUNE fenêtre de permission et AUCUN message d'erreur.
    const contexteSecurise = window.isSecureContext || location.hostname === 'localhost';
    if (!contexteSecurise) {
        document.getElementById('statutLocalisation').textContent =
            "Votre navigateur bloque la localisation sur cette adresse (" + location.hostname + "). " +
            "Ouvrez le site via http://localhost/... (pas une adresse IP) ou en HTTPS pour que la demande de permission s'affiche.";
        return;
    }

    if (!navigator.geolocation) {
        document.getElementById('statutLocalisation').textContent = "Géolocalisation non supportée par ce navigateur.";
        return;
    }

    document.getElementById('statutLocalisation').textContent = "Demande de position en cours...";

    navigator.geolocation.getCurrentPosition(function (position) {
        fetch('<?= BASE_URL ?>/index.php?page=client_localisation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'latitude=' + position.coords.latitude + '&longitude=' + position.coords.longitude
        }).then(response => response.json()).then(data => {
            document.getElementById('statutLocalisation').textContent = data.succes
                ? "Position enregistrée. Merci !"
                : "Erreur lors de l'enregistrement, réessayez.";
        }).catch(() => {
            document.getElementById('statutLocalisation').textContent = "Erreur réseau, réessayez.";
        });
    }, function (erreur) {
        const messages = {
            1: "Vous avez refusé le partage de position.",
            2: "Position indisponible (GPS/réseau désactivé).",
            3: "Délai dépassé pour obtenir la position."
        };
        document.getElementById('statutLocalisation').textContent = messages[erreur.code] || "Erreur de géolocalisation.";
    });
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
