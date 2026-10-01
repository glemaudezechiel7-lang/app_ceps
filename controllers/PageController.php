<?php
/**
 * CEPS - PageController : pages publiques (accueil, galerie, panier)
 */

class PageController
{
    private ProduitModel $produitModel;

    public function __construct()
    {
        $this->produitModel = new ProduitModel();
    }

    public function accueil(): void
    {
        $produitsAccueil = $this->produitModel->produitsAccueil(6);
        require __DIR__ . '/../views/accueil.php';
    }

    public function galerie(): void
    {
        $idCategorie = isset($_GET['categorie']) ? (int)$_GET['categorie'] : null;
        $type        = $_GET['type'] ?? null; // 'pharmaceutique' ou 'cosmetique', depuis le menu
        $produits    = $this->produitModel->listerPourGalerie($idCategorie, $type);
        $categories  = $this->produitModel->listerCategories();
        require __DIR__ . '/../views/galerie.php';
    }

    /** Page détail d'un produit (clic sur l'image), avec sélecteur de quantité */
    public function detailProduit(): void
    {
        $idProduit = (int)($_GET['id'] ?? 0);
        $produit = $this->produitModel->trouverParId($idProduit);
        if (!$produit) {
            http_response_code(404);
            require __DIR__ . '/../views/partials/header.php';
            echo '<p>Produit introuvable.</p>';
            require __DIR__ . '/../views/partials/footer.php';
            return;
        }
        require __DIR__ . '/../views/produit_detail.php';
    }

    /** Recherche d'un produit par nom, dans notre catalogue (remplace la recherche Google) */
    public function rechercherProduits(): void
    {
        $terme = trim($_GET['q'] ?? '');
        $produits = $terme !== '' ? $this->produitModel->rechercherPublic($terme) : [];
        require __DIR__ . '/../views/resultats_recherche.php';
    }

    public function apropos(): void
    {
        require __DIR__ . '/../views/apropos.php';
    }

    public function contact(): void
    {
        require __DIR__ . '/../views/contact.php';
    }

    /** Ajoute un produit au panier (session) — inscription/connexion requise avant de valider la commande */
    /** Ajoute un produit au panier SANS quitter la page en cours (galerie, recherche...),
     *  pour permettre de sélectionner plusieurs produits d'affilée. */
    public function ajouterAuPanier(): void
    {
        $idProduit = (int)($_POST['id_produit'] ?? 0);
        $quantite  = max(1, (int)($_POST['quantite'] ?? 1));
        $produit   = $this->produitModel->trouverParId($idProduit);

        // Page à laquelle revenir après l'ajout (transmise par le formulaire, sinon la galerie)
        $retour = $_POST['retour'] ?? 'index.php?page=galerie';

        if ($produit) {
            $_SESSION['panier'][$idProduit] = [
                'nom'      => $produit['nom_produit'],
                'quantite' => $quantite,
                'prix'     => (float)$produit['prix_unitaire'],
            ];
            flash('succes', e($produit['nom_produit']) . ' ajouté au panier !');
        }

        redirect($retour);
    }

    public function afficherPanier(): void
    {
        $panier = $_SESSION['panier'] ?? [];
        require __DIR__ . '/../views/panier.php';
    }

    /** Retrait du panier — POST + CSRF pour éviter qu'un lien externe modifie la session du visiteur */
    public function retirerDuPanier(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=panier');
        }
        $idProduit = (int)($_POST['id'] ?? 0);
        unset($_SESSION['panier'][$idProduit]);
        redirect('index.php?page=panier');
    }
}
