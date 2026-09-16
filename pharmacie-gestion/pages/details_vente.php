<?php
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    die("ID de vente manquant");
}

$venteId = (int)$_GET['id'];
$conn = getConnection();

$stmt = $conn->prepare("
    SELECT v.*, c.nom, c.prenom, c.telephone, c.email, c.adresse, c.points_fidelite 
    FROM ventes v 
    LEFT JOIN clients c ON v.id_client = c.id_client 
    WHERE v.id_vente = ?
");
$stmt->execute([$venteId]);
$vente = $stmt->fetch();

if (!$vente) {
    die("Vente non trouvée");
}

$stmt = $conn->prepare("
    SELECT d.*, m.nom_medicament, m.code_cip, m.categorie 
    FROM details_ventes d 
    JOIN medicaments m ON d.id_medicament = m.id_medicament 
    WHERE d.id_vente = ?
");
$stmt->execute([$venteId]);
$details = $stmt->fetchAll();

$pointsGagnes = floor($vente['montant_total'] / 1000);
$invoiceNumber = "FAC-" . date('Y', strtotime($vente['date_vente'])) . "-" . str_pad($vente['id_vente'], 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $invoiceNumber ?> - Pharmacie Souley-Guirou</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1b5e20;
            --primary-light: #2e7d32;
            --accent-color: #4caf50;
            --bg-light: #f4f6f9;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #e2e8f0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-main);
            padding: 20px 0 80px;
            -webkit-font-smoothing: antialiased;
        }

        /* PAGE A4 CONTAINER */
        .a4-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm 15mm;
            border-radius: 4px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* TOP HEADER BRANDING */
        .brand-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .brand-logo-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .pharmacy-logo {
            width: 65px;
            height: 65px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--accent-color);
        }

        .pharmacy-name {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary-color);
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .pharmacy-sub {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        .pharmacy-meta {
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.4;
            margin-top: 6px;
        }

        /* INVOICE BADGE & NUMBERS */
        .invoice-title-block {
            text-align: right;
        }

        .invoice-type-badge {
            display: inline-block;
            background: #e8f5e9;
            color: var(--primary-color);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 6px;
        }

        .invoice-num {
            font-size: 20px;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-main);
        }

        .invoice-date {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* CLIENT & TRANSACTION CARDS */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .meta-card {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 14px 16px;
        }

        .meta-card-title {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary-light);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-card-body {
            font-size: 12.5px;
            line-height: 1.5;
        }

        .meta-card-body strong {
            color: var(--text-main);
            font-weight: 700;
        }

        /* TABLE STYLING */
        .invoice-table-wrapper {
            margin: 10px 0 20px;
            flex-grow: 1;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .invoice-table th {
            background: var(--primary-color);
            color: #ffffff;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 10px 14px;
            text-align: left;
        }

        .invoice-table th.text-end {
            text-align: right;
        }

        .invoice-table th.text-center {
            text-align: center;
        }

        .invoice-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .invoice-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .cip-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--text-muted);
        }

        .med-name {
            font-weight: 600;
            color: var(--text-main);
        }

        /* TOTALS & SUMMARY SECTION */
        .summary-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px dashed var(--border-color);
        }

        .payment-status-box {
            flex: 1;
            background: #f1f5f9;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 11.5px;
            line-height: 1.5;
            color: var(--text-muted);
        }

        .status-badge-paid {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #15803d;
            font-weight: 700;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .totals-box {
            width: 280px;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 14px 16px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 6px;
            color: var(--text-muted);
        }

        .summary-row.total-final {
            border-top: 2px solid var(--primary-color);
            padding-top: 8px;
            margin-top: 8px;
            margin-bottom: 0;
            font-size: 16px;
            font-weight: 800;
            color: var(--primary-color);
        }

        /* FOOTER & BARCODE */
        .invoice-footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10.5px;
            color: var(--text-muted);
        }

        .fake-barcode {
            display: flex;
            align-items: flex-end;
            gap: 2px;
            height: 28px;
        }

        .fake-barcode span {
            display: inline-block;
            background: #000;
            height: 100%;
        }

        /* FLOATING ACTION BAR FOR SCREEN */
        .floating-action-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            padding: 10px 24px;
            border-radius: 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            display: flex;
            gap: 12px;
            z-index: 9999;
        }

        .btn-action-print {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: #ffffff;
            border: none;
            padding: 10px 22px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.4);
        }

        .btn-action-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(46, 125, 50, 0.5);
            color: #ffffff;
        }

        .btn-action-close {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: none;
            padding: 10px 18px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 13.5px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-action-close:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        /* PRINT CSS MEDIA QUERY */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .a4-page {
                width: 100% !important;
                min-height: auto !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                border-radius: 0 !important;
            }
            .floating-action-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="a4-page">
    <div>
        <!-- EN-TÊTE FACTURE -->
        <div class="brand-header">
            <div class="brand-logo-group">
                <img src="../assets/images/logo.png" alt="Pharmacie Souley-Guirou" class="pharmacy-logo" onerror="this.src='../assets/images/logos.png'">
                <div>
                    <h1 class="pharmacy-name">PHARMACIE SOULEY-GUIROU</h1>
                    <div class="pharmacy-sub">Officine de Pharmacie & Parapharmacie</div>
                    <div class="pharmacy-meta">
                        <i class="fas fa-map-marker-alt text-success me-1"></i> Avenue Principale, Dakar, Sénégal<br>
                        <i class="fas fa-phone text-success me-1"></i> +221 33 821 00 00 &bull; <i class="fas fa-envelope text-success me-1"></i> contact@pharmacie-souley-guirou.sn<br>
                        <span class="fw-bold">N° Agrément :</span> PH-2024-8891 &bull; <span class="fw-bold">NINEA :</span> 004928102
                    </div>
                </div>
            </div>
            
            <div class="invoice-title-block">
                <div class="invoice-type-badge">Facture Officielle / Reçu</div>
                <div class="invoice-num"><?= $invoiceNumber ?></div>
                <div class="invoice-date">
                    <i class="far fa-calendar-alt me-1"></i> Date : <?= date('d/m/Y à H:i', strtotime($vente['date_vente'])) ?>
                </div>
            </div>
        </div>

        <!-- GRILLE DE MÉTADONNÉES CLIENT & CAISSE -->
        <div class="meta-grid">
            <!-- CLIENT -->
            <div class="meta-card">
                <div class="meta-card-title">
                    <i class="fas fa-user-check"></i> Facturé à / Client
                </div>
                <div class="meta-card-body">
                    <?php if ($vente['nom']): ?>
                        <strong><?= htmlspecialchars($vente['nom'] . ' ' . $vente['prenom']) ?></strong><br>
                        <?php if ($vente['telephone']): ?>
                            <i class="fas fa-phone me-1 text-muted"></i> <?= htmlspecialchars($vente['telephone']) ?><br>
                        <?php endif; ?>
                        <?php if ($vente['email']): ?>
                            <i class="fas fa-envelope me-1 text-muted"></i> <?= htmlspecialchars($vente['email']) ?><br>
                        <?php endif; ?>
                        <?php if ($pointsGagnes > 0): ?>
                            <span class="badge bg-warning text-dark mt-1"><i class="fas fa-star me-1"></i> +<?= $pointsGagnes ?> Points fidélité crédités</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <strong>Client Comptoir (Vente Directe)</strong><br>
                        <span class="text-muted">Achat anonyme au guichet</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- REGLEMENT -->
            <div class="meta-card">
                <div class="meta-card-title">
                    <i class="fas fa-receipt"></i> Modalités de Règlement
                </div>
                <div class="meta-card-body">
                    <div class="status-badge-paid">
                        <i class="fas fa-check-circle"></i> Payé en Totalité
                    </div><br>
                    <strong>Mode de paiement :</strong> Espèces / Comptoir<br>
                    <strong>Opérateur Caisse :</strong> Pharmacien de Garde<br>
                    <strong>Statut Transaction :</strong> Validée & Enregistrée
                </div>
            </div>
        </div>

        <!-- TABLEAU DES ARTICLES -->
        <div class="invoice-table-wrapper">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width: 18%;">Réf. CIP</th>
                        <th>Désignation du Médicament</th>
                        <th style="width: 18%;">Catégorie</th>
                        <th class="text-end" style="width: 15%;">P.U (CFA)</th>
                        <th class="text-center" style="width: 10%;">Qté</th>
                        <th class="text-end" style="width: 18%;">Total (CFA)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalHt = 0;
                    foreach ($details as $item): 
                        $sub = $item['prix_unitaire'] * $item['quantite'];
                        $totalHt += $sub;
                    ?>
                    <tr>
                        <td class="cip-code"><?= htmlspecialchars($item['code_cip']) ?></td>
                        <td class="med-name"><?= htmlspecialchars($item['nom_medicament']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($item['categorie'] ?: 'Général') ?></td>
                        <td class="text-end font-monospace"><?= number_format($item['prix_unitaire'], 0, ',', ' ') ?></td>
                        <td class="text-center fw-bold"><?= $item['quantite'] ?></td>
                        <td class="text-end fw-bold text-success font-monospace"><?= number_format($sub, 0, ',', ' ') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECTION RÉCAPITULATIF FINANCIER -->
        <div class="summary-section">
            <div class="payment-status-box">
                <div class="fw-bold text-dark mb-1"><i class="fas fa-shield-alt text-success me-1"></i> Mentions Légales & Conditions</div>
                Facture délivrée conformément aux dispositions du Code de la Santé Publique.
                Les médicaments et produits parapharmaceutiques régulièrement livrés ne sont ni repris ni échangés par mesure d'hygiène et de sécurité sanitaire.
            </div>

            <div class="totals-box">
                <div class="summary-row">
                    <span>Total HT :</span>
                    <span class="font-monospace fw-bold"><?= number_format($vente['montant_total'], 0, ',', ' ') ?> CFA</span>
                </div>
                <div class="summary-row">
                    <span>TVA (Exonéré 0%) :</span>
                    <span class="font-monospace">0 CFA</span>
                </div>
                <div class="summary-row total-final">
                    <span>NET À PAYER :</span>
                    <span class="font-monospace"><?= number_format($vente['montant_total'], 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
        </div>
    </div>

    <!-- PIED DE PAGE ET BARCODE -->
    <div class="invoice-footer">
        <div>
            <strong>Pharmacie Souley-Guirou</strong> &bull; Document officiel certifié conforme &bull; Page 1/1
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="fake-barcode">
                <span style="width:2px;"></span><span style="width:1px;"></span><span style="width:3px;"></span><span style="width:1px;"></span><span style="width:2px;"></span><span style="width:4px;"></span><span style="width:1px;"></span><span style="width:2px;"></span><span style="width:3px;"></span><span style="width:1px;"></span><span style="width:2px;"></span>
            </div>
            <span class="font-monospace text-muted small"><?= $invoiceNumber ?></span>
        </div>
    </div>
</div>

<!-- BARRE DE BOUTONS FLOTTANTE DE LA PAGE -->
<div class="floating-action-bar">
    <button class="btn-action-print" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimer / Télécharger la Facture PDF
    </button>
    <button class="btn-action-close" onclick="window.close()">
        <i class="fas fa-times"></i> Fermer
    </button>
</div>

</body>
</html>
