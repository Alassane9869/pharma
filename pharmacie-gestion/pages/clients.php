<?php
require_once '../includes/config.php';
requireLogin();

// Définir le titre de la page
$page_title = 'Gestion des Clients - Pharmacie Natinin';
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
                        INSERT INTO clients (nom, prenom, telephone, email, adresse, date_naissance) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        trim($_POST['nom']),
                        trim($_POST['prenom']),
                        trim($_POST['telephone']),
                        trim($_POST['email']),
                        trim($_POST['adresse']),
                        $_POST['date_naissance'] ?: null
                    ]);
                    $message = "✅ Client ajouté avec succès !";
                    break;
                    
                case 'edit':
                    $stmt = $conn->prepare("
                        UPDATE clients SET 
                            nom = ?, prenom = ?, telephone = ?, 
                            email = ?, adresse = ?, date_naissance = ? 
                        WHERE id_client = ?
                    ");
                    $stmt->execute([
                        trim($_POST['nom']),
                        trim($_POST['prenom']),
                        trim($_POST['telephone']),
                        trim($_POST['email']),
                        trim($_POST['adresse']),
                        $_POST['date_naissance'] ?: null,
                        intval($_POST['id_client'])
                    ]);
                    $message = "✅ Client modifié avec succès !";
                    break;
                    
                case 'delete':
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM ventes WHERE id_client = ?");
                    $stmt->execute([$_POST['id_client']]);
                    $used = $stmt->fetchColumn();
                    
                    if ($used > 0) {
                        $error = "❌ Ce client a des ventes associées et ne peut pas être supprimé.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM clients WHERE id_client = ?");
                        $stmt->execute([$_POST['id_client']]);
                        $message = "✅ Client supprimé avec succès !";
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
$filter_points = $_GET['points'] ?? '';

$sql = "SELECT * FROM clients WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR telephone LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_points === 'fidele') {
    $sql .= " AND points_fidelite >= 100";
} elseif ($filter_points === 'nouveau') {
    $sql .= " AND points_fidelite < 100 AND points_fidelite > 0";
} elseif ($filter_points === 'zero') {
    $sql .= " AND points_fidelite = 0";
}

$sql .= " ORDER BY nom, prenom";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

// ===== STATISTIQUES =====
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$stats['nouveaux_mois'] = $conn->query("SELECT COUNT(*) FROM clients WHERE strftime('%Y-%m', date_inscription) = strftime('%Y-%m', 'now', 'localtime')")->fetchColumn();
$stats['points_total'] = $conn->query("SELECT COALESCE(SUM(points_fidelite), 0) FROM clients")->fetchColumn();
$stats['fideles'] = $conn->query("SELECT COUNT(*) FROM clients WHERE points_fidelite >= 100")->fetchColumn();

