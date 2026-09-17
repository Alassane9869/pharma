# 🚀 Guide de Déploiement o2switch (cPanel Git Version Control)

Ce guide détaille les étapes simples pour déployer **Pharmacie Souley-Guirou Pro** directement depuis GitHub sur le sous-domaine **Souley-Guirou.danayaplus.com** avec la base de données MySQL o2switch.

---

## 📋 Informations du Serveur & Production

- **URL de l'application** : [https://Souley-Guirou.danayaplus.com](https://Souley-Guirou.danayaplus.com)
- **Base de données MySQL** : `vuxe8870_SouleyGuirou`
- **Utilisateur MySQL** : `vuxe8870_souley`
- **Mot de passe MySQL** : `B_;H7Lz]=Q,Gd(?_`
- **Hôte DB** : `localhost`
- **Dépôt GitHub** : `https://github.com/Alassane9869/pharma.git` (Branche `main`)

---

## 🗄️ Étape 1 : Importer la Base de Données dans phpMyAdmin (o2switch)

1. Connectez-vous à votre **cPanel o2switch**.
2. Allez dans la section **Bases de données** > **phpMyAdmin**.
3. Sélectionnez la base de données **`vuxe8870_SouleyGuirou`** dans le menu de gauche.
4. Cliquez sur l'onglet **Importer** en haut.
5. Choisissez le fichier **`schema_mysql.sql`** (qui se trouve à la racine de ce projet ou sur votre ordinateur).
6. Cliquez sur **Exécuter** en bas pour importer la structure et les données de démarrage.

---

## ⚡ Étape 2 : Lier et Déployer le Dépôt Git dans cPanel

1. Dans votre cPanel o2switch, allez dans **Fichiers** > **Gestionnaire de version Git** (*Git Version Control*).
2. Cliquez sur **Créer** (*Create*).
3. Remplissez les champs suivants :
   - **URL du dépôt clone** (*Clone URL*) : `https://github.com/Alassane9869/pharma.git`
   - **Chemin du dépôt** (*Repository Path*) : `repositories/pharma` (ou laisser par défaut)
   - **Nom du dépôt** (*Repository Name*) : `pharma`
4. Cliquez sur **Créer**. cPanel va cloner le projet.
5. Une fois le dépôt créé, cliquez sur **Gérer** (*Manage*).
6. Cliquez sur l'onglet **Déploiement** (*Deploy HEAD Commit*).
7. Cliquez sur **Déployer le commit HEAD** (*Deploy HEAD Commit*).

---

## 🔑 Étape 3 : Accès et Connexion Administrateur

1. Accédez à l'application sur : **[https://Souley-Guirou.danayaplus.com](https://Souley-Guirou.danayaplus.com)**
2. Connectez-vous avec le compte administrateur initial :
   - **Nom d'utilisateur** : `admin`
   - **Mot de passe** : `admin123`

---

## 🔒 Étape 4 : Sécurité & Configurations

- La connexion à la base de données MySQL `vuxe8870_SouleyGuirou` se fait automatiquement via `includes/config.php`.
- Le fichier `.htaccess` assure la réécriture d'URL et la sécurité du serveur.
- Si le serveur fonctionne en local (sans serveur MySQL disponible), l'application bascule automatiquement sur SQLite pour vous permettre de travailler en hors-ligne.
