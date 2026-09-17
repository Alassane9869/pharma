<?php
// Configuration de la base de données
// Support PDO MySQL (o2switch production) et SQLite (développement local)
define('DB_HOST', 'localhost');
define('DB_NAME', 'vuxe8870_SouleyGuirou');
define('DB_USER', 'vuxe8870_souley');
define('DB_PASS', 'B_;H7Lz]=Q,Gd(?_');
define('DB_FILE', __DIR__ . '/../pharmacie.sqlite');

date_default_timezone_set('UTC');

// Connexion à la base de données (PDO MySQL avec fallback SQLite)
function getConnection() {
    static $conn = null;
    if ($conn === null) {
        // 1. Essayer la connexion MySQL / MariaDB (environnement de production o2switch)
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            // Auto-initialisation automatique de la base MySQL si vierge
            try {
                $check = $conn->query("SHOW TABLES LIKE 'utilisateurs'")->fetch();
                if (!$check) {
                    initMysqlDatabase($conn);
                }
            } catch(Exception $e) {
                // Ignorer si échec de vérification
            }

            return $conn;
        } catch(PDOException $mysqlErr) {
            // 2. Si MySQL n'est pas disponible (ex: environnement local SQLite), fallback sur SQLite
            try {
                $dbPath = DB_FILE;
                $dbExists = file_exists($dbPath);
                
                $conn = new PDO("sqlite:" . $dbPath);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $conn->exec('PRAGMA foreign_keys = ON;');

                // Fonctions personnalisées MySQL pour la compatibilité PDO SQLite
                if (method_exists($conn, 'sqliteCreateFunction')) {
                    $conn->sqliteCreateFunction('MD5', function($string) {
                        return md5((string)$string);
                    }, 1);

                    $conn->sqliteCreateFunction('CURDATE', function() {
                        return date('Y-m-d');
                    });

                    $conn->sqliteCreateFunction('NOW', function() {
                        return date('Y-m-d H:i:s');
                    });

                    $conn->sqliteCreateFunction('CONCAT', function(...$args) {
                        return implode('', $args);
                    });
                }

                // Auto-initialisation du schéma SQLite si base vierge
                if (!$dbExists || filesize($dbPath) === 0) {
                    initSqliteDatabase($conn);
                    @chmod($dbPath, 0666);
                } else {
                    migrateSqliteDatabase($conn);
                    @chmod($dbPath, 0666);
                }
            } catch(PDOException $sqliteErr) {
                die("Erreur de connexion à la base de données : " . $sqliteErr->getMessage());
            }
        }
    }
    return $conn;
}

