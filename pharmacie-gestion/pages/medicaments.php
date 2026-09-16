<?php
require_once '../includes/config.php';
requireLogin();

// Définir le titre de la page
$page_title = 'Gestion des Médicaments - Pharmacie Natinin';
$include_chart = false;

$conn = getConnection();
$message = '';
$error = '';

// ===== TRAITEMENT DES ACTIONS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $conn->prepare("
                        INSERT INTO medicaments (
                            code_cip, nom_medicament, description, categorie,
                            prix_achat, prix_vente, quantite_stock, stock_minimum,
                            date_expiration, id_fournisseur
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        trim($_POST['code_cip']),
                        trim($_POST['nom_medicament']),
                        trim($_POST['description']),
                        $_POST['categorie'] ?: null,
                        floatval($_POST['prix_achat']),
                        floatval($_POST['prix_vente']),
                        intval($_POST['quantite_stock']),
                        intval($_POST['stock_minimum']),
                        $_POST['date_expiration'],
                        $_POST['id_fournisseur'] ?: null
                    ]);
                    $message = "✅ Médicament ajouté avec succès !";
                    break;
                    
                case 'edit':
                    $stmt = $conn->prepare("
                        UPDATE medicaments SET
                            code_cip = ?, nom_medicament = ?, description = ?,
                            categorie = ?, prix_achat = ?, prix_vente = ?,
                            quantite_stock = ?, stock_minimum = ?,
                            date_expiration = ?, id_fournisseur = ?
                        WHERE id_medicament = ?
                    ");
                    $stmt->execute([
                        trim($_POST['code_cip']),
                        trim($_POST['nom_medicament']),
                        trim($_POST['description']),
                        $_POST['categorie'] ?: null,
                        floatval($_POST['prix_achat']),
                        floatval($_POST['prix_vente']),
                        intval($_POST['quantite_stock']),
                        intval($_POST['stock_minimum']),
                        $_POST['date_expiration'],
                        $_POST['id_fournisseur'] ?: null,
                        intval($_POST['id_medicament'])
                    ]);
                    $message = "✅ Médicament modifié avec succès !";
                    break;
                    
                case 'delete':
                    // Vérifier si le médicament est utilisé dans des ventes
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM details_ventes WHERE id_medicament = ?");
                    $stmt->execute([$_POST['id_medicament']]);
                    $used = $stmt->fetchColumn();
                    
                    if ($used > 0) {
                        $error = "❌ Ce médicament a des ventes associées et ne peut pas être supprimé.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM medicaments WHERE id_medicament = ?");
                        $stmt->execute([$_POST['id_medicament']]);
                        $message = "✅ Médicament supprimé avec succès !";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = "❌ Erreur : " . $e->getMessage();
        }
    }
}

// ===== RECHERCHE ET FILTRES =====
$search = $_GET['search'] ?? '';
$categorie_filter = $_GET['categorie'] ?? '';
$stock_filter = $_GET['stock'] ?? '';

$sql = "SELECT m.*, f.nom_fournisseur 
        FROM medicaments m 
        LEFT JOIN fournisseurs f ON m.id_fournisseur = f.id_fournisseur
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (m.nom_medicament LIKE ? OR m.code_cip LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categorie_filter) {
    $sql .= " AND m.categorie = ?";
    $params[] = $categorie_filter;
}

if ($stock_filter === 'alerte') {
    $sql .= " AND m.quantite_stock <= m.stock_minimum AND m.quantite_stock > 0";
} elseif ($stock_filter === 'rupture') {
    $sql .= " AND m.quantite_stock = 0";
} elseif ($stock_filter === 'disponible') {
    $sql .= " AND m.quantite_stock > m.stock_minimum";
}

$sql .= " ORDER BY m.nom_medicament";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$medicaments = $stmt->fetchAll();

// ===== RÉCUPÉRER LES DONNÉES POUR LES FILTRES =====
$fournisseurs = $conn->query("SELECT * FROM fournisseurs ORDER BY nom_fournisseur")->fetchAll();

