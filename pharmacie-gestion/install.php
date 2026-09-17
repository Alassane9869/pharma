<?php
require_once __DIR__ . '/includes/config.php';

$message = '';
$status = 'info';
$installed = [];

try {
    $conn = getConnection();
    
    // Initialiser les tables MySQL automatiquement
    initMysqlDatabase($conn);
    
    // Vérifier l'état de chaque table
    $tables = ['utilisateurs', 'medicaments', 'clients', 'ventes', 'details_ventes', 'fournisseurs', 'commandes_fournisseurs', 'details_commandes', 'logs_activites'];
    
    foreach ($tables as $t) {
        try {
            $count = $conn->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $installed[$t] = $count;
        } catch(Exception $e) {
            $installed[$t] = 'Erreur';
        }
    }
    
    $status = 'success';
    $message = "La base de données 'vuxe8870_SouleyGuirou' a été auto-initialisée et configurée avec succès !";
} catch(Exception $e) {
    $status = 'danger';
    $message = "Erreur lors de l'initialisation : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Automatique - Pharmacie Souley-Guirou</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .install-card { max-width: 650px; margin: 50px auto; border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
        .install-header { background: linear-gradient(135deg, #1b5e20, #388e3c); color: white; border-radius: 16px 16px 0 0; padding: 25px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card install-card">
        <div class="card-header install-header text-center">
            <h3><i class="fas fa-magic me-2"></i> Installation Automatique</h3>
            <p class="mb-0 text-white-50">Pharmacie Souley-Guirou Pro &bull; Souley-Guirou.danayaplus.com</p>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-<?= $status ?> d-flex align-items-center mb-4">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div><?= $message ?></div>
            </div>
            
            <h5 class="fw-bold text-dark mb-3"><i class="fas fa-database text-success me-2"></i> État de la Base de Données MySQL</h5>
            
            <div class="table-responsive mb-4">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th>Table MySQL</th>
                            <th>Statut / Lignes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($installed as $table => $cnt): ?>
                            <tr>
                                <td><code><?= $table ?></code></td>
                                <td>
                                    <?php if (is_numeric($cnt)): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Créée (<?= $cnt ?> enregistrements)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i> <?= $cnt ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info small mb-4">
                <i class="fas fa-key me-1"></i> <strong>Compte Administrateur par défaut :</strong><br>
                Nom d'utilisateur : <code>admin</code> | Mot de passe : <code>admin123</code>
            </div>

            <div class="text-center">
                <a href="../login.php" class="btn btn-success btn-lg w-100 py-3 font-weight-bold">
                    <i class="fas fa-rocket me-2"></i> Accéder à l'Application
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
