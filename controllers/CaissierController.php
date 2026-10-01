<?php
/**
 * CEPS - CaissierController : vente directe en magasin (point de vente)
 *
 * Le caissier constitue un panier (comme un client) puis valide la vente en une fois.
 * On réutilise le circuit commande -> approbation déjà en place (mêmes vérifications
 * de stock, même trigger de mise à jour automatique des ventes).
 */

class CaissierController
{
    private ProduitModel $produitModel;
    private CommandeModel $commandeModel;

    public function __construct()
    {
        $this->produitModel  = new ProduitModel();
        $this->commandeModel = new CommandeModel();
    }

    public function tableauDeBord(): void
    {
        exiger_caissier();
        $produits    = $this->produitModel->listerAvecStock();
        $panierCaisse = $_SESSION['panier_caisse'] ?? [];
        $gainDuJour  = $this->commandeModel->gainTotalJour();
        $parJour     = $this->commandeModel->totauxParJourPourGraphique(14);
        $parSemaine  = $this->commandeModel->totauxParSemainePourGraphique(12);
        $parMois     = $this->commandeModel->totauxParMoisPourGraphique(12);
        $mesVentes   = $this->commandeModel->historiqueVentesCaissier((int)$_SESSION['id_utilisateur']);
        require __DIR__ . '/../views/caissier/dashboard.php';
    }

    /** Reçu imprimable d'une vente : uniquement celles réalisées par CE caissier */
    public function recuVente(): void
    {
        exiger_caissier();
        $idCommande = (int)($_GET['id'] ?? 0);
        $infos = $this->commandeModel->informationsCommande($idCommande);

        if (!$infos || $infos['statut'] !== 'approuvee' || (int)$infos['id_utilisateur'] !== (int)$_SESSION['id_utilisateur']) {
            flash('erreur', 'Reçu introuvable.');
            redirect('index.php?page=caissier_dashboard');
        }

        $details = $this->commandeModel->detailsCommande($idCommande);
        require __DIR__ . '/../views/admin/recu_vente.php';
    }

    /** Ajoute un produit au panier de vente (reste sur le tableau de bord) */
    public function ajouterAuPanier(): void
    {
        exiger_caissier();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=caissier_dashboard');
        }

        $idProduit = (int)($_POST['id_produit'] ?? 0);
        $quantite  = max(1, (int)($_POST['quantite'] ?? 1));
        $produit   = $this->produitModel->trouverParId($idProduit);

        if ($produit) {
            $_SESSION['panier_caisse'][$idProduit] = [
                'nom'      => $produit['nom_produit'],
                'quantite' => $quantite,
                'prix'     => (float)$produit['prix_unitaire'],
            ];
            flash('succes', $produit['nom_produit'] . ' ajouté au panier de vente.');
        }
        redirect('index.php?page=caissier_dashboard');
    }

    /** Retire une ligne du panier de vente */
    public function retirerDuPanier(): void
    {
        exiger_caissier();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=caissier_dashboard');
        }
        unset($_SESSION['panier_caisse'][(int)($_POST['id'] ?? 0)]);
        redirect('index.php?page=caissier_dashboard');
    }

    /** Valide la vente : crée puis approuve automatiquement la commande de caisse */
    public function validerVente(): void
    {
        exiger_caissier();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifier($_POST['csrf_token'] ?? '')) {
            redirect('index.php?page=caissier_dashboard');
        }

        $panier = $_SESSION['panier_caisse'] ?? [];
        if (empty($panier)) {
            flash('erreur', 'Le panier de vente est vide.');
            redirect('index.php?page=caissier_dashboard');
        }

        try {
            $idCommande = $this->commandeModel->creer((int)$_SESSION['id_utilisateur'], $panier);
            $reussi = $this->commandeModel->approuver($idCommande, (int)$_SESSION['id_utilisateur']);

            if ($reussi) {
                $_SESSION['panier_caisse'] = [];
                redirect('index.php?page=caissier_commande_recu&id=' . $idCommande);
            }
            flash('erreur', 'Vente impossible : stock insuffisant pour un des produits.');
        } catch (RuntimeException $e) {
            flash('erreur', 'Stock insuffisant pour un des produits du panier.');
        }

        redirect('index.php?page=caissier_dashboard');
    }
}
