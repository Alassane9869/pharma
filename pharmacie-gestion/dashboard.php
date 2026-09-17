<?php
require_once 'includes/config.php';
requireLogin();

// Définir le titre de la page
$page_title = 'Tableau de Bord Exécutif - Pharmacie Souley-Guirou';
$include_chart = true;

$conn = getConnection();

// ===== STATISTIQUES GLOBALES =====
$totalMedicaments = $conn->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
$ruptureStock = $conn->query("SELECT COUNT(*) FROM medicaments WHERE quantite_stock <= stock_minimum")->fetchColumn();

$today = date('Y-m-d');
$currentMonth = date('Y-m');
$minus7days = date('Y-m-d', strtotime('-7 days'));

// Ventes du jour
$ventesJour = $conn->query("
    SELECT 
        COUNT(*) as total_ventes,
        COALESCE(SUM(montant_total), 0) as ca_jour
    FROM ventes 
    WHERE DATE(date_vente) = '$today'
")->fetch();

// Ventes du mois
$ventesMois = $conn->query("
    SELECT 
        COUNT(*) as total_ventes,
        COALESCE(SUM(montant_total), 0) as ca_mois
    FROM ventes 
    WHERE date_vente LIKE '$currentMonth%'
")->fetch();

// Nombre de clients
$totalClients = $conn->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$nouveauxClients = $conn->query("
    SELECT COUNT(*) 
    FROM clients 
    WHERE date_inscription LIKE '$currentMonth%'
")->fetchColumn();

// Points de fidélité total
$pointsTotal = $conn->query("SELECT COALESCE(SUM(points_fidelite), 0) FROM clients")->fetchColumn();

// Commandes fournisseurs en attente
$commandesAttente = $conn->query("
    SELECT COUNT(*) 
    FROM commandes_fournisseurs 
    WHERE statut = 'en_attente'
")->fetchColumn();

// Produits les plus vendus (Top 5)
$topProduits = $conn->query("
    SELECT 
        m.nom_medicament,
        m.code_cip,
        m.prix_vente,
        COALESCE(SUM(dv.quantite), 0) as total_vendu,
        COALESCE(SUM(dv.quantite * dv.prix_unitaire), 0) as chiffre_affaires
    FROM medicaments m
    LEFT JOIN details_ventes dv ON m.id_medicament = dv.id_medicament
    GROUP BY m.id_medicament, m.nom_medicament, m.code_cip, m.prix_vente
    ORDER BY total_vendu DESC
    LIMIT 5
")->fetchAll();

// Ventes récentes (6 dernières) avec détails des produits
$ventesRecentes = $conn->query("
    SELECT 
        v.id_vente,
        v.date_vente,
        v.montant_total,
        c.nom,
        c.prenom,
        (SELECT COUNT(*) FROM details_ventes WHERE id_vente = v.id_vente) as nb_articles,
        (SELECT GROUP_CONCAT(CONCAT(m.nom_medicament, ' (x', dv.quantite, ')')) 
         FROM details_ventes dv 
         JOIN medicaments m ON dv.id_medicament = m.id_medicament 
         WHERE dv.id_vente = v.id_vente) as produits_vendus
    FROM ventes v
    LEFT JOIN clients c ON v.id_client = c.id_client
    ORDER BY v.date_vente DESC
    LIMIT 6
")->fetchAll();

// Produits en stock faible (<= 10)
$stockFaible = $conn->query("
    SELECT 
        id_medicament,
        nom_medicament,
        code_cip,
        quantite_stock,
        prix_vente
    FROM medicaments 
    WHERE quantite_stock <= 10 
    ORDER BY quantite_stock ASC
    LIMIT 5
")->fetchAll();

// Ventes par jour (7 derniers jours)
$ventes7Jours = $conn->query("
    SELECT 
        DATE(date_vente) as date_jour,
        COUNT(*) as nb_ventes,
        COALESCE(SUM(montant_total), 0) as total_ca
    FROM ventes 
    WHERE date_vente >= '$minus7days'
    GROUP BY DATE(date_vente)
    ORDER BY date_jour ASC
")->fetchAll();

$jours = [];
$ca_jours = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $jours[] = date('d/m', strtotime($date));
    $trouve = false;
    foreach ($ventes7Jours as $v) {
        if ($v['date_jour'] == $date) {
            $ca_jours[] = (float)$v['total_ca'];
            $trouve = true;
            break;
        }
    }
    if (!$trouve) {
        $ca_jours[] = 0;
    }
}

// Date en français
$nom_jour = ['Sunday'=>'Dimanche','Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi','Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi'][date('l')] ?? date('l');
$nom_mois = ['January'=>'Janvier','February'=>'Février','March'=>'Mars','April'=>'Avril','May'=>'Mai','June'=>'Juin','July'=>'Juillet','August'=>'Août','September'=>'Septembre','October'=>'Octobre','November'=>'Novembre','December'=>'Décembre'][date('F')] ?? date('F');
$date_fr = $nom_jour . ' ' . date('d') . ' ' . $nom_mois . ' ' . date('Y');

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<style>
.dash-hero {
    background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 60%, #4caf50 100%);
    border-radius: 16px;
    padding: 24px 28px;
    color: #ffffff;
    box-shadow: 0 10px 25px rgba(27, 94, 32, 0.2);
    margin-bottom: 24px;
}

.kpi-box {
    background: #ffffff;
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    border: 1px solid #eef2f6;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
}

.kpi-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.08);
}

.kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 12px;
}

.rank-pill {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 12px;
}

.rank-gold { background: #fef08a; color: #854d0e; }
.rank-silver { background: #e2e8f0; color: #334155; }
.rank-bronze { background: #ffedd5; color: #9a3412; }
.rank-default { background: #f1f5f9; color: #64748b; }
</style>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header mb-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1b5e20;">
            <i class="fas fa-chart-pie me-2 text-success"></i>Tableau de Bord Exécutif
        </h4>
        <span class="text-muted small">
            <i class="far fa-calendar-alt me-1"></i> <?= $date_fr ?> &bull; <?= date('H:i') ?>
        </span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-success rounded-circle shadow-sm" onclick="window.location.reload()" title="Actualiser">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>

<!-- ===== HEROS BANNER ===== -->
<div class="dash-hero d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <span class="badge bg-white text-success fw-bold px-3 py-1 rounded-pill mb-2">
            <i class="fas fa-check-circle me-1"></i> Pharmacie Souley-Guirou v1.0 Pro
        </span>
        <h2 class="fw-extrabold mb-1" style="font-size: 24px;">
            Aperçu Analytique Global 📊
        </h2>
        <p class="mb-0 text-white-50 small">
            Chiffre d'affaires mensuel : <strong><?= number_format($ventesMois['ca_mois'], 0, ',', ' ') ?> FCFA</strong> (<?= $ventesMois['total_ventes'] ?> ventes ce mois)
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="pages/ventes.php" class="btn btn-light text-success fw-bold px-3 shadow-sm rounded-pill">
            <i class="fas fa-plus me-1"></i> Nouvelle Vente
        </a>
    </div>
</div>

<!-- ===== CARTES METRIQUES (8 CARTE GRID) ===== -->
<div class="row g-3 mb-4">
    <!-- Total Médicaments -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-box">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="kpi-icon" style="background: #e8f5e9; color: #1b5e20;"><i class="fas fa-pills"></i></div>
                <span class="badge <?= $ruptureStock > 0 ? 'bg-danger' : 'bg-success' ?>"><?= $ruptureStock > 0 ? $ruptureStock . ' en alerte' : 'Stock OK' ?></span>
            </div>
            <div class="fs-3 fw-bold text-dark"><?= number_format($totalMedicaments, 0, ',', ' ') ?></div>
            <div class="text-muted small fw-semibold">Médicaments en Stock</div>
        </div>
    </div>

    <!-- Ventes du Jour -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-box">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="kpi-icon" style="background: #e3f2fd; color: #1565c0;"><i class="fas fa-shopping-cart"></i></div>
                <span class="badge bg-primary">Aujourd'hui</span>
            </div>
            <div class="fs-3 fw-bold text-dark"><?= $ventesJour['total_ventes'] ?></div>
            <div class="text-muted small fw-semibold">Ventes du jour (<?= number_format($ventesJour['ca_jour'], 0, ',', ' ') ?> F)</div>
        </div>
    </div>

    <!-- Clients -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-box">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="kpi-icon" style="background: #fff3e0; color: #e65100;"><i class="fas fa-users"></i></div>
                <span class="badge bg-warning text-dark">+<?= $nouveauxClients ?> ce mois</span>
            </div>
            <div class="fs-3 fw-bold text-dark"><?= $totalClients ?></div>
            <div class="text-muted small fw-semibold">Clients Inscrits</div>
        </div>
    </div>

    <!-- Points de fidélité -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-box">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="kpi-icon" style="background: #f3e5f5; color: #6a1b9a;"><i class="fas fa-star"></i></div>
                <span class="badge bg-purple text-white" style="background:#6a1b9a;">Fidélité</span>
            </div>
            <div class="fs-3 fw-bold text-dark"><?= number_format($pointsTotal, 0, ',', ' ') ?></div>
            <div class="text-muted small fw-semibold">Points Fidélité Cumulés</div>
        </div>
    </div>
</div>

<!-- ===== ROW PRINCIPAL ===== -->
<div class="row g-4 mb-4">

    <!-- COLONNE GAUCHE (8) -->
    <div class="col-lg-8">
        <!-- GRAPHIQUE CA 7 JOURS -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-success">
                    <i class="fas fa-chart-bar me-2"></i>Évolution du Chiffre d'Affaires (7 Jours)
                </h5>
                <span class="badge bg-success badge-sm"><?= date('d/m', strtotime('-6 days')) ?> - <?= date('d/m') ?></span>
            </div>
            <div class="card-body">
                <div style="height: 200px; position: relative;">
                    <canvas id="caChart"></canvas>
                </div>
            </div>
        </div>

        <!-- TOP 5 MEDICAMENTS -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-trophy text-warning me-2"></i>Top 5 Médicaments Plus Vendus
                </h5>
                <span class="badge bg-light text-dark">Classement par volume</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Médicament</th>
                                <th class="text-center">Vendus</th>
                                <th class="text-end">CA Généré</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProduits)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">Aucune vente enregistrée.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topProduits as $index => $produit): 
                                    $rankClass = $index === 0 ? 'rank-gold' : ($index === 1 ? 'rank-silver' : ($index === 2 ? 'rank-bronze' : 'rank-default'));
                                ?>
                                    <tr>
                                        <td>
                                            <div class="rank-pill <?= $rankClass ?>"><?= $index + 1 ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($produit['nom_medicament'] ?? 'Inconnu') ?></div>
                                            <small class="text-muted font-monospace"><?= $produit['code_cip'] ?? '' ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success px-2 py-1"><?= $produit['total_vendu'] ?? 0 ?> unités</span>
                                        </td>
                                        <td class="text-end fw-bold text-success font-monospace">
                                            <?= number_format($produit['chiffre_affaires'] ?? 0, 0, ',', ' ') ?> FCFA
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- COLONNE DROITE (4) -->
    <div class="col-lg-4">
        <!-- VENTES RECENTES -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-clock text-info me-2"></i>Ventes Récentes
                </h5>
                <a href="pages/ventes.php" class="btn btn-sm btn-outline-success">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($ventesRecentes)): ?>
                        <li class="list-group-item text-center text-muted py-3">Aucune vente récente</li>
                    <?php else: ?>
                        <?php foreach ($ventesRecentes as $v): 
                            $clientStr = $v['nom'] ? htmlspecialchars($v['prenom'] . ' ' . $v['nom']) : 'Client Comptoir';
                        ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div>
                                    <div class="fw-bold text-dark small"><?= $clientStr ?></div>
                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('H:i', strtotime($v['date_vente'])) ?> &bull; <?= $v['nb_articles'] ?> art.</small>
                                </div>
                                <div class="fw-bold text-success font-monospace">
                                    <?= number_format($v['montant_total'], 0, ',', ' ') ?> F
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- STOCK CRITIQUE -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Stock Critique
                </h5>
                <span class="badge bg-danger"><?= count($stockFaible) ?></span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($stockFaible)): ?>
                        <li class="list-group-item text-center text-success py-3">
                            <i class="fas fa-check-circle me-1"></i> Tous les stocks sont suffisants
                        </li>
                    <?php else: ?>
                        <?php foreach ($stockFaible as $prod): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($prod['nom_medicament']) ?></div>
                                    <small class="text-muted font-monospace"><?= $prod['code_cip'] ?></small>
                                </div>
                                <span class="badge <?= $prod['quantite_stock'] <= 3 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                    <?= $prod['quantite_stock'] ?> en stock
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('caChart').getContext('2d');
    
    const jours = <?= json_encode($jours) ?>;
    const caData = <?= json_encode($ca_jours) ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: jours,
            datasets: [{
                label: 'CA (FCFA)',
                data: caData,
                backgroundColor: 'rgba(46, 125, 50, 0.75)',
                borderColor: '#1b5e20',
                borderWidth: 1.5,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { size: 9 },
                        callback: function(value) {
                            return value >= 1000 ? (value/1000) + 'k F' : value + ' F';
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            }
        }
    });
});
</script>

</body>
</html>