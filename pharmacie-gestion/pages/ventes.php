<?php
require_once '../includes/config.php';
requireLogin();

// Définir le titre de la page
$page_title = 'Enregistrement des ventes - Pharmacie Natinin';
$include_chart = false;

$conn = getConnection();
$message = '';
$error = '';

// Récupérer la liste des médicaments disponibles
$medicaments = [];
$stmt = $conn->query("
    SELECT id_medicament, code_cip, nom_medicament, prix_vente, quantite_stock 
    FROM medicaments 
    WHERE quantite_stock > 0 
    ORDER BY nom_medicament
");
$medicaments = $stmt->fetchAll();

// Récupérer la liste des clients
$clients = [];
$stmt = $conn->query("
    SELECT id_client, nom, prenom, telephone, points_fidelite 
    FROM clients 
    ORDER BY nom
");
$clients = $stmt->fetchAll();

// Récupérer les ventes du jour avec le détail des médicaments
$ventesJour = [];
$stmt = $conn->query("
    SELECT v.*, c.nom, c.prenom,
           (SELECT GROUP_CONCAT(m.nom_medicament || ' (x' || dv.quantite || ')', ', ') 
            FROM details_ventes dv 
            JOIN medicaments m ON dv.id_medicament = m.id_medicament 
            WHERE dv.id_vente = v.id_vente) as produits_vendus,
           (SELECT COALESCE(SUM(quantite), 0) FROM details_ventes WHERE id_vente = v.id_vente) as total_articles
    FROM ventes v 
    LEFT JOIN clients c ON v.id_client = c.id_client 
    WHERE date(v.date_vente) = date('now', 'localtime') 
    ORDER BY v.date_vente DESC
    LIMIT 20
");
$ventesJour = $stmt->fetchAll();

// Statistiques du jour
$statsJour = [];
$stmt = $conn->query("
    SELECT 
        COUNT(*) as total_ventes,
        COALESCE(SUM(montant_total), 0) as total_ca,
        COALESCE(AVG(montant_total), 0) as panier_moyen
    FROM ventes 
    WHERE date(date_vente) = date('now', 'localtime')
");
$statsJour = $stmt->fetch();

// Vérifier si un client est pré-sélectionné
$client_preselected = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header">
    <div>
        <h4><i class="fas fa-shopping-cart"></i> Enregistrement des ventes</h4>
        <span class="date-info">
            <i class="far fa-calendar-alt"></i> 
            <?= date('d/m/Y à H:i') ?>
        </span>
    </div>
    <div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</div>

<!-- ===== STATS MINI ===== -->
<div class="row g-3 mb-4 animated">
    <div class="col-md-3 col-6">
        <div class="stat-mini">
            <div class="number"><?= $statsJour['total_ventes'] ?></div>
            <div class="label"><i class="fas fa-shopping-bag text-success"></i> Ventes aujourd'hui</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-mini" style="border-left-color: #ff9800;">
            <div class="number"><?= number_format($statsJour['total_ca'], 0) ?></div>
            <div class="label"><i class="fas fa-money-bill-wave text-warning"></i> CA aujourd'hui</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-mini" style="border-left-color: #2196f3;">
            <div class="number"><?= number_format($statsJour['panier_moyen'], 0) ?></div>
            <div class="label"><i class="fas fa-calculator text-info"></i> Panier moyen</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-mini" style="border-left-color: #9c27b0;">
            <div class="number"><?= count($medicaments) ?></div>
            <div class="label"><i class="fas fa-pills text-purple"></i> Produits disponibles</div>
        </div>
    </div>
</div>

<!-- ===== ROW PRINCIPAL ===== -->
<div class="row g-4">

    <!-- ===== COLONNE GAUCHE - RECHERCHE ===== -->
    <div class="col-lg-5">
        
        <!-- RECHERCHE PRODUIT -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-success text-white rounded-3">
                <h5 class="mb-0"><i class="fas fa-search"></i> Rechercher un médicament</h5>
            </div>
            <div class="card-body">
                <div class="search-container">
                    <input type="text" id="searchProduct" class="form-control form-control-lg" 
                           placeholder="🔍 Nom ou code CIP..." autocomplete="off">
                    <div id="searchResults" class="search-results"></div>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <label class="fw-bold small text-muted">OU sélectionner directement :</label>
                    <select id="productSelect" class="form-select mt-1">
                        <option value="">-- Sélectionner un médicament --</option>
                        <?php foreach ($medicaments as $med): ?>
                            <option value="<?= $med['id_medicament'] ?>" 
                                    data-nom="<?= htmlspecialchars($med['nom_medicament']) ?>"
                                    data-prix="<?= $med['prix_vente'] ?>"
                                    data-stock="<?= $med['quantite_stock'] ?>"
                                    data-cip="<?= $med['code_cip'] ?>">
                                <?= htmlspecialchars($med['nom_medicament']) ?> - <?= number_format($med['prix_vente'], 0) ?> CFA (Stock: <?= $med['quantite_stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="row g-2">
                    <div class="col-6">
                        <label class="fw-bold small text-muted">Quantité</label>
                        <input type="number" id="quantity" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <button class="btn btn-success w-100" onclick="addToCart()">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
                
                <!-- Produits populaires -->
                <hr>
                <div>
                    <label class="fw-bold small text-muted">⚡ Ajout rapide :</label>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <?php 
                        $rapides = array_slice($medicaments, 0, 6);
                        foreach ($rapides as $med): 
                        ?>
                            <button class="btn btn-outline-success btn-sm" 
                                    onclick="quickAdd(<?= $med['id_medicament'] ?>, '<?= addslashes($med['nom_medicament']) ?>', <?= $med['prix_vente'] ?>, <?= $med['quantite_stock'] ?>)">
                                <?= substr(htmlspecialchars($med['nom_medicament']), 0, 15) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- CLIENT -->
        <div class="card shadow-sm border-0 rounded-3 mt-3">
            <div class="card-header bg-info text-white rounded-3">
                <h5 class="mb-0"><i class="fas fa-user"></i> Information client</h5>
            </div>
            <div class="card-body">
                <select id="clientSelect" class="form-select mb-2">
                    <option value="">-- Client non identifié --</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id_client'] ?>" <?= $client_preselected == $client['id_client'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['nom'] . ' ' . $client['prenom']) ?>
                            <?php if ($client['telephone']): ?>
                                - <?= $client['telephone'] ?>
                            <?php endif; ?>
                            <?php if ($client['points_fidelite'] > 0): ?>
                                (⭐ <?= $client['points_fidelite'] ?> pts)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($client_preselected > 0): 
                    $clientInfo = array_filter($clients, function($c) use ($client_preselected) {
                        return $c['id_client'] == $client_preselected;
                    });
                    $clientInfo = array_shift($clientInfo);
                ?>
                    <div class="client-badge">
                        <i class="fas fa-check-circle text-success"></i>
                        Client sélectionné : <strong><?= htmlspecialchars($clientInfo['nom'] . ' ' . $clientInfo['prenom']) ?></strong>
                    </div>
                <?php endif; ?>
                
                <button class="btn btn-outline-primary btn-sm w-100 mt-2" data-bs-toggle="modal" data-bs-target="#newClientModal">
                    <i class="fas fa-user-plus"></i> Nouveau client
                </button>
            </div>
        </div>
    </div>

    <!-- ===== COLONNE DROITE - PANIER ===== -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-success text-white rounded-3">
                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Panier en cours</h5>
            </div>
            <div class="card-body">
                
                <!-- ITEMS PANIER -->
                <div class="cart-items" id="cartItems" style="max-height: 400px; overflow-y: auto;">
                    <div class="empty-cart">
                        <i class="fas fa-cart-plus"></i>
                        <p>Panier vide.<br>Ajoutez des médicaments.</p>
                    </div>
                </div>
                
                <hr>
                
                <!-- TOTAL -->
                <div class="total-box">
                    <div>
                        <div class="total-label">Total</div>
                        <small class="text-muted" id="itemCount">0 article(s)</small>
                    </div>
                    <div>
                        <div class="total-amount" id="totalAmount">0 CFA</div>
                    </div>
                </div>
                
                <!-- BOUTONS -->
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <button class="btn btn-danger-custom btn-sm" onclick="clearCart()">
                        <i class="fas fa-trash"></i> Vider panier
                    </button>
                    <button class="btn btn-success-custom btn-sm flex-grow-1" onclick="saveSale()">
                        <i class="fas fa-check"></i> Valider la vente
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== HISTORIQUE VENTES DU JOUR ===== -->
<div class="card shadow-sm border-0 rounded-3 mt-4">
    <div class="card-header bg-secondary text-white rounded-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-history"></i> Historique & Produits des Ventes du Jour</h5>
        <span class="badge bg-light text-dark"><?= count($ventesJour) ?> Vente(s) enregistrée(s)</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 120px;">N° Facture</th>
                        <th style="width: 80px;">Heure</th>
                        <th style="width: 200px;">Client</th>
                        <th>Médicaments & Produits Vendus</th>
                        <th class="text-end" style="width: 130px;">Montant Total</th>
                        <th class="text-center" style="width: 150px;">Action / Ticket</th>
                    </tr>
                </thead>
                <tbody id="salesHistory">
                    <?php if (empty($ventesJour)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2 text-muted"></i>
                                Aucune vente enregistrée aujourd'hui.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ventesJour as $sale): ?>
                        <tr>
                            <td class="font-monospace fw-bold text-success">
                                #FAC-<?= str_pad($sale['id_vente'], 6, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td><i class="far fa-clock text-muted me-1"></i><?= date('H:i', strtotime($sale['date_vente'])) ?></td>
                            <td>
                                <?php if ($sale['nom']): ?>
                                    <strong><i class="fas fa-user text-muted me-1"></i><?= htmlspecialchars($sale['nom'] . ' ' . $sale['prenom']) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-user-slash me-1"></i>Client Comptoir</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-success me-1"><?= $sale['total_articles'] ?> art.</span>
                                <span class="text-dark small fw-bold"><?= htmlspecialchars($sale['produits_vendus'] ?? 'Produits divers') ?></span>
                            </td>
                            <td class="text-end fw-bold text-success font-monospace">
                                <?= number_format($sale['montant_total'], 0, ',', ' ') ?> FCFA
                            </td>
                            <td class="text-center">
                                <a href="details_vente.php?id=<?= $sale['id_vente'] ?>" target="_blank" class="btn btn-sm btn-outline-success px-2 py-1" title="Imprimer le Reçu / Ticket PDF">
                                    <i class="fas fa-file-invoice me-1"></i> Reçu PDF
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ===== MODAL NOUVEAU CLIENT ===== -->
<!-- ============================================ -->
<div class="modal fade" id="newClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nouveau client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newClientForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Nom *</label>
                            <input type="text" id="clientNom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Prénom *</label>
                            <input type="text" id="clientPrenom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Téléphone</label>
                            <input type="tel" id="clientTel" class="form-control" placeholder="77 123 45 67">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Email</label>
                            <input type="email" id="clientEmail" class="form-control" placeholder="client@email.com">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="fw-bold">Adresse</label>
                            <textarea id="clientAdresse" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="saveClient()">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ===== SCRIPTS ===== -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== VARIABLES =====
let cart = [];
let products = <?= json_encode($medicaments) ?>;

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

// ===== RECHERCHE PRODUIT =====
document.getElementById('searchProduct').addEventListener('input', function() {
    let search = this.value.toLowerCase().trim();
    let resultsDiv = document.getElementById('searchResults');
    
    if (search.length > 1) {
        let filtered = products.filter(p => 
            p.nom_medicament.toLowerCase().includes(search) || 
            p.code_cip.toLowerCase().includes(search)
        );
        
        if (filtered.length > 0) {
            resultsDiv.innerHTML = filtered.map(p => `
                <div class="search-result-item" onclick="selectProduct(${p.id_medicament}, '${p.nom_medicament.replace(/'/g, "\\'")}', ${p.prix_vente}, ${p.quantite_stock})">
                    <div class="result-name">${p.nom_medicament}</div>
                    <div class="result-detail">${p.code_cip} - ${p.prix_vente} CFA (Stock: ${p.quantite_stock})</div>
                </div>
            `).join('');
            resultsDiv.style.display = 'block';
        } else {
            resultsDiv.innerHTML = '<div class="p-2 text-muted text-center">Aucun résultat</div>';
            resultsDiv.style.display = 'block';
        }
    } else {
        resultsDiv.style.display = 'none';
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-container')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});

// ===== SÉLECTION PRODUIT =====
function selectProduct(id, name, price, stock) {
    document.getElementById('searchResults').style.display = 'none';
    let quantity = parseInt(document.getElementById('quantity').value) || 1;
    addProductToCart(id, name, price, quantity, stock);
}

// ===== AJOUT RAPIDE =====
function quickAdd(id, name, price, stock) {
    let quantity = parseInt(document.getElementById('quantity').value) || 1;
    addProductToCart(id, name, price, quantity, stock);
}

// ===== AJOUT AU PANIER =====
function addToCart() {
    let select = document.getElementById('productSelect');
    let option = select.options[select.selectedIndex];
    if (!select.value) {
        alert('⚠️ Veuillez sélectionner un médicament');
        return;
    }
    let id = select.value;
    let name = option.dataset.nom;
    let price = parseFloat(option.dataset.prix);
    let stock = parseInt(option.dataset.stock);
    let quantity = parseInt(document.getElementById('quantity').value) || 1;
    addProductToCart(id, name, price, quantity, stock);
}

function addProductToCart(id, name, price, quantity, maxStock) {
    let existing = cart.find(item => item.id == id);
    let currentQtyInCart = existing ? existing.quantity : 0;
    
    if (maxStock && (currentQtyInCart + quantity > maxStock)) {
        alert('⚠️ Impossible d\'ajouter : Stock insuffisant ! Available: ' + maxStock + ' (déjà ' + currentQtyInCart + ' dans le panier)');
        return;
    }
    
    if (existing) {
        existing.quantity += quantity;
    } else {
        cart.push({ id: id, name: name, price: price, quantity: quantity, maxStock: maxStock });
    }
    
    document.getElementById('searchProduct').value = '';
    document.getElementById('quantity').value = 1;
    updateCartDisplay();
}

function updateCartDisplay() {
    let container = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-cart-plus"></i>
                <p>Panier vide.<br>Ajoutez des médicaments.</p>
            </div>
        `;
        document.getElementById('totalAmount').innerText = '0 CFA';
        document.getElementById('itemCount').innerText = '0 article(s)';
        return;
    }
    
    let total = 0;
    let totalItems = 0;
    let html = '';
    
    cart.forEach((item, index) => {
        let itemTotal = item.price * item.quantity;
        total += itemTotal;
        totalItems += item.quantity;
        
        html += `
            <div class="cart-item">
                <div class="item-info">
                    <div class="item-name">${item.name}</div>
                    <div class="item-price">${item.price} CFA x ${item.quantity}</div>
                </div>
                <div class="item-total">${itemTotal} CFA</div>
                <div class="item-actions">
                    <button class="btn btn-sm btn-outline-secondary me-1" onclick="changeQuantity(${index}, -1)">-</button>
                    <button class="btn btn-sm btn-outline-secondary me-1" onclick="changeQuantity(${index}, 1)">+</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    document.getElementById('totalAmount').innerText = total + ' CFA';
    document.getElementById('itemCount').innerText = totalItems + ' article(s)';
}

function changeQuantity(index, delta) {
    let item = cart[index];
    let newQty = item.quantity + delta;
    
    if (newQty <= 0) {
        cart.splice(index, 1);
    } else if (item.maxStock && newQty > item.maxStock) {
        alert('⚠️ Impossible d\'augmenter : Stock maximal atteint !');
    } else {
        item.quantity = newQty;
    }
    updateCartDisplay();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

function clearCart() {
    if (cart.length > 0 && confirm('Voulez-vous vraiment vider le panier ?')) {
        cart = [];
        updateCartDisplay();
    }
}

function saveSale() {
    if (cart.length === 0) {
        alert('⚠️ Le panier est vide');
        return;
    }
    
    let clientId = document.getElementById('clientSelect').value || null;
    let btn = document.querySelector('.btn-success-custom');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    
    fetch('ajax_vente.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ client_id: clientId, items: cart })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Vente enregistrée avec succès !\nTotal : ' + data.total + ' CFA');
            if (data.points_credites > 0) {
                alert('⭐ ' + data.points_credites + ' points de fidélité crédités !');
            }
            // Ouvrir immédiatement la Facture / Reçu Ticket PDF pour impression
            window.open('details_vente.php?id=' + data.id_vente, '_blank');
            cart = [];
            updateCartDisplay();
            location.reload();
        } else {
            alert('❌ Erreur : ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Erreur réseau : ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Valider la vente';
    });
}

function saveClient() {
    let nom = document.getElementById('clientNom').value.trim();
    let prenom = document.getElementById('clientPrenom').value.trim();
    
    if (!nom || !prenom) {
        alert('⚠️ Veuillez remplir le nom et le prénom');
        return;
    }
    
    fetch('ajax_client_details.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            nom: nom,
            prenom: prenom,
            telephone: document.getElementById('clientTel').value.trim(),
            email: document.getElementById('clientEmail').value.trim(),
            adresse: document.getElementById('clientAdresse').value.trim()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Client ajouté avec succès !');
            location.reload();
        } else {
            alert('❌ Erreur : ' + data.message);
        }
    });
}
</script>

</body>
</html>