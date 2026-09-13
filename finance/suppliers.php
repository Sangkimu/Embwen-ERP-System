<?php
require_once "../config.php";
requireLogin();
if(!allowed(['finance','admin'])){http_response_code(403);die("Access denied.");}
$dashboardLink = '../' . user()['home'];

$message = '';
$messageClass = '';

// --- 1. POST ACTION ROUTER PIPELINES ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    
    // Pathway A: Add a New Supplier
    if ($_POST['action'] === 'save_supplier') {
        $companyName   = trim($_POST['company_name'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $category      = $_POST['category'] ?? 'Other';

        if (!empty($companyName) && !empty($phone)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO suppliers (company_name, contact_person, phone, email, category) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$companyName, $contactPerson, $phone, $email, $category]);
                $message = "Vendor entity logged successfully!"; $messageClass = "alert-success";
            } catch (Exception $e) { $message = "Save Error: " . $e->getMessage(); $messageClass = "alert-danger"; }
        }
    }

    // Pathway B: Generate a Brand New Purchase Order (PO) Document
    if ($_POST['action'] === 'create_po') {
        $supplierId  = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
        $itemName    = trim($_POST['item_name'] ?? '');
        $quantity    = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $unitPrice   = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
        $clerkId     = user()['admin_id'] ?? user()['id'] ?? 1;
        $generatedPoNo = "PO-" . date('Y') . "-" . rand(1000, 9999);

        if ($supplierId && !empty($itemName) && $quantity > 0 && $unitPrice > 0) {
            try {
                $pdo->beginTransaction();
                
                // Save parent purchase record tracking entry
                $estCost = $quantity * $unitPrice;
                $stmt1 = $pdo->prepare("INSERT INTO purchase_orders (po_number, supplier_id, order_date, estimated_cost, delivery_status, created_by) VALUES (?, ?, CURDATE(), ?, 'Ordered', ?)");
                $stmt1->execute([$generatedPoNo, $supplierId, $estCost, $clerkId]);
                $poId = $pdo->lastInsertId();

                // Inject child itemization row contents
                $stmt2 = $pdo->prepare("INSERT INTO po_items (po_id, item_name, quantity_ordered, unit_price) VALUES (?, ?, ?, ?)");
                $stmt2->execute([$poId, $itemName, $quantity, $unitPrice]);

                $pdo->commit();
                $message = "Purchase Order document [{$generatedPoNo}] created successfully!"; $messageClass = "alert-success";
            } catch (Exception $e) { $pdo->rollBack(); $message = "PO Generation Error: " . $e->getMessage(); $messageClass = "alert-danger"; }
        } else { $message = "PO Generation failed. Ensure numerical input values are greater than zero."; $messageClass = "alert-danger"; }
    }
}

// --- 2. DATA QUERY GENERATION (Procurement Lifecycle Telemetry) ---
$suppliers = $pdo->query("
    SELECT s.*, 
           COUNT(DISTINCT po.po_id) as total_pos,
           COALESCE(SUM(po.estimated_cost), 0) as aggregate_ordered_value,
           COUNT(CASE WHEN po.delivery_status != 'Fully Received' AND po.po_id IS NOT NULL THEN 1 END) as pending_deliveries
    FROM suppliers s
    LEFT JOIN purchase_orders po ON s.supplier_id = po.supplier_id
    GROUP BY s.supplier_id ORDER BY s.company_name ASC
")->fetchAll();

$purchaseOrders = $pdo->query("
    SELECT po.*, s.company_name, pi.item_name, pi.quantity_ordered
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN po_items pi ON po.po_id = pi.po_id
    ORDER BY po.order_date DESC
")->fetchAll();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Procurement & Logistics Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .procurement-layout { display: grid; grid-template-columns: 1fr; gap: 30px; margin-top: 20px; }
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .btn-group { display: flex; gap: 10px; }
        .btn-primary { background: #1e3d73; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-success { background: #2f855a; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #edf2f7; margin-bottom: 20px; }
        .data-table th, .data-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        .data-table th { background: #f7fafc; color: #4a5568; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-ordered { background: #feebc8; color: #c05621; }
        .status-received { background: #c6f6d5; color: #22543d; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); justify-content: center; align-items: center; z-index: 1000; padding: 15px; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 25px; border-radius: 8px; width: 100%; max-width: 550px; box-sizing: border-box; }
        .form-group { margin-bottom: 14px; display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 4px; font-size: 11px; font-weight: 700; color: #4a5568; text-transform: uppercase; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; box-sizing: border-box; background: #f7fafc; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #c6f6d5; color: #22543d; }
        .alert-danger { background: #fed7d7; color: #742a2a; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar">
            <div class="module-tag">Workspace / <b>Procurement Portal</b></div>
            <div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div><div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div>
        </header>
        
        <section class="content">
            <a href="<?=htmlspecialchars($dashboardLink)?>" style="display:inline-block;margin-bottom:16px;color:#174a9b;font-size:13px;font-weight:700;">← Back to dashboard</a>
            <?php if (!empty($message)): ?>
                <div class="alert <?= $messageClass ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="procurement-layout">
                
                <!-- LAYER 1: PURCHASE ORDER MONITORING LOGS -->
                <div class="card">
                    <div class="action-bar">
                        <div>
                            <h2 style="margin:0; font-size:18px;">Purchase Orders Tracking (PO)</h2>
                            <p class="text-muted" style="font-size:13px; margin:2px 0 0 0;">Manage ongoing supply chains and incoming shipments.</p>
                        </div>
                        <button class="btn-success" onclick="toggleModal('poModal', true)">+ Issue New PO</button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Issued Date</th>
                                <th>Supplier Target</th>
                                <th>Supply Item Details</th>
                                <th>Est Valuation</th>
                                <th>Delivery Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchaseOrders)): ?>
                                <tr><td colspan="6" style="text-align:center; color:#a0aec0; padding:20px;">No active purchase orders compiled.</td></tr>
                            <?php else: ?>
                                <?php foreach($purchaseOrders as $po): ?>
                                    <tr>
                                        <td><strong style="color:#1e3d73; font-family:monospace;"><?= $po['po_number'] ?></strong></td>
                                        <td><?= date('d-M-Y', strtotime($po['order_date'])) ?></td>
                                        <td><b><?= htmlspecialchars($po['company_name']) ?></b></td>
                                        <td><?= htmlspecialchars($po['item_name']) ?> <small style="color:#718096;">(x<?= $po['quantity_ordered'] ?>)</small></td>
                                        <td><strong>KES <?= number_format($po['estimated_cost'], 2) ?></strong></td>
                                        <td><span class="badge status-<?= strtolower($po['delivery_status'] === 'Ordered' ? 'ordered' : 'received') ?>"><?= $po['delivery_status'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- LAYER 2: SUPPLIERS GLOBAL DIRECTORY -->
                <div class="card">
                    <div class="action-bar">
                        <div>
