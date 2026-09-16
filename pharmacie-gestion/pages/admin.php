<?php
require_once '../includes/config.php';
requireLogin();

// Vérifier que l'utilisateur est administrateur
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

$conn = getConnection();
$message = '';
$error = '';

// ===== TRAITEMENT DES ACTIONS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add_user':
                    // Vérifier si l'utilisateur existe déjà
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = ?");
                    $stmt->execute([trim($_POST['nom_utilisateur'])]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "❌ Ce nom d'utilisateur existe déjà";
                        break;
                    }
                    
                    $stmt = $conn->prepare("
                        INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, role, email) 
                        VALUES (?, MD5(?), ?, ?)
                    ");
                    $stmt->execute([
                        trim($_POST['nom_utilisateur']),
                        trim($_POST['mot_de_passe']),
                        $_POST['role'],
                        trim($_POST['email'])
                    ]);
                    $message = "✅ Utilisateur ajouté avec succès !";
                    break;
                    
                case 'edit_user':
                    $stmt = $conn->prepare("
                        UPDATE utilisateurs SET 
                            role = ?, 
                            email = ?
                        WHERE id_utilisateur = ?
                    ");
                    $stmt->execute([
                        $_POST['role'],
                        trim($_POST['email']),
                        intval($_POST['id_utilisateur'])
                    ]);
                    
                    // Si un nouveau mot de passe est fourni
                    if (!empty($_POST['nouveau_mot_de_passe'])) {
                        $stmt = $conn->prepare("
                            UPDATE utilisateurs SET mot_de_passe = MD5(?) 
                            WHERE id_utilisateur = ?
                        ");
                        $stmt->execute([
                            trim($_POST['nouveau_mot_de_passe']),
                            intval($_POST['id_utilisateur'])
                        ]);
                    }
                    
                    $message = "✅ Utilisateur modifié avec succès !";
                    break;
                    
                case 'delete_user':
                    // Empêcher la suppression de son propre compte
                    if ($_POST['id_utilisateur'] == $_SESSION['user_id']) {
                        $error = "❌ Vous ne pouvez pas supprimer votre propre compte !";
                        break;
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
                    $stmt->execute([$_POST['id_utilisateur']]);
                    $message = "✅ Utilisateur supprimé avec succès !";
                    break;
            }
        } catch (Exception $e) {
            $error = "❌ Erreur : " . $e->getMessage();
        }
    }
}

// ===== RÉCUPÉRER LES UTILISATEURS =====
$users = $conn->query("SELECT * FROM utilisateurs ORDER BY date_creation DESC")->fetchAll();

// ===== STATISTIQUES =====
$stats = [];
$stats['total'] = count($users);
$stats['admins'] = $conn->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'")->fetchColumn();
$stats['pharmaciens'] = $conn->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'pharmacien'")->fetchColumn();
$stats['caissiers'] = $conn->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'caissier'")->fetchColumn();
$stats['actifs'] = $conn->query("SELECT COUNT(*) FROM utilisateurs WHERE status = 'actif'")->fetchColumn();

