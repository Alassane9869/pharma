<?php
require_once '../includes/config.php';
requireLogin();

header('Content-Type: application/json');

// Récupérer les données POST
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier si les données sont valides
if (!$data || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Panier vide']);
    exit;
}

try {
    $conn = getConnection();
    $conn->beginTransaction();
    
    $id_client = !empty($data['client_id']) ? (int)$data['client_id'] : null;
    $total = 0;
    $items = [];
    
    // Vérifier les stocks et calculer le total
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("SELECT id_medicament, prix_vente, quantite_stock, nom_medicament FROM medicaments WHERE id_medicament = ?");
        $stmt->execute([$item['id']]);
        $med = $stmt->fetch();
        
        if (!$med) {
            throw new Exception("Médicament non trouvé (ID: {$item['id']})");
        }
        
        if ($med['quantite_stock'] < $item['quantity']) {
            throw new Exception("Stock insuffisant pour {$med['nom_medicament']}. Disponible: {$med['quantite_stock']}");
        }
        
        $total += $med['prix_vente'] * $item['quantity'];
        $items[] = [
            'id' => $med['id_medicament'],
            'quantity' => $item['quantity'],
            'price' => $med['prix_vente'],
            'name' => $med['nom_medicament']
        ];
    }
    
    if ($total <= 0) {
        throw new Exception("Montant total invalide");
    }
    
    // Insérer la vente
    $stmt = $conn->prepare("INSERT INTO ventes (id_client, montant_total) VALUES (?, ?)");
    $stmt->execute([$id_client, $total]);
    $id_vente = $conn->lastInsertId();
    
    // Insérer les détails et mettre à jour le stock
    foreach ($items as $item) {
        // Insérer le détail
        $stmt = $conn->prepare("INSERT INTO details_ventes (id_vente, id_medicament, quantite, prix_unitaire) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_vente, $item['id'], $item['quantity'], $item['price']]);
        
        // Mettre à jour le stock
        $stmt = $conn->prepare("UPDATE medicaments SET quantite_stock = quantite_stock - ? WHERE id_medicament = ?");
        $stmt->execute([$item['quantity'], $item['id']]);
    }
    
    // Mettre à jour les points de fidélité du client
    $points_credites = 0;
    if ($id_client) {
        $points_credites = floor($total / 1000); // 1 point par 1000 CFA
        if ($points_credites > 0) {
            $stmt = $conn->prepare("UPDATE clients SET points_fidelite = points_fidelite + ? WHERE id_client = ?");
            $stmt->execute([$points_credites, $id_client]);
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'total' => $total,
        'id_vente' => $id_vente,
        'points_credites' => $points_credites,
        'message' => 'Vente enregistrée avec succès !'
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>