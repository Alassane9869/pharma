<?php
// includes/header.php
// Définir la langue française
// setlocale(LC_TIME, 'fr_FR.utf8', 'fr_FR', 'fr', 'french');
setlocale(LC_TIME, 'fr_ML.utf8', 'fr_ML', 'fr_FR.utf8', 'fr_FR', 'fr', 'french');

// Déterminer le chemin racine
$root_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1b5e20">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= $page_title ?? 'Pharmacie Souley-Guirou' ?></title>
    <link rel="manifest" href="<?= $root_path ?>manifest.json">
    <link rel="icon" type="image/png" href="<?= $root_path ?>assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= $root_path ?>assets/images/logo.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Souley-Guirou">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if (isset($include_chart) && $include_chart): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <?php endif; ?>
    <style>
        /* ===== RESET & BASE ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
            color: #1a1a2e;
        }
        
        /* ===== SIDEBAR ===== */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 50%, #388e3c 100%);
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-brand {
            padding: 20px 15px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-brand .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .sidebar-brand .logo-container img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.2);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
            margin-bottom: 8px;
        }
        
        .sidebar-brand .logo-container img:hover {
            transform: scale(1.05);
            border-color: rgba(255,255,255,0.4);
        }
        
        .sidebar-brand .logo-container .brand-name {
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.2;
        }
        
        .sidebar-brand .logo-container .brand-name i {
            color: #4caf50;
            margin-right: 4px;
        }
        
        .sidebar-brand .logo-container .brand-sub {
            color: rgba(255,255,255,0.6);
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        
        @keyframes pageFadeIn {
            from {
                opacity: 0.85;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 11px 18px;
            margin: 4px 12px;
            border-radius: 12px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            position: relative;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.18);
            color: #ffffff;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.25);
            color: #ffffff;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }

        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 20%;
            bottom: 20%;
            width: 4px;
            background: #81c784;
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 8px rgba(129, 199, 132, 0.8);
        }
        
        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
            font-size: 15px;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar .nav-link:hover i {
            transform: scale(1.15);
        }
        
        .sidebar .nav-link .badge {
            margin-left: auto;
            background: #ff6b6b;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 12px 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-footer .user-info {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            margin-bottom: 8px;
        }
        
        .sidebar-footer .btn-logout {
            background: rgba(255,255,255,0.1);
            border: none;
            color: rgba(255,255,255,0.8);
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .sidebar-footer .btn-logout:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 250px;
            padding: 15px 25px;
            min-height: 100vh;
            animation: pageFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        
        /* ===== PAGE HEADER ===== */
        .page-header {
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .page-header h4 {
            margin: 0;
            color: #1b5e20;
            font-weight: 600;
            font-size: 18px;
        }
        
        .page-header h4 i {
            color: #4caf50;
        }
        
        .page-header .date-info {
            color: #888;
            font-size: 13px;
        }
        
        /* ===== STAT CARDS ===== */
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 14px 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
            border-left: 4px solid #4caf50;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card .stat-icon {
            position: absolute;
            right: 12px;
            top: 12px;
            font-size: 28px;
            opacity: 0.12;
        }
        
        .stat-card .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1b5e20;
            margin-bottom: 1px;
        }
        
        .stat-card .stat-label {
            color: #888;
            font-size: 12px;
        }
        
        .stat-card .stat-change {
            font-size: 11px;
            font-weight: 600;
            margin-top: 3px;
        }
        
        .stat-card .stat-change.positive { color: #28a745; }
        .stat-card .stat-change.negative { color: #dc3545; }
        .stat-card .stat-change.warning { color: #f39c12; }
        
        .stat-card.border-blue { border-left-color: #2196f3; }
        .stat-card.border-blue .stat-number { color: #0d47a1; }
        .stat-card.border-orange { border-left-color: #ff9800; }
        .stat-card.border-orange .stat-number { color: #e65100; }
        .stat-card.border-red { border-left-color: #f44336; }
        .stat-card.border-red .stat-number { color: #b71c1c; }
        .stat-card.border-purple { border-left-color: #9c27b0; }
        .stat-card.border-purple .stat-number { color: #6a1b9a; }
        .stat-card.border-teal { border-left-color: #009688; }
        .stat-card.border-teal .stat-number { color: #004d40; }
        .stat-card.border-info { border-left-color: #2196f3; }
        .stat-card.border-warning { border-left-color: #ff9800; }
        
        /* ===== WIDGETS ===== */
        .widget {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #eef0f5;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            overflow: hidden;
            margin-bottom: 12px;
        }

        .widget:last-child {
            margin-bottom: 0;
        }

        .widget-header {
            padding: 10px 16px;
            border-bottom: 1px solid #f0f2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafbfc;
        }

        .widget-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 13px;
            color: #2d2d44;
        }

        .widget-header h5 i {
            color: #4caf50;
            margin-right: 6px;
            font-size: 13px;
        }

        .widget-body {
            padding: 10px 14px;
        }

        /* ===== CHART ===== */
        .chart-container {
            position: relative;
            height: 140px;
        }
        
        /* ===== TABLEAU MÉDICAMENTS ===== */
        .table-medicaments {
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .table-medicaments thead th {
            background: #e8f5e9;
            color: #1b5e20;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #4caf50;
            padding: 10px 8px;
            vertical-align: middle;
        }
        
        .table-medicaments tbody td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table-medicaments tbody tr:hover {
            background-color: #f8fff8;
        }
        
        .table-medicaments .product-name {
            font-weight: 600;
            color: #1b5e20;
            font-size: 14px;
        }
        
        /* ===== BOUTONS D'ACTION GÉNÉRIQUES ===== */

 .btn-action {
            width: 30px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
            border: none;
        }
        
        .btn-action:hover {
            transform: scale(1.1);
        }
        
        .btn-action.btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-action.btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-action.btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-action.btn-danger:hover {
            background: #b02a37;
        }
        
        .btn-action.btn-info {
            background: #0dcaf0;
            color: white;
        }
        
        .btn-action.btn-info:hover {
            background: #0bb5d9;
        }
        
        /* ===== VENTES RÉCENTES ===== */
        .sale-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .sale-item:last-child {
            border-bottom: none;
        }
        
        .sale-item .sale-info .sale-client {
            font-weight: 500;
            font-size: 13px;
        }
        
        .sale-item .sale-info .sale-time {
            font-size: 11px;
            color: #999;
        }
        
        .sale-item .sale-amount {
            font-weight: 700;
            color: #2e7d32;
            font-size: 14px;
        }
        
        /* ===== STOCK FAIBLE ===== */
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-item .product-name {
            font-weight: 500;
            font-size: 13px;
        }
        
        .product-item .product-stock {
            font-weight: 600;
            padding: 1px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .product-item .product-stock.critical { background: #ffebee; color: #c62828; }
        .product-item .product-stock.warning { background: #fff3e0; color: #e65100; }
        .product-item .product-stock.ok { background: #e8f5e9; color: #2e7d32; }
        
        /* ===== QUICK ACTIONS ===== */

.quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 8px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            border: 2px solid transparent;
            height: 100%;
        }
        
        .quick-action-btn:hover {
            border-color: #4caf50;
            background: #f0fff0;
            transform: translateY(-2px);
            text-decoration: none;
            color: #1b5e20;
        }
        
        .quick-action-btn i {
            font-size: 22px;
            color: #4caf50;
            margin-bottom: 4px;
        }
        
        .quick-action-btn span {
            font-size: 11px;
            font-weight: 500;
            text-align: center;
        }

        
        /* ===== RANK BADGE ===== */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 11px;
        }
        .rank-badge.gold { background: #ffd700; color: #7c6a00; }
        .rank-badge.silver { background: #c0c0c0; color: #5a5a5a; }
        .rank-badge.bronze { background: #cd7f32; color: white; }
        .rank-badge.default { background: #e9ecef; color: #6c757d; }
        
        /* ===== SCROLLBAR ===== */
        .widget-body-scroll {
            max-height: 170px;
            overflow-y: auto;
        }
        
        .widget-body-scroll::-webkit-scrollbar {
            width: 3px;
        }
        .widget-body-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .widget-body-scroll::-webkit-scrollbar-thumb {
            background: #4caf50;
            border-radius: 10px;
        }
        
        /* ================================================================ */
        /* ===== STYLES SPÉCIFIQUES PAGES VENTES, FOURNISSEURS, COMMANDES ===== */
        /* ================================================================ */
        
        /* ----- STATS MINI (page ventes) ----- */
        .stat-mini {
            background: white;
            border-radius: 10px;
            padding: 10px 15px;
            border-left: 3px solid #4caf50;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .stat-mini .number {
            font-size: 20px;
            font-weight: 700;
            color: #1b5e20;
        }
        
        .stat-mini .label {
            font-size: 11px;
            color: #888;
        }
        
        /* ----- CARTE PRODUIT (page ventes) ----- */
        .product-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 8px;
            transition: all 0.3s;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-card:hover {
            border-color: #4caf50;
            background: #f8fff8;
            transform: translateX(5px);
        }
        
        .product-card .product-name {
            font-weight: 600;
            color: #1b5e20;
        }
        
        .product-card .product-price {
            font-weight: 700;
            color: #2e7d32;
        }
        
        .product-card .product-stock {
            font-size: 12px;
            color: #888;
        }
        
        /* ----- CLIENT BADGE (page ventes) ----- */
        .client-badge {
            background: #e3f2fd;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            color: #0d47a1;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        /* ----- EMPTY CART (page ventes) ----- */
        .empty-cart {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-cart i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .empty-cart p {
            color: #999;
        }
        
        /* ----- BOUTONS PERSONNALISÉS (page ventes) ----- */
        .btn-success-custom {
            background: linear-gradient(135deg, #2e7d32, #4caf50);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
            color: white;
        }
        
        .btn-danger-custom {
            background: linear-gradient(135deg, #c62828, #dc3545);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        /* ----- CART ITEMS (page ventes) ----- */
        .cart-items {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .cart-items::-webkit-scrollbar {
            width: 5px;
        }
        
        .cart-items::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .cart-items::-webkit-scrollbar-thumb {
            background: #4caf50;
            border-radius: 10px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item .item-info {
            flex: 1;
        }
        
        .cart-item .item-name {
            font-weight: 600;
            font-size: 14px;
            color: #1b5e20;
        }
        
        .cart-item .item-price {
            font-size: 13px;
            color: #666;
        }
        
        .cart-item .item-qty {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cart-item .item-qty button {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid #ddd;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .cart-item .item-qty button:hover {
            background: #4caf50;
            color: white;
            border-color: #4caf50;
        }
        
        .cart-item .item-qty .qty-number {
            font-weight: 700;
            font-size: 16px;
            min-width: 30px;
            text-align: center;
        }
        
        .cart-item .item-total {
            font-weight: 700;
            color: #2e7d32;
            min-width: 80px;
            text-align: right;
        }
        
        .cart-item .item-remove {
            color: #dc3545;
            cursor: pointer;
            padding: 0 5px;
            transition: all 0.2s;
        }
        
        .cart-item .item-remove:hover {
            transform: scale(1.2);
        }
        
        /* ----- TOTAL BOX (page ventes) ----- */
        .total-box {
            background: #e8f5e9;
            border-radius: 10px;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .total-box .total-label {
            font-size: 16px;
            color: #1b5e20;
            font-weight: 600;
        }
        
        .total-box .total-amount {
            font-size: 28px;
            font-weight: 700;
            color: #2e7d32;
        }
        
        /* ----- SEARCH CONTAINER (page ventes) ----- */
        .search-container {
            position: relative;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 10px 10px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .search-results::-webkit-scrollbar {
            width: 5px;
        }
        
        .search-results::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .search-results::-webkit-scrollbar-thumb {
            background: #4caf50;
            border-radius: 10px;
        }
        
        .search-result-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s;
        }
        
        .search-result-item:hover {
            background: #f8fff8;
        }
        
        .search-result-item .result-name {
            font-weight: 600;
            color: #1b5e20;
        }
        
        .search-result-item .result-detail {
            font-size: 12px;
            color: #888;
        }
        
        /* ----- FOURNISSEURS ----- */
        .fournisseur-card {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .fournisseur-card:hover {
            border-color: #4caf50;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.15);
            transform: translateX(5px);
        }
        
        .fournisseur-card .fournisseur-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff9800, #f57c00);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .fournisseur-card .fournisseur-info {
            flex: 1;
            min-width: 150px;
        }
        
        .fournisseur-card .fournisseur-info .name {
            font-weight: 600;
            color: #1b5e20;
            font-size: 16px;
        }
        
        .fournisseur-card .fournisseur-info .details {
            font-size: 13px;
            color: #666;
        }
        
        .fournisseur-card .fournisseur-info .details i {
            width: 16px;
            color: #4caf50;
        }
        
        .fournisseur-card .fournisseur-stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .fournisseur-card .fournisseur-stats .stat-item {
            text-align: center;
        }
        
        .fournisseur-card .fournisseur-stats .stat-item .number {
            font-weight: 700;
            font-size: 16px;
            color: #1b5e20;
        }
        
        .fournisseur-card .fournisseur-stats .stat-item .label {
            font-size: 10px;
            color: #888;
        }
        
        .fournisseur-card .fournisseur-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        /* ----- BOUTONS ACTION FOURNISSEURS ----- */
        .btn-action-fournisseur {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
            border: none;
            font-size: 13px;
            cursor: pointer;
        }
        
        .btn-action-fournisseur:hover {
            transform: scale(1.1);
        }
        
        .btn-action-fournisseur.btn-edit {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-action-fournisseur.btn-edit:hover {
            background: #e0a800;
        }
        
        .btn-action-fournisseur.btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-action-fournisseur.btn-delete:hover {
            background: #b02a37;
        }
        
        .btn-action-fournisseur.btn-order {
            background: #28a745;
            color: white;
        }
        
        .btn-action-fournisseur.btn-order:hover {
            background: #1e7e34;
        }
        
        .btn-action-fournisseur.btn-view {
            background: #0dcaf0;
            color: white;
        }
        
        .btn-action-fournisseur.btn-view:hover {
            background: #0bb5d9;
        }
        
        /* ----- TOP ITEMS (fournisseurs) ----- */
        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .top-item:last-child {
            border-bottom: none;
        }
        
        .top-item .rank {
            font-weight: 700;
            color: #4caf50;
            margin-right: 10px;
        }
        
        .top-item .count {
            font-weight: 600;
            color: #ff9800;
        }
        
        /* ----- FOURNISSEUR LIST SCROLL ----- */
        .fournisseur-list::-webkit-scrollbar {
            width: 5px;
        }
        
        .fournisseur-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .fournisseur-list::-webkit-scrollbar-thumb {
            background: #4caf50;
            border-radius: 10px;
        }
        
        /* ----- FOURNISSEUR INFO BOX (page commande) ----- */
        .fournisseur-info-box {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 10px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .fournisseur-info-box .icon {
            font-size: 32px;
            color: #2e7d32;
        }
        
        .fournisseur-info-box .name {
            font-size: 20px;
            font-weight: 700;
            color: #1b5e20;
        }
        
        .fournisseur-info-box .detail {
            font-size: 13px;
            color: #555;
        }
        
        .fournisseur-info-box .detail i {
            color: #4caf50;
            width: 18px;
        }
        
        /* ----- TABLE PRODUITS (page commande) ----- */
        .table-produits {
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .table-produits thead th {
            background: #e8f5e9;
            color: #1b5e20;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #4caf50;
            padding: 10px 8px;
            vertical-align: middle;
        }
        
        .table-produits tbody td {
            padding: 8px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table-produits tbody tr:hover {
            background-color: #f8fff8;
        }
        
        .table-produits .product-name {
            font-weight: 600;
            color: #1b5e20;
        }
        
        /* ----- INPUTS COMMANDE ----- */
        .qty-input {
            width: 70px;
            text-align: center;
            font-weight: 600;
        }
        
        .qty-input:focus {
            border-color: #4caf50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
        
        .price-input {
            width: 100px;
            text-align: right;
            font-weight: 600;
        }
        
        .price-input:focus {
            border-color: #4caf50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
        
        .fournisseur-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .fournisseur-select:focus {
            border-color: #4caf50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
        
        /* ----- ALERTE SUCCÈS PERSONNALISÉE ----- */
        .alert-success-custom {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .alert-success-custom i {
            font-size: 48px;
            color: #2e7d32;
        }
        
        .alert-success-custom .title {
            font-size: 24px;
            font-weight: 700;
            color: #1b5e20;
        }
        
        /* ================================================================ */
        /* ===== RESPONSIVE ===== */
        /* ================================================================ */
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: block !important;
            }
        }
        
        .sidebar-toggle {
            display: none;
            background: #2e7d32;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 10px 12px;
            }
            .page-header {
                padding: 10px 14px;
            }
            .page-header h4 {
                font-size: 15px;
            }
            .stat-card .stat-number {
                font-size: 18px;
            }
            .stat-card .stat-icon {
                font-size: 20px;
            }
            .stat-card {
                padding: 10px 14px;
            }
            .chart-container {
                height: 120px;
            }
            
            .sidebar-brand .logo-container img {
                width: 60px;
                height: 60px;
            }
            
            .sidebar-brand .logo-container .brand-name {
                font-size: 15px;
            }
            
            .table-medicaments {
                font-size: 12px;
            }
            
            .table-medicaments thead th {
                font-size: 10px;
                padding: 6px 4px;
            }
            
            .table-medicaments tbody td {
                padding: 6px 4px;
            }
            
            .table-medicaments .product-name {
                font-size: 12px;
            }
            
            .btn-action {
                width: 25px;
                height: 25px;
                font-size: 10px;
            }
            
            /* Fournisseurs responsive */
            .fournisseur-card {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .fournisseur-card .fournisseur-icon {
                margin: 0 auto;
            }
            
            .fournisseur-card .fournisseur-stats {
                justify-content: center;
            }
            
            .fournisseur-card .fournisseur-actions {
                justify-content: center;
            }
            
            .fournisseur-info-box {
                flex-direction: column;
                text-align: center;
                padding: 12px;
            }
            
            .fournisseur-info-box .name {
                font-size: 17px;
            }
            
            .fournisseur-info-box .icon {
                font-size: 28px;
            }
            
            /* Ventes responsive */
            .total-box {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
            
            .total-box .total-amount {
                font-size: 22px;
            }
            
            .cart-item {
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .cart-item .item-qty {
                margin: 5px 0;
            }
            
            .stat-mini .number {
                font-size: 16px;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar-brand .logo-container img {
                width: 50px;
                height: 50px;
            }
            
            .sidebar-brand .logo-container .brand-name {
                font-size: 14px;
            }
        }
        
        /* ===== ANIMATION ===== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animated { animation: fadeInUp 0.4s ease; }
        
        /* ===== MARGES RÉDUITES ===== */
        .g-3 {
            --bs-gutter-y: 0.75rem;
            --bs-gutter-x: 0.75rem;
        }
        
        .mb-4 {
            margin-bottom: 1rem !important;
        }
        
        .g-4 {
            --bs-gutter-y: 0.75rem;
            --bs-gutter-x: 0.75rem;
        }
        
        .mt-4 {
            margin-top: 1rem !important;
        }
        
        /* ===== BADGES ===== */
        .badge-sm {
            font-size: 10px;
            padding: 2px 8px;
        }
        
        /* ===== FILTRES ===== */
        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        /* ===== FOOTER ===== */
        .footer-bar {
            text-align: center;
            padding: 10px 0 4px;
            font-size: 11px;
            color: #aab0c8;
            border-top: 1px solid #eef0f5;
            margin-top: 14px;
        }






                /* ============================================ */
        /* ===== STYLES SPÉCIFIQUES - CLIENTS ===== */
        /* ============================================ */

        /* Carte client */
        .client-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .client-card:hover {
            border-color: #4caf50;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.15);
            transform: translateX(5px);
        }

        .client-card .client-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .client-card .client-info {
            flex: 1;
            min-width: 150px;
        }

        .client-card .client-info .name {
            font-weight: 600;
            color: #1b5e20;
            font-size: 16px;
        }

        .client-card .client-info .details {
            font-size: 13px;
            color: #666;
        }

        .client-card .client-info .details i {
            width: 16px;
            color: #4caf50;
        }

        .client-card .client-points {
            text-align: center;
            padding: 5px 15px;
            background: #fff3cd;
            border-radius: 20px;
            min-width: 80px;
        }

        .client-card .client-points .points-number {
            font-weight: 700;
            font-size: 18px;
            color: #ff9800;
        }

        .client-card .client-points .points-label {
            font-size: 10px;
            color: #856404;
        }

        .client-card .client-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        /* Boutons action clients */
        .btn-action-client {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
            border: none;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-action-client:hover {
            transform: scale(1.1);
        }

        .btn-action-client.btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-action-client.btn-edit:hover {
            background: #e0a800;
        }

        .btn-action-client.btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-action-client.btn-delete:hover {
            background: #b02a37;
        }

        .btn-action-client.btn-view {
            background: #0dcaf0;
            color: white;
        }

        .btn-action-client.btn-view:hover {
            background: #0bb5d9;
        }

        .btn-action-client.btn-sale {
            background: #28a745;
            color: white;
        }

        .btn-action-client.btn-sale:hover {
            background: #1e7e34;
        }

        /* Badge niveau client */
        .badge-level {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-level.bg-warning {
            background: #ffc107;
            color: #212529;
        }

        .badge-level.bg-info {
            background: #0dcaf0;
            color: white;
        }

        .badge-level.bg-secondary {
            background: #6c757d;
            color: white;
        }

        .badge-level.bg-light {
            background: #f8f9fa;
            color: #212529;
        }

        /* Top clients */
        .top-client-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .top-client-item:last-child {
            border-bottom: none;
        }

        .top-client-item .rank {
            font-weight: 700;
            color: #4caf50;
            margin-right: 10px;
        }

        .top-client-item .points {
            font-weight: 600;
            color: #ff9800;
        }

        /* Client list scroll */
        .client-list::-webkit-scrollbar {
            width: 5px;
        }

        .client-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .client-list::-webkit-scrollbar-thumb {
            background: #4caf50;
            border-radius: 10px;
        }

        /* ===== ULTRA-RESPONSIVE MOBILE & TABLET (Android & iOS) ===== */
        * {
            -webkit-tap-highlight-color: transparent;
        }

        /* SUR DESKTOP / PC (Écrans >= 992px) -> Cacher TOUTES les barres mobiles */
        @media (min-width: 992px) {
            .mobile-navbar, 
            .mobile-bottom-nav, 
            .sidebar-backdrop {
                display: none !important;
            }
        }

        /* SUR MOBILE & TABLETTE (Écrans < 992px) -> Afficher les barres mobiles */
        @media (max-width: 991px) {
            /* Mobile Topbar fixed (Ultra-Pro Glassmorphism) */
            .mobile-navbar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 64px !important;
                background: linear-gradient(135deg, rgba(27, 94, 32, 0.95) 0%, rgba(46, 125, 50, 0.95) 100%) !important;
                backdrop-filter: blur(12px) !important;
                -webkit-backdrop-filter: blur(12px) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 0 16px !important;
                z-index: 1040 !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
            }

            .mobile-navbar .btn-mobile-toggle {
                background: rgba(255, 255, 255, 0.15) !important;
                border: 1px solid rgba(255, 255, 255, 0.25) !important;
                color: #ffffff !important;
                width: 42px !important;
                height: 42px !important;
                border-radius: 12px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 19px !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            }

            .mobile-navbar .btn-mobile-toggle:active {
                transform: scale(0.92) !important;
                background: rgba(255, 255, 255, 0.3) !important;
            }

            .mobile-navbar .mobile-brand {
                font-weight: 700 !important;
                font-size: 16px !important;
                color: #ffffff !important;
                letter-spacing: 0.3px !important;
                display: flex !important;
                align-items: center !important;
            }

            .mobile-navbar .mobile-brand img {
                height: 34px !important;
                width: 34px !important;
                border-radius: 9px !important;
                margin-right: 10px !important;
                object-fit: cover !important;
                border: 2px solid rgba(255, 255, 255, 0.35) !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
            }

            .mobile-navbar .mobile-user {
                width: 38px !important;
                height: 38px !important;
                border-radius: 50% !important;
                background: rgba(255, 255, 255, 0.2) !important;
                border: 1.5px solid rgba(255, 255, 255, 0.4) !important;
                color: #ffffff !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-weight: 700 !important;
                font-size: 15px !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
            }

            /* Sidebar Backdrop (Net sans flou agressif) */
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.4);
                z-index: 1040;
                display: none;
                opacity: 0;
                transition: opacity 0.25s ease;
            }

            .sidebar-backdrop.show {
                display: block;
                opacity: 1;
            }

            /* BARRE DE NAVIGATION INFÉRIEURE MOBILE (PRO BOTTOM TAB BAR) */
            .mobile-bottom-nav {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 62px !important;
                background: #ffffff !important;
                border-top: 1px solid #e2e8f0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-around !important;
                z-index: 1030 !important;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08) !important;
                padding-bottom: env(safe-area-inset-bottom, 0px) !important;
            }

            .mobile-bottom-nav .bottom-nav-item {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: #64748b;
                text-decoration: none;
                font-size: 10px;
                font-weight: 600;
                padding: 5px 0;
                transition: all 0.2s ease;
            }

            .mobile-bottom-nav .bottom-nav-item i {
                font-size: 18px;
                margin-bottom: 2px;
                transition: transform 0.2s ease, color 0.2s ease;
            }

            .mobile-bottom-nav .bottom-nav-item.active {
                color: #1b5e20;
                font-weight: 700;
            }

            .mobile-bottom-nav .bottom-nav-item.active i {
                transform: translateY(-2px);
                color: #2e7d32;
            }

            .mobile-bottom-nav .bottom-nav-item.highlight-btn {
                position: relative;
                top: -12px;
            }

            .mobile-bottom-nav .bottom-nav-item.highlight-btn .icon-box {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: linear-gradient(135deg, #1b5e20 0%, #388e3c 100%);
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 6px 16px rgba(27, 94, 32, 0.35);
                border: 3px solid #ffffff;
                transition: transform 0.2s ease;
            }

            .mobile-bottom-nav .bottom-nav-item.highlight-btn:active .icon-box {
                transform: scale(0.92);
            }

            .mobile-bottom-nav .bottom-nav-item.highlight-btn i {
                font-size: 20px;
                margin: 0;
                color: #ffffff;
            }

            .mobile-bottom-nav .bottom-nav-item.highlight-btn span {
                font-size: 10px;
                margin-top: 2px;
                color: #1b5e20;
                font-weight: 700;
            }
        }

        /* Drawer Sidebar (Tablets & Mobile < 992px) */
        @media (max-width: 991px) {
            .sidebar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                height: 100vh !important;
                width: 270px !important;
                transform: translateX(-100%) !important;
                z-index: 1050 !important;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                box-shadow: 5px 0 25px rgba(0,0,0,0.2) !important;
            }
            .sidebar.show {
                transform: translateX(0) !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding-top: 75px !important;
                padding-bottom: 85px !important;
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .page-header {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
                text-align: center;
                padding: 15px !important;
            }
            .page-header > div {
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
                flex-wrap: wrap !important;
                gap: 8px !important;
            }
            .sidebar-toggle {
                display: none !important;
            }
            .stat-card {
                margin-bottom: 10px;
            }
            .widget-header {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                text-align: center;
            }
            .widget-header > div {
                display: flex;
                justify-content: center;
                gap: 8px;
            }
        }

        /* Phones (< 576px - iPhone & Android) */
        @media (max-width: 576px) {
            /* Fix Auto-Zoom iOS Safari sur les inputs */
            input[type="text"], input[type="number"], input[type="email"], input[type="password"], select, textarea {
                font-size: 16px !important;
            }
            .stat-card {
                padding: 10px 12px;
            }
            .stat-card .stat-number {
                font-size: 18px;
            }
            .stat-card .stat-label {
                font-size: 10px;
            }
            .table-responsive {
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
                margin-bottom: 10px;
            }
            /* Adaptation du Panier & POS sur mobile (pages/ventes.php) */
            .cart-items {
                max-height: 220px;
            }
            .total-box {
                flex-direction: column;
                text-align: center;
                gap: 6px;
            }
            .total-box .total-amount {
                font-size: 22px;
            }
            /* Cartes clients (pages/clients.php) */
            .client-card {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            .client-card .client-avatar, .client-card .client-points {
                margin: 0 auto 8px auto;
            }
            .client-card .client-actions {
                justify-content: center;
                flex-wrap: wrap;
            }
            /* Modals sur écran mobile */
            .modal-dialog {
                margin: 8px;
                max-width: calc(100% - 16px);
            }
            .modal-body {
                padding: 15px;
            }
            .btn {
                min-height: 42px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* ===== BANNIÈRE D'INSTALLATION PWA ===== */
        .pwa-install-banner {
            position: fixed;
            bottom: 75px;
            right: 20px;
            z-index: 1090;
            background: #ffffff;
            border-left: 5px solid #1b5e20;
            border-radius: 14px;
            padding: 12px 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            max-width: 450px;
            width: calc(100% - 30px);
            animation: pwaSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes pwaSlideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pwa-install-banner .pwa-logo {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #eef0f5;
        }

        .pwa-install-banner .title {
            font-size: 13px;
            color: #1b5e20;
            line-height: 1.2;
        }

        .pwa-install-banner .subtitle {
            font-size: 11px;
            color: #64748b;
        }

        @media (max-width: 576px) {
            .pwa-install-banner {
                bottom: 70px;
                left: 15px;
                right: 15px;
                width: calc(100% - 30px);
            }
        }
    </style>
</head>
<body>

<!-- ===== BARRE DE NAVIGATION MOBILE (Android & iOS) ===== -->
<div class="mobile-navbar d-lg-none">
    <button class="btn-mobile-toggle" onclick="toggleSidebarMobile()" aria-label="Menu">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-brand d-flex align-items-center">
        <img src="<?= $root_path ?>assets/images/logo.png" alt="Logo" style="height: 30px; width: 30px; border-radius: 8px; margin-right: 8px; object-fit: cover;">
        <span>Souley-Guirou</span>
    </div>
    <div class="mobile-user" title="<?= htmlspecialchars($_SESSION['user_name'] ?? 'U') ?>">
        <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
    </div>
</div>

<!-- ===== OVERLAY BACKDROP MOBILE (Transparent sans flou) ===== -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebarMobile()"></div>

<!-- ===== BARRE DE NAVIGATION INFÉRIEURE MOBILE (PRO BOTTOM TAB BAR) ===== -->
<div class="mobile-bottom-nav d-lg-none">
    <?php $current_script = basename($_SERVER['PHP_SELF']); ?>
    <a href="<?= $root_path ?>index.php" class="bottom-nav-item <?= ($current_script == 'index.php') ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Accueil</span>
    </a>
    <a href="<?= $root_path ?>pages/medicaments.php" class="bottom-nav-item <?= ($current_script == 'medicaments.php') ? 'active' : '' ?>">
        <i class="fas fa-pills"></i>
        <span>Stocks</span>
    </a>
    <a href="<?= $root_path ?>pages/ventes.php" class="bottom-nav-item highlight-btn <?= ($current_script == 'ventes.php') ? 'active' : '' ?>">
        <div class="icon-box"><i class="fas fa-shopping-cart"></i></div>
        <span>Caisse POS</span>
    </a>
    <a href="<?= $root_path ?>pages/factures.php" class="bottom-nav-item <?= ($current_script == 'factures.php') ? 'active' : '' ?>">
        <i class="fas fa-receipt"></i>
        <span>Factures</span>
    </a>
    <a href="javascript:void(0)" onclick="toggleSidebarMobile()" class="bottom-nav-item">
        <i class="fas fa-bars"></i>
        <span>Plus</span>
    </a>
</div>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="<?= $root_path ?>index.php" class="logo-container">
            <img src="<?= $root_path ?>assets/images/logo.png" alt="Pharmacie Souley-Guirou">
            <div class="brand-name"><i class="fas fa-heartbeat"></i>Pharmacie Souley-Guirou</div>
            <div class="brand-sub">Système de Gestion Intégré</div>
        </a>
    </div>
    
    <nav class="nav flex-column mt-2">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        $pages = [
            ['url' => $root_path . 'index.php', 'icon' => 'fa-home', 'label' => 'Accueil'],
            ['url' => $root_path . 'dashboard.php', 'icon' => 'fa-chart-pie', 'label' => 'Dashboard'],
            ['url' => $root_path . 'pages/medicaments.php', 'icon' => 'fa-pills', 'label' => 'Médicaments'],
            ['url' => $root_path . 'pages/clients.php', 'icon' => 'fa-users', 'label' => 'Clients'],
            ['url' => $root_path . 'pages/ventes.php', 'icon' => 'fa-shopping-cart', 'label' => 'Ventes'],
            ['url' => $root_path . 'pages/fournisseurs.php', 'icon' => 'fa-truck', 'label' => 'Fournisseurs'],
            ['url' => $root_path . 'pages/commande_fournisseur.php', 'icon' => 'fa-file-invoice', 'label' => 'Commandes'],
            ['url' => $root_path . 'pages/factures.php', 'icon' => 'fa-receipt', 'label' => 'Factures'],
        ];
        
        foreach ($pages as $page): 
            $is_active = ($current_page == basename($page['url']));
        ?>
            <a href="<?= $page['url'] ?>" class="nav-link <?= $is_active ? 'active' : '' ?>">
                <i class="fas <?= $page['icon'] ?>"></i> <?= $page['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <i class="fas fa-user-circle"></i> <?= $_SESSION['user_name'] ?? 'Utilisateur' ?>
            <span class="badge bg-light text-dark ms-2"><?= $_SESSION['user_role'] ?? 'Admin' ?></span>
        </div>
        <a href="<?= $root_path ?>logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>

<!-- ===== BANNIÈRE D'INSTALLATION PWA NATIVE ===== -->
<div id="pwaInstallBanner" class="pwa-install-banner" style="display: none;">
    <div class="d-flex align-items-center gap-3">
        <img src="<?= $root_path ?>assets/images/logo.png" alt="App Logo" class="pwa-logo">
        <div>
            <div class="fw-bold title">Pharmacie Souley-Guirou</div>
            <div class="subtitle">Installer l'application sur votre écran d'accueil</div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
        <button id="pwaInstallBtn" class="btn btn-success btn-sm fw-bold shadow-sm">
            <i class="fas fa-download me-1"></i> Installer
        </button>
        <button onclick="dismissPwaBanner()" class="btn btn-outline-secondary btn-sm border-0">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
function toggleSidebarMobile() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar && backdrop) {
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
    }
}

// ===== ENREGISTREMENT SERVICE WORKER PWA =====
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= $root_path ?>sw.js')
            .then(reg => console.log('✅ PWA Service Worker actif sur :', reg.scope))
            .catch(err => console.log('⚠️ Erreur Service Worker :', err));
    });
}

// ===== GESTION DE LA BANNIÈRE D'INSTALLATION PWA =====
let deferredPwaPrompt;
const pwaBanner = document.getElementById('pwaInstallBanner');
const pwaBtn = document.getElementById('pwaInstallBtn');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPwaPrompt = e;
    if (pwaBanner && !localStorage.getItem('pwa_dismissed')) {
        pwaBanner.style.display = 'flex';
    }
});

if (pwaBtn) {
    pwaBtn.addEventListener('click', async () => {
        if (!deferredPwaPrompt) return;
        deferredPwaPrompt.prompt();
        const { outcome } = await deferredPwaPrompt.userChoice;
        console.log('Résultat installation PWA :', outcome);
        deferredPwaPrompt = null;
        if (pwaBanner) pwaBanner.style.display = 'none';
    });
}

function dismissPwaBanner() {
    if (pwaBanner) pwaBanner.style.display = 'none';
    localStorage.setItem('pwa_dismissed', 'true');
}
</script>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">