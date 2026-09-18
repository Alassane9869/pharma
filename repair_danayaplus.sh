#!/bin/bash
# ==============================================================================
# SCRIPT D'URGENCE : DISSOCIATION DE danayaplus.com ET DE LA PHARMACIE
# ==============================================================================

echo "=========================================================="
echo "🛡️ RÉPARATION ET DÉCOUPLAGE DE danayaplus.com"
echo "=========================================================="

PUBLIC_DIR="/home/vuxe8870/public_html"
PHARMA_SUBDOMAIN_DIR="/home/vuxe8870/Souley-Guirou.danayaplus.com"
REPO_DIR="/home/vuxe8870/repositories/pharma"

# 1. Vérifier la configuration des domaines cPanel
echo "🔍 1. Vérification des racines de document cPanel..."
if command -v uapi >/dev/null 2>&1; then
    uapi DomainInfo list_domains --output=yaml | grep -E "(domain:|documentroot:)" || true
fi

# 2. S'assurer que le sous-domaine pharmacie a bien ses fichiers dans son propre dossier
echo "📁 2. Sécurisation des fichiers de la pharmacie dans $PHARMA_SUBDOMAIN_DIR..."
mkdir -p "$PHARMA_SUBDOMAIN_DIR"
if [ -d "$REPO_DIR" ]; then
    rsync -a --exclude='.git' "$REPO_DIR/" "$PHARMA_SUBDOMAIN_DIR/"
    chmod -R 755 "$PHARMA_SUBDOMAIN_DIR" 2>/dev/null || true
    find "$PHARMA_SUBDOMAIN_DIR" -type f -exec chmod 644 {} + 2>/dev/null || true
fi

# 3. Nettoyer /home/vuxe8870/public_html (domaine principal danayaplus.com)
echo "🧹 3. Nettoyage des fichiers pharmacie parasites dans $PUBLIC_DIR..."

# Supprimer le dossier pharmacie-gestion de public_html s'il s'y trouve
if [ -d "$PUBLIC_DIR/pharmacie-gestion" ]; then
    echo "   -> Suppression de $PUBLIC_DIR/pharmacie-gestion..."
    rm -rf "$PUBLIC_DIR/pharmacie-gestion"
fi

# Supprimer les fichiers d'installation pharma parasites de public_html
rm -f "$PUBLIC_DIR/install.php"
rm -f "$PUBLIC_DIR/schema_mysql.sql"
rm -f "$PUBLIC_DIR/deploy.sh"
rm -f "$PUBLIC_DIR/DEPLOYMENT_O2SWITCH.md"
rm -f "$PUBLIC_DIR/repair_danayaplus.sh"

# Nettoyer l'index.php de public_html s'il contient la redirection pharmacie
if [ -f "$PUBLIC_DIR/index.php" ]; then
    if grep -q "pharmacie-gestion" "$PUBLIC_DIR/index.php"; then
        echo "   -> Nettoyage de la redirection dans $PUBLIC_DIR/index.php..."
        # Si c'était juste le fichier de 3 lignes de redirection, le supprimer ou le neutraliser
        LINES_COUNT=$(wc -l < "$PUBLIC_DIR/index.php")
        if [ "$LINES_COUNT" -le 10 ]; then
            echo "   -> Suppression du index.php parasite..."
            rm -f "$PUBLIC_DIR/index.php"
            # Si un index.html ou index original existe, laisser le serveur le servir
        else
            sed -i '/pharmacie-gestion/d' "$PUBLIC_DIR/index.php"
        fi
    fi
fi

# Nettoyer le .htaccess de public_html s'il contient la redirection pharmacie
if [ -f "$PUBLIC_DIR/.htaccess" ]; then
    if grep -q "pharmacie-gestion" "$PUBLIC_DIR/.htaccess"; then
        echo "   -> Suppression de la règle de redirection pharmacie dans $PUBLIC_DIR/.htaccess..."
        sed -i '/pharmacie-gestion/d' "$PUBLIC_DIR/.htaccess"
    fi
fi

echo "=========================================================="
echo "✅ TERMINÉ ! danayaplus.com EST COMPLÈTEMENT DÉCOUPLÉ !"
echo "Le domaine principal danayaplus.com ne redirige plus vers la pharmacie."
echo "La pharmacie reste active sur : https://Souley-Guirou.danayaplus.com"
echo "=========================================================="
