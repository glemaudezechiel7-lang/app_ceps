<?php
/**
 * CEPS - Fonctions utilitaires globales
 */

/** Redirige vers une URL interne et arrête le script */
function redirect(string $chemin): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($chemin, '/'));
    exit;
}

/** Nettoie une chaîne pour affichage HTML (anti XSS) */
function e(?string $valeur): string
{
    return htmlspecialchars($valeur ?? '', ENT_QUOTES, 'UTF-8');
}

/** Génère (ou récupère) le jeton CSRF de la session */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Vérifie le jeton CSRF envoyé par un formulaire */
function csrf_verifier(string $jeton): bool
{
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $jeton);
}

/** Champ caché CSRF prêt à insérer dans un formulaire */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/** Hash sécurisé d'un mot de passe (bcrypt) */
function hasher_mot_de_passe(string $motDePasse): string
{
    return password_hash($motDePasse, PASSWORD_BCRYPT);
}

/** Upload sécurisé d'une image (produit ou profil). Retourne le nom de fichier généré ou null. */
function uploader_image(array $fichier, string $dossierDestination): ?string
{
    if (!isset($fichier['tmp_name']) || $fichier['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $typesAutorises = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $typeMime = finfo_file($finfo, $fichier['tmp_name']);
    finfo_close($finfo);

    if (!isset($typesAutorises[$typeMime])) {
        return null; // type de fichier non autorisé
    }

    if ($fichier['size'] > 4 * 1024 * 1024) { // 4 Mo max
        return null;
    }

    $nomFichier = bin2hex(random_bytes(16)) . '.' . $typesAutorises[$typeMime];
    $cheminComplet = rtrim($dossierDestination, '/') . '/' . $nomFichier;

    if (!move_uploaded_file($fichier['tmp_name'], $cheminComplet)) {
        return null;
    }

    return $nomFichier;
}

/** Nombre de produits DIFFÉRENTS dans le panier (lignes distinctes) */
function panier_nombre_produits_distincts(array $panier): int
{
    return count($panier);
}

/** Nombre total d'articles dans le panier (quantités additionnées, même produit compté plusieurs fois) */
function panier_nombre_articles_total(array $panier): int
{
    return (int)array_sum(array_map(fn($ligne) => (int)$ligne['quantite'], $panier));
}

/** Le panier est-il éligible au mode "négociation" ?
 *  Règle : au moins 10 produits DIFFÉRENTS, OU au moins 10 articles au total
 *  (même produit répété plusieurs fois compte aussi). */
function panier_eligible_negociation(array $panier): bool
{
    return panier_nombre_produits_distincts($panier) >= 10 || panier_nombre_articles_total($panier) >= 10;
}

/** Enregistre un message flash affiché une seule fois */
function flash(string $cle, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$cle] = $message;
        return null;
    }
    $valeur = $_SESSION['flash'][$cle] ?? null;
    unset($_SESSION['flash'][$cle]);
    return $valeur;
}
