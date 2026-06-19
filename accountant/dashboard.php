<?php
require_once '../config/database.php';
requireRole('accountant');

$userId = getCurrentUserId();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

$today = date('Y-m-d');
$monthStart = date('Y-m-01');

// Handle expense entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_expense') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $expenseType = sanitize($_POST['expense_type']);
        $description = sanitize($_POST['description'] ?? '');
        $amount = (float)$_POST['amount'];
        
        try {
            $insertStmt = $pdo->prepare("INSERT INTO expenses (expense_type, description, amount, expense_date, created_by) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([$expenseType, $description, $amount, $today, $userId]);
            $success = 'Expense recorded successfully';
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'add_expense', "Added expense: $expenseType - $amount", $_SERVER['REMOTE_ADDR']]);
        } catch (PDOException $e) {
            $error = 'Error recording expense';
        }
    }
}

// Handle office cost entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_office_cost') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $costType = sanitize($_POST['cost_type']);
        $description = sanitize($_POST['description'] ?? '');
        $amount = (float)$_POST['amount'];
        $recipientId = !empty($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;
        
        try {
            $insertStmt = $pdo->prepare("INSERT INTO office_costs (cost_type, description, amount, recipient_id, payment_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->execute([$costType, $description, $amount, $recipientId, $today, $userId]);
            $success = 'Office cost recorded successfully';
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'add_office_cost', "Added office cost: $costType - $amount", $_SERVER['REMOTE_ADDR']]);
        } catch (PDOException $e) {
            $error = 'Error recording office cost';
        }
    }
}

// Handle profile picture upload with compression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_picture') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        if (!empty($_FILES['profile_image']['name'])) {
            $uploadDir = '../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExt = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                // Compress and resize image
                $maxFileSize = 1024 * 1024; // 1MB
                
                if ($_FILES['profile_image']['size'] > $maxFileSize) {
                    // Need to compress
                    $newFileName = uniqid('profile_') . '.jpg';
                    
                    if ($fileExt === 'jpeg' || $fileExt === 'jpg') {
                        $image = imagecreatefromjpeg($_FILES['profile_image']['tmp_name']);
                    } elseif ($fileExt === 'png') {
                        $image = imagecreatefrompng($_FILES['profile_image']['tmp_name']);
                    } else {
                        $image = imagecreatefromgif($_FILES['profile_image']['tmp_name']);
                    }
                    
                    // Resize to max 800x800
                    $width = imagesx($image);
                    $height = imagesy($image);
                    $maxDim = 800;
                    
                    if ($width > $maxDim || $height > $maxDim) {
                        $ratio = $width / $height;
                        if ($width > $height) {
                            $newWidth = $maxDim;
                            $newHeight = $maxDim / $ratio;
                        } else {
                            $newHeight = $maxDim;
                            $newWidth = $maxDim * $ratio;
                        }
                        
                        $resized = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                        imagedestroy($image);
                        $image = $resized;
                    }
                    
                    // Save as JPEG with 80% quality
                    imagejpeg($image, $uploadDir . $newFileName, 80);
                    imagedestroy($image);
                    
                    // Update database
                    $updateStmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $updateStmt->execute([$uploadDir . $newFileName, $userId]);
                    
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $currentUser = $stmt->fetch();
                    
                    $success = 'Profile picture uploaded successfully';
                } else {
                    $newFileName = uniqid('profile_') . '.' . $fileExt;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $newFileName)) {
                        $updateStmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        $updateStmt->execute([$uploadDir . $newFileName, $userId]);
                        
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $currentUser = $stmt->fetch();
                        
                        $success = 'Profile picture uploaded successfully';
                    }
                }
            } else {
                $error = 'Invalid file format. Please upload JPG, PNG or GIF.';
            }
        }
    }
}

// Get financial data
$todaySales = $pdo->prepare("SELECT SUM(total_sales) as total, SUM(cash_sales) as cash, SUM(bank_sales) as bank, SUM(mobile_sales) as mobile, SUM(litres_sold) as litres FROM sales WHERE sale_date = ?");
$todaySales->execute([$today]);
$todayData = $todaySales->fetch();

$monthSales = $pdo->prepare("SELECT SUM(total_sales) as total, SUM(litres_sold) as litres FROM sales WHERE sale_date >= ?");
$monthSales->execute([$monthStart]);
$monthData = $monthSales->fetch();

$todayExpenses = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date = ?");
$todayExpenses->execute([$today]);
$todayExpenseData = $todayExpenses->fetch();

$monthExpenses = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date >= ?");
$monthExpenses->execute([$monthStart]);
$monthExpenseData = $monthExpenses->fetch();

