<?php
require_once '../includes/config.php';
requireLogin();

// Définir le titre de la page
$page_title = 'Commande Fournisseur - Pharmacie Natinin';
$include_chart = false;

$conn = getConnection();
$message = '';
$error = '';

$id_fournisseur = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer le fournisseur
$stmt = $conn->prepare("SELECT * FROM fournisseurs WHERE id_fournisseur = ?");
$stmt->execute([$id_fournisseur]);
$fournisseur = $stmt->fetch();

if (!$fournisseur && $id_fournisseur > 0) {
    $error = "Fournisseur non trouvé";
}

// Récupérer tous les médicaments
$medicaments = $conn->query("
    SELECT m.*, f.nom_fournisseur 
    FROM medicaments m 
    LEFT JOIN fournisseurs f ON m.id_fournisseur = f.id_fournisseur 
    ORDER BY m.nom_medicament
")->fetchAll();

// Récupérer les fournisseurs pour le filtre
$fournisseurs = $conn->query("SELECT * FROM fournisseurs ORDER BY nom_fournisseur")->fetchAll();

// ===== TRAITEMENT COMMANDE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    try {
        $conn->beginTransaction();
        
        $total = 0;
        $items = [];
        
        if (isset($_POST['quantite'])) {
            foreach ($_POST['quantite'] as $id_med => $qte) {
                if (!empty($qte) && $qte > 0) {
                    $prix = isset($_POST['prix'][$id_med]) ? floatval($_POST['prix'][$id_med]) : 0;
                    $total += $prix * $qte;
                    $items[] = ['id' => $id_med, 'qte' => $qte, 'prix' => $prix];
                }
            }
        }
        
        if (count($items) == 0) {
            throw new Exception("Veuillez sélectionner au moins un médicament");
        }
        
        // Insérer la commande
        $stmt = $conn->prepare("
            INSERT INTO commandes_fournisseurs (id_fournisseur, montant_total, statut) 
            VALUES (?, ?, 'en_attente')
        ");
        $stmt->execute([$id_fournisseur, $total]);
        $id_commande = $conn->lastInsertId();
        
        // Insérer les détails
        foreach ($items as $item) {
            $stmt = $conn->prepare("
                INSERT INTO details_commandes (id_commande, id_medicament, quantite, prix_achat) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$id_commande, $item['id'], $item['qte'], $item['prix']]);
        }
        
        $conn->commit();
        $message = "✅ Commande N° " . str_pad($id_commande, 6, '0', STR_PAD_LEFT) . " enregistrée avec succès !";
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "❌ Erreur : " . $e->getMessage();
    }
}

// ===== STATISTIQUES POUR LE PANEL =====
$stats = [];
$stats['total_fournisseurs'] = $conn->query("SELECT COUNT(*) FROM fournisseurs")->fetchColumn();
$stats['total_medicaments'] = $conn->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
$stats['commandes_encours'] = $conn->query("SELECT COUNT(*) FROM commandes_fournisseurs WHERE statut IN ('en_attente', 'approuvee', 'expediee')")->fetchColumn();

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header">
    <div>
        <h4><i class="fas fa-file-invoice"></i> Passer une commande</h4>
        <span class="date-info">
            <i class="far fa-calendar-alt"></i> 
            <?= date('d/m/Y à H:i') ?>
        </span>
    </div>
    <div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <a href="fournisseurs.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<!-- ===== STATISTIQUES ===== -->
<div class="row g-3 mb-4 animated">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-truck"></i></div>
            <div class="stat-number"><?= $stats['total_fournisseurs'] ?></div>
            <div class="stat-label">Fournisseurs</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card border-info">
            <div class="stat-icon"><i class="fas fa-pills"></i></div>
            <div class="stat-number"><?= $stats['total_medicaments'] ?></div>
            <div class="stat-label">Médicaments</div>
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
            <div class="stat-icon"><i class="fas fa-calendar"></i></div>
            <div class="stat-number"><?= date('d/m/Y') ?></div>
            <div class="stat-label">Date</div>
        </div>
    </div>
</div>

<!-- ===== MESSAGES ===== -->
<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php if (isset($id_commande)): ?>
        <div class="alert-success-custom mb-3">
            <i class="fas fa-check-circle"></i>
            <div class="title">Commande validée !</div>
            <p class="mb-0">La commande a été enregistrée avec succès.</p>
            <a href="factures.php" class="btn btn-success-custom mt-2">
                <i class="fas fa-receipt"></i> Voir les factures
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$message || !isset($id_commande)): ?>