// Récupérer les dernières connexions
$dernieresConnexions = $conn->query("
    SELECT * FROM logs_activites 
    WHERE action LIKE 'connexion%' 
    ORDER BY date_action DESC 
    LIMIT 10
")->fetchAll();

// Récupérer les logs récents
$logsRecents = $conn->query("
    SELECT l.*, u.nom_utilisateur 
    FROM logs_activites l
    LEFT JOIN utilisateurs u ON l.utilisateur_id = u.id_utilisateur
    ORDER BY l.date_action DESC 
    LIMIT 20
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Pharmacie Natinin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --pharma-green: #2e7d32;
            --pharma-light-green: #4caf50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* ===== SIDEBAR ===== */
        .sidebar {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 50%, #388e3c 100%);
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-brand {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            flex-shrink: 0;
        }
        
        .sidebar-brand h5 {
            color: white;
            margin: 0;
            font-weight: 600;
        }
        
        .sidebar-brand small {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
        }
        
        .sidebar .nav-container {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 10px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 3px 10px;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            text-decoration: none;
            cursor: pointer;
            position: relative;
            z-index: 1;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
        }
        
        .sidebar-footer {
            flex-shrink: 0;
            width: 100%;
            padding: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }
        
        .sidebar-footer .btn {
            border-radius: 10px;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 250px;
            padding: 20px 30px;
            min-height: 100vh;
        }
        
        /* ===== PAGE HEADER ===== */
        .page-header {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .page-header h4 {
            margin: 0;
            color: #1b5e20;
            font-weight: 600;
        }
        
        .page-header h4 i {
            color: #4caf50;
        }
        
        .page-header .badge-admin {
            background: #ff6b6b;
            color: white;
            font-size: 12px;
            padding: 5px 15px;
            border-radius: 20px;
        }
        
        /* ===== STAT CARDS ===== */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
            border-left: 4px solid #4caf50;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1b5e20;
        }
        
        .stat-card .stat-label {
            color: #666;
            font-size: 13px;
        }
        
        .stat-card.border-warning { border-left-color: #ff9800; }
        .stat-card.border-info { border-left-color: #2196f3; }
        .stat-card.border-purple { border-left-color: #9c27b0; }
        .stat-card.border-danger { border-left-color: #dc3545; }
        
        /* ===== WIDGET ===== */
        .widget {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .widget-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
        
        .widget-header h5 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }
        
        .widget-header h5 i {
            color: #4caf50;
        }
        
        .widget-body {
            padding: 20px;
        }
        
        /* ===== TABLEAU ===== */
        .table-users {
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .table-users thead th {
            background: #e8f5e9;
            color: #1b5e20;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #4caf50;
            padding: 10px 8px;
            vertical-align: middle;
        }
        
        .table-users tbody td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table-users tbody tr:hover {
            background-color: #f8fff8;
        }
        
        .table-users .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: white;
        }
        
        /* ===== BOUTONS ===== */
        .btn-action {
            width: 30px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
            border: none;
            font-size: 12px;
        }
        
        .btn-action:hover {
            transform: scale(1.1);
        }
        
        .btn-action.btn-edit {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-action.btn-edit:hover {
            background: #e0a800;
        }
        
        .btn-action.btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-action.btn-delete:hover {
            background: #b02a37;
        }
        
        .btn-action.btn-reset {
            background: #17a2b8;
            color: white;
        }
        
        .btn-action.btn-reset:hover {
            background: #117a8b;
        }
        
        /* ===== BADGE RÔLE ===== */
        .badge-role {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-role.admin { background: #dc3545; color: white; }
        .badge-role.pharmacien { background: #28a745; color: white; }
        .badge-role.caissier { background: #17a2b8; color: white; }
        
        /* ===== LOG ITEMS ===== */
        .log-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }
        
        .log-item:last-child {
            border-bottom: none;
        }
        
        .log-item .log-icon {
            width: 30px;
            text-align: center;
            color: #6c757d;
        }
        
        .log-item .log-user {
            font-weight: 600;
            color: #1b5e20;
        }
        
        .log-item .log-time {
            font-size: 11px;
            color: #999;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: block !important;
            }
        }
        
        .sidebar-toggle {
            display: none;
            background: #2e7d32;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 10px 15px;
            }
            .page-header {
                padding: 10px 15px;
            }
            .page-header h4 {
                font-size: 16px;
            }
            .stat-card .stat-number {
                font-size: 18px;
            }
            .table-users {
                font-size: 12px;
            }
            .table-users thead th {
                font-size: 10px;
                padding: 6px 4px;
            }
            .table-users tbody td {
                padding: 6px 4px;
            }
        }
        
        /* ===== SCROLLBAR ===== */
        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #4caf50;
            border-radius: 10px;
        }
        
        /* ===== ANIMATION ===== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animated { animation: fadeInUp 0.5s ease; }
        
        /* ===== SECTION ===== */
        .section-title {
            font-weight: 600;
            color: #1b5e20;
            border-bottom: 2px solid #e8f5e9;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        /* ===== AVATAR COLORS ===== */
        .avatar-1 { background: #e74c3c; }
        .avatar-2 { background: #3498db; }
        .avatar-3 { background: #2ecc71; }
        .avatar-4 { background: #f39c12; }
        .avatar-5 { background: #9b59b6; }
        .avatar-6 { background: #1abc9c; }
        .avatar-7 { background: #e67e22; }
        .avatar-8 { background: #e84393; }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h5>🏥 Pharmacie Natinin</h5>
        <small>Administration</small>
    </div>
    
    <div class="nav-container">
        <nav class="nav flex-column mt-3">
            <a href="../dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="medicaments.php" class="nav-link">
                <i class="fas fa-pills"></i> Médicaments
            </a>
            <a href="clients.php" class="nav-link">
                <i class="fas fa-users"></i> Clients
            </a>
            <a href="ventes.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Ventes
            </a>
            <a href="fournisseurs.php" class="nav-link">
                <i class="fas fa-truck"></i> Fournisseurs
            </a>
            <a href="commande_fournisseur.php" class="nav-link">
                <i class="fas fa-file-invoice"></i> Commandes
            </a>
            <a href="factures.php" class="nav-link">
                <i class="fas fa-receipt"></i> Factures
            </a>
            <a href="admin.php" class="nav-link active">
                <i class="fas fa-user-shield"></i> Administration
            </a>
        </nav>
    </div>
    
    <div class="sidebar-footer">
        <div class="text-white-50 small mb-2">
            <i class="fas fa-user-circle"></i> <?= $_SESSION['user_name'] ?>
            <span class="badge bg-light text-dark ms-2"><?= $_SESSION['user_role'] ?></span>
        </div>
        <a href="../logout.php" class="btn btn-danger w-100 btn-sm">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">

    <!-- ===== HEADER ===== -->
    <div class="page-header">
        <div>
            <h4><i class="fas fa-user-shield"></i> Administration</h4>
            <small class="text-muted">Gestion des utilisateurs et configuration du système</small>
        </div>
        <div>
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <span class="badge-admin"><i class="fas fa-shield-alt"></i> Admin</span>
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
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label"><i class="fas fa-users text-success"></i> Total utilisateurs</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card border-danger">
                <div class="stat-number"><?= $stats['admins'] ?></div>
                <div class="stat-label"><i class="fas fa-user-shield text-danger"></i> Administrateurs</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card border-success">
                <div class="stat-number"><?= $stats['pharmaciens'] ?></div>
                <div class="stat-label"><i class="fas fa-user-md text-success"></i> Pharmaciens</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card border-info">
                <div class="stat-number"><?= $stats['caissiers'] ?></div>
                <div class="stat-label"><i class="fas fa-user-tie text-info"></i> Caissiers</div>
            </div>
        </div>
    </div>

    <!-- ===== TABLEAU DES UTILISATEURS ===== -->
    <div class="widget animated">
        <div class="widget-header">
            <h5><i class="fas fa-list"></i> Gestion des utilisateurs</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> Nouvel utilisateur
            </button>
        </div>
        <div class="widget-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-users">
                    <thead>
                        <tr>
                            <th style="width: 40px;">ID</th>
                            <th style="min-width: 160px;">Utilisateur</th>
                            <th style="width: 120px;">Rôle</th>
                            <th style="width: 180px;">Email</th>
                            <th style="width: 140px;">Date création</th>
                            <th style="width: 140px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted d-block mb-2"></i>
                                    <p class="text-muted">Aucun utilisateur</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): 
                                $avatarColors = ['avatar-1', 'avatar-2', 'avatar-3', 'avatar-4', 'avatar-5', 'avatar-6', 'avatar-7', 'avatar-8'];
                                $avatarClass = $avatarColors[$user['id_utilisateur'] % count($avatarColors)];
                                $initials = strtoupper(substr($user['nom_utilisateur'], 0, 2));
                                $isCurrentUser = $user['id_utilisateur'] == $_SESSION['user_id'];
                            ?>
                            <tr>
                                <td class="text-center"><?= $user['id_utilisateur'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="user-avatar <?= $avatarClass ?>"><?= $initials ?></span>
                                        <div>
                                            <span class="fw-bold"><?= htmlspecialchars($user['nom_utilisateur']) ?></span>
                                            <?php if ($isCurrentUser): ?>
                                                <span class="badge bg-primary ms-1">Vous</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-role <?= $user['role'] ?>">
                                        <?php 
                                        $roles = [
                                            'admin' => '👑 Administrateur',
                                            'pharmacien' => '💊 Pharmacien',
                                            'caissier' => '🧾 Caissier'
                                        ];
                                        echo $roles[$user['role']] ?? $user['role'];
                                        ?>
                                    </span>
                                </td>
                                <td><?= $user['email'] ? htmlspecialchars($user['email']) : '<span class="text-muted">-</span>' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($user['date_creation'])) ?></td>
                                <td class="text-center">
                                    <button class="btn-action btn-edit" onclick='editUser(<?= json_encode($user) ?>)' title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (!$isCurrentUser): ?>
                                        <button class="btn-action btn-delete" onclick="deleteUser(<?= $user['id_utilisateur'] ?>, '<?= addslashes($user['nom_utilisateur']) ?>')" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button class="btn-action btn-reset" onclick="resetPassword(<?= $user['id_utilisateur'] ?>, '<?= addslashes($user['nom_utilisateur']) ?>')" title="Réinitialiser mot de passe">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 11px;">(compte actif)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== LOGS ET ACTIVITÉS ===== -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="widget">
                <div class="widget-header">
                    <h5><i class="fas fa-history text-info"></i> Dernières connexions</h5>
                </div>
                <div class="widget-body">
                    <?php if (empty($dernieresConnexions)): ?>
                        <p class="text-muted text-center py-3">
                            <i class="fas fa-inbox d-block mb-2"></i>
                            Aucune connexion récente
                        </p>
                    <?php else: ?>
                        <?php foreach ($dernieresConnexions as $log): ?>
                            <div class="log-item">
                                <div>
                                    <span class="log-icon"><i class="fas fa-sign-in-alt text-success"></i></span>
                                    <span class="log-user"><?= htmlspecialchars($log['details'] ?? 'Connexion') ?></span>
                                </div>
                                <div>
                                    <span class="log-time"><?= date('d/m/Y H:i', strtotime($log['date_action'])) ?></span>
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
                    <h5><i class="fas fa-clipboard-list text-warning"></i> Activités récentes</h5>
                </div>
                <div class="widget-body">
                    <?php if (empty($logsRecents)): ?>
                        <p class="text-muted text-center py-3">
                            <i class="fas fa-inbox d-block mb-2"></i>
                            Aucune activité récente
                        </p>
                    <?php else: ?>
                        <?php foreach ($logsRecents as $log): ?>
                            <div class="log-item">
                                <div>
                                    <span class="log-icon">
                                        <?php if (strpos($log['action'], 'vente') !== false): ?>
                                            <i class="fas fa-shopping-cart text-success"></i>
                                        <?php elseif (strpos($log['action'], 'connexion') !== false): ?>
                                            <i class="fas fa-sign-in-alt text-info"></i>
                                        <?php elseif (strpos($log['action'], 'suppression') !== false): ?>
                                            <i class="fas fa-trash text-danger"></i>
                                        <?php else: ?>
                                            <i class="fas fa-circle text-secondary"></i>
                                        <?php endif; ?>
                                    </span>
                                    <span class="log-user"><?= htmlspecialchars($log['nom_utilisateur'] ?? 'Système') ?></span>
                                    <span class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($log['action']) ?></span>
                                </div>
                                <div>
                                    <span class="log-time"><?= date('d/m/Y H:i', strtotime($log['date_action'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ===== MODAL AJOUT UTILISATEUR ===== -->
<!-- ============================================ -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Ajouter un utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom d'utilisateur *</label>
                        <input type="text" name="nom_utilisateur" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mot de passe *</label>
                        <input type="password" name="mot_de_passe" class="form-control" required>
                        <small class="text-muted">Minimum 6 caractères</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rôle *</label>
                        <select name="role" class="form-select" required>
                            <option value="pharmacien">💊 Pharmacien</option>
                            <option value="caissier">🧾 Caissier</option>
                            <option value="admin">👑 Administrateur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@exemple.com">
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
<!-- ===== MODAL MODIFICATION UTILISATEUR ===== -->
<!-- ============================================ -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-user-edit"></i> Modifier l'utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id_utilisateur" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom d'utilisateur</label>
                        <input type="text" id="edit_nom" class="form-control" disabled>
                        <small class="text-muted">Le nom d'utilisateur ne peut pas être modifié</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rôle *</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="pharmacien">💊 Pharmacien</option>
                            <option value="caissier">🧾 Caissier</option>
                            <option value="admin">👑 Administrateur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" placeholder="email@exemple.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nouveau mot de passe</label>
                        <input type="password" name="nouveau_mot_de_passe" class="form-control" placeholder="Laisser vide pour ne pas changer">
                        <small class="text-muted">Minimum 6 caractères</small>
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
<!-- ===== MODAL RÉINITIALISATION MOT DE PASSE ===== -->
<!-- ============================================ -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-key"></i> Réinitialiser le mot de passe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id_utilisateur" id="reset_id">
                <div class="modal-body">
                    <p>Réinitialiser le mot de passe pour : <strong id="reset_nom"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nouveau mot de passe *</label>
                        <input type="password" name="nouveau_mot_de_passe" class="form-control" required>
                        <small class="text-muted">Minimum 6 caractères</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Confirmer le mot de passe *</label>
                        <input type="password" id="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-key"></i> Réinitialiser
                    </button>
                </div>
            </form>
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

// ===== MODIFIER UN UTILISATEUR =====
function editUser(user) {
    document.getElementById('edit_id').value = user.id_utilisateur;
    document.getElementById('edit_nom').value = user.nom_utilisateur;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_email').value = user.email || '';
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

// ===== SUPPRIMER UN UTILISATEUR =====
function deleteUser(id, name) {
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer l\'utilisateur :\n"' + name + '" ?')) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="id_utilisateur" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ===== RÉINITIALISER LE MOT DE PASSE =====
function resetPassword(id, name) {
    document.getElementById('reset_id').value = id;
    document.getElementById('reset_nom').textContent = name;
    
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

// ===== VALIDATION MOT DE PASSE =====
document.querySelector('#resetPasswordModal form')?.addEventListener('submit', function(e) {
    const password = this.querySelector('input[name="nouveau_mot_de_passe"]').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('⚠️ Les mots de passe ne correspondent pas !');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('⚠️ Le mot de passe doit contenir au moins 6 caractères !');
        return false;
    }
    
    return true;
});

// ===== VALIDATION FORMULAIRE AJOUT =====
document.querySelector('#addUserModal form')?.addEventListener('submit', function(e) {
    const password = this.querySelector('input[name="mot_de_passe"]').value;
    
    if (password.length < 6) {
        e.preventDefault();
        alert('⚠️ Le mot de passe doit contenir au moins 6 caractères !');
        return false;
    }
    
    return true;
});

// ===== RACCOURCI CLAVIER =====
document.addEventListener('keydown', function(e) {
    // Ctrl + U pour ajouter un utilisateur
    if (e.ctrlKey && e.key === 'u') {
        e.preventDefault();
        document.querySelector('[data-bs-target="#addUserModal"]')?.click();
    }
});
</script>

</body>
</html>