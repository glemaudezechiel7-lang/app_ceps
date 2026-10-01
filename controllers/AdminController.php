<?php
/**
 * CEPS - AdminController : espace de gestion réservé à l'administrateur CEPS
 */

class AdminController
{
    private UserModel $userModel;
    private ProduitModel $produitModel;
    private CommandeModel $commandeModel;
    private ChatModel $chatModel;
    private CommentaireModel $commentaireModel;

    public function __construct()
    {
        $this->userModel        = new UserModel();
        $this->produitModel     = new ProduitModel();
        $this->commandeModel    = new CommandeModel();
        $this->chatModel        = new ChatModel();
        $this->commentaireModel = new CommentaireModel();
    }

    public function tableauDeBord(): void
    {
        exiger_admin();
        $ventesJour   = $this->commandeModel->ventesParJour();
        $gainDuJour   = $this->commandeModel->gainTotalJour();
        $commandes    = $this->commandeModel->listerEnAttente();
        $utilisateurs = $this->userModel->listerTousLesUtilisateurs();
        $parJour      = $this->commandeModel->totauxParJourPourGraphique(14);
        $parSemaine   = $this->commandeModel->totauxParSemainePourGraphique(12);
        $parMois      = $this->commandeModel->totauxParMoisPourGraphique(12);
        require __DIR__ . '/../views/admin/dashboard.php';
    }

    // ---------------- GESTION DES PRODUITS / STOCK ----------------

    public function afficherStock(): void
    {
        exiger_admin();
        $terme = trim($_GET['recherche'] ?? '');
        $produits = $terme !== '' ? $this->produitModel->rechercher($terme) : $this->produitModel->listerAvecStock();
        $categories = $this->produitModel->listerCategories();
        $ventesJour = $this->commandeModel->ventesParJour();
        $gainDuJour = $this->commandeModel->gainTotalJour();
        require __DIR__ . '/../views/admin/stock.php';
    }

    public function ajouterProduit(): void
    {
        exiger_admin();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=admin_stock');
        }
        $image = !empty($_FILES['image']['tmp_name']) ? uploader_image($_FILES['image'], UPLOAD_PRODUITS) : null;

