<?php
require_once '../includes/config.php';
requireLogin();

// Définir le titre de la page
$page_title = 'Gestion des Fournisseurs - Pharmacie Natinin';
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
                        INSERT INTO fournisseurs (nom_fournisseur, contact, telephone, email, adresse, site_web) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        trim($_POST['nom_fournisseur']),
                        trim($_POST['contact']),
                        trim($_POST['telephone']),
                        trim($_POST['email']),
                        trim($_POST['adresse']),
                        trim($_POST['site_web'])
                    ]);
                    $message = "✅ Fournisseur ajouté avec succès !";
                    break;
                    
                case 'edit':
                    $stmt = $conn->prepare("
                        UPDATE fournisseurs SET 
                            nom_fournisseur = ?, contact = ?, telephone = ?, 
                            email = ?, adresse = ?, site_web = ? 
                        WHERE id_fournisseur = ?
                    ");
                    $stmt->execute([
                        trim($_POST['nom_fournisseur']),
                        trim($_POST['contact']),
                        trim($_POST['telephone']),
                        trim($_POST['email']),
                        trim($_POST['adresse']),
                        trim($_POST['site_web']),
                        intval($_POST['id_fournisseur'])
                    ]);
                    $message = "✅ Fournisseur modifié avec succès !";
                    break;
                    
                case 'delete':
                    // Vérifier si le fournisseur a des médicaments associés
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM medicaments WHERE id_fournisseur = ?");
                    $stmt->execute([$_POST['id_fournisseur']]);
                    $used = $stmt->fetchColumn();
                    
                    if ($used > 0) {
                        $error = "❌ Ce fournisseur a des médicaments associés et ne peut pas être supprimé.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM fournisseurs WHERE id_fournisseur = ?");
                        $stmt->execute([$_POST['id_fournisseur']]);
                        $message = "✅ Fournisseur supprimé avec succès !";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = "❌ Erreur : " . $e->getMessage();
        }
    }
}

