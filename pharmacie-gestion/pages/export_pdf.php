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
    <title>Document_Officiel_PDF_Pharmacie</title>
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

    <!-- EN-TÊTE DU DOCUMENT -->
    <div class="row align-items-center mb-4 pb-3 border-bottom">
        <div class="col-7 d-flex align-items-center gap-3">
            <img src="../assets/images/logo.png" alt="Pharmacie Natinin" class="header-logo" onerror="this.style.display='none'">
            <div>
                <h3 class="doc-title mb-1"><i class="fas fa-heartbeat text-success"></i> PHARMACIE NATININ</h3>
                <p class="text-muted small mb-0">Système Officiel de Gestion & Documents Officiels</p>
                <p class="text-muted small mb-0">Tél: +221 33 821 00 00 &bull; Email: direction@pharmacie-natinin.com</p>
            </div>
        </div>
        <div class="col-5 text-end">
            <?php if ($type === 'inventaire'): ?>
                <h4 class="fw-bold text-success mb-1">RAPPORT D'INVENTAIRE</h4>
                <div class="text-muted small">État complet des stocks</div>
                <div class="small fw-bold text-dark mt-1">Généré le : <?= date('d/m/Y à H:i') ?></div>
            <?php elseif ($type === 'commande'): ?>
                <h4 class="fw-bold text-success mb-1">BON DE COMMANDE</h4>
                <div class="text-muted small">Commande Fournisseur Officielle</div>
                <div class="small fw-bold text-dark mt-1">Ref N° #CMD-<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></div>
            <?php endif; ?>
        </div>
    </div>

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

        <!-- CONTENU RAPPORT D'INVENTAIRE -->
        <div class="row mb-3 g-3">
            <div class="col-4">
                <div class="p-3 bg-light rounded border">
                    <div class="small text-muted text-uppercase fw-bold">Nombre de références</div>
                    <div class="fs-5 fw-bold text-success"><?= count($medicaments) ?> médicaments</div>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 bg-light rounded border">
                    <div class="small text-muted text-uppercase fw-bold">Valeur d'Achat Stock</div>
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
            echo "<div class='alert alert-danger'>Commande non trouvée.</div></div></body></html>";
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

        <!-- CONTENU BON DE COMMANDE -->
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
                    <div>Émis par : Pharmacie Natinin</div>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-striped table-pdf mb-4">
            <thead>
                <tr>
                    <th>CIP</th>
                    <th>Désignation Médicament</th>
                    <th class="text-center">Quantité Commandée</th>
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

        <!-- SIGNATURE -->
        <div class="row mt-5 pt-3">
            <div class="col-6 text-center">
                <div class="small text-muted mb-5">Signature & Cachet du Pharmacien Responsable :</div>
                <div class="border-bottom mx-4"></div>
            </div>
            <div class="col-6 text-center">
                <div class="small text-muted mb-5">Accusé de réception Fournisseur :</div>
                <div class="border-bottom mx-4"></div>
            </div>
        </div>

    <?php endif; ?>

    <!-- BOUTONS D'ACTION PDF (Masqués à l'impression) -->
    <div class="text-center mt-4 pt-3 border-top no-print">
        <button class="btn btn-success btn-lg px-4 me-2" onclick="window.print()">
            <i class="fas fa-file-pdf me-2"></i> Télécharger / Imprimer Document PDF
        </button>
        <button class="btn btn-secondary btn-lg px-4" onclick="window.history.back()">
            <i class="fas fa-arrow-left me-2"></i> Retour
        </button>
    </div>

</div>

</body>
</html>
