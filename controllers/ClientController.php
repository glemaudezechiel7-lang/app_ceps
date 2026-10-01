<?php
/**
 * CEPS - ClientController : espace personnel du client
 */

class ClientController
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

    public function afficherProfil(): void
    {
        exiger_client();
        $profil = $this->userModel->profilClient((int)$_SESSION['id_utilisateur']);
        require __DIR__ . '/../views/client/profil.php';
    }

    /** Client : modifie nom/prénom/téléphone/photo */
    public function modifierProfil(): void
    {
        exiger_client();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=client_profil');
        }

        $photo = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photo = uploader_image($_FILES['photo'], UPLOAD_PROFILS);
        }

        $this->userModel->modifierProfil(
            (int)$_SESSION['id_utilisateur'],
            trim($_POST['nom'] ?? ''),
            trim($_POST['prenom'] ?? ''),
            trim($_POST['telephone'] ?? ''),
            $photo
        );

        flash('succes', 'Profil mis à jour.');
        redirect('index.php?page=client_profil');
    }

    /** Client : change son mot de passe */
    public function changerMotDePasse(): void
    {
        exiger_client();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=client_profil');
        }

        $ancien   = $_POST['ancien_mot_de_passe'] ?? '';
        $nouveau  = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirm  = $_POST['confirmation'] ?? '';

        // On récupère le hash actuel via une requête directe pour vérifier l'ancien mot de passe
        $stmt = Database::getConnection()->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $_SESSION['id_utilisateur']]);
        $hashActuel = $stmt->fetch()['mot_de_passe'] ?? '';

        if (!password_verify($ancien, $hashActuel) || strlen($nouveau) < 8 || $nouveau !== $confirm) {
            flash('erreur', 'Vérifiez votre ancien mot de passe et la confirmation (8 caractères min).');
            redirect('index.php?page=client_profil');
        }

        $this->userModel->modifierMotDePasse((int)$_SESSION['id_utilisateur'], hasher_mot_de_passe($nouveau));
        flash('succes', 'Mot de passe modifié.');
        redirect('index.php?page=client_profil');
    }

    /** Client : enregistre son consentement + coordonnées de localisation (pour livraison) */
    public function enregistrerLocalisation(): void
    {
        exiger_client();
        header('Content-Type: application/json');
        $latitude  = (float)($_POST['latitude'] ?? 0);
        $longitude = (float)($_POST['longitude'] ?? 0);
        $ok = $this->userModel->enregistrerLocalisation((int)$_SESSION['id_utilisateur'], $latitude, $longitude);
        echo json_encode(['succes' => $ok]);
    }

    /** Passage d'une commande à partir du panier en session */
    public function passerCommande(): void
    {
        exiger_client();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=galerie');
        }
        redirect('index.php?page=client_paiement');
    }

    /** Affiche la page de paiement (MonCash/NatCash/BNC) avant de passer commande */
    public function afficherPaiement(): void
    {
        exiger_client();
        if (empty($_SESSION['panier'])) {
            flash('erreur', 'Votre panier est vide.');
            redirect('index.php?page=galerie');
        }
        require __DIR__ . '/../views/client/paiement.php';
    }

    /** Choix 1 : le client envoie sa commande SANS payer, pour négocier avec l'admin d'abord */
    public function commanderSansPaiement(): void
    {
        exiger_client();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=panier');
        }
        // Sécurité serveur : la négociation exige au moins 10 produits différents OU 10 articles au total,
        // même si le client contourne le formulaire (le bouton est normalement masqué côté vue sinon).
        if (!panier_eligible_negociation($_SESSION['panier'] ?? [])) {
            flash('erreur', 'La négociation nécessite au moins 10 produits différents, ou 10 articles au total.');
            redirect('index.php?page=client_paiement');
        }
        $this->creerCommandeEtNotifier('non_paye', "🧾 Nouvelle commande envoyée (sans paiement, négociation) pour un total de");
    }

    /** Choix 2 : le client a payé et envoie une preuve (photo) dans le chat, à vérifier par l'admin */
    public function commanderAvecPreuve(): void
    {
        exiger_client();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=panier');
        }

        $mode = in_array($_POST['mode_paiement'] ?? '', ['moncash', 'natcash', 'bnc'], true) ? $_POST['mode_paiement'] : 'non_paye';

        if (empty($_FILES['preuve']['tmp_name'])) {
            flash('erreur', 'Merci de joindre une photo de la preuve de paiement (capture ou reçu).');
            redirect('index.php?page=client_paiement');
        }
        $nomFichier = uploader_image($_FILES['preuve'], UPLOAD_PREUVES);
        if (!$nomFichier) {
            flash('erreur', "La preuve n'a pas pu être enregistrée (format non supporté ou fichier trop lourd).");
            redirect('index.php?page=client_paiement');
        }

        $this->creerCommandeEtNotifier(
            $mode,
            "💳 Paiement effectué via " . strtoupper($mode) . ". Preuve envoyée pour un total de",
            $nomFichier
        );
    }

    /** Création de la commande + message automatique dans le chat, factorisé pour les deux parcours */
    private function creerCommandeEtNotifier(string $modePaiement, string $prefixeMessage, ?string $preuve = null): void
    {
        $panier = $_SESSION['panier'] ?? [];
        if (empty($panier)) {
            flash('erreur', 'Votre panier est vide.');
            redirect('index.php?page=galerie');
        }

        try {
            $this->commandeModel->creer((int)$_SESSION['id_utilisateur'], $panier, $modePaiement);

            $total = array_sum(array_map(fn($l) => $l['quantite'] * $l['prix'], $panier));
            $idConversation = $this->chatModel->obtenirConversation((int)$_SESSION['id_utilisateur']);
            $this->chatModel->envoyerMessage(
                $idConversation, 'system',
                $prefixeMessage . ' ' . number_format($total, 2) . " $. En attente de vérification par un administrateur CEPS.",
                $preuve
            );

            $_SESSION['panier'] = [];
            flash('succes', 'Commande envoyée ! Suivez son statut et échangez avec CEPS via le chat.');
            redirect('index.php?page=client_historique');
        } catch (RuntimeException $e) {
            flash('erreur', 'Stock insuffisant pour un des produits de votre panier. Ajustez les quantités.');
            redirect('index.php?page=panier');
        }
    }

    /** Affiche la page de dépôt (acompte) pour UNE commande dont l'admin a proposé un nouveau prix */
    public function afficherDepot(): void
    {
        exiger_client();
        $idCommande = (int)($_GET['id'] ?? 0);
        $infos = $this->commandeModel->informationsCommande($idCommande);

        if (!$infos || (int)$infos['id_utilisateur'] !== (int)$_SESSION['id_utilisateur'] || $infos['statut'] !== 'modifiee_adm') {
            flash('erreur', 'Le dépôt n\'est disponible que pour une commande dont le prix a été modifié par un administrateur, en attente d\'approbation.');
            redirect('index.php?page=client_historique');
        }

        $details = $this->commandeModel->detailsCommande($idCommande);
        require __DIR__ . '/../views/client/commande_depot.php'; // $idCommande, $infos, $details disponibles
    }

    /** Enregistre le dépôt (acompte) envoyé par le client, avec preuve photo */
    public function enregistrerDepot(): void
    {
        exiger_client();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=client_historique');
        }

        $idCommande = (int)($_POST['id_commande'] ?? 0);
        $montant    = (float)($_POST['montant'] ?? 0);
        $mode       = in_array($_POST['mode_paiement'] ?? '', ['moncash', 'natcash', 'bnc'], true) ? $_POST['mode_paiement'] : '';

        $infos = $this->commandeModel->informationsCommande($idCommande);
        if (!$infos || (int)$infos['id_utilisateur'] !== (int)$_SESSION['id_utilisateur']) {
            flash('erreur', 'Commande introuvable.');
            redirect('index.php?page=client_historique');
        }

        if ($montant <= 0 || $montant > (float)$infos['montant_total']) {
            flash('erreur', 'Montant du dépôt invalide (il doit être supérieur à 0 et ne pas dépasser le total de la commande).');
            redirect('index.php?page=client_commande_depot&id=' . $idCommande);
        }
        if ($mode === '') {
            flash('erreur', 'Merci de choisir un moyen de paiement.');
            redirect('index.php?page=client_commande_depot&id=' . $idCommande);
        }
        if (empty($_FILES['preuve']['tmp_name'])) {
            flash('erreur', 'Merci de joindre une photo de la preuve de votre dépôt.');
            redirect('index.php?page=client_commande_depot&id=' . $idCommande);
        }

        $nomFichier = uploader_image($_FILES['preuve'], UPLOAD_PREUVES);
        if (!$nomFichier) {
            flash('erreur', "La preuve n'a pas pu être enregistrée (format non supporté ou fichier trop lourd).");
            redirect('index.php?page=client_commande_depot&id=' . $idCommande);
        }

        $ok = $this->commandeModel->enregistrerAcompte(
            $idCommande, (int)$_SESSION['id_utilisateur'], $montant, $mode, $nomFichier
        );

        if ($ok) {
            $idConversation = $this->chatModel->obtenirConversation((int)$_SESSION['id_utilisateur']);
            $this->chatModel->envoyerMessage(
                $idConversation, 'system',
                "💰 Dépôt de " . number_format($montant, 2) . " $ envoyé via " . strtoupper($mode)
                . " pour la commande #" . $idCommande . ". En attente de vérification par un administrateur CEPS.",
                $nomFichier
            );
            flash('succes', 'Dépôt envoyé avec succès. Un administrateur va le vérifier avant d\'approuver votre commande.');
        } else {
            flash('erreur', 'Impossible d\'enregistrer le dépôt : la commande a peut-être déjà été approuvée ou refusée.');
        }

        redirect('index.php?page=client_historique');
    }

    public function afficherHistorique(): void
    {
        exiger_client();
        $historique = $this->commandeModel->historiqueClient((int)$_SESSION['id_utilisateur']);
        require __DIR__ . '/../views/client/historique.php';
    }

    public function afficherChat(): void
    {
        exiger_client();
        $idConversation = $this->chatModel->obtenirConversation((int)$_SESSION['id_utilisateur']);
        $messages = $this->chatModel->messagesConversation($idConversation);
        $this->chatModel->marquerCommeLu($idConversation, 'admin');
        require __DIR__ . '/../views/client/chat.php';
    }

    public function envoyerMessageChat(): void
    {
        exiger_client();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=client_chat');
        }
        $contenu = trim($_POST['contenu'] ?? '');
        if ($contenu !== '') {
            $idConversation = $this->chatModel->obtenirConversation((int)$_SESSION['id_utilisateur']);
            $this->chatModel->envoyerMessage($idConversation, 'client', $contenu);
        }
        redirect('index.php?page=client_chat');
    }

    /** Client : envoi d'un commentaire/avis */
    public function envoyerCommentaire(): void
    {
        exiger_client();
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=client_profil');
        }
        $texte = trim($_POST['texte'] ?? '');
        $note  = (int)($_POST['note'] ?? 5);
        if ($texte !== '') {
            $this->commentaireModel->ajouter((int)$_SESSION['id_utilisateur'], $texte, $note);
            flash('succes', 'Merci pour votre commentaire !');
        }
        redirect('index.php?page=client_profil');
    }
}
