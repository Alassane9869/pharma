-- ============================================================
-- SCHÉMA DE BASE DE DONNÉES MYSQL / MARIADB
-- PROJET : Pharmacie Souley-Guirou
-- BASE DE DONNÉES CIBLE : vuxe8870_SouleyGuirou
-- SERVEUR : o2switch (cPanel) / Localhost MySQL
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Table : utilisateurs
DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE `utilisateurs` (
  `id_utilisateur` INT NOT NULL AUTO_INCREMENT,
  `nom_utilisateur` VARCHAR(50) NOT NULL UNIQUE,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'assistant',
  `email` VARCHAR(100) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'actif',
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table : fournisseurs
DROP TABLE IF EXISTS `fournisseurs`;
CREATE TABLE `fournisseurs` (
  `id_fournisseur` INT NOT NULL AUTO_INCREMENT,
  `nom_fournisseur` VARCHAR(100) NOT NULL,
  `contact` VARCHAR(100) DEFAULT NULL,
  `telephone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `adresse` TEXT DEFAULT NULL,
  `site_web` VARCHAR(150) DEFAULT NULL,
  `date_ajout` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_fournisseur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table : medicaments
DROP TABLE IF EXISTS `medicaments`;
CREATE TABLE `medicaments` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table : clients
DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table : ventes
DROP TABLE IF EXISTS `ventes`;
CREATE TABLE `ventes` (
  `id_vente` INT NOT NULL AUTO_INCREMENT,
  `id_client` INT DEFAULT NULL,
  `date_vente` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `montant_total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id_vente`),
  KEY `idx_vente_client` (`id_client`),
  CONSTRAINT `fk_vente_client` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table : details_ventes
DROP TABLE IF EXISTS `details_ventes`;
CREATE TABLE `details_ventes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table : commandes_fournisseurs
DROP TABLE IF EXISTS `commandes_fournisseurs`;
CREATE TABLE `commandes_fournisseurs` (
  `id_commande` INT NOT NULL AUTO_INCREMENT,
  `id_fournisseur` INT NOT NULL,
  `date_commande` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `montant_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `statut` VARCHAR(20) NOT NULL DEFAULT 'en_attente',
  PRIMARY KEY (`id_commande`),
  KEY `idx_cf_fournisseur` (`id_fournisseur`),
  CONSTRAINT `fk_cf_fournisseur` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseurs` (`id_fournisseur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table : details_commandes
DROP TABLE IF EXISTS `details_commandes`;
CREATE TABLE `details_commandes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table : logs_activites
DROP TABLE IF EXISTS `logs_activites`;
CREATE TABLE `logs_activites` (
  `id_log` INT NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_adresse` VARCHAR(45) DEFAULT NULL,
  `date_action` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_user` (`utilisateur_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONNÉES INITIALES (SEED DATA)
-- ============================================================

-- Compte Administrateur par défaut (admin / admin123)
INSERT INTO `utilisateurs` (`nom_utilisateur`, `mot_de_passe`, `role`, `status`, `email`) VALUES
('admin', MD5('admin123'), 'admin', 'actif', 'admin@Souley-Guirou.danayaplus.com');

-- Fournisseur initial
INSERT INTO `fournisseurs` (`nom_fournisseur`, `contact`, `telephone`, `email`, `adresse`) VALUES
('Pharma Gros', 'Jean Dupont', '338210000', 'contact@pharmagros.com', 'Dakar Zone Industrielle');

-- Médicaments initiaux
INSERT INTO `medicaments` (`code_cip`, `nom_medicament`, `description`, `categorie`, `prix_achat`, `prix_vente`, `quantite_stock`, `stock_minimum`, `date_expiration`, `id_fournisseur`) VALUES
('3400934567890', 'Paracétamol 500mg', 'Antalgique et antipyrétique', 'Antalgique', 1.50, 3.00, 100, 20, '2026-12-31', 1),
('3400987654321', 'Ibuprofène 400mg', 'Anti-inflammatoire', 'Anti-inflammatoire', 2.00, 4.50, 50, 15, '2026-10-31', 1),
('3400956781234', 'Amoxicilline 500mg', 'Antibiotique à large spectre', 'Antibiotique', 3.50, 7.00, 30, 10, '2026-08-31', 1);

-- Client initial
INSERT INTO `clients` (`nom`, `prenom`, `telephone`, `email`, `adresse`, `points_fidelite`) VALUES
('Diallo', 'Mamadou', '771234567', 'mamadou.diallo@email.com', 'Dakar, Senegal', 50);
