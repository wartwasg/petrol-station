<?php
require_once '../config/database.php';
requireRoles(['chief_manager']);


// Get current user info
$userId = getCurrentUserId();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

// Get all active users only
$usersStmt = $pdo->query("SELECT * FROM users WHERE is_active = 1 ORDER BY role, full_name");
$allUsers = $usersStmt->fetchAll();

// Dates
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

// Summary queries (aggregates from stored data)
// Total sales (all time)
$totalSalesStmt = $pdo->query("
    SELECT 
        COALESCE(SUM(total_sales), 0) as total_sales,
        COALESCE(SUM(litres_sold), 0) as total_litres
    FROM sales
");
$totalSales = $totalSalesStmt->fetch();

// Monthly income (current month)
$monthIncomeStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(total_sales), 0) as total_sales,
        COALESCE(SUM(litres_sold), 0) as total_litres
    FROM sales 
    WHERE sale_date >= ?");
$monthIncomeStmt->execute([$monthStart]);
$monthIncome = $monthIncomeStmt->fetch();

// Total expenses (all time)
$totalExpensesStmt = $pdo->query("
    SELECT 
        COALESCE(SUM(amount), 0) as total
    FROM expenses
");
$totalExpenses = $totalExpensesStmt->fetch();

// Monthly expenses (current month)
$monthExpensesStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total
    FROM expenses
    WHERE expense_date >= ?");
$monthExpensesStmt->execute([$monthStart]);
$monthExpenses = $monthExpensesStmt->fetch();

// Get all pump readings
$readingsStmt = $pdo->query("
    SELECT pr.*, p.pump_number, ft.name as fuel_type, u.full_name as recorded_by_name
    FROM pump_readings pr
    JOIN pumps p ON pr.pump_id = p.id
    JOIN fuel_types ft ON p.fuel_type_id = ft.id
    JOIN users u ON pr.recorded_by = u.id
    ORDER BY pr.reading_date DESC, pr.shift DESC, pr.reading_type DESC
    LIMIT 50
");
$pumpReadings = $readingsStmt->fetchAll();

// Get daily expenses
$expensesStmt = $pdo->prepare("
    SELECT SUM(amount) as total FROM expenses WHERE expense_date = ?
");
$expensesStmt->execute([$today]);
$todayExpenses = $expensesStmt->fetch();

// Get monthly expenses
$monthExpensesStmt = $pdo->prepare("
    SELECT SUM(amount) as total FROM expenses WHERE expense_date >= ?
");
$monthExpensesStmt->execute([$monthStart]);
$monthExpenses = $monthExpensesStmt->fetch();

// Get tank refills
$refillsStmt = $pdo->query("
    SELECT tr.*, t.tank_number, ft.name as fuel_type, u.full_name as created_by_name
    FROM tank_refills tr
    JOIN tanks t ON tr.tank_id = t.id
    JOIN fuel_types ft ON t.fuel_type_id = ft.id
    JOIN users u ON tr.created_by = u.id
    ORDER BY tr.refill_date DESC
    LIMIT 20
");
$tankRefills = $refillsStmt->fetchAll();

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $fullName = sanitize($_POST['full_name']);
        $role = sanitize($_POST['role']);
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($email) || empty($fullName) || empty($role) || empty($password)) {
            $error = 'All fields are required';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            try {
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, password, email, full_name, role, phone)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([$username, $hashedPassword, $email, $fullName, $role, $phone]);
                $success = 'User created successfully';
                
                // Log activity
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $logStmt->execute([$userId, 'create_user', "Created user: $username with role: $role", $_SERVER['REMOTE_ADDR']]);
                
                // Refresh users list
                $usersStmt = $pdo->query("SELECT * FROM users ORDER BY role, full_name");
                $allUsers = $usersStmt->fetchAll();
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Username or email already exists';
                } else {
                    $error = 'Error creating user';
                }
            }
        }
    }
}

// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_role') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $userIdToUpdate = (int)($_POST['user_id'] ?? 0);
        $newRole = sanitize($_POST['new_role'] ?? '');

        // Chief manager can assign any role (including top roles)
        $allowedRoles = ['top_manager', 'chief_manager', 'manager', 'accountant', 'pump_attendant', 'security'];

        if ($userIdToUpdate > 0 && in_array($newRole, $allowedRoles, true)) {
            try {
                $updateStmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $updateStmt->execute([$newRole, $userIdToUpdate]);
                $success = 'Role updated successfully';

                // Log activity
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $logStmt->execute([$userId, 'assign_role', "Assigned role: $newRole to user ID: $userIdToUpdate", $_SERVER['REMOTE_ADDR']]);

                // Refresh users list
                $usersStmt = $pdo->query("SELECT * FROM users ORDER BY role, full_name");
                $allUsers = $usersStmt->fetchAll();
            } catch (PDOException $e) {
                $error = 'Error updating role';
            }
        }
    }
}


// Handle user activation (safe: restore by setting is_active = 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_user') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $currentRole = $currentUser['role'] ?? null;
        if ($currentRole !== 'chief_manager') {
            $error = 'Unauthorized';
        } else {
            $userIdToActivate = (int)($_POST['user_id'] ?? 0);
            if ($userIdToActivate <= 0) {
                $error = 'Invalid user';
            } else {
                try {
                    $checkStmt = $pdo->prepare("SELECT id, role, full_name, is_active FROM users WHERE id = ? LIMIT 1");
                    $checkStmt->execute([$userIdToActivate]);
                    $targetUser = $checkStmt->fetch();

                    if (!$targetUser) {
                        $error = 'User not found';
                    } else {
                        $activateStmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                        $activateStmt->execute([$userIdToActivate]);
                        $success = 'User activated successfully';

                        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                        $logStmt->execute([
                            $userId,
                            'activate_user',
                            'Activated user: ' . $targetUser['full_name'] . ' (ID: ' . $userIdToActivate . ')',
                            $_SERVER['REMOTE_ADDR']
                        ]);

                        $usersStmt = $pdo->query("SELECT * FROM users ORDER BY role, full_name");
                        $allUsers = $usersStmt->fetchAll();
                    }
                } catch (PDOException $e) {
                    $error = 'Error activating user';
                }
            }
        }
    }
}

