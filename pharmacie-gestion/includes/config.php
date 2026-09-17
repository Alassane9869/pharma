<?php
// Configuration de la base de données SQLite
define('DB_FILE', __DIR__ . '/../pharmacie.sqlite');
date_default_timezone_set('UTC');

// Connexion à la base de données SQLite
function getConnection() {
    static $conn = null;
    if ($conn === null) {
        $dbPath = DB_FILE;
        $dbExists = file_exists($dbPath);
        
        try {
            $conn = new PDO("sqlite:" . $dbPath);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->exec('PRAGMA foreign_keys = ON;');

            // Fonctions personnalisées MySQL pour SQLite (compatibilité PDO SQLite)
            if (method_exists($conn, 'sqliteCreateFunction')) {
                $conn->sqliteCreateFunction('MD5', function($string) {
                    return md5((string)$string);
                }, 1);

                $conn->sqliteCreateFunction('CURDATE', function() {
                    return date('Y-m-d');
                });
            }

            // Auto-initialisation et migration du schéma
            if (!$dbExists || filesize($dbPath) === 0) {
                initSqliteDatabase($conn);
                @chmod($dbPath, 0666);
            } else {
                migrateSqliteDatabase($conn);
                @chmod($dbPath, 0666);
            }
        } catch(PDOException $e) {
            die("Erreur de connexion SQLite : " . $e->getMessage());
        }
    }
    return $conn;
}

