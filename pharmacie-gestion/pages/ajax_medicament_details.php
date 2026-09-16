<?php
require_once '../includes/config.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die('<div class="alert alert-danger">ID invalide</div>');
}

$conn = getConnection();

$stmt = $conn->prepare("
    SELECT m.*, f.nom_fournisseur, f.telephone as fournisseur_tel, f.email as fournisseur_email
    FROM medicaments m
    LEFT JOIN fournisseurs f ON m.id_fournisseur = f.id_fournisseur
    WHERE m.id_medicament = ?
");
$stmt->execute([$id]);
$med = $stmt->fetch();

if (!$med) {
    die('<div class="alert alert-danger">Médicament non trouvé</div>');
}

// Statistiques des ventes
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as nb_ventes,
        SUM(quantite) as total_vendu,
        AVG(quantite) as moyenne_par_vente,
        SUM(quantite * prix_unitaire) as chiffre_affaires
    FROM details_ventes
    WHERE id_medicament = ?
");
$stmt->execute([$id]);
$stats = $stmt->fetch();

$statut_stock = '';
$statut_class = '';
if ($med['quantite_stock'] <= 0) {
    $statut_stock = 'Rupture de stock';
    $statut_class = 'danger';
} elseif ($med['quantite_stock'] <= $med['stock_minimum']) {
    $statut_stock = 'Stock bas';
    $statut_class = 'warning';
} else {
    $statut_stock = 'Disponible';
    $statut_class = 'success';
}
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="border-bottom pb-2"><i class="fas fa-info-circle text-info"></i> Informations générales</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td style="width: 40%;"><strong>Code CIP</strong></td>
                <td><code><?= $med['code_cip'] ?></code></td>
            </tr>
            <tr>
                <td><strong>Nom</strong></td>
                <td class="fw-bold" style="color: #1b5e20;"><?= htmlspecialchars($med['nom_medicament']) ?></td>
            </tr>
            <tr>
                <td><strong>Catégorie</strong></td>
                <td><?= $med['categorie'] ? '<span class="badge bg-info">' . htmlspecialchars($med['categorie']) . '</span>' : '<span class="text-muted">Non catégorisé</span>' ?></td>
            </tr>
            <tr>
                <td><strong>Description</strong></td>
                <td><?= nl2br(htmlspecialchars($med['description'] ?: 'Aucune description')) ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="border-bottom pb-2"><i class="fas fa-chart-bar text-success"></i> Statistiques</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td style="width: 40%;"><strong>Prix d'achat</strong></td>
                <td><?= number_format($med['prix_achat'], 0) ?> CFA</td>
            </tr>
            <tr>
                <td><strong>Prix de vente</strong></td>
                <td class="fw-bold text-success"><?= number_format($med['prix_vente'], 0) ?> CFA</td>
            </tr>
            <tr>
                <td><strong>Marge</strong></td>
                <td><?= number_format($med['prix_vente'] - $med['prix_achat'], 0) ?> CFA</td>
            </tr>
            <tr>
                <td><strong>Stock actuel</strong></td>
                <td>
                    <span class="badge bg-<?= $statut_class ?>" style="font-size: 14px;"><?= $med['quantite_stock'] ?></span>
                    <small class="text-muted">(Min: <?= $med['stock_minimum'] ?>)</small>
                </td>
            </tr>
            <tr>
                <td><strong>Statut stock</strong></td>
                <td><span class="badge bg-<?= $statut_class ?>"><?= $statut_stock ?></span></td>
            </tr>
            <tr>
                <td><strong>Date expiration</strong></td>
                <td class="<?= strtotime($med['date_expiration']) < time() ? 'text-danger fw-bold' : '' ?>">
                    <?= date('d/m/Y', strtotime($med['date_expiration'])) ?>
                    <?php if(strtotime($med['date_expiration']) < time()): ?>
                        <span class="badge bg-danger">Expiré</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6 mt-3">
        <h6 class="border-bottom pb-2"><i class="fas fa-truck text-primary"></i> Fournisseur</h6>
        <?php if ($med['nom_fournisseur']): ?>
            <p><strong><?= htmlspecialchars($med['nom_fournisseur']) ?></strong></p>
            <p class="text-muted small">
                <?php if($med['fournisseur_tel']): ?>
                    <i class="fas fa-phone"></i> <?= $med['fournisseur_tel'] ?><br>
                <?php endif; ?>
                <?php if($med['fournisseur_email']): ?>
                    <i class="fas fa-envelope"></i> <?= $med['fournisseur_email'] ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="text-muted">Aucun fournisseur associé</p>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6 mt-3">
        <h6 class="border-bottom pb-2"><i class="fas fa-shopping-cart text-warning"></i> Statistiques de ventes</h6>
        <?php if ($stats['nb_ventes'] > 0): ?>
            <table class="table table-sm table-borderless">
                <tr>
                    <td style="width: 40%;"><strong>Nombre de ventes</strong></td>
                    <td><?= $stats['nb_ventes'] ?></td>
                </tr>
                <tr>
                    <td><strong>Total vendu</strong></td>
                    <td><?= $stats['total_vendu'] ?> unités</td>
                </tr>
                <tr>
                    <td><strong>Moyenne par vente</strong></td>
                    <td><?= round($stats['moyenne_par_vente'], 1) ?> unités</td>
                </tr>
                <tr>
                    <td><strong>Chiffre d'affaires</strong></td>
                    <td class="fw-bold text-success"><?= number_format($stats['chiffre_affaires'], 0) ?> CFA</td>
                </tr>
            </table>
        <?php else: ?>
            <p class="text-muted text-center py-2">
                <i class="fas fa-inbox d-block mb-1"></i>
                Aucune vente enregistrée pour ce médicament
            </p>
        <?php endif; ?>
    </div>
</div>