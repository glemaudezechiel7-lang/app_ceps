<?php
/**
 * CEPS - Configuration de connexion à la base de données
 * Utilise PDO avec exceptions activées + requêtes préparées partout (anti injection SQL)
 *
 * ============================================================================
 *  À FAIRE AVANT LA MISE EN LIGNE SUR INFINITYFREE :
 *  Remplace les 4 valeurs ci-dessous par celles de TON compte InfinityFree.
 *  Tu les trouves dans le panneau "MySQL Databases" de ton client-panel (vPanel) :
 *
 *      HOST   -> ressemble à "sqlXXX.infinityfree.com" (PAS "localhost")
 *      DBNAME -> ressemble à "if0_XXXXXXXX_ceps_db"  (préfixe if0_XXXXXXXX_ imposé)
 *      USER   -> identique au nom de la base en général : "if0_XXXXXXXX"
 *      PASS   -> le mot de passe que tu as choisi/généré à la création de la base
 *
 *  En local avec WAMPSERVER, laisse les valeurs par défaut (localhost / root / '').
 * ============================================================================
 */

class Database
{
    private static ?PDO $instance = null;

    // === EN LOCAL (WAMPSERVER) ===
    // private const HOST    = 'localhost';
    // private const DBNAME  = 'ceps_db';
    // private const USER    = 'root';
    // private const PASS    = '';

    // === SUR INFINITYFREE (remplace ces 4 valeurs par les tiennes) ===
  private const HOST    = 'localhost';
    private const DBNAME  = 'ceps_db';
    private const USER    = 'root';
    private const PASS    = '';
    private const CHARSET = 'utf8mb4';

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . self::HOST . ';dbname=' . self::DBNAME . ';charset=' . self::CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, self::USER, self::PASS, $options);
            } catch (PDOException $e) {
                die('Erreur de connexion à la base de données : ' . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
