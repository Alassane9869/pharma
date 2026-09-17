<?php
require_once 'includes/config.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Définir le titre de la page
$page_title = 'Tableau de Bord - Pharmacie Souley-Guirou';
$include_chart = true;

$conn = getConnection();

// ===== STATISTIQUES RAPIDES =====
$totalMedicaments = $conn->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
$today = date('Y-m-d');
$in30days = date('Y-m-d', strtotime('+30 days'));
$minus7days = date('Y-m-d', strtotime('-7 days'));

$ventesAujourdhui = $conn->query("SELECT COUNT(*) FROM ventes WHERE DATE(date_vente) = '$today'")->fetchColumn();
$caAujourdhui = $conn->query("SELECT COALESCE(SUM(montant_total), 0) FROM ventes WHERE DATE(date_vente) = '$today'")->fetchColumn();
$totalClients = $conn->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$commandesAttente = $conn->query("SELECT COUNT(*) FROM commandes_fournisseurs WHERE statut = 'en_attente'")->fetchColumn();
$alertesStock = $conn->query("SELECT COUNT(*) FROM medicaments WHERE quantite_stock <= stock_minimum AND quantite_stock > 0")->fetchColumn();
$rupturesStock = $conn->query("SELECT COUNT(*) FROM medicaments WHERE quantite_stock = 0")->fetchColumn();
$fournisseursActifs = $conn->query("SELECT COUNT(*) FROM fournisseurs")->fetchColumn();
$pointsFideliteTotal = $conn->query("SELECT COALESCE(SUM(points_fidelite), 0) FROM clients")->fetchColumn();