$todayOfficeCosts = $pdo->prepare("SELECT SUM(amount) as total FROM office_costs WHERE payment_date = ?");
$todayOfficeCosts->execute([$today]);
$todayOfficeCostData = $todayOfficeCosts->fetch();

$monthOfficeCosts = $pdo->prepare("SELECT SUM(amount) as total FROM office_costs WHERE payment_date >= ?");
$monthOfficeCosts->execute([$monthStart]);
$monthOfficeCostData = $monthOfficeCosts->fetch();

// Get recent transactions
$recentSales = $pdo->query("
    SELECT s.*, p.pump_number, u.full_name as attendant_name
    FROM sales s
    JOIN pumps p ON s.pump_id = p.id
    JOIN users u ON s.attendant_id = u.id
    ORDER BY s.created_at DESC
    LIMIT 20
")->fetchAll();

$recentExpenses = $pdo->query("
    SELECT * FROM expenses ORDER BY expense_date DESC LIMIT 20
")->fetchAll();

// Get all employees for payment
$employees = $pdo->query("SELECT id, full_name, role FROM users WHERE role IN ('manager', 'accountant', 'pump_attendant', 'security') AND is_active = 1")->fetchAll();

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard - Petrol Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root { --primary: #e31837; --secondary: #2c3e50; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; }
        .sidebar { background: linear-gradient(180deg, var(--secondary) 0%, #1a252f 100%); min-height: 100vh; position: fixed; width: 260px; transition: all 0.3s; z-index: 1000; }
        .sidebar.collapsed { margin-left: -260px; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 10px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link i { width: 25px; }
        .main-content { margin-left: 260px; padding: 20px; transition: all 0.3s; }
        .main-content.expanded { margin-left: 0; }
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .table-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .mobile-toggle { display: none; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
        .sidebar-overlay.show { display: block; }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -260px;
                width: 100%;
                max-width: 260px;
            }
            .sidebar.show { margin-left: 0; }
            .sidebar .nav-link { padding: 10px 15px; font-size: 14px; }
            .sidebar .nav-link i { width: 20px; }
            .main-content {
                margin-left: 0;
                padding: 15px 10px;
            }
            .mobile-toggle { display: block !important; }
            .stat-card { padding: 10px 8px !important; margin-bottom: 10px; }
            .stat-card h4 { font-size: 1rem; }
            .stat-card small { font-size: 0.7rem; }
            .table-card { padding: 10px 5px; }
            .table-card h5 { font-size: 1rem; }
            .table { font-size: 0.85rem; }
            .table th, .table td { padding: 0.4rem 0.3rem; }
            .form-label { font-size: 0.9rem; }
            .form-control, .form-select { font-size: 0.9rem; padding: 0.4rem 0.75rem; }
            .btn { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
            .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
            .modal-dialog { margin: 0.5rem; }
            .modal-body { padding: 1rem 0.5rem; }
            .badge { font-size: 0.7rem; padding: 0.25em 0.5em; }
            .user-info { display: none; }
            .row { margin-left: -5px; margin-right: -5px; }
            .row > div { padding-left: 5px; padding-right: 5px; }
        }
        
        @media (max-width: 576px) {
            h4.mb-0 { font-size: 1.1rem; }
            h5 { font-size: 0.95rem; }
            .d-flex.justify-content-between { flex-direction: column; align-items: flex-start !important; }
            .table-responsive { font-size: 0.8rem; }
            .card { border-radius: 10px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="text-center py-4">
            <h4 class="text-white mb-0"><i class="fas fa-gas-pump me-2"></i>Petrol Station</h4>
            <small class="text-white-50">Accountant</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link active" data-bs-toggle="tab"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="nav-item"><a href="#transactions" class="nav-link" data-bs-toggle="tab"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
            <li class="nav-item"><a href="#expenses" class="nav-link" data-bs-toggle="tab"><i class="fas fa-minus-circle"></i> Expenses</a></li>
            <li class="nav-item"><a href="#office-costs" class="nav-link" data-bs-toggle="tab"><i class="fas fa-briefcase"></i> Office Costs</a></li>
            <li class="nav-item"><a href="#reports" class="nav-link" data-bs-toggle="tab"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li class="nav-item"><a href="#profile" class="nav-link" data-bs-toggle="tab"><i class="fas fa-user-circle"></i> My Profile</a></li>
            <li class="nav-item mt-5"><a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-bars mobile-toggle me-3 text-dark" onclick="toggleSidebar()" style="font-size: 1.3rem; cursor: pointer;"></i>
                <div>
                    <h4 class="mb-0">Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?></h4>
                    <small class="text-muted"><?php echo date('l, F j, Y'); ?></small>
                </div>
            </div>
            <div class="d-flex align-items-center user-info">
                <span class="badge bg-success me-3"><i class="fas fa-circle me-1"></i> Online</span>
                <img src="<?php echo $currentUser['profile_image'] ?? 'https://via.placeholder.com/40'; ?>" class="rounded-circle" width="40" height="40" alt="Profile">
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Dashboard Tab -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="dashboard">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card p-3 text-white bg-success">
                            <small>Today's Income</small>
                            <h4>TSh <?php echo number_format($todayData['total'] ?? 0); ?></h4>
                            <small><?php echo number_format($todayData['litres'] ?? 0); ?> Litres</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-3 text-white bg-primary">
                            <small>Monthly Income</small>
                            <h4>TSh <?php echo number_format($monthData['total'] ?? 0); ?></h4>
                            <small><?php echo number_format($monthData['litres'] ?? 0); ?> Litres</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-3 text-white bg-danger">
                            <small>Today's Expenses</small>
                            <h4>TSh <?php echo number_format(($todayExpenseData['total'] ?? 0) + ($todayOfficeCostData['total'] ?? 0)); ?></h4>
                            <small>Direct: TSh <?php echo number_format($todayExpenseData['total'] ?? 0); ?></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-3 text-white bg-warning">
                            <small>Monthly Expenses</small>
                            <h4>TSh <?php echo number_format(($monthExpenseData['total'] ?? 0) + ($monthOfficeCostData['total'] ?? 0)); ?></h4>
                            <small>Net: TSh <?php echo number_format(($monthData['total'] ?? 0) - ($monthExpenseData['total'] ?? 0) - ($monthOfficeCostData['total'] ?? 0)); ?></small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card table-card p-3">
                            <h5><i class="fas fa-chart-bar me-2"></i>Income vs Expenses</h5>
                            <canvas id="financeChart" height="150"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card table-card p-3">
                            <h5><i class="fas fa-chart-pie me-2"></i>Sales Breakdown</h5>
                            <canvas id="salesBreakdown" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Tab -->
            <div class="tab-pane fade" id="transactions">
                <div class="card table-card p-3">
                    <h5 class="mb-3"><i class="fas fa-list me-2"></i>All Sales Transactions</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Pump</th>
                                    <th>Attendant</th>
                                    <th>Cash</th>
                                    <th>Bank</th>
                                    <th>Mobile</th>
                                    <th>Total</th>
                                    <th>Litres</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($sale['pump_number']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['attendant_name']); ?></td>
                                    <td>TSh <?php echo number_format($sale['cash_sales']); ?></td>
                                    <td>TSh <?php echo number_format($sale['bank_sales']); ?></td>
                                    <td>TSh <?php echo number_format($sale['mobile_sales']); ?></td>
                                    <td><strong>TSh <?php echo number_format($sale['total_sales']); ?></strong></td>
                                    <td><?php echo number_format($sale['litres_sold']); ?> L</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Expenses Tab -->
            <div class="tab-pane fade" id="expenses">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-plus-circle me-2"></i>Add Expense</h5>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="add_expense">
                                <div class="mb-3">
                                    <label class="form-label">Expense Type</label>
                                    <input type="text" class="form-control" name="expense_type" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Amount (TSh)</label>
                                    <input type="number" class="form-control" name="amount" step="0.01" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Record Expense</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card table-card p-3">
                            <h5 class="mb-3"><i class="fas fa-list me-2"></i>All Expenses</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentExpenses as $expense): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($expense['expense_type']); ?></td>
                                            <td><?php echo htmlspecialchars($expense['description'] ?? '-'); ?></td>
                                            <td>TSh <?php echo number_format($expense['amount']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Office Costs Tab -->
            <div class="tab-pane fade" id="office-costs">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-briefcase me-2"></i>Add Office Cost / Payment</h5>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="add_office_cost">
                                <div class="mb-3">
                                    <label class="form-label">Cost Type</label>
                                    <input type="text" class="form-control" name="cost_type" required placeholder="e.g., Salary, Rent, Utilities">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Amount (TSh)</label>
                                    <input type="number" class="form-control" name="amount" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Pay To (Optional - for staff payments)</label>
                                    <select class="form-select" name="recipient_id">
                                        <option value="">-- Select Employee --</option>
                                        <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?> (<?php echo ucfirst($emp['role']); ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Record Payment</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card table-card p-3">
                            <h5 class="mb-3"><i class="fas fa-list me-2"></i>Office Costs & Payments</h5>
                            <?php
                            $officeCosts = $pdo->query("
                                SELECT oc.*, u.full_name as recipient_name
                                FROM office_costs oc
                                LEFT JOIN users u ON oc.recipient_id = u.id
                                ORDER BY oc.payment_date DESC
                            ")->fetchAll();
                            ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Recipient</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($officeCosts as $cost): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($cost['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($cost['cost_type']); ?></td>
                                            <td><?php echo htmlspecialchars($cost['description'] ?? '-'); ?></td>
                                            <td><?php echo $cost['recipient_name'] ? htmlspecialchars($cost['recipient_name']) : '-'; ?></td>
                                            <td>TSh <?php echo number_format($cost['amount']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Tab -->
            <div class="tab-pane fade" id="reports">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-calendar me-2"></i>Daily Report</h5>
                            <form method="POST" action="../reports/daily_report.php" target="_blank">
                                <div class="mb-3">
                                    <label class="form-label">Select Date</label>
                                    <input type="date" class="form-control" name="date" value="<?php echo $today; ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-file-pdf me-2"></i>Generate PDF</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Monthly Report</h5>
                            <form method="POST" action="../reports/monthly_report.php" target="_blank">
                                <div class="mb-3">
                                    <label class="form-label">Select Month</label>
                                    <input type="month" class="form-control" name="month" value="<?php echo date('Y-m'); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-file-pdf me-2"></i>Generate PDF</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-archive me-2"></i>Archived Reports</h5>
                            <?php
                            $archivedSales = $pdo->query("
                                SELECT sale_date, SUM(total_sales) as total, SUM(litres_sold) as litres
                                FROM sales
                                GROUP BY sale_date
                                ORDER BY sale_date DESC
                                LIMIT 30
                            ")->fetchAll();
                            ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Total Sales</th>
                                            <th>Total Litres</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($archivedSales as $sale): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></td>
                                            <td>TSh <?php echo number_format($sale['total']); ?></td>
                                            <td><?php echo number_format($sale['litres']); ?> L</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Tab -->
            <div class="tab-pane fade" id="profile">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-user me-2"></i>My Profile</h5>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="upload_picture">
                                <div class="text-center mb-3">
                                    <img src="<?php echo $currentUser['profile_image'] ?? 'https://via.placeholder.com/100'; ?>" class="rounded-circle" width="100" height="100" alt="Profile">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Upload Profile Picture (Max 1MB)</label>
                                    <input type="file" class="form-control" name="profile_image" accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-2"></i>Upload Picture</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                document.querySelector('.main-content').classList.toggle('expanded');
            }
        }
        
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
        
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    </script>
    <script>
        // Finance Chart
        const financeCtx = document.getElementById('financeChart').getContext('2d');
        new Chart(financeCtx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Income',
                    data: [<?php 
                        for($i=1; $i<=4; $i++) {
                            $w1 = date('Y-m-01', strtotime('+' . ($i-1) . ' weeks'));
                            $w2 = date('Y-m-07', strtotime('+' . ($i-1) . ' weeks'));
                            $s = $pdo->prepare("SELECT COALESCE(SUM(total_sales), 0) as t FROM sales WHERE sale_date BETWEEN ? AND ?");
                            $s->execute([$w1, $w2]);
                            echo $s->fetch()['t'] . ', ';
                        }
                    ?>],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }, {
                    label: 'Expenses',
                    data: [<?php 
                        for($i=1; $i<=4; $i++) {
                            $w1 = date('Y-m-01', strtotime('+' . ($i-1) . ' weeks'));
                            $w2 = date('Y-m-07', strtotime('+' . ($i-1) . ' weeks'));
                            $e = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as t FROM expenses WHERE expense_date BETWEEN ? AND ?");
                            $e->execute([$w1, $w2]);
                            echo $e->fetch()['t'] . ', ';
                        }
                    ?>],
                    backgroundColor: 'rgba(227, 24, 55, 0.8)'
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        // Sales Breakdown Chart
        const salesCtx = document.getElementById('salesBreakdown').getContext('2d');
        new Chart(salesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Cash', 'Bank', 'Mobile'],
                datasets: [{
                    data: [<?php echo $todayData['cash'] ?? 0; ?>, <?php echo $todayData['bank'] ?? 0; ?>, <?php echo $todayData['mobile'] ?? 0; ?>],
                    backgroundColor: ['rgba(40, 167, 69, 0.8)', 'rgba(23, 162, 184, 0.8)', 'rgba(255, 193, 7, 0.8)']
                }]
            }
        });
    </script>
</body>
</html>
