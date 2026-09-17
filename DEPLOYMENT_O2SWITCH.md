# 🚀 Guide de Déploiement o2switch (cPanel Git Version Control)

Ce guide détaille les étapes simples pour lier et déployer **Pharmacie Souley-Guirou Pro** directement depuis GitHub vers votre hébergement **o2switch**.

---

## 📋 Prérequis sur o2switch

1. **Compte cPanel o2switch** actif.
2. **Version de PHP 8.0, 8.1, 8.2 ou 8.3** activée dans cPanel > *Sélectionner une version de PHP* (`pdo_sqlite` activé par défaut).
3. **URL du Dépôt GitHub** : `https://github.com/Alassane9869/pharma.git`

---

## ⚡ Étape 1 : Configurer le Dépôt Git dans cPanel

1. Connectez-vous à votre interface **cPanel o2switch**.
2. Allez dans la section **Fichiers** > **Gestionnaire de version Git** (*Git Version Control*).
3. Cliquez sur le bouton bleu **Créer** (*Create*).
4. Remplissez les champs comme suit :
   - **URL du dépôt clone** (*Clone URL*) : `https://github.com/Alassane9869/pharma.git`
   - **Chemin du dépôt** (*Repository Path*) : `repositories/pharma` (ou laissez par défaut)
   - **Nom du dépôt** (*Repository Name*) : `pharma`
5. Cliquez sur **Créer**. cPanel va cloner automatiquement le dépôt.

---

## 🔄 Étape 2 : Déployer le Projet sur `public_html`

1. Dans cPanel > **Gestionnaire de version Git**, cliquez sur **Gérer** (*Manage*) à côté du dépôt `pharma`.
2. Allez dans l'onglet **Déploiement** (*Deploy HEAD Commit*).
3. Vérifiez les détails et cliquez sur **Déployer le commit HEAD** (*Deploy HEAD Commit*).

> **Note** : Le fichier `.cpanel.yml` présent à la racine du dépôt copie automatiquement l'ensemble des fichiers vers `public_html` et applique les permissions nécessaires pour la base de données SQLite.

---

## 🔒 Étape 3 : Vérification et Sécurité

1. Rendez-vous sur votre nom de domaine (ex: `https://votre-domaine.com`).
2. La redirection `.htaccess` vous amènera directement sur l'interface d'accueil.
3. Connectez-vous avec les identifiants administrateur :
   - **Nom d'utilisateur** : `admin`
   - **Mot de passe** : `admin123`
4. **Sécurité SQLite** : Le fichier `.htaccess` configuré bloque automatiquement tout accès direct au fichier `pharmacie.sqlite`.

---

## 🛠️ Résolution des Problèmes Fréquents (Troubleshooting)

- **Erreur 500 sur le serveur** : Vérifiez dans cPanel > *Erreurs* (Logs). Assurez-vous que PHP 8.0+ est sélectionné.
- **Permissions écriture SQLite** : Si le serveur indique que la base est verrouillée, passez les permissions du fichier `pharmacie.sqlite` en `666` via le *Gestionnaire de fichiers cPanel*.
