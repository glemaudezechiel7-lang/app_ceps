<?php
/**
 * CEPS - UserModel : accès sécurisé à la table utilisateurs
 * Toutes les requêtes utilisent des requêtes préparées (anti injection SQL)
 */

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Recherche un utilisateur par email (pour le login) */
    public function trouverParEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nom_role FROM utilisateurs u
             INNER JOIN roles r ON u.id_role = r.id_role
             WHERE u.email = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $resultat = $stmt->fetch();
        return $resultat ?: null;
    }

    /** Création automatique d'un client via la procédure stockée sp_creer_client */
    public function creerClient(string $nom, string $prenom, string $email, string $motDePasseHash, string $origine = 'formulaire', ?string $googleId = null): int
    {
        $stmt = $this->db->prepare("CALL sp_creer_client(:nom, :prenom, :email, :mdp, :origine, :google_id)");
        $stmt->execute([
            'nom'       => $nom,
            'prenom'    => $prenom,
            'email'     => $email,
            'mdp'       => $motDePasseHash,
            'origine'   => $origine,
            'google_id' => $googleId,
        ]);
        $ligne = $stmt->fetch();
        $stmt->closeCursor();
        return (int)($ligne['id_utilisateur_cree'] ?? 0);
    }

    /** Recherche ou crée un client connecté via Google (Sign in with Google) */
    public function trouverOuCreerParGoogle(string $googleId, string $email, string $nom, string $prenom): array
    {
        $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE google_id = :gid OR email = :email LIMIT 1");
        $stmt->execute(['gid' => $googleId, 'email' => $email]);
        $utilisateur = $stmt->fetch();

        if ($utilisateur) {
            return $utilisateur;
        }

        // Mot de passe aléatoire (inutilisé pour connexion Google, mais colonne NOT NULL)
        $motDePasseAleatoire = hasher_mot_de_passe(bin2hex(random_bytes(16)));
        $id = $this->creerClient($nom, $prenom, $email, $motDePasseAleatoire, 'google', $googleId);
        return $this->trouverParEmail($email);
    }

    /** Gère une tentative de connexion (succès ou échec) via la procédure stockée */
    public function enregistrerTentative(int $idUtilisateur, bool $succes): void
    {
        $stmt = $this->db->prepare("CALL sp_verifier_tentative_connexion(:id, :succes)");
        $stmt->execute(['id' => $idUtilisateur, 'succes' => $succes ? 1 : 0]);
    }

    /** Débloque un compte (action admin) */
    public function debloquerCompte(int $idUtilisateur): void
    {
        $stmt = $this->db->prepare("CALL sp_debloquer_compte(:id)");
        $stmt->execute(['id' => $idUtilisateur]);
    }

    /** Historique de connexion : insertion */
    public function enregistrerHistorique(int $idUtilisateur, string $statut): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO historique_connexion (id_utilisateur, adresse_ip, statut)
             VALUES (:id, :ip, :statut)"
        );
        $stmt->execute([
            'id'     => $idUtilisateur,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'inconnu',
            'statut' => $statut,
        ]);
    }

    /** Met à jour les statistiques de fréquentation quotidienne (nb connexions) */
    public function majStatistiquesJour(int $idUtilisateur): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO statistiques_frequentation (id_utilisateur, date_jour, nb_connexions)
             VALUES (:id, CURDATE(), 1)
             ON DUPLICATE KEY UPDATE nb_connexions = nb_connexions + 1"
        );
        $stmt->execute(['id' => $idUtilisateur]);
    }

    /** Ajoute la durée de session (en secondes) au moment de la déconnexion */
    public function ajouterDureeSession(int $idUtilisateur, int $secondes): void
    {
        $stmt = $this->db->prepare(
            "UPDATE statistiques_frequentation SET duree_totale_secondes = duree_totale_secondes + :sec
             WHERE id_utilisateur = :id AND date_jour = CURDATE()"
        );
        $stmt->execute(['sec' => $secondes, 'id' => $idUtilisateur]);
    }

    /** Client : consultation de son propre profil via la vue sécurisée */
    public function profilClient(int $idUtilisateur): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vue_client_profil WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $idUtilisateur]);
        return $stmt->fetch() ?: null;
    }

    /** Client : modification de ses propres données personnelles */
    public function modifierProfil(int $idUtilisateur, string $nom, string $prenom, string $telephone, ?string $photo = null): bool
    {
        if ($photo) {
            $stmt = $this->db->prepare(
                "UPDATE utilisateurs SET nom = :nom, prenom = :prenom, telephone = :tel, photo_profil = :photo WHERE id_utilisateur = :id"
            );
            return $stmt->execute(['nom' => $nom, 'prenom' => $prenom, 'tel' => $telephone, 'photo' => $photo, 'id' => $idUtilisateur]);
        }
        $stmt = $this->db->prepare(
            "UPDATE utilisateurs SET nom = :nom, prenom = :prenom, telephone = :tel WHERE id_utilisateur = :id"
        );
        return $stmt->execute(['nom' => $nom, 'prenom' => $prenom, 'tel' => $telephone, 'id' => $idUtilisateur]);
    }

    /** Modification du mot de passe (client ou admin, sur son propre compte) */
    public function modifierMotDePasse(int $idUtilisateur, string $nouveauHash): bool
    {
        $stmt = $this->db->prepare("UPDATE utilisateurs SET mot_de_passe = :mdp WHERE id_utilisateur = :id");
        return $stmt->execute(['mdp' => $nouveauHash, 'id' => $idUtilisateur]);
    }

    /** Enregistre le consentement de localisation + coordonnées GPS */
    public function enregistrerLocalisation(int $idUtilisateur, float $latitude, float $longitude): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE utilisateurs SET consentement_localisation = 1, latitude = :lat, longitude = :lng WHERE id_utilisateur = :id"
        );
        return $stmt->execute(['lat' => $latitude, 'lng' => $longitude, 'id' => $idUtilisateur]);
    }

    /** ADMIN : liste de tous les utilisateurs avec leur rôle (inner join),
     *  avec filtre optionnel de recherche par nom ou email */
    public function listerTousLesUtilisateurs(?string $terme = null): array
    {
        if ($terme) {
            $stmt = $this->db->prepare(
                "SELECT * FROM vue_admin_utilisateurs
                 WHERE nom LIKE :terme OR prenom LIKE :terme OR email LIKE :terme
                 ORDER BY date_inscription DESC"
            );
            $stmt->execute(['terme' => '%' . $terme . '%']);
            return $stmt->fetchAll();
        }
        $stmt = $this->db->query("SELECT * FROM vue_admin_utilisateurs ORDER BY date_inscription DESC");
        return $stmt->fetchAll();
    }

    /** ADMIN : crée un compte caissier (accès direct au module de vente en magasin) */
    public function creerCaissier(string $nom, string $prenom, string $email, string $motDePasseHash): bool
    {
        $stmt = $this->db->prepare("SELECT id_role FROM roles WHERE nom_role = 'caissier'");
        $idRole = $stmt->execute() ? $stmt->fetchColumn() : null;
        if (!$idRole) {
            return false; // le rôle "caissier" n'existe pas encore (script SQL non exécuté)
        }

        $insert = $this->db->prepare(
            "INSERT INTO utilisateurs (id_role, nom, prenom, email, mot_de_passe, origine_inscription)
             VALUES (:role, :nom, :prenom, :email, :mdp, 'formulaire')"
        );
        return $insert->execute([
            'role' => $idRole, 'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'mdp' => $motDePasseHash,
        ]);
    }

    /** ADMIN : liste des comptes caissiers existants */
    public function listerCaissiers(): array
    {
        $stmt = $this->db->query(
            "SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.date_inscription
             FROM utilisateurs u INNER JOIN roles r ON u.id_role = r.id_role
             WHERE r.nom_role = 'caissier' ORDER BY u.date_inscription DESC"
        );
        return $stmt->fetchAll();
    }
}
