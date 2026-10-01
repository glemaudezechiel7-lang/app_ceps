<?php
/**
 * CEPS - CommentaireModel : commentaires clients stockés en JSON
 */

class CommentaireModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Client : envoie un commentaire (texte + note), stocké en JSON */
    public function ajouter(int $idUtilisateur, string $texte, int $note): bool
    {
        $json = json_encode(['texte' => $texte, 'note' => max(1, min(5, $note))], JSON_UNESCAPED_UNICODE);
        $stmt = $this->db->prepare(
            "INSERT INTO commentaires (id_utilisateur, contenu_json) VALUES (:id, :json)"
        );
        return $stmt->execute(['id' => $idUtilisateur, 'json' => $json]);
    }

    /** ADMIN : liste de tous les commentaires reçus (inner join utilisateur) */
    public function listerTous(): array
    {
        return $this->db->query(
            "SELECT co.id_commentaire, co.contenu_json, co.date_commentaire, co.publie_par_admin,
                    u.nom, u.prenom, u.id_utilisateur
             FROM commentaires co
             INNER JOIN utilisateurs u ON co.id_utilisateur = u.id_utilisateur
             ORDER BY co.date_commentaire DESC"
        )->fetchAll();
    }

    /** ADMIN : publie une réponse/commentaire qui apparaîtra dans la page du client (via le chat) */
    public function marquerPublie(int $idCommentaire): bool
    {
        $stmt = $this->db->prepare("UPDATE commentaires SET publie_par_admin = 1 WHERE id_commentaire = :id");
        return $stmt->execute(['id' => $idCommentaire]);
    }

    /** Commentaires visibles sur la page d'un client précis (les siens) */
    public function parUtilisateur(int $idUtilisateur): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM commentaires WHERE id_utilisateur = :id ORDER BY date_commentaire DESC"
        );
        $stmt->execute(['id' => $idUtilisateur]);
        return $stmt->fetchAll();
    }
}