$categories = $conn->query("
    SELECT DISTINCT categorie 
    FROM medicaments 
    WHERE categorie IS NOT NULL AND categorie != ''
    ORDER BY categorie
")->fetchAll();

// ===== STATISTIQUES =====
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
$stats['alerte'] = $conn->query("SELECT COUNT(*) FROM medicaments WHERE quantite_stock <= stock_minimum AND quantite_stock > 0")->fetchColumn();
$stats['rupture'] = $conn->query("SELECT COUNT(*) FROM medicaments WHERE quantite_stock = 0")->fetchColumn();
$stats['expiration'] = $conn->query("SELECT COUNT(*) FROM medicaments WHERE date_expiration BETWEEN date('now', 'localtime') AND date('now', 'localtime', '+30 days')")->fetchColumn();
$stats['valeur_stock'] = $conn->query("SELECT COALESCE(SUM(prix_achat * quantite_stock), 0) FROM medicaments")->fetchColumn();

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header">
    <div>
        <h4><i class="fas fa-pills"></i> Gestion des Médicaments</h4>
        <span class="date-info">
            <i class="far fa-calendar-alt"></i> 
            <?= date('d/m/Y à H:i') ?>
        </span>
    </div>
    <div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <a href="export_pdf.php?type=inventaire" target="_blank" class="btn btn-outline-success btn-sm me-1">
            <i class="fas fa-file-pdf"></i> Imprimer Rapport PDF
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Nouveau médicament
        </button>
    </div>
</div>

<!-- ===== MESSAGES ===== -->
<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ===== STATISTIQUES ===== -->
<div class="row g-3 mb-4 animated">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-pills"></i></div>
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">Total médicaments</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-warning">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-number"><?= $stats['alerte'] ?></div>
            <div class="stat-label">Stock alerte</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-danger">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-number"><?= $stats['rupture'] ?></div>
            <div class="stat-label">En rupture</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-info">
            <div class="stat-icon"><i class="fas fa-warehouse"></i></div>
            <div class="stat-number"><?= number_format($stats['valeur_stock'], 0) ?></div>
            <div class="stat-label">Valeur stock (CFA)</div>
        </div>
    </div>
</div>

<!-- ===== LISTE DES MÉDICAMENTS ===== -->
<div class="widget">
    <div class="widget-header">
        <h5><i class="fas fa-list"></i> Liste des médicaments</h5>
        <div class="d-flex gap-2">
            <span class="badge bg-secondary"><?= count($medicaments) ?> produit(s)</span>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
    <div class="widget-body">
        
        <!-- ===== FILTRES ===== -->
        <form method="GET" class="mb-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="🔍 Rechercher..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="categorie" class="form-select form-select-sm">
                        <option value="">Toutes catégories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['categorie'] ?>" <?= $categorie_filter == $cat['categorie'] ? 'selected' : '' ?>>
                                <?= $cat['categorie'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="stock" class="form-select form-select-sm">
                        <option value="">Tous stocks</option>
                        <option value="disponible" <?= $stock_filter == 'disponible' ? 'selected' : '' ?>>✅ Disponible</option>
                        <option value="alerte" <?= $stock_filter == 'alerte' ? 'selected' : '' ?>>⚠️ Alerte</option>
                        <option value="rupture" <?= $stock_filter == 'rupture' ? 'selected' : '' ?>>❌ Rupture</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-success w-100">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="medicaments.php" class="btn btn-sm btn-secondary w-100">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </a>
                </div>
            </div>
        </form>

        <!-- ===== TABLEAU ===== -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-medicaments" id="medicamentsTable">
                <thead>
                    <tr>
                        <th style="width: 35px; text-align: center;">#</th>
                        <th style="min-width: 180px;">Nom du médicament</th>
                        <th style="width: 120px;">Catégorie</th>
                        <th style="width: 120px; text-align: right;">Prix vente</th>
                        <th style="width: 100px; text-align: center;">Stock</th>
                        <th style="width: 140px;">Fournisseur</th>
                        <th style="width: 130px; text-align: center;">Expiration</th>
                        <th style="width: 110px; text-align: center;">Statut</th>
                        <th style="width: 130px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicaments)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted d-block mb-2"></i>
                                <p class="text-muted">Aucun médicament trouvé</p>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                                    <i class="fas fa-plus"></i> Ajouter un médicament
                                </button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($medicaments as $index => $med): 
                            // Déterminer le statut du stock
                            if ($med['quantite_stock'] <= 0) {
                                $stockBadge = '<span class="badge bg-danger">Rupture</span>';
                                $stockClass = 'text-danger';
                                $stockBg = '#f8d7da';
                            } elseif ($med['quantite_stock'] <= $med['stock_minimum']) {
                                $stockBadge = '<span class="badge bg-warning text-dark">Stock bas</span>';
                                $stockClass = 'text-warning';
                                $stockBg = '#fff3cd';
                            } else {
                                $stockBadge = '<span class="badge bg-success">Disponible</span>';
                                $stockClass = 'text-success';
                                $stockBg = '#d1e7dd';
                            }
                            
                            // Statut expiration
                            $today = new DateTime();
                            $expiration = new DateTime($med['date_expiration']);
                            $diff = $today->diff($expiration)->days;
                            
                            if ($expiration < $today) {
                                $expBadge = '<span class="badge bg-danger">Expiré</span>';
                            } elseif ($diff <= 30) {
                                $expBadge = '<span class="badge bg-warning text-dark">' . $diff . ' jrs</span>';
                            } else {
                                $expBadge = '<span class="badge bg-success">Valide</span>';
                            }
                        ?>
                        <tr>
                            <td class="text-center text-muted" style="font-size: 12px;"><?= $index + 1 ?></td>
                            <td>
                                <span class="product-name"><?= htmlspecialchars($med['nom_medicament']) ?></span>
                                <?php if (!empty($med['description'])): ?>
                                    <br><small class="text-muted" style="font-size: 11px;"><?= substr(htmlspecialchars($med['description']), 0, 40) ?><?= strlen($med['description']) > 40 ? '...' : '' ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($med['categorie']): ?>
                                    <span class="badge bg-info" style="font-size: 11px;"><?= htmlspecialchars($med['categorie']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: #2e7d32;">
                                <?= number_format($med['prix_vente'], 0) ?> CFA
                            </td>
                            <td style="text-align: center; background-color: <?= $stockBg ?>; border-radius: 4px;">
                                <span class="fw-bold <?= $stockClass ?>" style="font-size: 16px;"><?= $med['quantite_stock'] ?></span>
                                <br><small class="text-muted" style="font-size: 10px;">Min: <?= $med['stock_minimum'] ?></small>
                            </td>
                            <td style="font-size: 13px;">
                                <?= htmlspecialchars($med['nom_fournisseur'] ?? '-') ?>
                            </td>
                            <td style="text-align: center;">
                                <div><?= date('d/m/Y', strtotime($med['date_expiration'])) ?></div>
                                <div style="margin-top: 3px;"><?= $expBadge ?></div>
                            </td>
                            <td style="text-align: center;">
                                <?= $stockBadge ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <button class="btn btn-warning btn-action" onclick='editMedicament(<?= json_encode($med) ?>)' title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-action" onclick="deleteMedicament(<?= $med['id_medicament'] ?>, '<?= addslashes($med['nom_medicament']) ?>')" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="btn btn-info btn-action" onclick="viewMedicament(<?= $med['id_medicament'] ?>)" title="Détails">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ===== MODAL AJOUT ===== -->
<!-- ============================================ -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Ajouter un médicament</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Code CIP *</label>
                            <input type="text" name="code_cip" class="form-control" 
                                   pattern="[0-9]{13}" placeholder="13 chiffres" required>
                            <small class="text-muted">Code à 13 chiffres</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nom du médicament *</label>
                            <input type="text" name="nom_medicament" class="form-control" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="2" 
                                      placeholder="Description du médicament..."></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Catégorie</label>
                            <select name="categorie" class="form-control">
                                <option value="">Sélectionner</option>
                                <option value="Antalgiques">Antalgiques</option>
                                <option value="Antibiotiques">Antibiotiques</option>
                                <option value="Anti-inflammatoires">Anti-inflammatoires</option>
                                <option value="Antihistaminiques">Antihistaminiques</option>
                                <option value="Antiviraux">Antiviraux</option>
                                <option value="Vaccins">Vaccins</option>
                                <option value="Vitamines">Vitamines</option>
                                <option value="Cardiovasculaires">Cardiovasculaires</option>
                                <option value="Digestifs">Digestifs</option>
                                <option value="Autres">Autres</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Fournisseur</label>
                            <select name="id_fournisseur" class="form-control">
                                <option value="">Aucun</option>
                                <?php foreach($fournisseurs as $f): ?>
                                    <option value="<?= $f['id_fournisseur'] ?>">
                                        <?= htmlspecialchars($f['nom_fournisseur']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Date d'expiration *</label>
                            <input type="date" name="date_expiration" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Prix achat (CFA)</label>
                            <input type="number" name="prix_achat" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Prix vente (CFA) *</label>
                            <input type="number" name="prix_vente" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Quantité stock</label>
                            <input type="number" name="quantite_stock" class="form-control" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock minimum</label>
                            <input type="number" name="stock_minimum" class="form-control" value="10">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ===== MODAL MODIFICATION ===== -->
<!-- ============================================ -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le médicament</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_medicament" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Code CIP *</label>
                            <input type="text" name="code_cip" id="edit_code_cip" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nom du médicament *</label>
                            <input type="text" name="nom_medicament" id="edit_nom" class="form-control" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Catégorie</label>
                            <select name="categorie" id="edit_categorie" class="form-control">
                                <option value="">Sélectionner</option>
                                <option value="Antalgiques">Antalgiques</option>
                                <option value="Antibiotiques">Antibiotiques</option>
                                <option value="Anti-inflammatoires">Anti-inflammatoires</option>
                                <option value="Antihistaminiques">Antihistaminiques</option>
                                <option value="Antiviraux">Antiviraux</option>
                                <option value="Vaccins">Vaccins</option>
                                <option value="Vitamines">Vitamines</option>
                                <option value="Cardiovasculaires">Cardiovasculaires</option>
                                <option value="Digestifs">Digestifs</option>
                                <option value="Autres">Autres</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Fournisseur</label>
                            <select name="id_fournisseur" id="edit_fournisseur" class="form-control">
                                <option value="">Aucun</option>
                                <?php foreach($fournisseurs as $f): ?>
                                    <option value="<?= $f['id_fournisseur'] ?>">
                                        <?= htmlspecialchars($f['nom_fournisseur']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Date d'expiration *</label>
                            <input type="date" name="date_expiration" id="edit_expiration" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Prix achat (CFA)</label>
                            <input type="number" name="prix_achat" id="edit_prix_achat" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Prix vente (CFA) *</label>
                            <input type="number" name="prix_vente" id="edit_prix_vente" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Quantité stock</label>
                            <input type="number" name="quantite_stock" id="edit_stock" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock minimum</label>
                            <input type="number" name="stock_minimum" id="edit_stock_min" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Modifier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ===== MODAL DÉTAILS ===== -->
<!-- ============================================ -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails du médicament</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewContent">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Chargement...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ===== SCRIPTS ===== -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== TOGGLE SIDEBAR =====
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}

document.addEventListener('click', function(e) {
    if (window.innerWidth <= 992) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    }
});

// ===== MODIFIER UN MÉDICAMENT =====
function editMedicament(med) {
    document.getElementById('edit_id').value = med.id_medicament;
    document.getElementById('edit_code_cip').value = med.code_cip;
    document.getElementById('edit_nom').value = med.nom_medicament;
    document.getElementById('edit_description').value = med.description || '';
    document.getElementById('edit_categorie').value = med.categorie || '';
    document.getElementById('edit_fournisseur').value = med.id_fournisseur || '';
    document.getElementById('edit_expiration').value = med.date_expiration;
    document.getElementById('edit_prix_achat').value = med.prix_achat;
    document.getElementById('edit_prix_vente').value = med.prix_vente;
    document.getElementById('edit_stock').value = med.quantite_stock;
    document.getElementById('edit_stock_min').value = med.stock_minimum;
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ===== SUPPRIMER UN MÉDICAMENT =====
function deleteMedicament(id, name) {
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer le médicament :\n"' + name + '" ?')) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_medicament" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ===== VOIR DÉTAILS =====
function viewMedicament(id) {
    document.getElementById('viewContent').innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Chargement des détails...</p>
        </div>
    `;
    
    fetch(`ajax_medicament_details.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('viewContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('viewModal')).show();
        })
        .catch(() => {
            document.getElementById('viewContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Erreur lors du chargement des détails
                </div>
            `;
            new bootstrap.Modal(document.getElementById('viewModal')).show();
        });
}
</script>

</body>
</html>