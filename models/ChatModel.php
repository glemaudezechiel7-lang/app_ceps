<?php
/**
 * CEPS - ChatModel : conversation privée entre chaque client et l'admin
 */

class ChatModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Récupère (ou crée si absente) la conversation d'un client */
    public function obtenirConversation(int $idUtilisateur): int
    {
        $stmt = $this->db->prepare("SELECT id_conversation FROM conversations WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $idUtilisateur]);
        $ligne = $stmt->fetch();

        if ($ligne) {
            return (int)$ligne['id_conversation'];
        }

        $insert = $this->db->prepare("INSERT INTO conversations (id_utilisateur) VALUES (:id)");
        $insert->execute(['id' => $idUtilisateur]);
        return (int)$this->db->lastInsertId();
    }

    public function envoyerMessage(int $idConversation, string $expediteur, string $contenu, ?string $cheminPreuve = null): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO messages_chat (id_conversation, expediteur, contenu, preuve_paiement) VALUES (:conv, :exp, :contenu, :preuve)"
        );
        return $stmt->execute(['conv' => $idConversation, 'exp' => $expediteur, 'contenu' => $contenu, 'preuve' => $cheminPreuve]);
    }

    public function messagesConversation(int $idConversation): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM messages_chat WHERE id_conversation = :conv ORDER BY date_envoi ASC"
        );
        $stmt->execute(['conv' => $idConversation]);
        return $stmt->fetchAll();
    }

    /** ADMIN : liste de toutes les conversations avec le dernier message (inner join) */
    public function listerConversationsAdmin(): array
    {
        return $this->db->query(
            "SELECT c.id_conversation, u.id_utilisateur, u.nom, u.prenom,
                    (SELECT contenu FROM messages_chat m WHERE m.id_conversation = c.id_conversation ORDER BY date_envoi DESC LIMIT 1) AS dernier_message,
                    (SELECT COUNT(*) FROM messages_chat m WHERE m.id_conversation = c.id_conversation AND m.expediteur = 'client' AND m.lu = 0) AS non_lus
             FROM conversations c
             INNER JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
             ORDER BY c.id_conversation DESC"
        )->fetchAll();
    }

    public function marquerCommeLu(int $idConversation, string $expediteurOppose): void
    {
        $stmt = $this->db->prepare(
            "UPDATE messages_chat SET lu = 1 WHERE id_conversation = :conv AND expediteur = :exp"
        );
        $stmt->execute(['conv' => $idConversation, 'exp' => $expediteurOppose]);
    }
}