<!-- ===== FOURNISSEUR INFO ===== -->
<?php if ($fournisseur): ?>
    <div class="fournisseur-info-box mb-4 animated">
        <div class="icon"><i class="fas fa-truck"></i></div>
        <div>
            <div class="name"><?= htmlspecialchars($fournisseur['nom_fournisseur']) ?></div>
            <div class="detail">
                <?php if ($fournisseur['contact']): ?>
                    <i class="fas fa-user"></i> <?= htmlspecialchars($fournisseur['contact']) ?>
                <?php endif; ?>
                <?php if ($fournisseur['telephone']): ?>
                    <i class="fas fa-phone ms-2"></i> <?= htmlspecialchars($fournisseur['telephone']) ?>
                <?php endif; ?>
                <?php if ($fournisseur['email']): ?>
                    <i class="fas fa-envelope ms-2"></i> <?= htmlspecialchars($fournisseur['email']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="ms-auto">
            <span class="badge bg-success">Fournisseur sélectionné</span>
        </div>
    </div>
<?php else: ?>
    <!-- Sélection du fournisseur -->
    <div class="widget mb-4 animated shadow-sm border-0">
        <div class="widget-header bg-light py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="m-0 text-success fw-bold"><i class="fas fa-truck me-2"></i> Étape 1 sur 2 : Sélectionner un fournisseur</h5>
            <span class="badge bg-success bg-gradient px-3 py-2">Étape 1 / 2</span>
        </div>
        <div class="widget-body p-4">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light text-success border-end-0"><i class="fas fa-building"></i></span>
                        <select name="id" class="form-select border-start-0 fournisseur-select" required style="font-size: 15px;">
                            <option value="">-- Choisir un partenaire fournisseur --</option>
                            <?php foreach ($fournisseurs as $f): ?>
                                <option value="<?= $f['id_fournisseur'] ?>">
                                    <?= htmlspecialchars($f['nom_fournisseur']) ?>
                                    <?php if ($f['telephone']): ?>
                                        (Tél: <?= $f['telephone'] ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" style="background: linear-gradient(135deg, #1b5e20, #388e3c); border: none;">
                        <i class="fas fa-arrow-right me-2"></i> Continuer la commande
                    </button>
                </div>
            </form>
            <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top text-muted small">
                <span><i class="fas fa-info-circle text-info me-1"></i> Choisissez un fournisseur pour afficher son catalogue.</span>
                <a href="fournisseurs.php" class="text-success text-decoration-none fw-bold">
                    <i class="fas fa-plus-circle me-1"></i> Nouveau fournisseur ?
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($fournisseur): ?>

<!-- ===== LISTE DES MÉDICAMENTS ===== -->
<div class="widget animated">
    <div class="widget-header">
        <h5><i class="fas fa-list"></i> Sélectionner les médicaments à commander</h5>
        <div>
            <span class="badge bg-secondary"><?= count($medicaments) ?> produit(s)</span>
            <button class="btn btn-sm btn-outline-secondary" onclick="selectAll()">
                <i class="fas fa-check-double"></i> Tout sélectionner
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                <i class="fas fa-times"></i> Tout désélectionner
            </button>
        </div>
    </div>
    <div class="widget-body">
        <form method="POST" id="orderSaveForm">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id_fournisseur" value="<?= $fournisseur['id_fournisseur'] ?>">
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-produits">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="selectAllCheck" onchange="toggleAll(this)">
                            </th>
                            <th style="min-width: 180px;">Médicament</th>
                            <th style="width: 100px;">Code CIP</th>
                            <th style="width: 100px;">Stock actuel</th>
                            <th style="width: 120px;">Prix achat (CFA)</th>
                            <th style="width: 100px;">Quantité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($medicaments)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted d-block mb-2"></i>
                                    <p class="text-muted">Aucun médicament enregistré</p>
                                    <a href="medicaments.php" class="btn btn-sm btn-success">
                                        <i class="fas fa-plus"></i> Ajouter un médicament
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($medicaments as $med): 
                                $estAlerte = $med['quantite_stock'] <= $med['stock_minimum'];
                            ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="product-check" 
                                           data-stock="<?= $med['quantite_stock'] ?>"
                                           <?= $estAlerte ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <span class="product-name"><?= htmlspecialchars($med['nom_medicament']) ?></span>
                                    <?php if ($estAlerte): ?>
                                        <span class="badge bg-warning text-dark ms-1">Stock bas</span>
                                    <?php endif; ?>
                                    <?php if ($med['quantite_stock'] == 0): ?>
                                        <span class="badge bg-danger">Rupture</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= $med['code_cip'] ?></code></td>
                                <td class="text-center">
                                    <span class="<?= $med['quantite_stock'] <= $med['stock_minimum'] ? 'text-danger fw-bold' : '' ?>">
                                        <?= $med['quantite_stock'] ?>
                                    </span>
                                    <br><small class="text-muted">Min: <?= $med['stock_minimum'] ?></small>
                                </td>
                                <td>
                                    <input type="number" name="prix[<?= $med['id_medicament'] ?>]" 
                                           class="form-control form-control-sm price-input" 
                                           step="0.01" value="<?= $med['prix_achat'] ?>">
                                </td>
                                <td>
                                    <input type="number" name="quantite[<?= $med['id_medicament'] ?>]" 
                                           class="form-control form-control-sm qty-input" 
                                           value="0" min="0" step="1">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- RÉSUMÉ DU TOTAL ESTIMÉ EN TEMPS RÉEL -->
            <div class="total-box my-3 p-3 bg-light rounded border d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="small text-muted font-uppercase fw-bold"><i class="fas fa-calculator text-success me-1"></i> MONTANT TOTAL ESTIMÉ DE LA COMMANDE</div>
                    <small class="text-muted">Calculé automatiquement selon les médicaments sélectionnés</small>
                </div>
                <div class="fs-3 fw-bold text-success" id="orderTotalDisplay">0 CFA</div>
            </div>
            
            <div class="d-flex gap-3 mt-4 flex-wrap">
                <button type="submit" class="btn btn-success-custom">
                    <i class="fas fa-check me-1"></i> Valider et Enregistrer la commande
                </button>
                <a href="fournisseurs.php" class="btn btn-secondary-custom">
                    <i class="fas fa-times me-1"></i> Annuler
                </a>
                <button type="reset" class="btn btn-outline-secondary" onclick="setTimeout(calculateOrderTotal, 100)">
                    <i class="fas fa-undo me-1"></i> Réinitialiser
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php endif; ?>

<!-- ============================================ -->
<!-- ===== SCRIPTS ===== -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== TOGGLE SIDEBAR =====
function toggleSidebar() {
    document.getElementById('sidebar')?.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    if (window.innerWidth <= 992) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    }
});

// ===== CALCUL DU TOTAL ESTIMÉ EN TEMPS RÉEL =====
function calculateOrderTotal() {
    const totalDisplay = document.getElementById('orderTotalDisplay');
    if (!totalDisplay) return;
    
    let total = 0;
    document.querySelectorAll('.product-check:checked').forEach(cb => {
        const row = cb.closest('tr');
        const qtyInput = row.querySelector('.qty-input');
        const priceInput = row.querySelector('.price-input');
        
        if (qtyInput && priceInput && !qtyInput.disabled) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            total += qty * price;
        }
    });
    
    totalDisplay.textContent = Math.round(total).toLocaleString('fr-FR') + ' CFA';
}

