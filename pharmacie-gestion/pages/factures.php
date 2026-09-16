<?php
require_once '../includes/config.php';
requireLogin();

// Définir le titre de la page
$page_title = 'Gestion des Factures - Pharmacie Natinin';
$include_chart = false;

$pdo = getConnection();

// Récupérer toutes les factures (ventes)
$factures = $pdo->query("
    SELECT v.*, c.nom, c.prenom, c.telephone,
           (SELECT COUNT(*) FROM details_ventes WHERE id_vente = v.id_vente) as nb_articles
    FROM ventes v 
    LEFT JOIN clients c ON v.id_client = c.id_client 
    ORDER BY v.date_vente DESC
")->fetchAll();

// Statistiques
$totalGeneral = $pdo->query("SELECT COALESCE(SUM(montant_total), 0) FROM ventes")->fetchColumn();
$nbVentes = count($factures);
$moyenne = $nbVentes > 0 ? $totalGeneral / $nbVentes : 0;

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header">
    <div>
        <h4><i class="fas fa-file-invoice"></i> Gestion des Factures</h4>
        <span class="date-info">
            <i class="far fa-calendar-alt"></i> 
            <?= date('d/m/Y à H:i') ?>
        </span>
    </div>
    <div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>

<!-- ===== STATISTIQUES ===== -->
<div class="row g-3 mb-4 animated">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-number"><?= number_format($totalGeneral, 0) ?></div>
            <div class="stat-label">Chiffre d'affaires total</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card border-info">
            <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
            <div class="stat-number"><?= $nbVentes ?></div>
            <div class="stat-label">Nombre total de ventes</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card border-warning">
            <div class="stat-icon"><i class="fas fa-calculator"></i></div>
            <div class="stat-number"><?= number_format($moyenne, 0) ?></div>
            <div class="stat-label">Panier moyen (CFA)</div>
        </div>
    </div>
</div>

<!-- ===== LISTE DES FACTURES ===== -->
<div class="widget">
    <div class="widget-header">
        <h5><i class="fas fa-list"></i> Historique des factures</h5>
        <div class="d-flex gap-2">
            <span class="badge bg-secondary"><?= $nbVentes ?> facture(s)</span>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
    <div class="widget-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Téléphone</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center">Articles</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($factures as $f): ?>
                    <tr>
                        <td>
                            <span class="fw-bold">#<?= str_pad($f['id_vente'], 6, '0', STR_PAD_LEFT) ?></span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($f['date_vente'])) ?></td>
                        <td>
                            <?php if ($f['nom']): ?>
                                <strong><?= htmlspecialchars($f['nom'] . ' ' . $f['prenom']) ?></strong>
                            <?php else: ?>
                                <span class="text-muted">Client anonyme</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $f['telephone'] ?: '-' ?></td>
                        <td class="text-end fw-bold text-success">
                            <?= number_format($f['montant_total'], 0) ?> CFA
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info"><?= $f['nb_articles'] ?></span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="details_vente.php?id=<?= $f['id_vente'] ?>" class="btn btn-sm btn-info" target="_blank" title="Voir détails">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                                <a href="details_vente.php?id=<?= $f['id_vente'] ?>" class="btn btn-sm btn-success" target="_blank" title="Télécharger / Imprimer PDF">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($factures)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted d-block mb-2"></i>
                                <p class="text-muted">Aucune facture enregistrée</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <th colspan="4" class="text-end">TOTAL GÉNÉRAL :</th>
                        <th class="text-end text-success"><?= number_format($totalGeneral, 0) ?> CFA</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
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
</script>

</body>
</html>