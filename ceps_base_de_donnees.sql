-- ============================================================================
--  CEPS - Centre Équipements Produit de Santé
--  Script SQL complet : tables, vues, procédures stockées, triggers, données
--  de départ (rôles + catégories).
--
--  UTILISATION SUR INFINITYFREE :
--   1. Crée d'abord ta base de données MySQL dans le panneau "MySQL Databases"
--      de ton client-panel InfinityFree (note bien le nom de la base généré,
--      du type if0_XXXXXXXX_ceps_db).
--   2. Ouvre phpMyAdmin depuis ce même panneau, sélectionne cette base.
--   3. Onglet "Importer" -> choisis ce fichier ceps_base_de_donnees.sql -> Exécuter.
--   4. Reporte le nom de la base / utilisateur / mot de passe dans
--      config/database.php (voir les commentaires dans ce fichier).
--
--  UTILISATION EN LOCAL (WAMPSERVER) :
--   Décommente la ligne CREATE DATABASE / USE ci-dessous avant d'importer,
--   ou crée la base "ceps_db" toi-même dans phpMyAdmin avant d'importer.
-- ============================================================================

-- CREATE DATABASE IF NOT EXISTS ceps_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE ceps_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
--  TABLES
-- ============================================================================

DROP TABLE IF EXISTS `messages_chat`;
DROP TABLE IF EXISTS `conversations`;
DROP TABLE IF EXISTS `commentaires`;
DROP TABLE IF EXISTS `ventes`;
DROP TABLE IF EXISTS `commande_details`;
DROP TABLE IF EXISTS `commandes`;
DROP TABLE IF EXISTS `stock`;
DROP TABLE IF EXISTS `produits`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `statistiques_frequentation`;
DROP TABLE IF EXISTS `historique_connexion`;
DROP TABLE IF EXISTS `utilisateurs`;
DROP TABLE IF EXISTS `roles`;