// Dernières ventes (6 dernières) avec détails des produits
$dernieresVentes = $conn->query("
    SELECT v.*, c.nom, c.prenom,
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

// Alertes péremption (30 prochains jours)
$alertesPeremption = $conn->query("
    SELECT COUNT(*) 
    FROM medicaments 
    WHERE date_expiration BETWEEN '$today' AND '$in30days'
")->fetchColumn();

// Données graphique 7 jours
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
    $dateStr = date('Y-m-d', strtotime("-$i days"));
    $jours[] = date('d/m', strtotime($dateStr));
    $found = false;
    foreach ($ventes7Jours as $v) {
        if ($v['date_jour'] == $dateStr) {
            $ca_jours[] = (float)$v['total_ca'];
            $found = true;
            break;
        }
    }
    if (!$found) {
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

<!-- ===== STYLE PERSONNALISÉ PRO ===== -->
<style>
.pro-welcome-banner {
    background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 60%, #388e3c 100%);
    border-radius: 16px;
    padding: 24px 28px;
    color: #ffffff;
    box-shadow: 0 10px 25px rgba(27, 94, 32, 0.25);
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.pro-welcome-banner::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
    pointer-events: none;
}

.pro-badge-live {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.kpi-card-pro {
    background: #ffffff;
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    border: 1px solid #eef2f6;
    transition: all 0.25s ease-in-out;
    position: relative;
    overflow: hidden;
    height: 100%;
}

.kpi-card-pro:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.08);
}

.kpi-icon-bubble {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 12px;
}

.kpi-value-pro {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    letter-spacing: -0.5px;
}

.kpi-title-pro {
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
    margin-top: 4px;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    margin-top: 10px;
}

.status-pill.success { background: #e8f5e9; color: #2e7d32; }
.status-pill.warning { background: #fff8e1; color: #f57f17; }
.status-pill.danger { background: #ffebee; color: #c62828; }
.status-pill.info { background: #e3f2fd; color: #1565c0; }

.quick-action-tile {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    text-align: center;
    text-decoration: none;
    color: #1e293b;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.quick-action-tile:hover {
    background: #f8fafc;
    border-color: #2e7d32;
    transform: translateY(-2px);
    color: #1b5e20;
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.12);
}

.quick-action-tile i {
    font-size: 22px;
    color: #2e7d32;
}

.avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e8f5e9;
    color: #1b5e20;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    flex-shrink: 0;
}
</style>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header mb-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1b5e20;">
            <i class="fas fa-prescription-bottle-alt me-2 text-success"></i>Tableau de Bord
        </h4>
        <span class="text-muted small">
            <i class="far fa-clock me-1"></i> Synchronisé à <?= date('H:i') ?> &bull; Pharmacie Souley-Guirou
        </span>    
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success px-3 py-2 rounded-pill">
            <i class="fas fa-signal me-1"></i> En ligne
        </span>
        <button class="btn btn-sm btn-outline-success rounded-circle shadow-sm" onclick="window.location.reload()" title="Actualiser">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>

<!-- ===== BANNIÈRE DE BIENVENUE EXECUTIVE ===== -->
<div class="pro-welcome-banner d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="pro-badge-live">
                <i class="fas fa-shield-alt me-1"></i> Session Administrateur Active
            </span>
            <span class="pro-badge-live bg-white text-success fw-bold">
                <?= number_format($caAujourdhui, 0, ',', ' ') ?> FCFA aujourd'hui
            </span>
        </div>
        <h2 class="fw-extrabold mb-1" style="font-size: 24px;">
            Bonjour, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrateur') ?> 👋
        </h2>
        <p class="mb-0 text-white-50 small">
            Supervision en temps réel des stocks, ventes et performances de la pharmacie.
        </p>
    </div>
    <div>
        <div class="bg-white text-dark rounded-3 px-3 py-2 text-center shadow-sm" style="min-width: 180px;">
            <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 1px;">Date du Jour</div>
            <div class="fw-bold text-success" style="font-size: 14px;">
                <i class="far fa-calendar-alt me-1"></i><?= $date_fr ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== CARTE KPIs RANGÉE 1 ===== -->
<div class="row g-3 mb-4">
    <!-- Card 1: Stocks -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-card-pro">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon-bubble" style="background: #e8f5e9; color: #2e7d32;">
                    <i class="fas fa-pills"></i>
                </div>
                <span class="badge <?= $rupturesStock > 0 ? 'bg-danger' : 'bg-success' ?>">
                    <?= $rupturesStock > 0 ? $rupturesStock . ' Rupture(s)' : 'Stock Global OK' ?>
                </span>
            </div>
            <div class="kpi-value-pro"><?= number_format($totalMedicaments, 0, ',', ' ') ?></div>
            <div class="kpi-title-pro">Médicaments Référencés</div>
            <div class="status-pill <?= $rupturesStock > 0 ? 'danger' : 'success' ?>">
                <i class="fas <?= $rupturesStock > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                <?= $rupturesStock > 0 ? "$rupturesStock produit(s) épuisé(s)" : "Aucune rupture de stock" ?>
            </div>
        </div>
    </div>

    <!-- Card 2: Ventes du jour -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-card-pro">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon-bubble" style="background: #e3f2fd; color: #1565c0;">
                    <i class="fas fa-cash-register"></i>
                </div>
                <span class="badge bg-primary">Aujourd'hui</span>
            </div>
            <div class="kpi-value-pro"><?= $ventesAujourdhui ?> <span class="fs-6 fw-normal text-muted">ventes</span></div>
            <div class="kpi-title-pro">Chiffre d'Affaires du Jour</div>
            <div class="status-pill info">
                <i class="fas fa-coins me-1"></i> <?= number_format($caAujourdhui, 0, ',', ' ') ?> FCFA
            </div>
        </div>
    </div>

    <!-- Card 3: Clients -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-card-pro">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon-bubble" style="background: #fff3e0; color: #e65100;">
                    <i class="fas fa-users"></i>
                </div>
                <span class="badge bg-warning text-dark">Fidélité</span>
            </div>
            <div class="kpi-value-pro"><?= number_format($totalClients, 0, ',', ' ') ?></div>
            <div class="kpi-title-pro">Clients Enregistrés</div>
            <div class="status-pill warning">
                <i class="fas fa-star me-1"></i> <?= number_format($pointsFideliteTotal, 0, ',', ' ') ?> Points Cumulés
            </div>
        </div>
    </div>

    <!-- Card 4: Alertes Stock -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-card-pro">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon-bubble" style="background: #ffebee; color: #c62828;">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <span class="badge <?= ($alertesStock + $rupturesStock) > 0 ? 'bg-danger' : 'bg-success' ?>">
                    Alertes
                </span>
            </div>
            <div class="kpi-value-pro"><?= $alertesStock + $rupturesStock ?></div>
            <div class="kpi-title-pro">Alertes Réapprovisionnement</div>
            <div class="status-pill <?= ($alertesStock + $rupturesStock) > 0 ? 'danger' : 'success' ?>">
                <i class="fas <?= ($alertesStock + $rupturesStock) > 0 ? 'fa-bell' : 'fa-check' ?>"></i>
                <?= ($alertesStock + $rupturesStock) > 0 ? "$alertesStock sous le seuil min." : "Tous les niveaux sont bons" ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== CARTE KPIs RANGÉE 2 ===== -->
<div class="row g-3 mb-4">
    <!-- Commandes -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-card-pro">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon-bubble" style="background: #e0f2f1; color: #00695c;">
                    <i class="fas fa-truck-loading"></i>
                </div>
                <span class="badge bg-teal text-white" style="background: #00695c;">Fournisseurs</span>
            </div>
            <div class="kpi-value-pro"><?= $commandesAttente ?></div>
            <div class="kpi-title-pro">Commandes en Attente</div>
            <div class="status-pill <?= $commandesAttente > 0 ? 'warning' : 'success' ?>">
                <i class="fas fa-clock"></i> <?= $commandesAttente > 0 ? 'En cours de livraison' : 'Aucune commande en attente' ?>
            </div>
        </div>
    </div>

    <!-- Peremptions -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-card-pro">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon-bubble" style="background: #f3e5f5; color: #6a1b9a;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <span class="badge bg-purple text-white" style="background: #6a1b9a;">30 Jours</span>
            </div>
            <div class="kpi-value-pro"><?= $alertesPeremption ?></div>
            <div class="kpi-title-pro">Alertes Péremption Proche</div>
            <div class="status-pill <?= $alertesPeremption > 0 ? 'danger' : 'success' ?>">
                <i class="fas <?= $alertesPeremption > 0 ? 'fa-calendar-times' : 'fa-calendar-check' ?>"></i>
                <?= $alertesPeremption > 0 ? "$alertesPeremption produit(s) à surveiller" : "Aucun périmé sous 30 jours" ?>
            </div>
        </div>
    </div>

    <!-- Fournisseurs -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-card-pro">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon-bubble" style="background: #e8eaf6; color: #283593;">
                    <i class="fas fa-building"></i>
                </div>
                <span class="badge bg-indigo text-white" style="background: #283593;">Partenaires</span>
            </div>
            <div class="kpi-value-pro"><?= $fournisseursActifs ?></div>
            <div class="kpi-title-pro">Fournisseurs Enregistrés</div>
            <div class="status-pill info">
                <i class="fas fa-handshake"></i> Partenaires actifs
            </div>
        </div>
    </div>

    <!-- Points fidélité -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="kpi-card-pro">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon-bubble" style="background: #fff8e1; color: #ff8f00;">
                    <i class="fas fa-gift"></i>
                </div>
                <span class="badge bg-warning text-dark">Récompenses</span>
            </div>
            <div class="kpi-value-pro"><?= number_format($pointsFideliteTotal, 0, ',', ' ') ?></div>
            <div class="kpi-title-pro">Points Client Cumulés</div>
            <div class="status-pill warning">
                <i class="fas fa-award"></i> Programme Actif
            </div>
        </div>
    </div>
</div>

<!-- ===== ROW PRINCIPAL : GRAPHIQUE & VENTES ===== -->
<div class="row g-4 mb-4">
    <!-- COLONNE GAUCHE (8) : GRAPHIQUE 7 JOURS + VENTES DÉTAILLÉES -->
    <div class="col-lg-8">
        <!-- GRAPHIQUE CA 7 JOURS -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-success">
                    <i class="fas fa-chart-line me-2"></i>Évolution du Chiffre d'Affaires (7 Derniers Jours)
                </h5>
                <span class="badge bg-light text-dark font-monospace"><?= date('d/m', strtotime('-6 days')) ?> - <?= date('d/m') ?></span>
            </div>
            <div class="card-body">
                <div style="height: 220px; position: relative;">
                    <canvas id="mainCaChart"></canvas>
                </div>
            </div>
        </div>

        <!-- DERNIÈRES VENTES DÉTAILLÉES -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-history me-2 text-info"></i>Dernières Transactions Enregistrées
                </h5>
                <a href="pages/factures.php" class="btn btn-sm btn-outline-success px-3 rounded-pill">
                    <i class="fas fa-file-invoice me-1"></i> Toutes les factures
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Facture N°</th>
                                <th>Heure / Client</th>
                                <th>Produits Vendus</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dernieresVentes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                        Aucune vente enregistrée récemment.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dernieresVentes as $v): 
                                    $clientName = $v['nom'] ? htmlspecialchars($v['prenom'] . ' ' . $v['nom']) : 'Client Comptoir';
                                    $initials = $v['nom'] ? strtoupper(substr($v['prenom'],0,1) . substr($v['nom'],0,1)) : 'CC';
                                ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-success font-monospace">
                                                #FAC-<?= str_pad($v['id_vente'], 6, '0', STR_PAD_LEFT) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-circle"><?= $initials ?></div>
                                                <div>
                                                    <div class="fw-bold text-dark small"><?= $clientName ?></div>
                                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('H:i', strtotime($v['date_vente'])) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success me-1"><?= $v['nb_articles'] ?> art.</span>
                                            <span class="small text-muted"><?= htmlspecialchars(substr($v['produits_vendus'] ?? 'Articles divers', 0, 35)) ?>...</span>
                                        </td>
                                        <td class="text-end fw-bold text-success font-monospace">
                                            <?= number_format($v['montant_total'], 0, ',', ' ') ?> FCFA
                                        </td>
                                        <td class="text-center">
                                            <a href="pages/details_vente.php?id=<?= $v['id_vente'] ?>" target="_blank" class="btn btn-sm btn-outline-success px-2 py-1" title="Voir le Reçu Ticket PDF">
                                                <i class="fas fa-print me-1"></i> Ticket
                                            </a>
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

    <!-- COLONNE DROITE (4) : ACTIONS RAPIDES & ETAT DU SYSTEME -->
    <div class="col-lg-4">
        <!-- ACTIONS RAPIDES -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-bolt text-warning me-2"></i>Raccourcis & Actions Rapides
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="pages/ventes.php" class="quick-action-tile">
                            <i class="fas fa-cart-plus"></i>
                            <span class="fw-bold small">Nouvelle Vente</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="pages/clients.php" class="quick-action-tile">
                            <i class="fas fa-user-plus"></i>
                            <span class="fw-bold small">Ajouter Client</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="pages/medicaments.php" class="quick-action-tile">
                            <i class="fas fa-plus-circle"></i>
                            <span class="fw-bold small">Nouveau Produit</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="pages/commande_fournisseur.php" class="quick-action-tile">
                            <i class="fas fa-truck"></i>
                            <span class="fw-bold small">Passer Commande</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- INFORMATIONS ÉCOSYSTÈME -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-server text-primary me-2"></i>État du Système
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small"><i class="fas fa-user-shield me-2 text-success"></i>Rôle Utilisateur</span>
                    <span class="badge bg-success"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Administrateur') ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small"><i class="fas fa-database me-2 text-info"></i>Base de données</span>
                    <span class="badge bg-light text-dark border">SQLite 3 (Local)</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small"><i class="fas fa-check-circle me-2 text-success"></i>Statut Serveur</span>
                    <span class="badge bg-success"><i class="fas fa-circle text-white me-1" style="font-size:8px;"></i> Actif</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small"><i class="fas fa-code-branch me-2 text-muted"></i>Version Logiciel</span>
                    <span class="font-monospace fw-bold text-dark small">v1.0.0 Pro</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== SCRIPTS & CHARTS ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('mainCaChart').getContext('2d');
    
    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(46, 125, 50, 0.4)');
    gradient.addColorStop(1, 'rgba(46, 125, 50, 0.0)');
    
    const jours = <?= json_encode($jours) ?>;
    const caData = <?= json_encode($ca_jours) ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: jours,
            datasets: [{
                label: 'Chiffre d\'Affaires (FCFA)',
                data: caData,
                borderColor: '#1b5e20',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#2e7d32',
                pointRadius: 4,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.parsed.y.toLocaleString('fr-FR') + ' FCFA';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { size: 10 },
                        callback: function(value) {
                            return value.toLocaleString('fr-FR') + ' F';
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