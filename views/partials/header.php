<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(NOM_ENTREPRISE) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Bootstrap CSS : nécessaire pour que le carrousel (et toute autre classe "carousel-*") fonctionne -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
<header class="ceps-header">
    <div class="ceps-container ceps-nav">
        <a href="<?= BASE_URL ?>/index.php?page=accueil" class="ceps-logo">
            <img src="<?= BASE_URL ?>/assets/images/logo_ceps.jpeg" alt="Logo CEPS" class="ceps-logo-img">
            CEPS
        </a>

        <!-- Barre de recherche universelle par nom de produit -->
        <form method="get" action="<?= BASE_URL ?>/index.php" class="ceps-recherche">
            <input type="hidden" name="page" value="recherche_produit">
            <input type="search" name="q" placeholder="Rechercher un produit..." required>
            <button type="submit"><i class="bi bi-search"></i></button>
        </form>

       <button type="button" 
        class="ceps-menu-toggle d-lg-none" 
        onclick="ouvrirMenu()" 
        aria-label="Menu">
    <i class="bi bi-list"></i>
</button>

        <nav class="ceps-menu" id="cepsMenu">
            <?php
            // Détecte la page active pour lui appliquer la couleur rose (surligne aussi les variantes avec paramètre)
            $pageActive = $_GET['page'] ?? 'accueil';
            $estActif = fn(string $page) => $pageActive === $page ? ' class="active"' : '';
            ?>
            <a href="<?= BASE_URL ?>/index.php?page=accueil"<?= $estActif('accueil') ?>>Accueil</a>
            <a href="<?= BASE_URL ?>/index.php?page=galerie"<?= $estActif('galerie') ?>>Produits</a>
            <a href="<?= BASE_URL ?>/index.php?page=galerie&type=cosmetique"<?= ($pageActive === 'galerie' && ($_GET['type'] ?? '') === 'cosmetique') ? ' class="active"' : '' ?>>Cosmétiques</a>
            <a href="<?= BASE_URL ?>/index.php?page=galerie&type=pharmaceutique"<?= ($pageActive === 'galerie' && ($_GET['type'] ?? '') === 'pharmaceutique') ? ' class="active"' : '' ?>>Médicaments</a>
            <a href="<?= BASE_URL ?>/index.php?page=apropos"<?= $estActif('apropos') ?>>À propos</a>
            <a href="<?= BASE_URL ?>/index.php?page=contact"<?= $estActif('contact') ?>>Contact</a>
            <a href="<?= BASE_URL ?>/index.php?page=panier"<?= $estActif('panier') ?>> Panier<?php $n = count($_SESSION['panier'] ?? []); if ($n > 0): ?> (<?= $n ?>)<?php endif; ?></a>

            <?php if (utilisateur_connecte() && role_utilisateur() === 'client'): ?>
                <a href="<?= BASE_URL ?>/index.php?page=client_historique"<?= $estActif('client_historique') ?>>Historique</a>
                <a href="<?= BASE_URL ?>/index.php?page=client_chat"<?= $estActif('client_chat') ?>> Chat</a>
                <a href="<?= BASE_URL ?>/index.php?page=client_profil"<?= $estActif('client_profil') ?>>Mon profil</a>
                <a href="<?= BASE_URL ?>/index.php?page=deconnexion">Déconnexion</a>
            <?php elseif (utilisateur_connecte() && role_utilisateur() === 'admin'): ?>
                <a href="<?= BASE_URL ?>/index.php?page=admin_dashboard"<?= $estActif('admin_dashboard') ?>>Tableau de bord</a>
                <a href="<?= BASE_URL ?>/index.php?page=deconnexion">Déconnexion</a>
            <?php elseif (utilisateur_connecte() && role_utilisateur() === 'caissier'): ?>
                <a href="<?= BASE_URL ?>/index.php?page=caissier_dashboard"<?= $estActif('caissier_dashboard') ?>>Caisse</a>
                <a href="<?= BASE_URL ?>/index.php?page=deconnexion">Déconnexion</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/index.php?page=login"<?= $estActif('login') ?>>Connexion</a>
                <a href="<?= BASE_URL ?>/index.php?page=inscription" class="ceps-btn-nav<?= $pageActive === 'inscription' ? ' active' : '' ?>">Créer un compte</a>
            <?php endif; ?>
            <button type="button" 
            id="btnFermerMenu"
            class="ceps-menu-fermer d-none d-lg-none" 
            onclick="fermerMenu()" 
            aria-label="Fermer le menu">
        <i class="bi bi-x-lg"></i>
    </button>
        </nav>
       
    </div>
</header>

<?php
/* --- Bouton retour + fil d'Ariane, générés automatiquement sur toutes les pages sauf l'accueil --- */
$pageActuelle = $_GET['page'] ?? 'accueil';
$libellesPages = [
    'galerie' => 'Produits', 'recherche_produit' => 'Résultats de recherche', 'apropos' => 'À propos',
    'contact' => 'Contact', 'panier' => 'Panier', 'login' => 'Connexion', 'inscription' => 'Inscription',
    'client_profil' => 'Mon profil', 'client_historique' => 'Mon historique', 'client_chat' => 'Chat',
    'admin_dashboard' => 'Tableau de bord', 'admin_stock' => 'Gestion du stock', 'admin_commandes' => 'Commandes',
    'admin_chat' => 'Conversations', 'admin_utilisateurs' => 'Utilisateurs', 'admin_commentaires' => 'Commentaires',
    'caissier_dashboard' => 'Caisse',
];
if ($pageActuelle !== 'accueil'):
?>
<div class="ceps-container ceps-fil-ariane">
    <button type="button" class="ceps-btn-retour" onclick="history.back()">← Retour</button>
    <span class="ceps-breadcrumb">
        <a href="<?= BASE_URL ?>/index.php?page=accueil">Accueil</a>
        <?php if (isset($libellesPages[$pageActuelle])): ?>
            / <?= e($libellesPages[$pageActuelle]) ?>
        <?php endif; ?>
    </span>
</div>
<?php endif; ?>

<main class="ceps-container">
    <?php if ($msg = flash('succes')): ?>
        <div class="ceps-alert ceps-alert-succes"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('erreur')): ?>
        <div class="ceps-alert ceps-alert-erreur"><?= e($msg) ?></div>
    <?php endif; ?>

<?php
/* --- Bouton flottant du chat : visible pour client/admin connectés (pas pour le caissier, hors périmètre) --- */
if (utilisateur_connecte() && in_array(role_utilisateur(), ['client', 'admin'], true)):
    $lienChat = role_utilisateur() === 'admin' ? 'index.php?page=admin_chat' : 'index.php?page=client_chat';
?>
<a href="<?= BASE_URL ?>/<?= $lienChat ?>" class="ceps-chat-flottant" title="Discuter avec CEPS">
    <span class="ceps-chat-flottant-cercle">
        <img src="<?= BASE_URL ?>/assets/images/logo_ceps.jpeg" alt="Chat CEPS">
    </span>
    <span class="ceps-chat-flottant-label">Chat</span>
</a>
<?php endif; ?>
 <script>
    function ouvrirMenu() {
  document.getElementById('cepsMenu').classList.add('ouvert');
  document.getElementById('btnFermerMenu').classList.remove('d-none'); // affiche le bouton fermer
}

function fermerMenu() {
  document.getElementById('cepsMenu').classList.remove('ouvert');
  document.getElementById('btnFermerMenu').classList.add('d-none'); // cache le bouton fermer
}

 </script>