-- ---------- roles ----------
CREATE TABLE `roles` (
    `id_role`  INT AUTO_INCREMENT PRIMARY KEY,
    `nom_role` VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- utilisateurs ----------
CREATE TABLE `utilisateurs` (
    `id_utilisateur`             INT AUTO_INCREMENT PRIMARY KEY,
    `id_role`                    INT NOT NULL,
    `nom`                        VARCHAR(80)  NOT NULL,
    `prenom`                     VARCHAR(80)  NOT NULL,
    `email`                      VARCHAR(150) NOT NULL UNIQUE,
    `mot_de_passe`               VARCHAR(255) NOT NULL,
    `telephone`                  VARCHAR(30)  NULL,
    `photo_profil`               VARCHAR(255) NULL,
    `origine_inscription`        ENUM('formulaire','google') NOT NULL DEFAULT 'formulaire',
    `google_id`                  VARCHAR(50)  NULL,
    `consentement_localisation`  TINYINT(1)   NOT NULL DEFAULT 0,
    `latitude`                   DECIMAL(10,7) NULL,
    `longitude`                  DECIMAL(10,7) NULL,
    `tentatives_connexion`       INT NOT NULL DEFAULT 0,
    `compte_bloque`              TINYINT(1) NOT NULL DEFAULT 0,
    `date_inscription`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_utilisateurs_role` FOREIGN KEY (`id_role`) REFERENCES `roles`(`id_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- historique_connexion ----------
CREATE TABLE `historique_connexion` (
    `id_historique`  INT AUTO_INCREMENT PRIMARY KEY,
    `id_utilisateur` INT NOT NULL,
    `adresse_ip`     VARCHAR(45) NOT NULL,
    `statut`         ENUM('succes','echec') NOT NULL,
    `date_connexion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_historique_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- statistiques_frequentation ----------
CREATE TABLE `statistiques_frequentation` (
    `id_utilisateur`         INT NOT NULL,
    `date_jour`              DATE NOT NULL,
    `nb_connexions`          INT NOT NULL DEFAULT 0,
    `duree_totale_secondes`  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_utilisateur`, `date_jour`),
    CONSTRAINT `fk_stats_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- categories ----------
CREATE TABLE `categories` (
    `id_categorie`  INT AUTO_INCREMENT PRIMARY KEY,
    `nom_categorie` VARCHAR(100) NOT NULL,
    `type`          ENUM('pharmaceutique','cosmetique') NOT NULL DEFAULT 'pharmaceutique'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- produits ----------
CREATE TABLE `produits` (
    `id_produit`     INT AUTO_INCREMENT PRIMARY KEY,
    `id_categorie`   INT NOT NULL,
    `nom_produit`    VARCHAR(150) NOT NULL,
    `description`    TEXT NULL,
    `prix_unitaire`  DECIMAL(10,2) NOT NULL DEFAULT 0,
    `image`          VARCHAR(255) NULL,
    `actif`          TINYINT(1) NOT NULL DEFAULT 1,
    `date_ajout`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_produits_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categories`(`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- stock ----------
CREATE TABLE `stock` (
    `id_produit`           INT PRIMARY KEY,
    `quantite_disponible`  INT NOT NULL DEFAULT 0,
    `seuil_alerte`         INT NOT NULL DEFAULT 5,
    `derniere_maj`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_stock_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits`(`id_produit`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- commandes ----------
CREATE TABLE `commandes` (
    `id_commande`             INT AUTO_INCREMENT PRIMARY KEY,
    `id_utilisateur`          INT NOT NULL,
    `statut`                  ENUM('en_attente','modifiee_adm','approuvee','refusee') NOT NULL DEFAULT 'en_attente',
    `mode_paiement`           VARCHAR(20) NOT NULL DEFAULT 'non_paye',
    `montant_total`           DECIMAL(12,2) NOT NULL DEFAULT 0,
    -- Dépôt (acompte) que le client peut envoyer après que l'admin a modifié le prix,
    -- et AVANT que la commande soit définitivement approuvée.
    `montant_acompte`         DECIMAL(12,2) NULL,
    `mode_paiement_acompte`   VARCHAR(20) NULL,
    `preuve_acompte`          VARCHAR(255) NULL,
    `date_acompte`            DATETIME NULL,
    `date_commande`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_approbation`        DATETIME NULL,
    `id_admin_approbateur`    INT NULL,
    CONSTRAINT `fk_commandes_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id_utilisateur`),
    CONSTRAINT `fk_commandes_admin` FOREIGN KEY (`id_admin_approbateur`) REFERENCES `utilisateurs`(`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- commande_details ----------
CREATE TABLE `commande_details` (
    `id_detail`               INT AUTO_INCREMENT PRIMARY KEY,
    `id_commande`             INT NOT NULL,
    `id_produit`              INT NOT NULL,
    `quantite`                INT NOT NULL,
    `prix_unitaire_negocie`   DECIMAL(10,2) NOT NULL,
    `sous_total`              DECIMAL(12,2) GENERATED ALWAYS AS (`quantite` * `prix_unitaire_negocie`) STORED,
    CONSTRAINT `fk_details_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes`(`id_commande`) ON DELETE CASCADE,
    CONSTRAINT `fk_details_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits`(`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- ventes (statistiques détaillées : heure / jour / mois, pour les graphiques) ----------
CREATE TABLE `ventes` (
    `id_vente`          INT AUTO_INCREMENT PRIMARY KEY,
    `id_commande`       INT NOT NULL,
    `id_produit`        INT NOT NULL,
    `quantite_vendue`   INT NOT NULL,
    `montant`           DECIMAL(12,2) NOT NULL,
    `date_vente`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `jour_vente`        DATE NOT NULL,
    `heure_vente`       TIME NOT NULL,
    `mois_vente`        VARCHAR(7) NOT NULL, -- format 'YYYY-MM'
    CONSTRAINT `fk_ventes_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes`(`id_commande`) ON DELETE CASCADE,
    CONSTRAINT `fk_ventes_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits`(`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- commentaires (stockés en JSON) ----------
CREATE TABLE `commentaires` (
    `id_commentaire`    INT AUTO_INCREMENT PRIMARY KEY,
    `id_utilisateur`    INT NOT NULL,
    `contenu_json`      JSON NOT NULL,
    `publie_par_admin`  TINYINT(1) NOT NULL DEFAULT 0,
    `date_commentaire`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_commentaires_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- conversations (une par client) ----------
CREATE TABLE `conversations` (
    `id_conversation`  INT AUTO_INCREMENT PRIMARY KEY,
    `id_utilisateur`   INT NOT NULL UNIQUE,
    `date_creation`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_conversations_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- messages_chat ----------
CREATE TABLE `messages_chat` (
    `id_message`        INT AUTO_INCREMENT PRIMARY KEY,
    `id_conversation`   INT NOT NULL,
    `expediteur`        ENUM('client','admin','system') NOT NULL,
    `contenu`           TEXT NOT NULL,
    `preuve_paiement`   VARCHAR(255) NULL,
    `lu`                TINYINT(1) NOT NULL DEFAULT 0,
    `date_envoi`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`id_conversation`) REFERENCES `conversations`(`id_conversation`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
--  VUES (accès sécurisé : pas d'accès direct aux tables pour le public/client)
-- ============================================================================

DROP VIEW IF EXISTS `vue_produits_public`;
CREATE VIEW `vue_produits_public` AS
    SELECT p.id_produit, p.id_categorie, p.nom_produit, p.description, p.prix_unitaire, p.image,
           c.nom_categorie, c.type, s.quantite_disponible
    FROM produits p
    INNER JOIN categories c ON p.id_categorie = c.id_categorie
    INNER JOIN stock s ON p.id_produit = s.id_produit
    WHERE p.actif = 1;

DROP VIEW IF EXISTS `vue_client_profil`;
CREATE VIEW `vue_client_profil` AS
    SELECT id_utilisateur, nom, prenom, email, telephone, photo_profil,
           consentement_localisation, latitude, longitude, date_inscription
    FROM utilisateurs;

DROP VIEW IF EXISTS `vue_admin_utilisateurs`;
CREATE VIEW `vue_admin_utilisateurs` AS
    SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.telephone, u.origine_inscription,
           u.compte_bloque, u.tentatives_connexion, u.consentement_localisation,
           u.latitude, u.longitude, u.date_inscription, r.nom_role
    FROM utilisateurs u
    INNER JOIN roles r ON u.id_role = r.id_role;

DROP VIEW IF EXISTS `vue_historique_client`;
CREATE VIEW `vue_historique_client` AS
    SELECT c.id_commande, c.id_utilisateur, c.statut, c.montant_total, c.mode_paiement,
           c.montant_acompte, c.mode_paiement_acompte, c.date_acompte,
           c.date_commande, c.date_approbation,
           cd.id_produit, p.nom_produit, cd.quantite, cd.prix_unitaire_negocie, cd.sous_total
    FROM commandes c
    INNER JOIN commande_details cd ON c.id_commande = cd.id_commande
    INNER JOIN produits p ON cd.id_produit = p.id_produit;

DROP VIEW IF EXISTS `vue_ventes_jour`;
CREATE VIEW `vue_ventes_jour` AS
    SELECT v.jour_vente, p.nom_produit, SUM(v.quantite_vendue) AS total_quantite, SUM(v.montant) AS total_gain
    FROM ventes v
    INNER JOIN produits p ON v.id_produit = p.id_produit
    GROUP BY v.jour_vente, p.nom_produit;

-- ============================================================================
--  PROCÉDURES STOCKÉES
-- ============================================================================

DELIMITER $$

-- ---------- Création automatique d'un client (formulaire ou Google) ----------
DROP PROCEDURE IF EXISTS `sp_creer_client`$$
CREATE PROCEDURE `sp_creer_client` (
    IN p_nom VARCHAR(80), IN p_prenom VARCHAR(80), IN p_email VARCHAR(150),
    IN p_mdp VARCHAR(255), IN p_origine VARCHAR(20), IN p_google_id VARCHAR(50)
)
BEGIN
    DECLARE v_id_role INT;
    SELECT id_role INTO v_id_role FROM roles WHERE nom_role = 'client' LIMIT 1;

    INSERT INTO utilisateurs (id_role, nom, prenom, email, mot_de_passe, origine_inscription, google_id)
    VALUES (v_id_role, p_nom, p_prenom, p_email, p_mdp, p_origine, p_google_id);

    SELECT LAST_INSERT_ID() AS id_utilisateur_cree;
END$$

-- ---------- Gère une tentative de connexion (blocage après 3 échecs) ----------
DROP PROCEDURE IF EXISTS `sp_verifier_tentative_connexion`$$
CREATE PROCEDURE `sp_verifier_tentative_connexion` (
    IN p_id_utilisateur INT, IN p_succes TINYINT
)
BEGIN
    IF p_succes = 1 THEN
        UPDATE utilisateurs SET tentatives_connexion = 0 WHERE id_utilisateur = p_id_utilisateur;
    ELSE
        UPDATE utilisateurs SET tentatives_connexion = tentatives_connexion + 1
        WHERE id_utilisateur = p_id_utilisateur;

        UPDATE utilisateurs SET compte_bloque = 1
        WHERE id_utilisateur = p_id_utilisateur AND tentatives_connexion >= 3;
    END IF;
END$$

-- ---------- Débloque un compte (action admin) ----------
DROP PROCEDURE IF EXISTS `sp_debloquer_compte`$$
CREATE PROCEDURE `sp_debloquer_compte` (IN p_id_utilisateur INT)
BEGIN
    UPDATE utilisateurs SET compte_bloque = 0, tentatives_connexion = 0
    WHERE id_utilisateur = p_id_utilisateur;
END$$

-- ---------- Approbation d'une commande : décrémente le stock, alimente les ventes,
--            annule tout (SAVEPOINT) si le stock est insuffisant pour une ligne ----------
DROP PROCEDURE IF EXISTS `sp_approuver_commande`$$
CREATE PROCEDURE `sp_approuver_commande` (
    IN p_id_commande INT, IN p_id_admin INT
)
proc: BEGIN
    DECLARE v_id_produit INT;
    DECLARE v_quantite INT;
    DECLARE v_prix DECIMAL(10,2);
    DECLARE v_sous_total DECIMAL(12,2);
    DECLARE v_dispo INT;
    DECLARE v_fini INT DEFAULT 0;

    DECLARE curseur_lignes CURSOR FOR
        SELECT id_produit, quantite, prix_unitaire_negocie, sous_total
        FROM commande_details WHERE id_commande = p_id_commande;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_fini = 1;

    START TRANSACTION;
    SAVEPOINT avant_approbation;

    -- 1) Vérification du stock disponible pour CHAQUE ligne avant toute modification
    OPEN curseur_lignes;
    verif: LOOP
        FETCH curseur_lignes INTO v_id_produit, v_quantite, v_prix, v_sous_total;
        IF v_fini = 1 THEN
            LEAVE verif;
        END IF;

        SELECT quantite_disponible INTO v_dispo FROM stock WHERE id_produit = v_id_produit FOR UPDATE;
        IF v_dispo IS NULL OR v_dispo < v_quantite THEN
            CLOSE curseur_lignes;
            ROLLBACK TO SAVEPOINT avant_approbation;
            COMMIT;
            SELECT 'Stock insuffisant pour approuver cette commande.' AS message;
            LEAVE proc;
        END IF;
    END LOOP;
    CLOSE curseur_lignes;

    -- 2) Stock suffisant partout : on décrémente le stock et on alimente les ventes
    SET v_fini = 0;
    OPEN curseur_lignes;
    boucle_maj: LOOP
        FETCH curseur_lignes INTO v_id_produit, v_quantite, v_prix, v_sous_total;
        IF v_fini = 1 THEN
            LEAVE boucle_maj;
        END IF;

        UPDATE stock SET quantite_disponible = quantite_disponible - v_quantite
        WHERE id_produit = v_id_produit;

        INSERT INTO ventes (id_commande, id_produit, quantite_vendue, montant, jour_vente, heure_vente, mois_vente)
        VALUES (p_id_commande, v_id_produit, v_quantite, v_sous_total, CURDATE(), CURTIME(), DATE_FORMAT(NOW(), '%Y-%m'));
    END LOOP;
    CLOSE curseur_lignes;

    -- 3) Marque la commande comme approuvée
    UPDATE commandes
    SET statut = 'approuvee', date_approbation = NOW(), id_admin_approbateur = p_id_admin
    WHERE id_commande = p_id_commande;

    COMMIT;
    SELECT 'Commande approuvée avec succès.' AS message;
END$$

DELIMITER ;

-- ============================================================================
--  TRIGGERS
-- ============================================================================

DELIMITER $$

-- Recalcule automatiquement le total de la commande après ajout d'une ligne
DROP TRIGGER IF EXISTS `trg_commande_details_apres_insert`$$
CREATE TRIGGER `trg_commande_details_apres_insert`
AFTER INSERT ON `commande_details`
FOR EACH ROW
BEGIN
    UPDATE commandes
    SET montant_total = (SELECT COALESCE(SUM(sous_total), 0) FROM commande_details WHERE id_commande = NEW.id_commande)
    WHERE id_commande = NEW.id_commande;
END$$

-- Recalcule automatiquement le total de la commande après une négociation de prix par l'admin
DROP TRIGGER IF EXISTS `trg_commande_details_apres_update`$$
CREATE TRIGGER `trg_commande_details_apres_update`
AFTER UPDATE ON `commande_details`
FOR EACH ROW
BEGIN
    UPDATE commandes
    SET montant_total = (SELECT COALESCE(SUM(sous_total), 0) FROM commande_details WHERE id_commande = NEW.id_commande)
    WHERE id_commande = NEW.id_commande;
END$$

DELIMITER ;

-- ============================================================================
--  DONNÉES DE DÉPART
-- ============================================================================

INSERT INTO `roles` (`nom_role`) VALUES ('admin'), ('client'), ('caissier');

-- Compte administrateur CEPS par défaut — email : admin@ceps.com / mot de passe : Admin1234
-- ⚠️ Change ce mot de passe dès la première connexion (menu "Modifier mon mot de passe").
INSERT INTO `utilisateurs` (`id_role`, `nom`, `prenom`, `email`, `mot_de_passe`, `origine_inscription`)
VALUES (
    (SELECT id_role FROM roles WHERE nom_role = 'admin'),
    'CEPS', 'Administrateur', 'admin@ceps.com',
    '$2b$10$MR62AY/bQzl/.BQTTQLHXuUWJO7OLDqNsVrjss3./rU7nbrIZQKpy', -- Admin1234 (bcrypt, vérifié compatible password_verify PHP)
    'formulaire'
);

INSERT INTO `categories` (`nom_categorie`, `type`) VALUES
    ('Médicaments génériques', 'pharmaceutique'),
    ('Vitamines & compléments', 'pharmaceutique'),
    ('Matériel médical', 'pharmaceutique'),
    ('Soins du visage', 'cosmetique'),
    ('Soins du corps', 'cosmetique'),
    ('Parfumerie', 'cosmetique');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
--  FIN DU SCRIPT
-- ============================================================================