$topClients = $conn->query("
    SELECT nom, prenom, points_fidelite, telephone 
    FROM clients 
    WHERE points_fidelite > 0 
    ORDER BY points_fidelite DESC 
    LIMIT 5
")->fetchAll();

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header">
    <div>
        <h4><i class="fas fa-users"></i> Gestion des Clients</h4>
        <span class="date-info">
            <i class="far fa-calendar-alt"></i> 
            <?= date('d/m/Y à H:i') ?>
        </span>
    </div>
    <div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Nouveau client
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
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">Total clients</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-info">
            <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
            <div class="stat-number"><?= $stats['nouveaux_mois'] ?></div>
            <div class="stat-label">Nouveaux ce mois</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-warning">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-number"><?= $stats['points_total'] ?></div>
            <div class="stat-label">Points fidélité totaux</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-purple">
            <div class="stat-icon"><i class="fas fa-crown"></i></div>
            <div class="stat-number"><?= $stats['fideles'] ?></div>
            <div class="stat-label">Clients fidèles</div>
        </div>
    </div>
</div>

<!-- ===== LISTE DES CLIENTS ===== -->
<div class="widget">
    <div class="widget-header">
        <h5><i class="fas fa-list"></i> Liste des clients</h5>
        <div class="d-flex gap-2">
            <span class="badge bg-secondary"><?= count($clients) ?> client(s)</span>
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
                    <select name="points" class="form-select form-select-sm">
                        <option value="">Tous les clients</option>
                        <option value="fidele" <?= $filter_points == 'fidele' ? 'selected' : '' ?>>⭐ Clients fidèles</option>
                        <option value="nouveau" <?= $filter_points == 'nouveau' ? 'selected' : '' ?>>🌱 Nouveaux</option>
                        <option value="zero" <?= $filter_points == 'zero' ? 'selected' : '' ?>>🔄 Sans points</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-success w-100">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="clients.php" class="btn btn-sm btn-secondary w-100">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </a>
                </div>
            </div>
        </form>

        <!-- ===== LISTE CLIENTS EN CARTES ===== -->
        <div class="client-list">
            <?php if (empty($clients)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted d-block mb-3"></i>
                    <p class="text-muted">Aucun client trouvé</p>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Ajouter un client
                    </button>
                </div>
            <?php else: ?>
                <?php foreach($clients as $client): 
                    // Initiales pour l'avatar
                    $initials = strtoupper(substr($client['prenom'], 0, 1) . substr($client['nom'], 0, 1));
                    
                    // Niveau de fidélité
                    if ($client['points_fidelite'] >= 200) {
                        $levelBadge = '<span class="badge-level bg-warning text-dark"><i class="fas fa-crown"></i> VIP</span>';
                        $pointsColor = '#ff9800';
                    } elseif ($client['points_fidelite'] >= 100) {
                        $levelBadge = '<span class="badge-level bg-info text-white"><i class="fas fa-star"></i> Fidèle</span>';
                        $pointsColor = '#0dcaf0';
                    } elseif ($client['points_fidelite'] > 0) {
                        $levelBadge = '<span class="badge-level bg-secondary text-white">Débutant</span>';
                        $pointsColor = '#6c757d';
                    } else {
                        $levelBadge = '<span class="badge-level bg-light text-dark">Nouveau</span>';
                        $pointsColor = '#adb5bd';
                    }
                ?>
                <div class="client-card">
                    <!-- Avatar -->
                    <div class="client-avatar"><?= $initials ?></div>
                    
                    <!-- Informations -->
                    <div class="client-info">
                        <div class="name">
                            <?= htmlspecialchars($client['nom'] . ' ' . $client['prenom']) ?>
                            <?= $levelBadge ?>
                        </div>
                        <div class="details">
                            <?php if ($client['telephone']): ?>
                                <i class="fas fa-phone"></i> <?= htmlspecialchars($client['telephone']) ?>
                            <?php endif; ?>
                            <?php if ($client['email']): ?>
                                <i class="fas fa-envelope ms-2"></i> <?= htmlspecialchars($client['email']) ?>
                            <?php endif; ?>
                            <?php if ($client['adresse']): ?>
                                <i class="fas fa-map-marker-alt ms-2"></i> <?= htmlspecialchars(substr($client['adresse'], 0, 25)) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Points -->
                    <div class="client-points">
                        <div class="points-number" style="color: <?= $pointsColor ?>;">
                            <?= $client['points_fidelite'] ?>
                        </div>
                        <div class="points-label">POINTS</div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="client-actions">
                        <button class="btn-action-client btn-edit" onclick='editClient(<?= json_encode($client) ?>)' title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-action-client btn-delete" onclick="deleteClient(<?= $client['id_client'] ?>, '<?= addslashes($client['nom'] . ' ' . $client['prenom']) ?>')" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn-action-client btn-view" onclick="viewClient(<?= $client['id_client'] ?>)" title="Détails">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-action-client btn-sale" onclick="newSale(<?= $client['id_client'] ?>)" title="Nouvelle vente">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== TOP CLIENTS ===== -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="widget">
            <div class="widget-header">
                <h5><i class="fas fa-crown text-warning"></i> Top 5 clients fidèles</h5>
            </div>
            <div class="widget-body">
                <?php if (empty($topClients)): ?>
                    <p class="text-muted text-center py-3">
                        <i class="fas fa-inbox d-block mb-2"></i>
                        Aucun client avec des points
                    </p>
                <?php else: ?>
                    <?php foreach($topClients as $index => $client): ?>
                        <div class="top-client-item">
                            <div>
                                <span class="rank">#<?= $index + 1 ?></span>
                                <strong><?= htmlspecialchars($client['nom'] . ' ' . $client['prenom']) ?></strong>
                                <br><small class="text-muted"><?= $client['telephone'] ?: 'Pas de téléphone' ?></small>
                            </div>
                            <div>
                                <span class="points"><?= $client['points_fidelite'] ?></span>
                                <span style="font-size: 12px; color: #666;">pts</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="widget">
            <div class="widget-header">
                <h5><i class="fas fa-chart-bar text-success"></i> Statistiques</h5>
            </div>
            <div class="widget-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <div class="text-muted small">Moyenne points</div>
                            <div class="fw-bold" style="font-size: 20px; color: #1b5e20;">
                                <?= $stats['total'] > 0 ? round($stats['points_total'] / $stats['total'], 1) : 0 ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <div class="text-muted small">Taux de fidélisation</div>
                            <div class="fw-bold" style="font-size: 20px; color: #1b5e20;">
                                <?= $stats['total'] > 0 ? round(($stats['fideles'] / $stats['total']) * 100, 1) : 0 ?>%
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-success text-white rounded text-center">
                            <i class="fas fa-gift me-2"></i>
                            <?= $stats['points_total'] ?> points à utiliser
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ===== MODAL AJOUT ===== -->
<!-- ============================================ -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Ajouter un client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Prénom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" placeholder="77 123 45 67">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="client@email.com">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Adresse</label>
                            <textarea name="adresse" class="form-control" rows="2" placeholder="Adresse complète"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Date de naissance</label>
                            <input type="date" name="date_naissance" class="form-control">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-user-edit"></i> Modifier le client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_client" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nom *</label>
                            <input type="text" name="nom" id="edit_nom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Prénom *</label>
                            <input type="text" name="prenom" id="edit_prenom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="tel" name="telephone" id="edit_telephone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Adresse</label>
                            <textarea name="adresse" id="edit_adresse" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Date de naissance</label>
                            <input type="date" name="date_naissance" id="edit_naissance" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Points fidélité</label>
                            <input type="number" name="points_fidelite" id="edit_points" class="form-control" readonly disabled>
                            <small class="text-muted">Les points sont automatiquement cumulés par les achats</small>
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
                <h5 class="modal-title"><i class="fas fa-user-circle"></i> Détails du client</h5>
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

// ===== MODIFIER UN CLIENT =====
function editClient(client) {
    document.getElementById('edit_id').value = client.id_client;
    document.getElementById('edit_nom').value = client.nom;
    document.getElementById('edit_prenom').value = client.prenom;
    document.getElementById('edit_telephone').value = client.telephone || '';
    document.getElementById('edit_email').value = client.email || '';
    document.getElementById('edit_adresse').value = client.adresse || '';
    document.getElementById('edit_naissance').value = client.date_naissance || '';
    document.getElementById('edit_points').value = client.points_fidelite || 0;
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ===== SUPPRIMER UN CLIENT =====
function deleteClient(id, name) {
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer le client :\n"' + name + '" ?')) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_client" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ===== VOIR DÉTAILS =====
function viewClient(id) {
    let modalEl = document.getElementById('viewModal');
    let modal = new bootstrap.Modal(modalEl);
    let content = document.getElementById('viewContent');

    content.innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Chargement des détails...</p>
        </div>
    `;
    
    modal.show();

    fetch(`ajax_client_details.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(() => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Erreur lors du chargement des détails
                </div>
            `;
        });
}

// ===== NOUVELLE VENTE =====
function newSale(id) {
    window.location.href = `ventes.php?client_id=${id}`;
}
</script>

</body>
</html>