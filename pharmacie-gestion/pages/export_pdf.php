<?php
require_once '../includes/config.php';
requireLogin();

$conn = getConnection();
$type = $_GET['type'] ?? 'inventaire';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document_PDF_Pharmacie_Souley_Guirou</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --pharma-green: #2e7d32;
            --pharma-dark: #1b5e20;
            --pharma-light: #e8f5e9;
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #2c3e50;
        }

        .pdf-box {
            max-width: 950px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 40px;
            border-top: 6px solid var(--pharma-green);
        }

        .header-logo {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--pharma-light);
        }

        .doc-title {
            color: var(--pharma-dark);
            font-weight: 800;
        }

        .table-pdf thead {
            background: var(--pharma-dark);
            color: #ffffff;
        }

        .table-pdf th {
            padding: 10px 12px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .table-pdf td {
            padding: 10px 12px;
            font-size: 13px;
            vertical-align: middle;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .pdf-box {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 15px !important;
                border-top: none !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="pdf-box">

    <!-- BARRE DE NAVIGATION MODÈLES PDF (MASQUÉE À L'IMPRESSION) -->
    <div class="no-print mb-4 p-3 bg-light rounded border d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="fw-bold text-success"><i class="fas fa-file-pdf me-2"></i> Modèles de Documents PDF :</div>
        <div class="btn-group btn-group-sm flex-wrap">
            <a href="export_pdf.php?type=inventaire" class="btn btn-outline-success <?= $type==='inventaire'?'active':'' ?>"><i class="fas fa-boxes me-1"></i> Inventaire</a>
            <a href="export_pdf.php?type=rapport_journalier" class="btn btn-outline-success <?= $type==='rapport_journalier'?'active':'' ?>"><i class="fas fa-chart-line me-1"></i> Bilan Ventes</a>
            <a href="export_pdf.php?type=clients" class="btn btn-outline-success <?= $type==='clients'?'active':'' ?>"><i class="fas fa-users me-1"></i> Annuaire Clients</a>
            <a href="export_pdf.php?type=fournisseurs" class="btn btn-outline-success <?= $type==='fournisseurs'?'active':'' ?>"><i class="fas fa-truck me-1"></i> Fournisseurs</a>
        </div>
    </div>

    <!-- EN-TÊTE DU DOCUMENT -->
    <div class="row align-items-center mb-4 pb-3 border-bottom">
        <div class="col-7 d-flex align-items-center gap-3">
            <img src="../assets/images/logo.png" alt="Pharmacie Souley-Guirou" class="header-logo" onerror="this.style.display='none'">
            <div>
                <h3 class="doc-title mb-1"><i class="fas fa-heartbeat text-success"></i> PHARMACIE SOULEY-GUIROU</h3>
                <p class="text-muted small mb-0">Système de Gestion & Documents</p>
                <p class="text-muted small mb-0">Tél: +221 33 821 00 00 &bull; Email: contact@Souley-Guirou.danayaplus.com</p>
            </div>
        </div>
        <div class="col-5 text-end">
            <?php if ($type === 'inventaire'): ?>
                <h4 class="fw-bold text-success mb-1">RAPPORT D'INVENTAIRE</h4>
                <div class="text-muted small">État complet des stocks</div>
                <div class="small fw-bold text-dark mt-1">Généré le : <?= date('d/m/Y à H:i') ?></div>
            <?php elseif ($type === 'commande'): ?>
                <h4 class="fw-bold text-success mb-1">BON DE COMMANDE</h4>
                <div class="text-muted small">Commande Fournisseur</div>
                <div class="small fw-bold text-dark mt-1">Ref N° #CMD-<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></div>
            <?php elseif ($type === 'facture'): ?>
                <h4 class="fw-bold text-success mb-1">FACTURE DE VENTE</h4>
                <div class="text-muted small">Document de Caisse</div>
                <div class="small fw-bold text-dark mt-1">Ref N° #FAC-<?= date('Y') ?>-<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></div>
            <?php elseif ($type === 'clients'): ?>
                <h4 class="fw-bold text-success mb-1">ANNUAIRE CLIENTS & FIDÉLITÉ</h4>
                <div class="text-muted small">Relevé des Comptes Clients</div>
                <div class="small fw-bold text-dark mt-1">Généré le : <?= date('d/m/Y à H:i') ?></div>
            <?php elseif ($type === 'fournisseurs'): ?>
                <h4 class="fw-bold text-success mb-1">ANNUAIRE FOURNISSEURS</h4>
                <div class="text-muted small">Partenaires Pharmaceutiques</div>
                <div class="small fw-bold text-dark mt-1">Généré le : <?= date('d/m/Y à H:i') ?></div>
            <?php elseif ($type === 'rapport_journalier'): ?>
                <h4 class="fw-bold text-success mb-1">BILAN FINANCIER DU JOUR</h4>
                <div class="text-muted small">Rapport de Ventes Quotidien</div>
                <div class="small fw-bold text-dark mt-1">Date : <?= date('d/m/Y') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODÈLE 1 : INVENTAIRE STOCKS -->
    <!-- ============================================================ -->
    <?php if ($type === 'inventaire'): 
        $medicaments = $conn->query("
            SELECT m.*, f.nom_fournisseur 
            FROM medicaments m 
            LEFT JOIN fournisseurs f ON m.id_fournisseur = f.id_fournisseur 
            ORDER BY m.nom_medicament ASC
        ")->fetchAll();
        $totalStockVal = $conn->query("SELECT COALESCE(SUM(prix_achat * quantite_stock), 0) FROM medicaments")->fetchColumn();
        $totalVenteVal = $conn->query("SELECT COALESCE(SUM(prix_vente * quantite_stock), 0) FROM medicaments")->fetchColumn();
    ?>
        <div class="row mb-3 g-3">
            <div class="col-4">
                <div class="p-3 bg-light rounded border">
                    <div class="small text-muted text-uppercase fw-bold">Références</div>
                    <div class="fs-5 fw-bold text-success"><?= count($medicaments) ?> médicaments</div>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 bg-light rounded border">
                    <div class="small text-muted text-uppercase fw-bold">Valeur Achat Stock</div>
                    <div class="fs-5 fw-bold text-dark"><?= number_format($totalStockVal, 0) ?> CFA</div>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 bg-light rounded border">
                    <div class="small text-muted text-uppercase fw-bold">Valeur Vente Estimée</div>
                    <div class="fs-5 fw-bold text-primary"><?= number_format($totalVenteVal, 0) ?> CFA</div>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-striped table-pdf mb-4">
            <thead>
                <tr>
                    <th>CIP</th>
                    <th>Nom du Médicament</th>
                    <th>Catégorie</th>
                    <th>Fournisseur</th>
                    <th class="text-end">Prix Achat</th>
                    <th class="text-end">Prix Vente</th>
                    <th class="text-center">Stock</th>
                    <th>Péremption</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medicaments as $m): ?>
                <tr>
                    <td class="font-monospace small"><?= htmlspecialchars($m['code_cip']) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($m['nom_medicament']) ?></td>
                    <td><?= htmlspecialchars($m['categorie'] ?: 'Général') ?></td>
                    <td><?= htmlspecialchars($m['nom_fournisseur'] ?: 'Non spécifié') ?></td>
                    <td class="text-end"><?= number_format($m['prix_achat'], 0) ?> F</td>
                    <td class="text-end fw-bold"><?= number_format($m['prix_vente'], 0) ?> F</td>
                    <td class="text-center fw-bold">
                        <span class="badge <?= $m['quantite_stock'] == 0 ? 'bg-danger' : ($m['quantite_stock'] <= $m['stock_minimum'] ? 'bg-warning text-dark' : 'bg-success') ?>">
                            <?= $m['quantite_stock'] ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($m['date_expiration'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <!-- ============================================================ -->
    <!-- MODÈLE 2 : BON DE COMMANDE FOURNISSEUR -->
    <!-- ============================================================ -->
    <?php elseif ($type === 'commande'): 
        $cmd = $conn->prepare("
            SELECT c.*, f.nom_fournisseur, f.contact, f.telephone, f.email, f.adresse 
            FROM commandes_fournisseurs c 
            JOIN fournisseurs f ON c.id_fournisseur = f.id_fournisseur 
            WHERE c.id_commande = ?
        ");
        $cmd->execute([$id]);
        $commande = $cmd->fetch();

        if (!$commande) {
            echo "<div class='alert alert-danger'>Commande N° #$id introuvable.</div></div></body></html>";
            exit;
        }

        $stmtDetails = $conn->prepare("
            SELECT d.*, m.nom_medicament, m.code_cip 
            FROM details_commandes d 
            JOIN medicaments m ON d.id_medicament = m.id_medicament 
            WHERE d.id_commande = ?
        ");
        $stmtDetails->execute([$id]);
        $details = $stmtDetails->fetchAll();
    ?>
        <div class="row mb-4">
            <div class="col-6">
                <div class="p-3 bg-light rounded border border-start border-4 border-success">
                    <h6 class="fw-bold text-success mb-2"><i class="fas fa-building me-1"></i> FOURNISSEUR DESTINATAIRE</h6>
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($commande['nom_fournisseur']) ?></div>
                    <div>Contact : <?= htmlspecialchars($commande['contact'] ?: '-') ?></div>
                    <div>Tél : <?= htmlspecialchars($commande['telephone'] ?: '-') ?></div>
                    <div>Email : <?= htmlspecialchars($commande['email'] ?: '-') ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-light rounded border border-start border-4 border-info">
                    <h6 class="fw-bold text-info mb-2"><i class="fas fa-file-alt me-1"></i> DÉTAILS COMMANDE</h6>
                    <div>Date émission : <?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?></div>
                    <div>Statut : <span class="badge bg-warning text-dark"><?= strtoupper($commande['statut']) ?></span></div>
                    <div>Émis par : Pharmacie Souley-Guirou</div>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-striped table-pdf mb-4">
            <thead>
                <tr>
                    <th>CIP</th>
                    <th>Désignation Médicament</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-end">Prix Achat Estimé</th>
                    <th class="text-end">Sous-Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalCmd = 0;
                foreach ($details as $d): 
                    $sub = $d['quantite'] * $d['prix_achat'];
                    $totalCmd += $sub;
                ?>
                <tr>
                    <td class="font-monospace small"><?= htmlspecialchars($d['code_cip']) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($d['nom_medicament']) ?></td>
                    <td class="text-center fw-bold"><span class="badge bg-secondary"><?= $d['quantite'] ?></span></td>
                    <td class="text-end"><?= number_format($d['prix_achat'], 0) ?> CFA</td>
                    <td class="text-end fw-bold text-success"><?= number_format($sub, 0) ?> CFA</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end font-uppercase">TOTAL ESTIMÉ COMMANDE :</th>
                    <th class="text-end text-success fs-6"><?= number_format($totalCmd, 0) ?> CFA</th>
                </tr>
            </tfoot>
        </table>

        <div class="row mt-5 pt-3">
            <div class="col-6 text-center">
                <div class="small text-muted mb-5">Signature & Cachet Pharmacien :</div>
                <div class="border-bottom mx-4"></div>
            </div>
            <div class="col-6 text-center">
                <div class="small text-muted mb-5">Accusé de Réception Fournisseur :</div>
                <div class="border-bottom mx-4"></div>
            </div>
        </div>

    <!-- ============================================================ -->
    <!-- MODÈLE 3 : FACTURE CLIENT -->
    <!-- ============================================================ -->
    <?php elseif ($type === 'facture'): 
        $stmtV = $conn->prepare("
            SELECT v.*, c.nom, c.prenom, c.telephone, c.email, c.adresse, c.points_fidelite 
            FROM ventes v 
            LEFT JOIN clients c ON v.id_client = c.id_client 
            WHERE v.id_vente = ?
        ");
        $stmtV->execute([$id]);
        $vente = $stmtV->fetch();

        if (!$vente) {
            echo "<div class='alert alert-danger'>Facture N° #$id introuvable.</div></div></body></html>";
            exit;
        }

        $stmtD = $conn->prepare("
            SELECT dv.*, m.nom_medicament, m.code_cip 
            FROM details_ventes dv 
            JOIN medicaments m ON dv.id_medicament = m.id_medicament 
            WHERE dv.id_vente = ?
        ");
        $stmtD->execute([$id]);
        $items = $stmtD->fetchAll();
    ?>
        <div class="row mb-4">
            <div class="col-6">
                <div class="p-3 bg-light rounded border border-start border-4 border-success">
                    <h6 class="fw-bold text-success mb-2"><i class="fas fa-user me-1"></i> CLIENT / ACHETEUR</h6>
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars(($vente['nom'] ? $vente['nom'].' '.$vente['prenom'] : 'Client Passage')) ?></div>
                    <div>Tél : <?= htmlspecialchars($vente['telephone'] ?: 'Non renseigné') ?></div>
                    <div>Adresse : <?= htmlspecialchars($vente['adresse'] ?: '-') ?></div>
                    <?php if ($vente['points_fidelite']): ?>
                        <div class="badge bg-warning text-dark mt-1">Points fidélité : <?= $vente['points_fidelite'] ?> pts</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-light rounded border border-start border-4 border-primary">
                    <h6 class="fw-bold text-primary mb-2"><i class="fas fa-receipt me-1"></i> INFORMATIONS FACTURE</h6>
                    <div>N° Facture : <strong>FAC-<?= date('Y', strtotime($vente['date_vente'])) ?>-<?= str_pad($vente['id_vente'], 6, '0', STR_PAD_LEFT) ?></strong></div>
                    <div>Date & Heure : <?= date('d/m/Y à H:i', strtotime($vente['date_vente'])) ?></div>
                    <div>Mode de paiement : Comptant / Caisse</div>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-striped table-pdf mb-4">
            <thead>
                <tr>
                    <th>Désignation Article</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-end">Prix Unitaire</th>
                    <th class="text-end">Montant Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($item['nom_medicament']) ?></td>
                    <td class="text-center"><?= $item['quantite'] ?></td>
                    <td class="text-end"><?= number_format($item['prix_unitaire'], 0) ?> CFA</td>
                    <td class="text-end fw-bold"><?= number_format($item['quantite'] * $item['prix_unitaire'], 0) ?> CFA</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end font-uppercase fs-6">TOTAL PAYÉ (TTC) :</th>
                    <th class="text-end text-success fs-5"><?= number_format($vente['montant_total'], 0) ?> CFA</th>
                </tr>
            </tfoot>
        </table>

        <div class="text-center text-muted small mt-4 pt-3 border-top">
            Merci pour votre confiance &bull; Bon rétablissement ! &bull; Pharmacie Souley-Guirou
        </div>

    <!-- ============================================================ -->
    <!-- MODÈLE 4 : ANNUAIRE CLIENTS & FIDÉLITÉ -->
    <!-- ============================================================ -->
    <?php elseif ($type === 'clients'): 
        $clients = $conn->query("SELECT * FROM clients ORDER BY points_fidelite DESC, nom ASC")->fetchAll();
    ?>
        <table class="table table-bordered table-striped table-pdf mb-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nom & Prénom</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Adresse</th>
                    <th class="text-center">Points Fidélité</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $index => $c): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></td>
                    <td><?= htmlspecialchars($c['telephone'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($c['email'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($c['adresse'] ?: '-') ?></td>
                    <td class="text-center fw-bold text-warning"><?= $c['points_fidelite'] ?> pts</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <!-- ============================================================ -->
    <!-- MODÈLE 5 : ANNUAIRE FOURNISSEURS -->
    <!-- ============================================================ -->
    <?php elseif ($type === 'fournisseurs'): 
        $fournisseurs = $conn->query("SELECT * FROM fournisseurs ORDER BY nom_fournisseur ASC")->fetchAll();
    ?>
        <table class="table table-bordered table-striped table-pdf mb-4">
            <thead>
                <tr>
                    <th>Fournisseur</th>
                    <th>Personne Contact</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Adresse</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fournisseurs as $f): ?>
                <tr>
                    <td class="fw-bold text-success"><?= htmlspecialchars($f['nom_fournisseur']) ?></td>
                    <td><?= htmlspecialchars($f['contact'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($f['telephone'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($f['email'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($f['adresse'] ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <!-- ============================================================ -->
    <!-- MODÈLE 6 : BILAN FINANCIER DU JOUR -->
    <!-- ============================================================ -->
    <?php elseif ($type === 'rapport_journalier'): 
        $today = date('Y-m-d');
        $ventes = $conn->query("
            SELECT v.*, c.nom, c.prenom 
            FROM ventes v 
            LEFT JOIN clients c ON v.id_client = c.id_client 
            WHERE DATE(v.date_vente) = '$today' 
            ORDER BY v.date_vente DESC
        ")->fetchAll();
        $totalCA = $conn->query("SELECT COALESCE(SUM(montant_total), 0) FROM ventes WHERE DATE(date_vente) = '$today'")->fetchColumn();
    ?>
        <div class="row mb-4">
            <div class="col-4">
                <div class="p-3 bg-light rounded border text-center">
                    <div class="small text-muted fw-bold">TOTAL VENTES DU JOUR</div>
                    <div class="fs-4 fw-bold text-success"><?= count($ventes) ?> transactions</div>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 bg-light rounded border text-center">
                    <div class="small text-muted fw-bold">CHIFFRE D'AFFAIRES JOUR</div>
                    <div class="fs-4 fw-bold text-primary"><?= number_format($totalCA, 0) ?> CFA</div>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 bg-light rounded border text-center">
                    <div class="small text-muted fw-bold">PANIER MOYEN</div>
                    <div class="fs-4 fw-bold text-dark"><?= count($ventes) > 0 ? number_format($totalCA / count($ventes), 0) : 0 ?> CFA</div>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-striped table-pdf mb-4">
            <thead>
                <tr>
                    <th>N° Vente</th>
                    <th>Heure</th>
                    <th>Client</th>
                    <th class="text-end">Montant Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventes as $v): ?>
                <tr>
                    <td class="fw-bold">#FAC-<?= date('Y') ?>-<?= str_pad($v['id_vente'], 6, '0', STR_PAD_LEFT) ?></td>
                    <td><?= date('H:i', strtotime($v['date_vente'])) ?></td>
                    <td><?= htmlspecialchars(($v['nom'] ? $v['nom'].' '.$v['prenom'] : 'Client Passage')) ?></td>
                    <td class="text-end fw-bold text-success"><?= number_format($v['montant_total'], 0) ?> CFA</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- BOUTONS D'ACTION PDF (Masqués à l'impression) -->
    <div class="text-center mt-4 pt-3 border-top no-print">
        <button class="btn btn-success btn-lg px-4 me-2" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Télécharger / Imprimer ce Document PDF
        </button>
        <button class="btn btn-secondary btn-lg px-4" onclick="window.history.back()">
            <i class="fas fa-arrow-left me-2"></i> Retour
        </button>
    </div>

</div>

</body>
</html>
