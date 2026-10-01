<?php
/**
 * CEPS - CommandeModel : commandes, négociation de prix, approbation, ventes
 */

class CommandeModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Création d'une commande par un client (panier = tableau [id_produit => [quantite, prix]]).
     *  Vérifie d'abord que le stock disponible couvre chaque ligne demandée. */
    public function creer(int $idUtilisateur, array $panier, string $modePaiement = 'non_paye'): int
    {
        // Vérification du stock disponible (évite une commande impossible à honorer)
        $stmtStock = $this->db->prepare("SELECT quantite_disponible FROM stock WHERE id_produit = :id");
        foreach ($panier as $idProduit => $ligne) {
            $stmtStock->execute(['id' => $idProduit]);
            $dispo = $stmtStock->fetch();
            if (!$dispo || (int)$dispo['quantite_disponible'] < (int)$ligne['quantite']) {
                throw new RuntimeException('Stock insuffisant pour un des produits du panier.');
            }
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO commandes (id_utilisateur, mode_paiement) VALUES (:id, :mode)");
            $stmt->execute(['id' => $idUtilisateur, 'mode' => $modePaiement]);
            $idCommande = (int)$this->db->lastInsertId();

            $stmtDetail = $this->db->prepare(
                "INSERT INTO commande_details (id_commande, id_produit, quantite, prix_unitaire_negocie)
                 VALUES (:cmd, :prod, :qte, :prix)"
            );
            foreach ($panier as $idProduit => $ligne) {
                $stmtDetail->execute([
                    'cmd'  => $idCommande,
                    'prod' => $idProduit,
                    'qte'  => $ligne['quantite'],
                    'prix' => $ligne['prix'],
                ]);
            }

            $this->db->commit();
            return $idCommande;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Client : modification d'une commande tant qu'elle n'est pas encore approuvée */
    public function clientModifierLigne(int $idCommande, int $idUtilisateur, int $idProduit, int $nouvelleQuantite): bool
    {
        // Vérifie que la commande appartient bien au client ET n'est pas déjà approuvée
        $stmt = $this->db->prepare(
            "SELECT statut FROM commandes WHERE id_commande = :cmd AND id_utilisateur = :user"
        );
        $stmt->execute(['cmd' => $idCommande, 'user' => $idUtilisateur]);
        $commande = $stmt->fetch();

        if (!$commande || $commande['statut'] === 'approuvee') {
            return false; // interdit de modifier une commande déjà approuvée
        }

        $update = $this->db->prepare(
            "UPDATE commande_details SET quantite = :qte WHERE id_commande = :cmd AND id_produit = :prod"
        );
        return $update->execute(['qte' => $nouvelleQuantite, 'cmd' => $idCommande, 'prod' => $idProduit]);
    }

    /** ADMIN : modifie le prix négocié d'une ligne avant approbation (le trigger recalcule le total).
     *  Interdit une fois la commande déjà approuvée (le stock et les ventes ont déjà été enregistrés
     *  avec l'ancien prix — les modifier après coup créerait une incohérence comptable). */
    public function adminModifierPrix(int $idCommande, int $idProduit, float $nouveauPrix): bool
    {
        $stmt = $this->db->prepare("SELECT statut FROM commandes WHERE id_commande = :cmd");
        $stmt->execute(['cmd' => $idCommande]);
        $commande = $stmt->fetch();

        if (!$commande || $commande['statut'] === 'approuvee' || $commande['statut'] === 'refusee') {
            return false;
        }

        $update = $this->db->prepare(
            "UPDATE commande_details SET prix_unitaire_negocie = :prix WHERE id_commande = :cmd AND id_produit = :prod"
        );
        $ok = $update->execute(['prix' => $nouveauPrix, 'cmd' => $idCommande, 'prod' => $idProduit]);

        $this->db->prepare("UPDATE commandes SET statut = 'modifiee_adm' WHERE id_commande = :cmd AND statut = 'en_attente'")
                  ->execute(['cmd' => $idCommande]);

        return $ok;
    }

    /** ADMIN : approuve la commande (le trigger SQL gère stock + ventes automatiquement).
     *  Retourne true si l'approbation a réussi, false si le stock était insuffisant
     *  (la procédure stockée annule alors la transaction via ROLLBACK TO SAVEPOINT). */
    public function approuver(int $idCommande, int $idAdmin): bool
    {
        $stmt = $this->db->prepare("CALL sp_approuver_commande(:cmd, :admin)");
        $stmt->execute(['cmd' => $idCommande, 'admin' => $idAdmin]);
        $ligne = $stmt->fetch();
        $stmt->closeCursor();
        return isset($ligne['message']) && str_contains($ligne['message'], 'succès');
    }

    /** CLIENT : enregistre un dépôt/acompte sur une commande dont l'admin a déjà proposé un nouveau prix
     *  (statut 'modifiee_adm'), et ce AVANT que l'admin n'approuve définitivement.
     *  Refuse si la commande n'appartient pas au client, ou si elle est déjà approuvée/refusée. */
    public function enregistrerAcompte(int $idCommande, int $idUtilisateur, float $montant, string $modePaiement, string $preuve): bool
    {
        $stmt = $this->db->prepare(
            "SELECT statut FROM commandes WHERE id_commande = :cmd AND id_utilisateur = :user"
        );
        $stmt->execute(['cmd' => $idCommande, 'user' => $idUtilisateur]);
        $commande = $stmt->fetch();

        if (!$commande || $commande['statut'] !== 'modifiee_adm') {
            return false; // le dépôt n'est proposé qu'après une modification de prix par l'admin, avant approbation
        }

        $update = $this->db->prepare(
            "UPDATE commandes
             SET montant_acompte = :montant, mode_paiement_acompte = :mode, preuve_acompte = :preuve, date_acompte = NOW()
             WHERE id_commande = :cmd"
        );
        return $update->execute([
            'montant' => $montant, 'mode' => $modePaiement, 'preuve' => $preuve, 'cmd' => $idCommande,
        ]);
    }

    /** ADMIN : refuse une commande */
    public function refuser(int $idCommande): bool
    {
        $stmt = $this->db->prepare("UPDATE commandes SET statut = 'refusee' WHERE id_commande = :cmd");
        return $stmt->execute(['cmd' => $idCommande]);
    }

    /** ADMIN : liste des commandes en attente (avec inner join client) */
    public function listerEnAttente(): array
    {
        return $this->db->query(
            "SELECT c.*, u.nom, u.prenom, u.email FROM commandes c
             INNER JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
             WHERE c.statut IN ('en_attente','modifiee_adm')
             ORDER BY c.date_commande ASC"
        )->fetchAll();
    }

    /** Détails d'une commande (produits, quantités, prix) */
    public function detailsCommande(int $idCommande): array
    {
        $stmt = $this->db->prepare(
            "SELECT cd.*, p.nom_produit FROM commande_details cd
             INNER JOIN produits p ON cd.id_produit = p.id_produit
             WHERE cd.id_commande = :cmd"
        );
        $stmt->execute(['cmd' => $idCommande]);
        return $stmt->fetchAll();
    }

    /** Infos générales d'une commande + son client (utilisées pour le chat auto et le reçu imprimable) */
    public function informationsCommande(int $idCommande): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.nom, u.prenom, u.email, u.telephone
             FROM commandes c INNER JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
             WHERE c.id_commande = :cmd"
        );
        $stmt->execute(['cmd' => $idCommande]);
        return $stmt->fetch() ?: null;
    }

    /** Toutes les fiches de vente approuvées (commandes clients + ventes caisse), les plus récentes
     *  en premier — chaque ligne reste consultable et réimprimable indéfiniment. */
    public function historiqueVentesApprouvees(): array
    {
        return $this->db->query(
            "SELECT c.id_commande, c.montant_total, c.mode_paiement, c.date_approbation, c.date_commande,
                    u.nom, u.prenom, r.nom_role
             FROM commandes c
             INNER JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
             INNER JOIN roles r ON u.id_role = r.id_role
             WHERE c.statut = 'approuvee'
             ORDER BY c.date_approbation DESC"
        )->fetchAll();
    }

    /** Historique des ventes réalisées par UN caissier précis (ses propres fiches réimprimables) */
    public function historiqueVentesCaissier(int $idCaissier): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_commande, montant_total, date_approbation
             FROM commandes WHERE id_utilisateur = :id AND statut = 'approuvee'
             ORDER BY date_approbation DESC"
        );
        $stmt->execute(['id' => $idCaissier]);
        return $stmt->fetchAll();
    }

    /** Historique complet d'un client (vue sécurisée) */
    public function historiqueClient(int $idUtilisateur): array
    {
        $stmt = $this->db->prepare("SELECT * FROM vue_historique_client WHERE id_utilisateur = :id ORDER BY date_commande DESC");
        $stmt->execute(['id' => $idUtilisateur]);
        return $stmt->fetchAll();
    }

    /** ADMIN : ventes agrégées par jour (vue avec inner join) */
    public function ventesParJour(): array
    {
        return $this->db->query("SELECT * FROM vue_ventes_jour ORDER BY jour_vente DESC")->fetchAll();
    }

    /** ADMIN : ventes détaillées par heure pour une journée donnée */
    public function ventesParHeure(string $jour): array
    {
        $stmt = $this->db->prepare(
            "SELECT heure_vente, p.nom_produit, v.quantite_vendue, v.montant
             FROM ventes v INNER JOIN produits p ON v.id_produit = p.id_produit
             WHERE v.jour_vente = :jour ORDER BY heure_vente"
        );
        $stmt->execute(['jour' => $jour]);
        return $stmt->fetchAll();
    }

    /** ADMIN : gain total du jour. Utilise CURDATE() côté MySQL (jamais date() côté PHP)
     *  pour éviter tout décalage si le fuseau horaire du serveur web diffère de celui du
     *  serveur MySQL — cause fréquente d'un total figé à 0.00 alors que des ventes existent. */
    public function gainTotalJour(): float
    {
        $stmt = $this->db->query("SELECT COALESCE(SUM(montant),0) AS total FROM ventes WHERE jour_vente = CURDATE()");
        return (float)$stmt->fetch()['total'];
    }

    /** ADMIN : ventes agrégées par mois */
    public function ventesParMois(): array
    {
        return $this->db->query(
            "SELECT mois_vente, SUM(quantite_vendue) AS total_quantite, SUM(montant) AS total_gain
             FROM ventes GROUP BY mois_vente ORDER BY mois_vente DESC"
        )->fetchAll();
    }

    /** Totaux (tous produits confondus) pour les graphiques — derniers jours/semaines/mois,
     *  renvoyés en ordre chronologique croissant (prêts pour un graphique). */
    public function totauxParJourPourGraphique(int $nbJours = 14): array
    {
        $stmt = $this->db->prepare(
            "SELECT jour_vente AS periode, SUM(montant) AS total
             FROM ventes GROUP BY jour_vente ORDER BY jour_vente DESC LIMIT :n"
        );
        $stmt->bindValue('n', $nbJours, PDO::PARAM_INT);
        $stmt->execute();
        return array_reverse($stmt->fetchAll());
    }

    public function totauxParSemainePourGraphique(int $nbSemaines = 12): array
    {
        $stmt = $this->db->prepare(
            "SELECT YEARWEEK(date_vente, 3) AS cle, MIN(jour_vente) AS periode, SUM(montant) AS total
             FROM ventes GROUP BY cle ORDER BY cle DESC LIMIT :n"
        );
        $stmt->bindValue('n', $nbSemaines, PDO::PARAM_INT);
        $stmt->execute();
        return array_reverse($stmt->fetchAll());
    }

    public function totauxParMoisPourGraphique(int $nbMois = 12): array
    {
        $stmt = $this->db->prepare(
            "SELECT mois_vente AS periode, SUM(montant) AS total
             FROM ventes GROUP BY mois_vente ORDER BY mois_vente DESC LIMIT :n"
        );
        $stmt->bindValue('n', $nbMois, PDO::PARAM_INT);
        $stmt->execute();
        return array_reverse($stmt->fetchAll());
    }
}