// Handle user deletion (safe: soft-delete by setting is_active = 0)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {

    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        // defense in depth (dashboard route is protected, but double-check)
        $currentRole = $currentUser['role'] ?? null;
        if ($currentRole !== 'chief_manager') {
            $error = 'Unauthorized';
        } else {
            $userIdToDelete = (int)($_POST['user_id'] ?? 0);

            if ($userIdToDelete <= 0) {
                $error = 'Invalid user';
            } else if ($userIdToDelete === (int)$userId) {
                $error = 'You cannot deactivate your own account';
            } else {
                try {
                    // prevent deactivating the chief_manager role
                    $checkStmt = $pdo->prepare("SELECT id, role, full_name FROM users WHERE id = ? LIMIT 1");
                    $checkStmt->execute([$userIdToDelete]);
                    $targetUser = $checkStmt->fetch();

                    if (!$targetUser) {
                        $error = 'User not found';
                    } else {
                        // Safe policy:
                        // - self-deactivation is blocked above
                        // - chief_admin/chief_manager can deactivate other chief_manager accounts
                        $deleteStmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                        $deleteStmt->execute([$userIdToDelete]);
                        $success = 'User deactivated successfully';

                        // Log activity
                        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                        $logStmt->execute([
                            $userId,
                            'delete_user',
                            'Deactivated user: ' . $targetUser['full_name'] . ' (ID: ' . $userIdToDelete . ')',
                            $_SERVER['REMOTE_ADDR']
                        ]);

                        // Refresh users list
                        $usersStmt = $pdo->query("SELECT * FROM users ORDER BY role, full_name");
                        $allUsers = $usersStmt->fetchAll();
                    }
                } catch (PDOException $e) {
                    $error = 'Error deactivating user';
                }
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chief Manager Dashboard - Petrol Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" rel="stylesheet">
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
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-card.sales .icon { background: rgba(227, 24, 55, 0.1); color: var(--primary); }
        .stat-card.expenses .icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-card.income .icon { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-card.readings .icon { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        .table-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .nav-tabs .nav-link { border: none; color: #6c757d; font-weight: 500; }
        .nav-tabs .nav-link.active { border: none; border-bottom: 3px solid var(--primary); color: var(--primary); }
        .sidebar-toggle { cursor: pointer; font-size: 1.2rem; }
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
            .stat-card .icon { width: 40px; height: 40px; font-size: 1rem; }
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
            .nav-tabs .nav-link { padding: 0.5rem 0.75rem; font-size: 0.85rem; }
            .badge { font-size: 0.7rem; padding: 0.25em 0.5em; }
            .progress { height: 10px !important; }
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
            <small class="text-white-50">Chief Manager</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="nav-item"><a href="#users" class="nav-link" data-bs-toggle="tab"><i class="fas fa-users"></i> User Management</a></li>
            <li class="nav-item"><a href="#sales" class="nav-link" data-bs-toggle="tab"><i class="fas fa-chart-line"></i> Sales & Readings</a></li>
            <li class="nav-item"><a href="#expenses" class="nav-link" data-bs-toggle="tab"><i class="fas fa-money-bill-wave"></i> Expenses</a></li>
            <li class="nav-item"><a href="#refills" class="nav-link" data-bs-toggle="tab"><i class="fas fa-gas-pump"></i> Tank Refills</a></li>
            <li class="nav-item"><a href="#reports" class="nav-link" data-bs-toggle="tab"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li class="nav-item mt-5"><a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
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

        <!-- Alerts -->
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

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card sales p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Total Sales</small>
                            <h4 class="mb-0">TSh <?php echo number_format($totalSales['total_sales'] ?? 0, 2); ?></h4>
                            <small class="text-success"><?php echo number_format($totalSales['total_litres'] ?? 0, 2); ?> Litres</small>
                        </div>
                        <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card income p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Monthly Income</small>
                            <h4 class="mb-0">TSh <?php echo number_format($monthIncome['total_sales'] ?? 0, 2); ?></h4>
                            <small class="text-info"><?php echo number_format($monthIncome['total_litres'] ?? 0, 2); ?> Litres</small>
                        </div>
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card expenses p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Total Expenses</small>
                            <h4 class="mb-0">TSh <?php echo number_format($totalExpenses['total'] ?? 0, 2); ?></h4>
                            <small class="text-warning">All time</small>
                        </div>
                        <div class="icon"><i class="fas fa-minus"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card readings p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Monthly Expenses</small>
                            <h4 class="mb-0">TSh <?php echo number_format($monthExpenses['total'] ?? 0, 2); ?></h4>
                            <small class="text-primary">Net: TSh <?php echo number_format((($monthIncome['total_sales'] ?? 0) - ($monthExpenses['total'] ?? 0)), 2); ?></small>
                        </div>
                        <div class="icon"><i class="fas fa-calculator"></i></div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Dashboard Overview -->
            <div class="tab-pane fade show active" id="dashboard">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card table-card p-3">
                            <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Monthly Sales & Expenses</h5>
                            <canvas id="monthlyChart" height="150"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card table-card p-3">
                            <h5 class="mb-3"><i class="fas fa-gas-pump me-2"></i>Recent Tank Levels</h5>
                            <?php
                            $tanksStmt = $pdo->query("SELECT t.*, ft.name as fuel_name FROM tanks t JOIN fuel_types ft ON t.fuel_type_id = ft.id");
                            $tanks = $tanksStmt->fetchAll();
                            foreach ($tanks as $tank):
                                $percentage = ($tank['current_volume'] / $tank['max_capacity']) * 100;
                            ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <small><?php echo htmlspecialchars($tank['tank_number']); ?> - <?php echo htmlspecialchars($tank['fuel_name']); ?></small>
                                        <small><?php echo number_format($tank['current_volume']); ?> / <?php echo number_format($tank['max_capacity']); ?> L</small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar <?php echo $percentage > 50 ? 'bg-success' : ($percentage > 20 ? 'bg-warning' : 'bg-danger'); ?>" 
                                             style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Management -->
            <div class="tab-pane fade" id="users">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-user-plus me-2"></i>Create New User</h5>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="create_user">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="manager">Chief_manager</option>
                                        <option value="manager">Manager</option>
                                        <option value="accountant">Accountant</option>
                                        
                                        
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Create User</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card table-card p-3">
                            <h5 class="mb-3"><i class="fas fa-users me-2"></i>All Users</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allUsers as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $user['role'] === 'chief_manager' ? 'danger' : 
                                                        ($user['role'] === 'manager' ? 'primary' : 
                                                        ($user['role'] === 'accountant' ? 'info' : 
                                                        ($user['role'] === 'pump_attendant' ? 'warning' : 'secondary')));
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ((int)$user['id'] !== (int)$userId): ?>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal<?php echo $user['id']; ?>" title="Assign role">
                                                            <i class="fas fa-edit"></i>
                                                        </button>

                                                        <?php if (!(int)$user['is_active'] && (int)$user['id'] !== (int)$userId): ?>
                                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#activateModal<?php echo $user['id']; ?>" title="Activate user">
                                                                <i class="fas fa-user-check"></i>
                                                            </button>

                                                            <div class="modal fade" id="activateModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Activate User</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <p class="mb-0">Are you sure you want to activate <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>?</p>
                                                                            <small class="text-muted d-block mt-2">This action will set the user status to Active.</small>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <form method="POST" style="margin: 0;">
                                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                                                <input type="hidden" name="action" value="activate_user">
                                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                                <button type="submit" class="btn btn-success">Activate</button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- Role Assignment Modal -->
                                                        <div class="modal fade" id="roleModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">

                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Assign Role</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                                            <input type="hidden" name="action" value="assign_role">
                                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">User: <?php echo htmlspecialchars($user['full_name']); ?></label>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label">Select Role</label>
                                                                                <select class="form-select" name="new_role" required>
                                                                                    
                                                                                    <option value="chief_manager" <?php echo $user['role'] === 'chief_manager' ? 'selected' : ''; ?>>Chief Manager</option>
                                                                                    <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                                                                    <option value="accountant" <?php echo $user['role'] === 'accountant' ? 'selected' : ''; ?>>Accountant</option>
                                                                                    
                                                                                    
                                                                                </select>

                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" class="btn btn-primary">Update Role</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Delete (Deactivate) Modal Trigger -->
                                                        <?php if ((int)$user['id'] !== (int)$userId): ?>
                                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>" title="Deactivate user">
                                                                <i class="fas fa-trash"></i>
                                                            </button>

                                                            <!-- Deactivate Modal -->
                                                            <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Deactivate User</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <p class="mb-0">Are you sure you want to deactivate <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>?</p>
                                                                            <small class="text-muted d-block mt-2">This action is safe (soft delete) and will set the user status to Inactive.</small>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <form method="POST" style="margin: 0;">
                                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                                                <input type="hidden" name="action" value="delete_user">
                                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                                <button type="submit" class="btn btn-danger">Deactivate</button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales & Readings -->
            <div class="tab-pane fade" id="sales">
                <div class="card table-card p-3">
                    <h5 class="mb-3"><i class="fas fa-tachometer-alt me-2"></i>All Pump Readings</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Shift</th>
                                    <th>Type</th>
                                    <th>Pump</th>
                                    <th>Fuel Type</th>
                                    <th>Reading (L)</th>
                                    <th>Litres Sold</th>
                                    <th>Income</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pumpReadings as $reading): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($reading['reading_date'])); ?></td>
                                    <td><span class="badge bg-<?php echo $reading['shift'] === 'morning' ? 'warning' : 'info'; ?>"><?php echo ucfirst($reading['shift']); ?></span></td>
                                    <td><span class="badge bg-<?php echo $reading['reading_type'] === 'opening' ? 'success' : 'primary'; ?>"><?php echo ucfirst($reading['reading_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($reading['pump_number']); ?></td>
                                    <td><?php echo htmlspecialchars($reading['fuel_type']); ?></td>
                                    <td><?php echo number_format($reading['meter_reading']); ?> L</td>
                                    <td><?php echo number_format($reading['litres_sold']); ?> L</td>
                                    <td>TSh <?php echo number_format($reading['income']); ?></td>
                                    <td><?php echo htmlspecialchars($reading['recorded_by_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Expenses -->
            <div class="tab-pane fade" id="expenses">
                <div class="card table-card p-3">
                    <h5 class="mb-3"><i class="fas fa-money-bill-wave me-2"></i>All Expenses</h5>
                    <?php
                    $allExpenses = $pdo->query("
                        SELECT e.*, u.full_name as created_by_name
                        FROM expenses e
                        JOIN users u ON e.created_by = u.id
                        ORDER BY e.expense_date DESC
                        LIMIT 50
                    ")->fetchAll();
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allExpenses as $expense): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['expense_type']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description'] ?? '-'); ?></td>
                                    <td>TSh <?php echo number_format($expense['amount']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['created_by_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tank Refills -->
            <div class="tab-pane fade" id="refills">
                <div class="card table-card p-3">
                    <h5 class="mb-3"><i class="fas fa-gas-pump me-2"></i>Tank Refill History</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Tank</th>
                                    <th>Fuel Type</th>
                                    <th>Volume Added</th>
                                    <th>Cost</th>
                                    <th>Created By</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tankRefills as $refill): ?>
                                <tr>
                                    <td><?php echo date('M j, Y H:i', strtotime($refill['refill_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($refill['tank_number']); ?></td>
                                    <td><?php echo htmlspecialchars($refill['fuel_type']); ?></td>
                                    <td><?php echo number_format($refill['refill_volume']); ?> L</td>
                                    <td>TSh <?php echo number_format($refill['cost']); ?></td>
                                    <td><?php echo htmlspecialchars($refill['created_by_name']); ?></td>
                                    <td>
                                        <?php if ($refill['receipt_image']): ?>
                                            <a href="<?php echo htmlspecialchars($refill['receipt_image']); ?>" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-image"></i></a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="tab-pane fade" id="reports">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-calendar me-2"></i>Monthly Report</h5>
                            <form method="POST" action="../reports/monthly_report.php" target="_blank">
                                <div class="mb-3">
                                    <label class="form-label">Select Month</label>
                                    <input type="month" class="form-control" name="month" value="<?php echo date('Y-m'); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-file-pdf me-2"></i>Generate PDF Report</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Statistics</h5>
                            <canvas id="salesChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            // Check if mobile view
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                document.querySelector('.main-content').classList.toggle('expanded');
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
        
        // Adjust sidebar on window resize
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
        // Monthly Sales Chart
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Sales (TSh)',
                    data: [<?php 
                        for($i=1; $i<=4; $i++) {
                            $weekStart = date('Y-m-01', strtotime('+' . ($i-1) . ' weeks'));
                            $weekEnd = date('Y-m-07', strtotime('+' . ($i-1) . ' weeks'));
                            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_sales), 0) as total FROM sales WHERE sale_date BETWEEN ? AND ?");
                            $stmt->execute([$weekStart, $weekEnd]);
                            echo $stmt->fetch()['total'] . ', ';
                        }
                    ?>],
                    backgroundColor: 'rgba(227, 24, 55, 0.8)',
                    borderColor: 'rgba(227, 24, 55, 1)',
                    borderWidth: 1
                }, {
                    label: 'Expenses (TSh)',
                    data: [<?php 
                        for($i=1; $i<=4; $i++) {
                            $weekStart = date('Y-m-01', strtotime('+' . ($i-1) . ' weeks'));
                            $weekEnd = date('Y-m-07', strtotime('+' . ($i-1) . ' weeks'));
                            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE expense_date BETWEEN ? AND ?");
                            $stmt->execute([$weekStart, $weekEnd]);
                            echo $stmt->fetch()['total'] . ', ';
                        }
                    ?>],
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Sales by Fuel Type Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php
                    $fuelTypes = $pdo->query("SELECT name FROM fuel_types")->fetchAll(PDO::FETCH_COLUMN);
                    echo implode(', ', array_map(fn($f) => "'$f'", $fuelTypes));
                ?>],
                datasets: [{
                    data: [<?php
                        $fuelSales = $pdo->query("
                            SELECT COALESCE(SUM(s.total_sales), 0) as total
                            FROM sales s
                            JOIN pumps p ON s.pump_id = p.id
                            GROUP BY p.fuel_type_id
                        ")->fetchAll(PDO::FETCH_COLUMN);
                        echo implode(', ', $fuelSales);
                    ?>],
                    backgroundColor: [
                        'rgba(227, 24, 55, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ]
                }]
            }
        });
    </script>
</body>
</html>
