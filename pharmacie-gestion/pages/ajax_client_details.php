<?php
require_once '../includes/config.php';
requireLogin();

// SI APPEL GET : RETOURNER L'HTML DES DÉTAILS DE CLIENT
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id_client = (int)$_GET['id'];
    $conn = getConnection();
    
    // Récupérer le client
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id_client = ?");
    $stmt->execute([$id_client]);
    $client = $stmt->fetch();
    
    if (!$client) {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Client introuvable.</div>';
        exit;
    }
    
    // Récupérer l'historique des achats du client
    $stmt = $conn->prepare("
        SELECT v.*, 
               (SELECT COUNT(*) FROM details_ventes WHERE id_vente = v.id_vente) as nb_articles,
               (SELECT GROUP_CONCAT(CONCAT(m.nom_medicament, ' (x', dv.quantite, ')')) 
                FROM details_ventes dv 
                JOIN medicaments m ON dv.id_medicament = m.id_medicament 
                WHERE dv.id_vente = v.id_vente) as produits_vendus
        FROM ventes v 
        WHERE v.id_client = ? 
        ORDER BY v.date_vente DESC 
        LIMIT 10
    ");
    $stmt->execute([$id_client]);
    $ventes = $stmt->fetchAll();
    
    // Statistiques d'achat du client
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as nb_achats,
            COALESCE(SUM(montant_total), 0) as total_depense,
            COALESCE(AVG(montant_total), 0) as panier_moyen
        FROM ventes 
        WHERE id_client = ?
    ");
    $stmt->execute([$id_client]);
    $stats = $stmt->fetch();
    
    $initials = strtoupper(substr($client['prenom'], 0, 1) . substr($client['nom'], 0, 1));
    ?>
    <div class="client-detail-header d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-4">
        <div class="avatar-circle-lg bg-success text-white fw-bold fs-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:64px; height:64px;">
            <?= $initials ?>
        </div>
        <div>
            <h4 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($client['nom'] . ' ' . $client['prenom']) ?></h4>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i> <?= $client['points_fidelite'] ?> Points</span>
                <span class="badge bg-secondary"><i class="fas fa-calendar-alt me-1"></i> Inscrit le <?= date('d/m/Y', strtotime($client['date_inscription'] ?? 'now')) ?></span>
            </div>
        </div>
    </div>

    <!-- METRIQUES D'ACHAT -->
    <div class="row g-3 mb-4 text-center">
        <div class="col-4">
            <div class="p-3 bg-white border rounded-3 shadow-sm">
                <div class="text-muted small">Achats Effectués</div>
                <div class="fs-4 fw-bold text-success"><?= $stats['nb_achats'] ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 bg-white border rounded-3 shadow-sm">
                <div class="text-muted small">Total Dépensé</div>
                <div class="fs-4 fw-bold text-primary"><?= number_format($stats['total_depense'], 0, ',', ' ') ?> F</div>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 bg-white border rounded-3 shadow-sm">
                <div class="text-muted small">Panier Moyen</div>
                <div class="fs-4 fw-bold text-warning"><?= number_format($stats['panier_moyen'], 0, ',', ' ') ?> F</div>
            </div>
        </div>
    </div>

    <!-- COORDONNEES -->
    <div class="card mb-4 border-0 bg-light">
        <div class="card-body">
            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-address-card me-2 text-success"></i>Coordonnées Client</h6>
            <div class="row g-2 small">
                <div class="col-md-6"><strong><i class="fas fa-phone text-muted me-2"></i>Téléphone :</strong> <?= htmlspecialchars($client['telephone'] ?: 'Non renseigné') ?></div>
                <div class="col-md-6"><strong><i class="fas fa-envelope text-muted me-2"></i>Email :</strong> <?= htmlspecialchars($client['email'] ?: 'Non renseigné') ?></div>
                <div class="col-md-6"><strong><i class="fas fa-map-marker-alt text-muted me-2"></i>Adresse :</strong> <?= htmlspecialchars($client['adresse'] ?: 'Non renseignée') ?></div>
                <div class="col-md-6"><strong><i class="fas fa-birthday-cake text-muted me-2"></i>Naissance :</strong> <?= $client['date_naissance'] ? date('d/m/Y', strtotime($client['date_naissance'])) : 'Non renseignée' ?></div>
            </div>
        </div>
    </div>

    <!-- HISTORIQUE DES ACHATS -->
    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-history me-2 text-info"></i>Historique des Ventes / Achats</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>N° Facture</th>
                    <th>Date</th>
                    <th>Articles & Produits</th>
                    <th class="text-end">Montant</th>
                    <th class="text-center">Ticket</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ventes)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">Aucun achat enregistré pour ce client.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ventes as $v): ?>
                        <tr>
                            <td class="fw-bold text-success font-monospace">#FAC-<?= str_pad($v['id_vente'], 6, '0', STR_PAD_LEFT) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($v['date_vente'])) ?></td>
                            <td class="small"><?= htmlspecialchars($v['produits_vendus'] ?? 'Articles divers') ?></td>
                            <td class="text-end fw-bold text-success font-monospace"><?= number_format($v['montant_total'], 0, ',', ' ') ?> FCFA</td>
                            <td class="text-center">
                                <a href="details_vente.php?id=<?= $v['id_vente'] ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2">
                                    <i class="fas fa-file-invoice"></i> PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    exit;
}

// SI APPEL POST : CREATION JSON AJAX DU CLIENT
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['nom']) || empty($data['prenom'])) {
    echo json_encode(['success' => false, 'message' => 'Nom et prénom requis']);
    exit;
}

try {
    $conn = getConnection();
    
    // Vérifier si le client existe déjà
    $stmt = $conn->prepare("SELECT id_client FROM clients WHERE nom = ? AND prenom = ? AND telephone = ?");
    $stmt->execute([
        trim($data['nom']),
        trim($data['prenom']),
        trim($data['telephone'] ?? '')
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce client existe déjà']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO clients (nom, prenom, telephone, email, adresse) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        trim($data['nom']),
        trim($data['prenom']),
        trim($data['telephone'] ?? ''),
        trim($data['email'] ?? ''),
        trim($data['adresse'] ?? '')
    ]);
    
    $id_client = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => $id_client,
        'message' => 'Client ajouté avec succès'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
?>