<?php
/**
 * CEPS - Contrôle d'accès (authentification + rôles)
 */

function utilisateur_connecte(): bool
{
    return !empty($_SESSION['id_utilisateur']);
}

function role_utilisateur(): ?string
{
    return $_SESSION['role'] ?? null;
}

/** Bloque l'accès si l'utilisateur n'est pas connecté */
function exiger_connexion(): void
{
    if (!utilisateur_connecte()) {
        flash('erreur', 'Veuillez vous connecter pour accéder à cette page.');
        redirect('index.php?page=login');
    }
}

/** Bloque l'accès si l'utilisateur n'est pas administrateur */
function exiger_admin(): void
{
    exiger_connexion();
    if (role_utilisateur() !== 'admin') {
        http_response_code(403);
        die('Accès refusé : réservé aux administrateurs CEPS.');
    }
}

/** Bloque l'accès si l'utilisateur n'est pas client */
function exiger_client(): void
{
    exiger_connexion();
    if (role_utilisateur() !== 'client') {
        http_response_code(403);
        die('Accès refusé.');
    }
}

/** Bloque l'accès si l'utilisateur n'est pas caissier */
function exiger_caissier(): void
{
    exiger_connexion();
    if (role_utilisateur() !== 'caissier') {
        http_response_code(403);
        die('Accès refusé : réservé aux caissiers CEPS.');
    }
}
