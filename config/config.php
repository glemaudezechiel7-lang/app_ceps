<?php
/**
 * CEPS - Configuration générale + sécurisation des sessions
 */

// --- En production : ne jamais afficher les erreurs à l'écran (elles peuvent révéler
//     des chemins serveur ou des requêtes SQL). Elles sont journalisées à la place. ---
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// --- Sécurisation des cookies de session (avant session_start) ---
$estEnHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

ini_set('session.cookie_httponly', 1);   // JS ne peut pas lire le cookie (anti XSS)
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $estEnHttps ? 1 : 0); // cookie envoyé en HTTPS uniquement si dispo

session_start();

// Régénère l'ID de session périodiquement (anti session fixation)
if (!isset($_SESSION['derniere_regeneration'])) {
    $_SESSION['derniere_regeneration'] = time();
} elseif (time() - $_SESSION['derniere_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['derniere_regeneration'] = time();
}

// --- En-têtes de sécurité HTTP (protègent contre le clickjacking, le MIME-sniffing, XSS) ---
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; script-src 'self' https://accounts.google.com https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net data:; frame-src https://accounts.google.com;");

/**
 * BASE_URL est calculé automatiquement à partir de l'URL réelle du script.
 * Cela évite toute erreur si le dossier est renommé ou déplacé (ex: /ceps_app/public,
 * /public_html/public, ou la racine du site) : les liens et le CSS fonctionnent
 * quel que soit l'endroit où l'application est installée.
 */
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));

define('NOM_ENTREPRISE', 'CEPS - Centre Équipements Produit de Santé');
define('UPLOAD_PRODUITS', __DIR__ . '/../public/uploads/produits/');
define('UPLOAD_PROFILS', __DIR__ . '/../public/uploads/profils/');
define('MAX_TENTATIVES_CONNEXION', 3);

// Identifiant client OAuth Google (Sign in with Google) — à remplacer par le vôtre.
// Sans cette valeur correcte, la connexion Google refusera tous les jetons (sécurité par défaut).
define('GOOGLE_CLIENT_ID', '295430124747-u3mlsmt7f3ogofmcne655bq6ieod2hvn.apps.googleusercontent.com');

// ============================================================
// NUMÉROS DE PAIEMENT — remplace les valeurs ci-dessous par tes vrais numéros.
// Ils s'affichent automatiquement sur la page de paiement du client.
// ============================================================
define('NUMERO_MONCASH', '+509 31 77 66 47(HERARD JUGENS)');   // ton numéro MonCash
define('NUMERO_NATCASH', '+509 32 10 10 44 (GLEMAUD EZECHIEL)');   // ton numéro NatCash
define('NUMERO_BNC', 'Compte BNC : 471-0000-143 (GLEMAUD EZECHIEL)'); // tes coordonnées BNC

define('UPLOAD_PREUVES', __DIR__ . '/../public/uploads/preuves/');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
