<?php
/**
 * CEPS - AuthController : inscription, connexion, blocage 3 tentatives, Google, déconnexion
 */

class AuthController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /** Affiche le formulaire d'inscription */
    public function afficherInscription(): void
    {
        require __DIR__ . '/../views/auth/inscription.php';
    }

    /** Traite l'inscription d'un nouveau client */
    public function inscrire(): void
    {
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            flash('erreur', 'Session expirée, veuillez réessayer.');
            redirect('index.php?page=inscription');
        }

        $nom     = trim($_POST['nom'] ?? '');
        $prenom  = trim($_POST['prenom'] ?? '');
        $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $mdp     = $_POST['mot_de_passe'] ?? '';
        $confirm = $_POST['confirmation'] ?? '';

        if (!$nom || !$prenom || !$email || strlen($mdp) < 8 || $mdp !== $confirm) {
            flash('erreur', 'Merci de vérifier vos informations (mot de passe : 8 caractères minimum).');
            redirect('index.php?page=inscription');
        }

        if ($this->userModel->trouverParEmail($email)) {
            flash('erreur', 'Cet email est déjà utilisé.');
            redirect('index.php?page=inscription');
        }

        $this->userModel->creerClient($nom, $prenom, $email, hasher_mot_de_passe($mdp));
        flash('succes', 'Compte créé avec succès ! Vous pouvez vous connecter.');
        redirect('index.php?page=login');
    }

    public function afficherLogin(): void
    {
        require __DIR__ . '/../views/auth/login.php';
    }

    /** Traite la connexion, avec blocage automatique après 3 échecs */
    public function connecter(): void
    {
        if (!csrf_verifier($_POST['csrf_token'] ?? '')) {
            flash('erreur', 'Session expirée, veuillez réessayer.');
            redirect('index.php?page=login');
        }

        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $mdp   = $_POST['mot_de_passe'] ?? '';

        $utilisateur = $email ? $this->userModel->trouverParEmail($email) : null;

        if (!$utilisateur) {
            flash('erreur', 'Identifiants incorrects.');
            redirect('index.php?page=login');
        }

        if ((int)$utilisateur['compte_bloque'] === 1) {
            flash('erreur', 'Votre compte est bloqué après plusieurs tentatives échouées. Contactez un administrateur CEPS pour le débloquer.');
            redirect('index.php?page=login');
        }

        if (!password_verify($mdp, $utilisateur['mot_de_passe'])) {
            $this->userModel->enregistrerTentative((int)$utilisateur['id_utilisateur'], false);
            $this->userModel->enregistrerHistorique((int)$utilisateur['id_utilisateur'], 'echec');
            flash('erreur', 'Identifiants incorrects.');
            redirect('index.php?page=login');
        }

        // Connexion réussie
        $this->userModel->enregistrerTentative((int)$utilisateur['id_utilisateur'], true);
        $this->userModel->enregistrerHistorique((int)$utilisateur['id_utilisateur'], 'succes');
        $this->userModel->majStatistiquesJour((int)$utilisateur['id_utilisateur']);

        session_regenerate_id(true); // anti fixation de session

        $_SESSION['id_utilisateur'] = (int)$utilisateur['id_utilisateur'];
        $_SESSION['nom_complet']    = $utilisateur['prenom'] . ' ' . $utilisateur['nom'];
        $_SESSION['role']           = $utilisateur['nom_role'];
        $_SESSION['heure_connexion'] = time();

        $destinations = ['admin' => 'admin_dashboard', 'caissier' => 'caissier_dashboard'];
        redirect('index.php?page=' . ($destinations[$utilisateur['nom_role']] ?? 'accueil'));
    }

    /**
     * Connexion via Google (Sign in with Google - Google Identity Services).
     * Le jeton JWT envoyé par le bouton Google est vérifié côté serveur :
     * - signature/validité via l'endpoint officiel Google
     * - "aud" (audience) DOIT correspondre à notre GOOGLE_CLIENT_ID, sinon un jeton
     *   valide émis pour un autre site pourrait être rejoué contre CEPS (usurpation).
     * - email_verified DOIT être vrai (Google garantit alors la propriété de l'email).
     */
    public function connecterAvecGoogle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=login');
        }

        $credential = $_POST['credential'] ?? '';
        if (!$credential) {
            flash('erreur', 'Connexion Google échouée.');
            redirect('index.php?page=login');
        }

        $reponse = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential));
        $payload = $reponse ? json_decode($reponse, true) : null;

        if (!$payload || empty($payload['email']) || empty($payload['sub'])) {
            flash('erreur', 'Jeton Google invalide.');
            redirect('index.php?page=login');
        }

        // --- Vérifications de sécurité obligatoires ---
        if (!hash_equals(GOOGLE_CLIENT_ID, (string)($payload['aud'] ?? ''))) {
            flash('erreur', 'Connexion Google refusée (application non reconnue).');
            redirect('index.php?page=login');
        }
        if (($payload['email_verified'] ?? 'false') !== 'true' && ($payload['email_verified'] ?? false) !== true) {
            flash('erreur', 'Votre email Google doit être vérifié.');
            redirect('index.php?page=login');
        }

        $utilisateur = $this->userModel->trouverOuCreerParGoogle(
            $payload['sub'],
            $payload['email'],
            $payload['family_name'] ?? 'Client',
            $payload['given_name'] ?? 'Google'
        );

        if ((int)$utilisateur['compte_bloque'] === 1) {
            flash('erreur', 'Votre compte est bloqué. Contactez un administrateur CEPS pour le débloquer.');
            redirect('index.php?page=login');
        }

        $this->userModel->enregistrerHistorique((int)$utilisateur['id_utilisateur'], 'succes');
        $this->userModel->majStatistiquesJour((int)$utilisateur['id_utilisateur']);
        session_regenerate_id(true);

        $_SESSION['id_utilisateur']  = (int)$utilisateur['id_utilisateur'];
        $_SESSION['nom_complet']     = $utilisateur['prenom'] . ' ' . $utilisateur['nom'];
        $_SESSION['role']            = 'client';
        $_SESSION['heure_connexion'] = time();

        redirect('index.php?page=accueil');
    }

    public function deconnecter(): void
    {
        if (utilisateur_connecte()) {
            $duree = time() - ($_SESSION['heure_connexion'] ?? time());
            $this->userModel->ajouterDureeSession((int)$_SESSION['id_utilisateur'], $duree);
        }
        $_SESSION = [];
        session_destroy();
        redirect('index.php?page=accueil');
    }
}
