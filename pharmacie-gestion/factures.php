<?php
require_once __DIR__ . '/includes/config.php';
$pdo = getConnection();

// Récupérer toutes les factures (ventes)
$factures = $pdo->query("
    SELECT v.*, c.nom, c.prenom, c.telephone,
           (SELECT COUNT(*) FROM details_ventes WHERE id_vente = v.id_vente) as nb_articles
    FROM ventes v 
    LEFT JOIN clients c ON v.id_client = c.id_client 
    ORDER BY v.date_vente DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Factures - Pharmacie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand"><i class="fas fa-file-invoice"></i> Gestion des Factures</span>
            <div>
                <a href="index.php" class="btn btn-sm btn-outline-light"><i class="fas fa-home"></i> Dashboard</a>
                <a href="ventes.php" class="btn btn-sm btn-outline-light"><i class="fas fa-shopping-cart"></i> Nouvelle vente</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-list"></i> Historique des factures</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>N° Facture</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Téléphone</th>
                                <th>Montant</th>
                                <th>Articles</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($factures as $f): ?>
                            <tr>
                                <td>#<?= str_pad($f['id_vente'], 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($f['date_vente'])) ?></td>
                                <td><?= $f['nom'] ? htmlspecialchars($f['nom'] . ' ' . $f['prenom']) : 'Client anonyme' ?></td>
                                <td><?= $f['telephone'] ?: '-' ?></td>
                                <td class="text-end fw-bold"><?= number_format($f['montant_total'], 2) ?> CFA</td>
                                <td class="text-center"><?= $f['nb_articles'] ?></td>
                                <td>
                                    <a href="details_vente.php?id=<?= $f['id_vente'] ?>" class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-print"></i> Imprimer
                                    </a>
                                    <a href="details_vente.php?id=<?= $f['id_vente'] ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($factures)): ?>
                                <tr><td colspan="7" class="text-center">Aucune facture enregistrée</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">TOTAL GÉNÉRAL :</th>
                                <th class="text-end">
                                    <?php 
                                        $totalGeneral = $pdo->query("SELECT SUM(montant_total) FROM ventes")->fetchColumn();
                                        echo number_format($totalGeneral, 2) . ' CFA';
                                    ?>
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6>Chiffre d'affaires total</h6>
                        <h3><?= number_format($totalGeneral ?? 0, 0) ?> CFA</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6>Nombre total ventes</h6>
                        <h3><?= count($factures) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h6>Facture moyenne</h6>
                        <h3><?= count($factures) > 0 ? number_format($totalGeneral / count($factures), 0) : 0 ?> CFA</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>