// Initialisation automatique de la base de données MySQL / MariaDB
function initMysqlDatabase($conn) {
    try {
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        $queries = [
            "CREATE TABLE IF NOT EXISTS `utilisateurs` (
              `id_utilisateur` INT NOT NULL AUTO_INCREMENT,
              `nom_utilisateur` VARCHAR(50) NOT NULL UNIQUE,
              `mot_de_passe` VARCHAR(255) NOT NULL,
              `role` VARCHAR(20) NOT NULL DEFAULT 'assistant',
              `email` VARCHAR(100) DEFAULT NULL,
              `status` VARCHAR(20) NOT NULL DEFAULT 'actif',
              `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_utilisateur`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `fournisseurs` (
              `id_fournisseur` INT NOT NULL AUTO_INCREMENT,
              `nom_fournisseur` VARCHAR(100) NOT NULL,
              `contact` VARCHAR(100) DEFAULT NULL,
              `telephone` VARCHAR(30) DEFAULT NULL,
              `email` VARCHAR(100) DEFAULT NULL,
              `adresse` TEXT DEFAULT NULL,
              `site_web` VARCHAR(150) DEFAULT NULL,
              `date_ajout` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_fournisseur`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `medicaments` (
              `id_medicament` INT NOT NULL AUTO_INCREMENT,
              `code_cip` VARCHAR(50) NOT NULL UNIQUE,
              `nom_medicament` VARCHAR(150) NOT NULL,
              `description` TEXT DEFAULT NULL,
              `categorie` VARCHAR(50) DEFAULT NULL,
              `prix_achat` DECIMAL(10,2) NOT NULL,
              `prix_vente` DECIMAL(10,2) NOT NULL,
              `quantite_stock` INT NOT NULL DEFAULT 0,
              `stock_minimum` INT NOT NULL DEFAULT 10,
              `date_expiration` DATE NOT NULL,
              `id_fournisseur` INT DEFAULT NULL,
              `date_ajout` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_medicament`),
              KEY `idx_med_fournisseur` (`id_fournisseur`),
              CONSTRAINT `fk_med_fournisseur` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseurs` (`id_fournisseur`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `clients` (
              `id_client` INT NOT NULL AUTO_INCREMENT,
              `nom` VARCHAR(100) NOT NULL,
              `prenom` VARCHAR(100) NOT NULL,
              `telephone` VARCHAR(30) DEFAULT NULL,
              `email` VARCHAR(100) DEFAULT NULL,
              `adresse` TEXT DEFAULT NULL,
              `date_naissance` DATE DEFAULT NULL,
              `points_fidelite` INT NOT NULL DEFAULT 0,
              `date_inscription` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_client`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `ventes` (
              `id_vente` INT NOT NULL AUTO_INCREMENT,
              `id_client` INT DEFAULT NULL,
              `date_vente` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `montant_total` DECIMAL(10,2) NOT NULL,
              PRIMARY KEY (`id_vente`),
              KEY `idx_vente_client` (`id_client`),
              CONSTRAINT `fk_vente_client` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `details_ventes` (
              `id_detail` INT NOT NULL AUTO_INCREMENT,
              `id_vente` INT NOT NULL,
              `id_medicament` INT NOT NULL,
              `quantite` INT NOT NULL,
              `prix_unitaire` DECIMAL(10,2) NOT NULL,
              PRIMARY KEY (`id_detail`),
              KEY `idx_dv_vente` (`id_vente`),
              KEY `idx_dv_medicament` (`id_medicament`),
              CONSTRAINT `fk_dv_vente` FOREIGN KEY (`id_vente`) REFERENCES `ventes` (`id_vente`) ON DELETE CASCADE,
              CONSTRAINT `fk_dv_medicament` FOREIGN KEY (`id_medicament`) REFERENCES `medicaments` (`id_medicament`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `commandes_fournisseurs` (
              `id_commande` INT NOT NULL AUTO_INCREMENT,
              `id_fournisseur` INT NOT NULL,
              `date_commande` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `montant_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
              `statut` VARCHAR(20) NOT NULL DEFAULT 'en_attente',
              PRIMARY KEY (`id_commande`),
              KEY `idx_cf_fournisseur` (`id_fournisseur`),
              CONSTRAINT `fk_cf_fournisseur` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseurs` (`id_fournisseur`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `details_commandes` (
              `id_detail` INT NOT NULL AUTO_INCREMENT,
              `id_commande` INT NOT NULL,
              `id_medicament` INT NOT NULL,
              `quantite` INT NOT NULL,
              `prix_achat` DECIMAL(10,2) NOT NULL,
              PRIMARY KEY (`id_detail`),
              KEY `idx_dc_commande` (`id_commande`),
              KEY `idx_dc_medicament` (`id_medicament`),
              CONSTRAINT `fk_dc_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes_fournisseurs` (`id_commande`) ON DELETE CASCADE,
              CONSTRAINT `fk_dc_medicament` FOREIGN KEY (`id_medicament`) REFERENCES `medicaments` (`id_medicament`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "CREATE TABLE IF NOT EXISTS `logs_activites` (
              `id_log` INT NOT NULL AUTO_INCREMENT,
              `utilisateur_id` INT DEFAULT NULL,
              `action` VARCHAR(100) NOT NULL,
              `description` TEXT DEFAULT NULL,
              `ip_adresse` VARCHAR(45) DEFAULT NULL,
              `date_action` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_log`),
              KEY `idx_log_user` (`utilisateur_id`),
              CONSTRAINT `fk_log_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            "INSERT IGNORE INTO `utilisateurs` (`nom_utilisateur`, `mot_de_passe`, `role`, `status`, `email`) VALUES ('admin', MD5('admin123'), 'admin', 'actif', 'admin@Souley-Guirou.danayaplus.com');",
            "INSERT IGNORE INTO `fournisseurs` (`id_fournisseur`, `nom_fournisseur`, `contact`, `telephone`, `email`, `adresse`) VALUES (1, 'Pharma Gros', 'Jean Dupont', '338210000', 'contact@pharmagros.com', 'Dakar Zone Industrielle');",
            "INSERT IGNORE INTO `medicaments` (`code_cip`, `nom_medicament`, `description`, `categorie`, `prix_achat`, `prix_vente`, `quantite_stock`, `stock_minimum`, `date_expiration`, `id_fournisseur`) VALUES ('3400934567890', 'Paracétamol 500mg', 'Antalgique et antipyrétique', 'Antalgique', 1.50, 3.00, 100, 20, '2026-12-31', 1), ('3400987654321', 'Ibuprofène 400mg', 'Anti-inflammatoire', 'Anti-inflammatoire', 2.00, 4.50, 50, 15, '2026-10-31', 1), ('3400956781234', 'Amoxicilline 500mg', 'Antibiotique à large spectre', 'Antibiotique', 3.50, 7.00, 30, 10, '2026-08-31', 1);",
            "INSERT IGNORE INTO `clients` (`nom`, `prenom`, `telephone`, `email`, `adresse`, `points_fidelite`) VALUES ('Diallo', 'Mamadou', '771234567', 'mamadou.diallo@email.com', 'Dakar, Senegal', 50);"
        ];

        foreach ($queries as $q) {
            $conn->exec($q);
        }

        $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
    } catch(Exception $e) {
        // En cas d'erreur de création automatique
    }
}

// Migration dynamique pour SQLite (ajouter colonnes et tables manquantes)
function migrateSqliteDatabase($conn) {
    try {
        $cols = $conn->query("PRAGMA table_info(medicaments)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('categorie', $cols)) {
            $conn->exec("ALTER TABLE medicaments ADD COLUMN categorie TEXT;");
        }
        if (!in_array('id_fournisseur', $cols)) {
            $conn->exec("ALTER TABLE medicaments ADD COLUMN id_fournisseur INTEGER;");
        }

        $uCols = $conn->query("PRAGMA table_info(utilisateurs)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('status', $uCols)) {
            $conn->exec("ALTER TABLE utilisateurs ADD COLUMN status TEXT DEFAULT 'actif';");
        }

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
        // Ignorer si déjà migré
    }
}

// Initialisation automatique de la base SQLite
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
        date_naissance DATE,
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