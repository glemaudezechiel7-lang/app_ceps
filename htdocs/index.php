<?php
/**
 * CEPS - Front Controller (routeur unique de l'application)
 * Toutes les requêtes passent par ce fichier : index.php?page=...
 */

require_once __DIR__ . '/../config/config.php';

// Chargement des modèles
foreach (glob(__DIR__ . '/../models/*.php') as $fichier) {
    require_once $fichier;
}
// Chargement des contrôleurs
foreach (glob(__DIR__ . '/../controllers/*.php') as $fichier) {
    require_once $fichier;
}

$page = $_GET['page'] ?? 'accueil';

switch ($page) {

    // ---------- Pages publiques ----------
    case 'accueil':
        (new PageController())->accueil();
        break;
    case 'galerie':
        (new PageController())->galerie();
        break;
    case 'produit_detail':
        (new PageController())->detailProduit();
        break;
    case 'recherche_produit':
        (new PageController())->rechercherProduits();
        break;
    case 'apropos':
        (new PageController())->apropos();
        break;
    case 'contact':
        (new PageController())->contact();
        break;
    case 'panier':
        (new PageController())->afficherPanier();
        break;
    case 'panier_ajouter':
        (new PageController())->ajouterAuPanier();
        break;
    case 'panier_retirer':
        (new PageController())->retirerDuPanier();
        break;

    // ---------- Authentification ----------
    case 'login':
        (new AuthController())->afficherLogin();
        break;
    case 'login_traiter':
        (new AuthController())->connecter();
        break;
    case 'login_google':
        (new AuthController())->connecterAvecGoogle();
        break;
    case 'inscription':
        (new AuthController())->afficherInscription();
        break;
    case 'inscription_traiter':
        (new AuthController())->inscrire();
        break;
    case 'deconnexion':
        (new AuthController())->deconnecter();
        break;

    // ---------- Espace client ----------
    case 'client_profil':
        (new ClientController())->afficherProfil();
        break;
    case 'client_profil_modifier':
        (new ClientController())->modifierProfil();
        break;
    case 'client_mdp_modifier':
        (new ClientController())->changerMotDePasse();
        break;
    case 'client_localisation':
        (new ClientController())->enregistrerLocalisation();
        break;
    case 'client_commander':
        (new ClientController())->passerCommande();
        break;
    case 'client_paiement':
        (new ClientController())->afficherPaiement();
        break;
    case 'client_commander_sans_paiement':
        (new ClientController())->commanderSansPaiement();
        break;
    case 'client_commander_preuve':
        (new ClientController())->commanderAvecPreuve();
        break;
    case 'client_historique':
        (new ClientController())->afficherHistorique();
        break;
    case 'client_commande_depot':
        (new ClientController())->afficherDepot();
        break;
    case 'client_commande_depot_enregistrer':
        (new ClientController())->enregistrerDepot();
        break;
    case 'client_chat':
        (new ClientController())->afficherChat();
        break;
    case 'client_chat_envoyer':
        (new ClientController())->envoyerMessageChat();
        break;
    case 'client_commentaire':
        (new ClientController())->envoyerCommentaire();
        break;

    // ---------- Espace caissier ----------
    case 'caissier_dashboard':
        (new CaissierController())->tableauDeBord();
        break;
    case 'caissier_panier_ajouter':
        (new CaissierController())->ajouterAuPanier();
        break;
    case 'caissier_panier_retirer':
        (new CaissierController())->retirerDuPanier();
        break;
    case 'caissier_valider':
        (new CaissierController())->validerVente();
        break;
    case 'caissier_commande_recu':
        (new CaissierController())->recuVente();
        break;

    // ---------- Espace admin ----------
    case 'admin_dashboard':
        (new AdminController())->tableauDeBord();
        break;
    case 'admin_stock':
        (new AdminController())->afficherStock();
        break;
    case 'admin_produit_ajouter':
        (new AdminController())->ajouterProduit();
        break;
    case 'admin_produit_modifier':
        (new AdminController())->modifierProduit();
        break;
    case 'admin_produit_supprimer':
        (new AdminController())->supprimerProduit();
        break;
    case 'admin_commandes':
        (new AdminController())->afficherCommandes();
        break;
    case 'admin_commande_details':
        (new AdminController())->detailsCommande();
        break;
    case 'admin_commande_recu':
        (new AdminController())->recuVente();
        break;
    case 'admin_historique_ventes':
        (new AdminController())->historiqueVentes();
        break;
    case 'admin_commande_prix':
        (new AdminController())->modifierPrixCommande();
        break;
    case 'admin_commande_approuver':
        (new AdminController())->approuverCommande();
        break;
    case 'admin_commande_refuser':
        (new AdminController())->refuserCommande();
        break;
    case 'admin_chat':
        (new AdminController())->listerConversations();
        break;
    case 'admin_chat_conversation':
        (new AdminController())->afficherConversation();
        break;
    case 'admin_chat_repondre':
        (new AdminController())->repondreChat();
        break;
    case 'admin_utilisateurs':
        (new AdminController())->afficherUtilisateurs();
        break;
    case 'admin_caissier_creer':
        (new AdminController())->creerCaissier();
        break;
    case 'admin_chat_demarrer':
        (new AdminController())->demarrerChat();
        break;
    case 'admin_utilisateur_debloquer':
        (new AdminController())->debloquerUtilisateur();
        break;
    case 'admin_mdp_modifier':
        (new AdminController())->changerMotDePasse();
        break;
    case 'admin_commentaires':
        (new AdminController())->afficherCommentaires();
        break;
    case 'admin_commentaire_publier':
        (new AdminController())->publierCommentaire();
        break;

    default:
        http_response_code(404);
        echo "Page introuvable.";
}