// ===== RECHERCHE =====
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM fournisseurs WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (nom_fournisseur LIKE ? OR contact LIKE ? OR telephone LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY nom_fournisseur";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$fournisseurs = $stmt->fetchAll();

// ===== STATISTIQUES =====
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) FROM fournisseurs")->fetchColumn();
$stats['avec_produits'] = $conn->query("
    SELECT COUNT(DISTINCT id_fournisseur) 
    FROM medicaments 
    WHERE id_fournisseur IS NOT NULL
")->fetchColumn();
$stats['commandes_encours'] = $conn->query("
    SELECT COUNT(*) 
    FROM commandes_fournisseurs 
    WHERE statut IN ('en_attente', 'approuvee', 'expediee')
")->fetchColumn();

// Top fournisseurs (par nombre de produits)
$topFournisseurs = $conn->query("
    SELECT f.nom_fournisseur, COUNT(m.id_medicament) as nb_produits
    FROM fournisseurs f
    LEFT JOIN medicaments m ON f.id_fournisseur = m.id_fournisseur
    GROUP BY f.id_fournisseur
    ORDER BY nb_produits DESC
    LIMIT 5
")->fetchAll();

// Dernières commandes par fournisseur
$dernieresCommandes = $conn->query("
    SELECT c.*, f.nom_fournisseur 
    FROM commandes_fournisseurs c
    JOIN fournisseurs f ON c.id_fournisseur = f.id_fournisseur
    ORDER BY c.date_commande DESC
    LIMIT 5
")->fetchAll();

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header">
    <div>
        <h4><i class="fas fa-truck"></i> Gestion des Fournisseurs</h4>
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
            <i class="fas fa-plus"></i> Nouveau fournisseur
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
            <div class="stat-icon"><i class="fas fa-truck"></i></div>
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">Total fournisseurs</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-info">
            <div class="stat-icon"><i class="fas fa-pills"></i></div>
            <div class="stat-number"><?= $stats['avec_produits'] ?></div>
            <div class="stat-label">Avec produits</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-warning">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-number"><?= $stats['commandes_encours'] ?></div>
            <div class="stat-label">Commandes en cours</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-purple">
            <div class="stat-icon"><i class="fas fa-percent"></i></div>
            <div class="stat-number"><?= $stats['total'] > 0 ? round(($stats['avec_produits'] / $stats['total']) * 100, 1) : 0 ?>%</div>
            <div class="stat-label">Taux d'activité</div>
        </div>
    </div>
</div>

<!-- ===== LISTE DES FOURNISSEURS ===== -->
<div class="widget">
    <div class="widget-header">
        <h5><i class="fas fa-list"></i> Liste des fournisseurs</h5>
        <div class="d-flex gap-2">
            <span class="badge bg-secondary"><?= count($fournisseurs) ?> fournisseur(s)</span>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
    <div class="widget-body">
        
        <!-- ===== FILTRES ===== -->
        <form method="GET" class="mb-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="🔍 Rechercher un fournisseur..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-success w-100">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="fournisseurs.php" class="btn btn-sm btn-secondary w-100">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </a>
                </div>
            </div>
        </form>

        <!-- ===== LISTE FOURNISSEURS EN CARTES ===== -->
        <div class="fournisseur-list">
            <?php if (empty($fournisseurs)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-truck fa-4x text-muted d-block mb-3"></i>
                    <p class="text-muted">Aucun fournisseur trouvé</p>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Ajouter un fournisseur
                    </button>
                </div>
            <?php else: ?>
                <?php foreach($fournisseurs as $fournisseur):
                    // Compter les produits associés
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM medicaments WHERE id_fournisseur = ?");
                    $stmt->execute([$fournisseur['id_fournisseur']]);
                    $nbProduits = $stmt->fetchColumn();
                    
                    // Compter les commandes
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM commandes_fournisseurs WHERE id_fournisseur = ?");
                    $stmt->execute([$fournisseur['id_fournisseur']]);
                    $nbCommandes = $stmt->fetchColumn();
                    
                    // Initiales
                    $initials = strtoupper(substr($fournisseur['nom_fournisseur'], 0, 2));
                ?>
                <div class="fournisseur-card">
                    <!-- Icône -->
                    <div class="fournisseur-icon"><?= $initials ?></div>
                    
                    <!-- Informations -->
                    <div class="fournisseur-info">
                        <div class="name">
                            <?= htmlspecialchars($fournisseur['nom_fournisseur']) ?>
                        </div>
                        <div class="details">
                            <?php if ($fournisseur['contact']): ?>
                                <i class="fas fa-user"></i> <?= htmlspecialchars($fournisseur['contact']) ?>
                            <?php endif; ?>
                            <?php if ($fournisseur['telephone']): ?>
                                <i class="fas fa-phone ms-2"></i> <?= htmlspecialchars($fournisseur['telephone']) ?>
                            <?php endif; ?>
                            <?php if ($fournisseur['email']): ?>
                                <i class="fas fa-envelope ms-2"></i> <?= htmlspecialchars($fournisseur['email']) ?>
                            <?php endif; ?>
                            <?php if ($fournisseur['site_web']): ?>
                                <i class="fas fa-globe ms-2"></i> <?= htmlspecialchars($fournisseur['site_web']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Statistiques -->
                    <div class="fournisseur-stats">
                        <div class="stat-item">
                            <div class="number"><?= $nbProduits ?></div>
                            <div class="label">Produits</div>
                        </div>
                        <div class="stat-item">
                            <div class="number"><?= $nbCommandes ?></div>
                            <div class="label">Commandes</div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="fournisseur-actions">
                        <button class="btn-action-fournisseur btn-edit" onclick='editFournisseur(<?= json_encode($fournisseur) ?>)' title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-action-fournisseur btn-delete" onclick="deleteFournisseur(<?= $fournisseur['id_fournisseur'] ?>, '<?= addslashes($fournisseur['nom_fournisseur']) ?>')" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn-action-fournisseur btn-order" onclick="newOrder(<?= $fournisseur['id_fournisseur'] ?>)" title="Passer commande">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                        <button class="btn-action-fournisseur btn-view" onclick="viewFournisseur(<?= $fournisseur['id_fournisseur'] ?>)" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== TOP FOURNISSEURS & DERNIÈRES COMMANDES ===== -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="widget">
            <div class="widget-header">
                <h5><i class="fas fa-crown text-warning"></i> Top fournisseurs</h5>
            </div>
            <div class="widget-body">
                <?php if (empty($topFournisseurs)): ?>
                    <p class="text-muted text-center py-3">
                        <i class="fas fa-inbox d-block mb-2"></i>
                        Aucun fournisseur avec des produits
                    </p>
                <?php else: ?>
                    <?php foreach($topFournisseurs as $index => $fournisseur): ?>
                        <div class="top-item">
                            <div>
                                <span class="rank">#<?= $index + 1 ?></span>
                                <strong><?= htmlspecialchars($fournisseur['nom_fournisseur']) ?></strong>
                            </div>
                            <div>
                                <span class="count"><?= $fournisseur['nb_produits'] ?></span>
                                <span style="font-size: 12px; color: #666;">produits</span>
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
                <h5><i class="fas fa-clock text-info"></i> Dernières commandes</h5>
            </div>
            <div class="widget-body">
                <?php if (empty($dernieresCommandes)): ?>
                    <p class="text-muted text-center py-3">
                        <i class="fas fa-inbox d-block mb-2"></i>
                        Aucune commande récente
                    </p>
                <?php else: ?>
                    <?php foreach($dernieresCommandes as $commande): ?>
                        <div class="top-item">
                            <div>
                                <span class="rank">#<?= str_pad($commande['id_commande'], 6, '0', STR_PAD_LEFT) ?></span>
                                <strong><?= htmlspecialchars($commande['nom_fournisseur']) ?></strong>
                                <br><small class="text-muted"><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></small>
                            </div>
                            <div>
                                <span class="count"><?= number_format($commande['montant_total'], 0) ?> CFA</span>
                                <br>
                                <span class="badge <?= $commande['statut'] == 'recue' ? 'bg-success' : ($commande['statut'] == 'en_attente' ? 'bg-warning text-dark' : 'bg-info') ?>">
                                    <?= $commande['statut'] ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Ajouter un fournisseur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom du fournisseur *</label>
                        <input type="text" name="nom_fournisseur" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Personne de contact</label>
                        <input type="text" name="contact" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" placeholder="77 123 45 67">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="fournisseur@email.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Adresse</label>
                        <textarea name="adresse" class="form-control" rows="2" placeholder="Adresse complète"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Site web</label>
                        <input type="text" name="site_web" class="form-control" placeholder="www.site.com">
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
                <h5 class="modal-title"><i class="fas fa-user-edit"></i> Modifier le fournisseur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_fournisseur" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom du fournisseur *</label>
                        <input type="text" name="nom_fournisseur" id="edit_nom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Personne de contact</label>
                        <input type="text" name="contact" id="edit_contact" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="tel" name="telephone" id="edit_telephone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Adresse</label>
                        <textarea name="adresse" id="edit_adresse" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Site web</label>
                        <input type="text" name="site_web" id="edit_site" class="form-control">
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails du fournisseur</h5>
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

// ===== MODIFIER UN FOURNISSEUR =====
function editFournisseur(fournisseur) {
    document.getElementById('edit_id').value = fournisseur.id_fournisseur;
    document.getElementById('edit_nom').value = fournisseur.nom_fournisseur;
    document.getElementById('edit_contact').value = fournisseur.contact || '';
    document.getElementById('edit_telephone').value = fournisseur.telephone || '';
    document.getElementById('edit_email').value = fournisseur.email || '';
    document.getElementById('edit_adresse').value = fournisseur.adresse || '';
    document.getElementById('edit_site').value = fournisseur.site_web || '';
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ===== SUPPRIMER UN FOURNISSEUR =====
function deleteFournisseur(id, name) {
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer le fournisseur :\n"' + name + '" ?')) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_fournisseur" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ===== NOUVELLE COMMANDE =====
function newOrder(id) {
    window.location.href = `commande_fournisseur.php?id=${id}`;
}

// ===== VOIR DÉTAILS =====
function viewFournisseur(id) {
    document.getElementById('viewContent').innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Chargement des détails...</p>
        </div>
    `;
    
    fetch(`ajax_fournisseur_details.php?id=${id}`)
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