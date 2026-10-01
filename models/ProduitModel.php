<?php
/**
 * CEPS - ProduitModel : gestion des produits & catégories (galerie + admin)
 */

class ProduitModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Galerie publique (vue sécurisée), avec filtre optionnel par catégorie précise
     *  ou par type de rayon ('pharmaceutique' / 'cosmetique') pour les liens du menu */
    public function listerPourGalerie(?int $idCategorie = null, ?string $type = null): array
    {
        if ($idCategorie) {
            $stmt = $this->db->prepare("SELECT * FROM vue_produits_public WHERE id_produit IN (
                SELECT id_produit FROM produits WHERE id_categorie = :cat
            )");
            $stmt->execute(['cat' => $idCategorie]);
            return $stmt->fetchAll();
        }
        if ($type) {
            $stmt = $this->db->prepare("SELECT * FROM vue_produits_public WHERE type = :type");
            $stmt->execute(['type' => $type]);
            return $stmt->fetchAll();
        }
        return $this->db->query("SELECT * FROM vue_produits_public")->fetchAll();
    }

    /** Quelques produits mis en avant pour la page d'accueil */
    public function produitsAccueil(int $limite = 6): array
    {
        $stmt = $this->db->prepare("SELECT * FROM vue_produits_public ORDER BY id_produit DESC LIMIT :lim");
        $stmt->bindValue('lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listerCategories(): array
    {
        return $this->db->query("SELECT * FROM categories ORDER BY nom_categorie")->fetchAll();
    }

    public function trouverParId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, s.quantite_disponible FROM produits p
             INNER JOIN stock s ON p.id_produit = s.id_produit
             WHERE p.id_produit = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Recherche admin par nom ou ID */
    public function rechercher(string $terme): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, s.quantite_disponible, c.nom_categorie FROM produits p
             INNER JOIN stock s ON p.id_produit = s.id_produit
             INNER JOIN categories c ON p.id_categorie = c.id_categorie
             WHERE p.nom_produit LIKE :terme OR p.id_produit = :id_exact
             ORDER BY p.nom_produit"
        );
        $stmt->execute([
            'terme'    => '%' . $terme . '%',
            'id_exact' => is_numeric($terme) ? (int)$terme : -1,
        ]);
        return $stmt->fetchAll();
    }

    /** Recherche publique (page de recherche client) : uniquement les produits actifs,
     *  via la vue sécurisée (pas d'accès direct à la table produits pour le public) */
    public function rechercherPublic(string $terme): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM vue_produits_public WHERE nom_produit LIKE :terme ORDER BY nom_produit"
        );
        $stmt->execute(['terme' => '%' . $terme . '%']);
        return $stmt->fetchAll();
    }

    /** ADMIN : liste complète avec stock (inner join) */
    public function listerAvecStock(): array
    {
        return $this->db->query(
            "SELECT p.*, s.quantite_disponible, s.seuil_alerte, c.nom_categorie FROM produits p
             INNER JOIN stock s ON p.id_produit = s.id_produit
             INNER JOIN categories c ON p.id_categorie = c.id_categorie
             ORDER BY p.date_ajout DESC"
        )->fetchAll();
    }

    /** ADMIN : ajout d'un produit + création de sa ligne de stock */
    public function ajouter(int $idCategorie, string $nom, string $description, float $prix, ?string $image, int $quantiteInitiale): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO produits (id_categorie, nom_produit, description, prix_unitaire, image)
                 VALUES (:cat, :nom, :desc, :prix, :image)"
            );
            $stmt->execute([
                'cat'   => $idCategorie,
                'nom'   => $nom,
                'desc'  => $description,
                'prix'  => $prix,
                'image' => $image,
            ]);
            $idProduit = (int)$this->db->lastInsertId();

            $stmtStock = $this->db->prepare(
                "INSERT INTO stock (id_produit, quantite_disponible) VALUES (:id, :qte)"
            );
            $stmtStock->execute(['id' => $idProduit, 'qte' => $quantiteInitiale]);

            $this->db->commit();
            return $idProduit;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** ADMIN : modification d'un produit */
    public function modifier(int $id, int $idCategorie, string $nom, string $description, float $prix, ?string $image = null): bool
    {
        if ($image) {
            $stmt = $this->db->prepare(
                "UPDATE produits SET id_categorie = :cat, nom_produit = :nom, description = :desc, prix_unitaire = :prix, image = :image WHERE id_produit = :id"
            );
            return $stmt->execute(['cat' => $idCategorie, 'nom' => $nom, 'desc' => $description, 'prix' => $prix, 'image' => $image, 'id' => $id]);
        }
        $stmt = $this->db->prepare(
            "UPDATE produits SET id_categorie = :cat, nom_produit = :nom, description = :desc, prix_unitaire = :prix WHERE id_produit = :id"
        );
        return $stmt->execute(['cat' => $idCategorie, 'nom' => $nom, 'desc' => $description, 'prix' => $prix, 'id' => $id]);
    }

    /** ADMIN : suppression logique (désactivation) d'un produit */
    public function supprimer(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE produits SET actif = 0 WHERE id_produit = :id");
        return $stmt->execute(['id' => $id]);
    }

    /** ADMIN : mise à jour manuelle de la quantité en stock */
    public function ajusterStock(int $idProduit, int $nouvelleQuantite): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE stock SET quantite_disponible = :qte, derniere_maj = NOW() WHERE id_produit = :id"
        );
        return $stmt->execute(['qte' => $nouvelleQuantite, 'id' => $idProduit]);
    }
}