// Migration dynamique pour ajouter les colonnes et tables manquantes
function migrateSqliteDatabase($conn) {
    try {
        // 1. Colonnes de medicaments
        $cols = $conn->query("PRAGMA table_info(medicaments)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('categorie', $cols)) {
            $conn->exec("ALTER TABLE medicaments ADD COLUMN categorie TEXT;");
        }
        if (!in_array('id_fournisseur', $cols)) {
            $conn->exec("ALTER TABLE medicaments ADD COLUMN id_fournisseur INTEGER;");
        }

        // 2. Colonnes de utilisateurs
        $uCols = $conn->query("PRAGMA table_info(utilisateurs)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('status', $uCols)) {
            $conn->exec("ALTER TABLE utilisateurs ADD COLUMN status TEXT DEFAULT 'actif';");
        }

        // 3. Table logs_activites
        $conn->exec("
            CREATE TABLE IF NOT EXISTS logs_activites (
                id_log INTEGER PRIMARY KEY AUTOINCREMENT,
                utilisateur_id INTEGER,
                action TEXT NOT NULL,
                description TEXT,
                ip_adresse TEXT,
                date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL
            );
        ");
    } catch(Exception $e) {
        // Ne pas bloquer si déjà migré
    }
}

// Initialisation automatique de la base de données SQLite
function initSqliteDatabase($conn) {
    $sql = "
    CREATE TABLE IF NOT EXISTS utilisateurs (
        id_utilisateur INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_utilisateur TEXT UNIQUE NOT NULL,
        mot_de_passe TEXT NOT NULL,
        role TEXT CHECK (role IN ('admin', 'pharmacien', 'assistant', 'caissier')) DEFAULT 'assistant',
        email TEXT,
        status TEXT DEFAULT 'actif',
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS medicaments (
        id_medicament INTEGER PRIMARY KEY AUTOINCREMENT,
        code_cip TEXT UNIQUE NOT NULL,
        nom_medicament TEXT NOT NULL,
        description TEXT,
        categorie TEXT,
        prix_achat REAL NOT NULL,
        prix_vente REAL NOT NULL,
        quantite_stock INTEGER DEFAULT 0,
        stock_minimum INTEGER DEFAULT 10,
        date_expiration DATE NOT NULL,
        id_fournisseur INTEGER,
        date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_fournisseur) REFERENCES fournisseurs(id_fournisseur) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS clients (
        id_client INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        prenom TEXT NOT NULL,
        telephone TEXT,
        email TEXT,
        adresse TEXT,
        points_fidelite INTEGER DEFAULT 0,
        date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS ventes (
        id_vente INTEGER PRIMARY KEY AUTOINCREMENT,
        id_client INTEGER,
        date_vente DATETIME DEFAULT CURRENT_TIMESTAMP,
        montant_total REAL NOT NULL,
        FOREIGN KEY (id_client) REFERENCES clients(id_client) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS details_ventes (
        id_detail INTEGER PRIMARY KEY AUTOINCREMENT,
        id_vente INTEGER NOT NULL,
        id_medicament INTEGER NOT NULL,
        quantite INTEGER NOT NULL,
        prix_unitaire REAL NOT NULL,
        FOREIGN KEY (id_vente) REFERENCES ventes(id_vente) ON DELETE CASCADE,
        FOREIGN KEY (id_medicament) REFERENCES medicaments(id_medicament)
    );

    CREATE TABLE IF NOT EXISTS fournisseurs (
        id_fournisseur INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_fournisseur TEXT NOT NULL,
        contact TEXT,
        telephone TEXT,
        email TEXT,
        adresse TEXT,
        site_web TEXT,
        date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS commandes_fournisseurs (
        id_commande INTEGER PRIMARY KEY AUTOINCREMENT,
        id_fournisseur INTEGER NOT NULL,
        date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
        montant_total REAL DEFAULT 0,
        statut TEXT CHECK (statut IN ('en_attente', 'livree', 'annulee', 'approuvee', 'expediee')) DEFAULT 'en_attente',
        FOREIGN KEY (id_fournisseur) REFERENCES fournisseurs(id_fournisseur) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS details_commandes (
        id_detail INTEGER PRIMARY KEY AUTOINCREMENT,
        id_commande INTEGER NOT NULL,
        id_medicament INTEGER NOT NULL,
        quantite INTEGER NOT NULL,
        prix_achat REAL NOT NULL,
        FOREIGN KEY (id_commande) REFERENCES commandes_fournisseurs(id_commande) ON DELETE CASCADE,
        FOREIGN KEY (id_medicament) REFERENCES medicaments(id_medicament) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS logs_activites (
        id_log INTEGER PRIMARY KEY AUTOINCREMENT,
        utilisateur_id INTEGER,
        action TEXT NOT NULL,
        description TEXT,
        ip_adresse TEXT,
        date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL
    );

    INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, role, status) 
    VALUES ('admin', '" . md5('admin123') . "', 'admin', 'actif');

    INSERT INTO fournisseurs (nom_fournisseur, contact, telephone, email, adresse) VALUES
    ('Pharma Gros', 'Jean Dupont', '338210000', 'contact@pharmagros.com', 'Dakar Zone Industrielle');

    INSERT INTO medicaments (code_cip, nom_medicament, description, categorie, prix_achat, prix_vente, quantite_stock, stock_minimum, date_expiration, id_fournisseur) VALUES
    ('3400934567890', 'Paracétamol 500mg', 'Antalgique et antipyrétique', 'Antalgique', 1.50, 3.00, 100, 20, '2026-12-31', 1),
    ('3400987654321', 'Ibuprofène 400mg', 'Anti-inflammatoire', 'Anti-inflammatoire', 2.00, 4.50, 50, 15, '2026-10-31', 1),
    ('3400956781234', 'Amoxicilline 500mg', 'Antibiotique à large spectre', 'Antibiotique', 3.50, 7.00, 30, 10, '2026-08-31', 1);

    INSERT INTO clients (nom, prenom, telephone, email, adresse, points_fidelite) VALUES
    ('Diallo', 'Mamadou', '771234567', 'mamadou.diallo@email.com', 'Dakar, Senegal', 50);
    ";

    $conn->exec($sql);
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Rediriger vers login si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        $loginUrl = file_exists('login.php') ? 'login.php' : '../login.php';
        header("Location: $loginUrl");
        exit();
    }
}
?>