        $this->produitModel->ajouter(
            (int)$_POST['id_categorie'],
            trim($_POST['nom_produit']),
            trim($_POST['description']),
            (float)$_POST['prix_unitaire'],
            $image,
            (int)$_POST['quantite_initiale']
        );
        flash('succes', 'Produit ajouté avec succès.');
        redirect('index.php?page=admin_stock');
    }

    public function modifierProduit(): void
    {
        exiger_admin();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=admin_stock');
        }
        $image = !empty($_FILES['image']['tmp_name']) ? uploader_image($_FILES['image'], UPLOAD_PRODUITS) : null;

        $this->produitModel->modifier(
            (int)$_POST['id_produit'],
            (int)$_POST['id_categorie'],
            trim($_POST['nom_produit']),
            trim($_POST['description']),
            (float)$_POST['prix_unitaire'],
            $image
        );

        if (isset($_POST['quantite_disponible'])) {
            $this->produitModel->ajusterStock((int)$_POST['id_produit'], (int)$_POST['quantite_disponible']);
        }

        flash('succes', 'Produit mis à jour.');
        redirect('index.php?page=admin_stock');
    }

    /** Suppression d'un produit — action sensible : POST + jeton CSRF obligatoires */
    public function supprimerProduit(): void
    {
        exiger_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifier($_POST['csrf_token'] ?? '')) {
            flash('erreur', 'Requête invalide.');
            redirect('index.php?page=admin_stock');
        }
        $this->produitModel->supprimer((int)($_POST['id'] ?? 0));
        flash('succes', 'Produit supprimé.');
        redirect('index.php?page=admin_stock');
    }

    // ---------------- GESTION DES COMMANDES ----------------

    public function afficherCommandes(): void
    {
        exiger_admin();
        $commandes = $this->commandeModel->listerEnAttente();
        require __DIR__ . '/../views/admin/commandes.php';
    }

    public function detailsCommande(): void
    {
        exiger_admin();
        $idCommande     = (int)($_GET['id'] ?? 0);
        $details        = $this->commandeModel->detailsCommande($idCommande);
        $infosCommande  = $this->commandeModel->informationsCommande($idCommande);
        require __DIR__ . '/../views/admin/commande_details.php'; // $idCommande, $details, $infosCommande disponibles dans la vue
    }

    /** Historique de toutes les fiches de vente approuvées (ventes client + ventes caisse),
     *  toutes réimprimables à tout moment — répond au besoin de tout garder enregistré. */
    public function historiqueVentes(): void
    {
        exiger_admin();
        $ventes = $this->commandeModel->historiqueVentesApprouvees();
        require __DIR__ . '/../views/admin/historique_ventes.php';
    }
    public function recuVente(): void
    {
        exiger_admin();
        $idCommande = (int)($_GET['id'] ?? 0);
        $infos = $this->commandeModel->informationsCommande($idCommande);

        if (!$infos || $infos['statut'] !== 'approuvee') {
            flash('erreur', 'Le reçu n\'est disponible que pour une commande déjà approuvée.');
            redirect('index.php?page=admin_commandes');
        }

        $details = $this->commandeModel->detailsCommande($idCommande);
        require __DIR__ . '/../views/admin/recu_vente.php';
    }

    /** Négociation : l'admin modifie le prix d'une ligne avant approbation */
    public function modifierPrixCommande(): void
    {
        exiger_admin();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=admin_commandes');
        }
        $ok = $this->commandeModel->adminModifierPrix(
            (int)$_POST['id_commande'],
            (int)$_POST['id_produit'],
            (float)$_POST['nouveau_prix']
        );
        flash($ok ? 'succes' : 'erreur', $ok
            ? 'Prix mis à jour, total recalculé automatiquement.'
            : 'Impossible de modifier le prix : la commande est déjà approuvée ou refusée.');
        redirect('index.php?page=admin_commande_details&id=' . (int)$_POST['id_commande']);
    }

    /** Approbation d'une commande — action sensible (diminue le stock) : POST + CSRF obligatoires */
    public function approuverCommande(): void
    {
        exiger_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifier($_POST['csrf_token'] ?? '')) {
            flash('erreur', 'Requête invalide.');
            redirect('index.php?page=admin_commandes');
        }
        $idCommande = (int)($_POST['id'] ?? 0);
        $reussi = $this->commandeModel->approuver($idCommande, (int)$_SESSION['id_utilisateur']);

        if ($reussi) {
            $infos = $this->commandeModel->informationsCommande($idCommande);
            if ($infos) {
                $idConversation = $this->chatModel->obtenirConversation((int)$infos['id_utilisateur']);
                $this->chatModel->envoyerMessage(
                    $idConversation, 'system',
                    "✅ Commande approuvée. Montant total : " . number_format((float)$infos['montant_total'], 2) . " $. Merci pour votre confiance !"
                );
            }
        }

        if (!$reussi) {
            flash('erreur', 'Approbation impossible : stock insuffisant pour un des produits de cette commande.');
            redirect('index.php?page=admin_commandes');
        }

        // Pas de message flash ici : la page de reçu qui suit sert déjà de confirmation visuelle.
        redirect('index.php?page=admin_commande_recu&id=' . $idCommande);
    }

    /** Refus d'une commande — action sensible : POST + CSRF obligatoires */
    public function refuserCommande(): void
    {
        exiger_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifier($_POST['csrf_token'] ?? '')) {
            flash('erreur', 'Requête invalide.');
            redirect('index.php?page=admin_commandes');
        }
        $idCommande = (int)($_POST['id'] ?? 0);
        $infos = $this->commandeModel->informationsCommande($idCommande);

        $this->commandeModel->refuser($idCommande);

        if ($infos) {
            $idConversation = $this->chatModel->obtenirConversation((int)$infos['id_utilisateur']);
            $this->chatModel->envoyerMessage(
                $idConversation, 'system',
                "❌ Commande refusée. Contactez-nous via ce chat pour plus d'informations."
            );
        }

        flash('succes', 'Commande refusée.');
        redirect('index.php?page=admin_commandes');
    }

    // ---------------- CHAT ----------------

    public function listerConversations(): void
    {
        exiger_admin();
        $conversations = $this->chatModel->listerConversationsAdmin();
        require __DIR__ . '/../views/admin/chat_liste.php';
    }

    public function afficherConversation(): void
    {
        exiger_admin();
        $idConversation = (int)($_GET['id'] ?? 0);
        $messages = $this->chatModel->messagesConversation($idConversation);
        $this->chatModel->marquerCommeLu($idConversation, 'client');
        require __DIR__ . '/../views/admin/chat_conversation.php'; // $idConversation et $messages disponibles
    }

    public function repondreChat(): void
    {
        exiger_admin();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=admin_chat');
        }
        $contenu = trim($_POST['contenu'] ?? '');
        $idConversation = (int)$_POST['id_conversation'];
        if ($contenu !== '') {
            $this->chatModel->envoyerMessage($idConversation, 'admin', $contenu);
        }
        redirect('index.php?page=admin_chat_conversation&id=' . $idConversation);
    }

    // ---------------- UTILISATEURS / SÉCURITÉ ----------------

    public function afficherUtilisateurs(): void
    {
        exiger_admin();
        $terme = trim($_GET['recherche'] ?? '');
        $utilisateurs = $this->userModel->listerTousLesUtilisateurs($terme !== '' ? $terme : null);
        $caissiers = $this->userModel->listerCaissiers();
        require __DIR__ . '/../views/admin/utilisateurs.php';
    }

    /** ADMIN : crée un compte caissier */
    public function creerCaissier(): void
    {
        exiger_admin();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=admin_utilisateurs');
        }

        $nom    = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $mdp    = $_POST['mot_de_passe'] ?? '';

        if (!$nom || !$prenom || !$email || strlen($mdp) < 8) {
            flash('erreur', 'Merci de vérifier les informations (mot de passe : 8 caractères minimum).');
            redirect('index.php?page=admin_utilisateurs');
        }
        if ($this->userModel->trouverParEmail($email)) {
            flash('erreur', 'Cet email est déjà utilisé.');
            redirect('index.php?page=admin_utilisateurs');
        }

        $ok = $this->userModel->creerCaissier($nom, $prenom, $email, hasher_mot_de_passe($mdp));
        flash($ok ? 'succes' : 'erreur', $ok
            ? 'Compte caissier créé avec succès.'
            : "Impossible de créer le compte : exécute d'abord le script SQL qui ajoute le rôle \"caissier\".");
        redirect('index.php?page=admin_utilisateurs');
    }

    /** Ouvre (ou crée) directement la conversation d'un client depuis la fiche utilisateur */
    public function demarrerChat(): void
    {
        exiger_admin();
        $idUtilisateur = (int)($_GET['id'] ?? 0);
        $idConversation = $this->chatModel->obtenirConversation($idUtilisateur);
        redirect('index.php?page=admin_chat_conversation&id=' . $idConversation);
    }

    /** Déblocage d'un compte — action sensible : POST + CSRF obligatoires */
    public function debloquerUtilisateur(): void
    {
        exiger_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifier($_POST['csrf_token'] ?? '')) {
            flash('erreur', 'Requête invalide.');
            redirect('index.php?page=admin_utilisateurs');
        }
        $this->userModel->debloquerCompte((int)($_POST['id'] ?? 0));
        flash('succes', 'Compte débloqué.');
        redirect('index.php?page=admin_utilisateurs');
    }

    /** Admin : modifie son propre mot de passe */
    public function changerMotDePasse(): void
    {
        exiger_admin();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=admin_dashboard');
        }
        $stmt = Database::getConnection()->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $_SESSION['id_utilisateur']]);
        $hashActuel = $stmt->fetch()['mot_de_passe'] ?? '';

        $ancien  = $_POST['ancien_mot_de_passe'] ?? '';
        $nouveau = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirm = $_POST['confirmation'] ?? '';

        if (!password_verify($ancien, $hashActuel) || strlen($nouveau) < 8 || $nouveau !== $confirm) {
            flash('erreur', 'Vérifiez votre ancien mot de passe et la confirmation.');
            redirect('index.php?page=admin_dashboard');
        }

        $this->userModel->modifierMotDePasse((int)$_SESSION['id_utilisateur'], hasher_mot_de_passe($nouveau));
        flash('succes', 'Mot de passe administrateur modifié.');
        redirect('index.php?page=admin_dashboard');
    }

    // ---------------- COMMENTAIRES ----------------

    public function afficherCommentaires(): void
    {
        exiger_admin();
        $commentaires = $this->commentaireModel->listerTous();
        require __DIR__ . '/../views/admin/commentaires.php';
    }

    /** Publie un commentaire admin qui apparaît comme message dans la page/chat du client */
    public function publierCommentaire(): void
    {
        exiger_admin();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=admin_commentaires');
        }
        $idUtilisateur = (int)$_POST['id_utilisateur'];
        $reponse = trim($_POST['reponse'] ?? '');

        if ($reponse !== '') {
            $idConversation = $this->chatModel->obtenirConversation($idUtilisateur);
            $this->chatModel->envoyerMessage($idConversation, 'admin', $reponse);
        }
        if (!empty($_POST['id_commentaire'])) {
            $this->commentaireModel->marquerPublie((int)$_POST['id_commentaire']);
        }

        flash('succes', 'Réponse publiée dans le chat du client.');
        redirect('index.php?page=admin_commentaires');
    }
}
