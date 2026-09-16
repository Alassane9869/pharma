<div align="center">

  <img src="pharmacie-gestion/assets/images/logo.png" alt="Pharmacie Souley-Guirou Logo" width="120" style="border-radius: 50%; box-shadow: 0 8px 25px rgba(0,0,0,0.2);">

  # 🏥 Pharmacie Souley-Guirou Pro v1.0
  ### *Système Exécutif de Gestion de Pharmacie, Caisse POS & CRM Client*

  [![PHP Version](https://img.shields.io/badge/PHP-8.0%20%7C%208.1%20%7C%208.2%20%7C%208.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
  [![Database](https://img.shields.io/badge/Database-SQLite%203-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://sqlite.org)
  [![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
  [![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
  [![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen?style=for-the-badge)](#)

  <p align="center">
    <b>Une solution professionnelle, rapide et 100% autonome pour la gestion intégrée de pharmacie.</b><br>
    Caisse enregistreuse instantanée &bull; Édition de tickets A4 PDF &bull; CRM & Fidélité Client &bull; Auto-migration SQLite Zero-Config
  </p>

</div>

---

## 🌟 Aperçu du Projet

**Pharmacie Souley-Guirou Pro** est une application web moderne de gestion d'officine pharmaceutique conçue pour offrir une expérience fluide, hautement performante et visuellement élégante. 

Grâce à son architecture **Zero-Config basée sur SQLite 3**, l'application fonctionne immédiatement sans nécessiter d'installation complexe de serveur MySQL.

```
                    ┌─────────────────────────────────────────┐
                    │      PHARMACIE SOULEY-GUIROU PRO        │
                    └────────────────────┬────────────────────┘
                                         │
         ┌───────────────────────────────┼───────────────────────────────┐
         │                               │                               │
┌────────┴────────┐             ┌────────┴────────┐             ┌────────┴────────┐
│  TERMINAL POS   │             │   GESTION CRM   │             │ DOSSIERS PDF A4 │
│ Caisse & Tickets│             │  Points & VIP   │             │ Reçus & Stocks  │
└─────────────────┘             └─────────────────┘             └─────────────────┘
```

---

## 🔥 Fonctionnalités Majeures

### 🛒 1. Terminal de Caisse POS & Ventes Enregistreuses
- **Recherche Instantanée** : Saisie rapide par Nom de médicament ou Code CIP avec autocomplétion.
- **Ajout Rapide 1-Clic** : Boutons de raccourcis pour les médicaments les plus sollicités.
- **Gestion du Panier** : Ajustement interactif des quantités (+/-), calcul du total en temps réel et détection automatique des ruptures de stock.
- **Impression PDF Automatique** : Dès la validation de la vente, le ticket/facture A4 au format officiel s'ouvre automatiquement dans un onglet d'impression.
- **Historique de Caisse** : Suivi des ventes du jour avec détail des produits vendus et boutons de réimpression des reçus.

### 💊 2. Gestion des Médicaments & Stocks
- **Suivi des Stocks** : Quantités en stock, seuils minimums d'alerte et détection des ruptures.
- **Péremptions Proches** : Alertes automatiques pour les produits expirant dans les 30 jours.
- **Association Fournisseurs** : Liaison directe des références produits avec les partenaires fournisseurs.
- **Rapports PDF d'Inventaire** : Génération d'états de stock complets prêts pour audit ou impression.

### 👥 3. CRM & Programme de Fidélité Client
- **Règle de Fidélisation** : Attribution automatique de **1 Point pour chaque tranche de 1 000 FCFA d'achat**.
- **Statuts VIP et Niveaux** : Catégorisation automatique des membres (`👑 VIP`, `⭐ Fidèle`, `Membre`).
- **Fiche Client & Historique Achats** : Consultation des dépenses cumulées, du panier moyen et de l'historique complet des factures du client via modal.

### 📊 4. Tableau de Bord Exécutif & Analytics
- **Graphiques Interactifs** : Analyse visuelle du Chiffre d'Affaires sur 7 jours via Chart.js.
- **Indicateurs KPIs** : Suivi des ventes du jour, CA du mois, nombre de clients et alertes produits.
- **Top 5 Médicaments** : Classement dynamique des meilleures ventes en volume et valeur.

### 📄 5. Reçus & Documents PDF Corporate
- **Tickets A4 de Caisse** : Factures officielles comportant le numéro `#FAC-YYYY-XXXXXX`, le logo de l'officine, le détail des articles, les points crédités et les mentions légales.
- **Bons de Commande Fournisseurs** : Documents officiels de réapprovisionnement imprimables en PDF.

---

## 🛠️ Stack Technique

| Composant | Technologie Utilisée |
| :--- | :--- |
| **Langage Backend** | PHP 8.0 / 8.1 / 8.2 / 8.3 (Compatibilité ascendante garantie) |
| **Base de Données** | SQLite 3 (Auto-création & Auto-migrations automatiques) |
| **Interface Frontend** | HTML5, Vanilla CSS3, JavaScript ES6, Bootstrap 5.1.3 |
| **Iconographie & Fonts**| FontAwesome 6 Pro, Google Fonts (`Inter`, `JetBrains Mono`) |
| **Moteur de Graphiques**| Chart.js 3.9 |

---

## 🚀 Démarrage Rapide

### Prérequis
- **PHP 8.0 ou supérieur** (avec l'extension `pdo_sqlite` activée).

### Lancement en 1 Ligne de Commande

1. **Cloner le dépôt Git** :
   ```bash
   git clone https://github.com/Alassane9869/pharma.git
   cd pharma
   ```

2. **Démarrer le serveur de développement PHP** :
   ```bash
   php -S 127.0.0.1:8000
   ```

3. **Accéder à l'application** :
   Ouvrez votre navigateur et rendez-vous sur :
   👉 **`http://127.0.0.1:8000/`**

---

## 🔑 Identifiants de Connexion par Défaut

Lors du premier démarrage, la base SQLite est créée et initialisée automatiquement avec un compte administrateur :

| Champ | Valeur par Défaut |
| :--- | :--- |
| **Nom d'utilisateur** | `admin` |
| **Mot de passe** | `admin123` |
| **Rôle** | `Administrateur` |

---

## 📁 Structure du Projet

```
kadi projet/
├── index.php                      # Redirection racine vers le module pharmacie
├── README.md                      # Documentation officielle du projet
├── .gitignore                     # Configuration des exclusions Git
└── pharmacie-gestion/
    ├── pharmacie.sqlite           # Base de données SQLite auto-générée
    ├── index.php                  # Tableau de bord principal (Accueil)
    ├── dashboard.php              # Tableau de bord exécutif & graphiques
    ├── login.php                  # Interface de connexion sécurisée
    ├── logout.php                 # Déconnexion de session
    ├── includes/
    │   ├── config.php             # Connexion PDO SQLite & migrations auto
    │   ├── fonctions.php          # Helpers & utilitaires système
    │   └── header.php             # En-tête global, CSS & navigation
    ├── pages/
    │   ├── ventes.php             # Terminal de caisse & enregistrement POS
    │   ├── details_vente.php      # Ticket / Facture A4 PDF d'impression
    │   ├── medicaments.php        # Gestion du catalogue & stocks
    │   ├── clients.php            # Gestion du CRM & fidélité client
    │   ├── ajax_client_details.php# Modal détails & fiche historique client
    │   ├── fournisseurs.php       # Gestion des partenaires fournisseurs
    │   ├── commande_fournisseur.php# Passage de commandes fournisseurs
    │   ├── factures.php           # Journal & historique global des factures
    │   ├── export_pdf.php         # Moteur de génération des rapports PDF
    │   └── admin.php              # Administration des utilisateurs
    └── assets/
        ├── css/style.css          # Feuille de style personnalisée
        └── images/logo.png        # Logo officiel de la pharmacie
```

---

## 🛡️ Sécurité & Bonnes Pratiques

- **Hashage des Mots de Passe** : MD5 / Compatibilité PDO sécurisée.
- **Protection des Sessions** : Authentification requise sur l'ensemble des pages d'administration via `requireLogin()`.
- **Contrôle d'Accès par Rôles** : Restriction d'accès aux modules sensibles selon les privilèges (`admin`, `pharmacien`, `caissier`).
- **Préparation des Requêtes SQL** : Utilisation stricte des requêtes préparées PDO pour immuniser l'application contre les injections SQL.

---

## 📄 Licence & Crédits

Ce projet est sous licence **MIT**.  
Développé avec ❤️ pour la **Pharmacie Souley-Guirou**.

---

<div align="center">
  <b>Pharmacie Souley-Guirou Pro v1.0 &bull; 2026</b><br>
  <sub>Propulsé par PHP & SQLite 3</sub>
</div>
