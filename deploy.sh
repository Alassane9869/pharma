#!/bin/bash
echo "=========================================================="
echo "🚀 Déploiement et Initialisation Automatique o2switch"
echo "Domaine : Souley-Guirou.danayaplus.com"
echo "=========================================================="

REPO_DIR="/home/vuxe8870/repositories/pharma"
WEB_DIR="/home/vuxe8870/Souley-Guirou.danayaplus.com"

# 1. Mise à jour Git
echo "📥 1. Récupération du code depuis GitHub..."
cd "$REPO_DIR" || exit 1
git fetch --all
git reset --hard origin/main

# 2. Copie propre des fichiers (sans le dossier .git)
echo "📁 2. Synchronisation des fichiers vers $WEB_DIR..."
rsync -a --exclude='.git' "$REPO_DIR/" "$WEB_DIR/"

# 3. Correction ultime des permissions o2switch (Répertoires 755, Fichiers 644)
echo "🔒 3. Normalisation absolue des permissions o2switch..."
chmod -R 755 "$WEB_DIR" 2>/dev/null || true
find "$WEB_DIR" -type f -exec chmod 644 {} + 2>/dev/null || true

# 4. Auto-initialisation de la base de données MySQL
echo "🗄️ 4. Initialisation de la base MySQL vuxe8870_SouleyGuirou..."
php "$WEB_DIR/install.php"

echo "=========================================================="
echo "✅ DÉPLOIEMENT TERMINÉ AVEC SUCCÈS !"
echo "URL : https://Souley-Guirou.danayaplus.com"
echo "=========================================================="
