# 🚀 Guide de Déploiement 100% Automatique o2switch

L'application **Pharmacie Souley-Guirou Pro** intègre un **système d'installation zéro-clic**. Aucune manipulation manuelle sur phpMyAdmin n'est nécessaire !

---

## 📋 Informations du Serveur & Production

- **URL de l'application** : [https://Souley-Guirou.danayaplus.com](https://Souley-Guirou.danayaplus.com)
- **Base de données MySQL** : `vuxe8870_SouleyGuirou`
- **Utilisateur MySQL** : `vuxe8870_souley`
- **Mot de passe MySQL** : `B_;H7Lz]=Q,Gd(?_`
- **Hôte DB** : `localhost`
- **Dépôt GitHub** : `https://github.com/Alassane9869/pharma.git` (Branche `main`)

---

## ⚡ Étape Unique : Lier et Déployer le Dépôt Git dans cPanel

1. Connectez-vous à votre **cPanel o2switch**.
2. Allez dans **Fichiers** > **Gestionnaire de version Git** (*Git Version Control*).
3. Cliquez sur **Créer** (*Create*).
4. Remplissez les champs :
   - **URL du dépôt clone** (*Clone URL*) : `https://github.com/Alassane9869/pharma.git`
   - **Nom du dépôt** (*Repository Name*) : `pharma`
5. Cliquez sur **Créer**.
6. Cliquez sur **Gérer** (*Manage*) > onglet **Déploiement** (*Deploy*) > **Déployer le commit HEAD**.

---

## ✨ Auto-installation & Connexion Instantanée !

Dès que vous ouvrez votre site **[https://Souley-Guirou.danayaplus.com](https://Souley-Guirou.danayaplus.com)** :

1. L'application se connecte automatiquement à MySQL (`vuxe8870_SouleyGuirou`).
2. Si la base est vide, elle **crée automatiquement toutes les 9 tables** et insère le compte administrateur.
3. Vous pouvez directement vous connecter avec :
   - **Nom d'utilisateur** : `admin`
   - **Mot de passe** : `admin123`

*(Optionnel)* Vous pouvez aussi consulter le statut d'installation visuel à tout moment sur : [https://Souley-Guirou.danayaplus.com/install.php](https://Souley-Guirou.danayaplus.com/install.php).
