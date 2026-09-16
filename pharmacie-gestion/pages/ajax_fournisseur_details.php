<?php
require_once '../includes/config.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die('<div class="alert alert-danger">ID fournisseur invalide</div>');
}

$conn = getConnection();

// Récupérer les infos du fournisseur
$stmt = $conn->prepare("SELECT * FROM fournisseurs WHERE id_fournisseur = ?");
$stmt->execute([$id]);
$fournisseur = $stmt->fetch();

if (!$fournisseur) {
    die('<div class="alert alert-danger">Fournisseur non trouvé</div>');
}

// Récupérer les produits associés
$stmt = $conn->prepare("
    SELECT * FROM medicaments 
    WHERE id_fournisseur = ? 
    ORDER BY nom_medicament
    LIMIT 20
");
$stmt->execute([$id]);
$produits = $stmt->fetchAll();

// Récupérer les commandes
$stmt = $conn->prepare("
    SELECT * FROM commandes_fournisseurs 
    WHERE id_fournisseur = ? 
    ORDER BY date_commande DESC 
    LIMIT 10
");
$stmt->execute([$id]);
$commandes = $stmt->fetchAll();

// Statistiques
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as nb_produits,
        COALESCE(SUM(quantite_stock), 0) as stock_total,
        COALESCE(SUM(prix_achat * quantite_stock), 0) as valeur_stock
    FROM medicaments 
    WHERE id_fournisseur = ?
");
$stmt->execute([$id]);
$stats = $stmt->fetch();

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as nb_commandes,
        COALESCE(SUM(montant_total), 0) as total_commandes
    FROM commandes_fournisseurs 
    WHERE id_fournisseur = ?
");
$stmt->execute([$id]);
$statsCmd = $stmt->fetch();
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="border-bottom pb-2"><i class="fas fa-info-circle text-info"></i> Informations</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td style="width: 35%;"><strong>Nom</strong></td>
                <td class="fw-bold" style="color: #1b5e20;"><?= htmlspecialchars($fournisseur['nom_fournisseur']) ?></td>
            </tr>
            <tr>
                <td><strong>Contact</strong></td>
                <td><?= $fournisseur['contact'] ?: '<span class="text-muted">Non renseigné</span>' ?></td>
            </tr>
            <tr>
                <td><strong>Téléphone</strong></td>
                <td><?= $fournisseur['telephone'] ?: '<span class="text-muted">Non renseigné</span>' ?></td>
            </tr>
            <tr>
                <td><strong>Email</strong></td>
                <td><?= $fournisseur['email'] ?: '<span class="text-muted">Non renseigné</span>' ?></td>
            </tr>
            <tr>
                <td><strong>Adresse</strong></td>
                <td><?= htmlspecialchars($fournisseur['adresse'] ?: 'Non renseignée') ?></td>
            </tr>
            <tr>
                <td><strong>Site web</strong></td>
                <td><?= $fournisseur['site_web'] ?: '<span class="text-muted">Non renseigné</span>' ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="border-bottom pb-2"><i class="fas fa-chart-bar text-success"></i> Statistiques</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td style="width: 50%;"><strong>Produits associés</strong></td>
                <td><span class="badge bg-info"><?= $stats['nb_produits'] ?></span></td>
            </tr>
            <tr>
                <td><strong>Stock total</strong></td>
                <td><?= $stats['stock_total'] ?> unités</td>
            </tr>
            <tr>
                <td><strong>Valeur du stock</strong></td>
                <td class="fw-bold text-success"><?= number_format($stats['valeur_stock'], 0) ?> CFA</td>
            </tr>
            <tr>
                <td><strong>Commandes passées</strong></td>
                <td><?= $statsCmd['nb_commandes'] ?></td>
            </tr>
            <tr>
                <td><strong>Total commandes</strong></td>
                <td class="fw-bold text-primary"><?= number_format($statsCmd['total_commandes'], 0) ?> CFA</td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($produits)): ?>
<div class="row mt-3">
    <div class="col-12">
        <h6 class="border-bottom pb-2"><i class="fas fa-pills text-success"></i> Produits associés (<?= count($produits) ?>)</h6>
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
            <table class="table table-sm table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th class="text-end">Prix vente</th>
                        <th class="text-center">Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($produits as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nom_medicament']) ?></td>
                        <td class="text-end"><?= number_format($p['prix_vente'], 0) ?> CFA</td>
                        <td class="text-center"><?= $p['quantite_stock'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex gap-2">
            <a href="commande_fournisseur.php?id=<?= $id ?>" class="btn btn-success btn-sm">
                <i class="fas fa-shopping-cart"></i> Passer commande
            </a>
            <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
    </div>
</div>