// ===== SELECTION TOUS LES PRODUITS =====
function toggleAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.product-check');
    checkboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
        toggleQuantity(cb);
    });
    calculateOrderTotal();
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.product-check');
    checkboxes.forEach(cb => {
        cb.checked = true;
        toggleQuantity(cb);
    });
    const master = document.getElementById('selectAllCheck');
    if (master) master.checked = true;
    calculateOrderTotal();
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.product-check');
    checkboxes.forEach(cb => {
        cb.checked = false;
        toggleQuantity(cb);
    });
    const master = document.getElementById('selectAllCheck');
    if (master) master.checked = false;
    calculateOrderTotal();
}

// ===== ACTIVER/DÉSACTIVER LA QUANTITÉ =====
document.querySelectorAll('.product-check').forEach(cb => {
    cb.addEventListener('change', function() {
        toggleQuantity(this);
        calculateOrderTotal();
    });
});

document.querySelectorAll('.qty-input, .price-input').forEach(input => {
    input.addEventListener('input', calculateOrderTotal);
    input.addEventListener('change', calculateOrderTotal);
});

function toggleQuantity(checkbox) {
    const row = checkbox.closest('tr');
    const qtyInput = row.querySelector('.qty-input');
    const priceInput = row.querySelector('.price-input');
    
    if (checkbox.checked) {
        qtyInput.disabled = false;
        priceInput.disabled = false;
        if (qtyInput.value == '0' || qtyInput.value === '') {
            qtyInput.value = '1';
        }
        // Si stock bas, proposer une quantité recommandée
        const stock = parseInt(checkbox.dataset.stock || '0');
        if (stock <= 10 && stock > 0) {
            qtyInput.value = Math.max(1, Math.floor(stock * 1.5));
        } else if (stock == 0) {
            qtyInput.value = '10';
        }
    } else {
        qtyInput.disabled = true;
        priceInput.disabled = true;
        qtyInput.value = '0';
    }
}

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.product-check').forEach(cb => {
        toggleQuantity(cb);
    });
    updateMasterCheckbox();
    calculateOrderTotal();
});

function updateMasterCheckbox() {
    const checkboxes = document.querySelectorAll('.product-check');
    const checked = document.querySelectorAll('.product-check:checked');
    const master = document.getElementById('selectAllCheck');
    if (master) {
        master.checked = checkboxes.length > 0 && checkboxes.length === checked.length;
    }
}

// ===== VALIDATION DU FORMULAIRE =====
const orderSaveForm = document.getElementById('orderSaveForm');
if (orderSaveForm) {
    orderSaveForm.addEventListener('submit', function(e) {
        const quantities = document.querySelectorAll('.qty-input:not([disabled])');
        let hasItems = false;
        
        quantities.forEach(qty => {
            if (parseInt(qty.value) > 0) {
                hasItems = true;
            }
        });
        
        if (!hasItems) {
            e.preventDefault();
            alert('⚠️ Veuillez sélectionner au moins un médicament avec une quantité supérieure à 0.');
        }
    });
}
</script>

</body>
</html>