<?php
require_once '../config/database.php';
requireRole('security');

$userId = getCurrentUserId();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

// Get assigned shift
$today = date('Y-m-d');
$assignedShift = $pdo->prepare("
    SELECT * FROM security_shifts 
    WHERE security_id = ? AND shift_date = ?
");
$assignedShift->execute([$userId, $today]);
$shift = $assignedShift->fetchAll();

// Get all my shifts
$myShifts = $pdo->prepare("
    SELECT * FROM security_shifts 
    WHERE security_id = ?
    ORDER BY shift_date DESC
    LIMIT 30
");
$myShifts->execute([$userId]);
$allShifts = $myShifts->fetchAll();

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
                $maxFileSize = 1024 * 1024; // 1MB
                
                if ($_FILES['profile_image']['size'] > $maxFileSize) {
                    $newFileName = uniqid('profile_') . '.jpg';
                    
                    if ($fileExt === 'jpeg' || $fileExt === 'jpg') {
                        $image = imagecreatefromjpeg($_FILES['profile_image']['tmp_name']);
                    } elseif ($fileExt === 'png') {
                        $image = imagecreatefrompng($_FILES['profile_image']['tmp_name']);
                    } else {
                        $image = imagecreatefromgif($_FILES['profile_image']['tmp_name']);
                    }
                    
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
                    
                    imagejpeg($image, $uploadDir . $newFileName, 80);
                    imagedestroy($image);
                    
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

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard - Petrol Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
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
        .shift-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
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
            .stat-card i { font-size: 1.2rem !important; }
            .shift-card { padding: 15px 10px; }
            .shift-card h4 { font-size: 1.1rem; }
            .shift-card h5 { font-size: 1rem; }
            .table { font-size: 0.85rem; }
            .table th, .table td { padding: 0.4rem 0.3rem; }
            .form-label { font-size: 0.9rem; }
            .form-control, .form-select { font-size: 0.9rem; padding: 0.4rem 0.75rem; }
            .btn { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
            .badge { font-size: 0.7rem; padding: 0.25em 0.5em; }
            .user-info { display: none; }
            .row { margin-left: -5px; margin-right: -5px; }
            .row > div { padding-left: 5px; padding-right: 5px; }
        }
        
        @media (max-width: 576px) {
            h4.mb-0 { font-size: 1.1rem; }
            .d-flex.justify-content-between { flex-direction: column; align-items: flex-start !important; }
            .table-responsive { font-size: 0.8rem; }
            .card { border-radius: 10px; }
            .fa-4x { font-size: 2.5rem !important; }
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
            <small class="text-white-50">Security</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link active" data-bs-toggle="tab"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="nav-item"><a href="#shifts" class="nav-link" data-bs-toggle="tab"><i class="fas fa-clock"></i> My Shifts</a></li>
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
                <span class="badge bg-success me-3"><i class="fas fa-circle me-1"></i> On Duty</span>
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

        <div class="tab-content">
            <!-- Dashboard -->
            <div class="tab-pane fade show active" id="dashboard">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card p-3 text-white bg-primary">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small>Today's Status</small>
                                    <h4 class="mb-0"><?php echo empty($shift) ? 'No Shift' : 'On Duty'; ?></h4>
                                </div>
                                <i class="fas fa-shield-alt fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card p-3 text-white bg-info">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small>Role</small>
                                    <h4 class="mb-0">Security</h4>
                                </div>
                                <i class="fas fa-user-shield fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card p-3 text-white bg-success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small>Shifts This Month</small>
                                    <h4 class="mb-0"><?php echo count($allShifts); ?></h4>
                                </div>
                                <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($shift)): ?>
                <div class="card shift-card p-4">
                    <h4 class="mb-4"><i class="fas fa-clock me-2"></i>Today's Assigned Shift</h4>
                    <div class="row">
                        <?php foreach ($shift as $s): ?>
                        <div class="col-md-4">
                            <div class="card bg-<?php echo strpos($s['shift_type'], 'Morning') !== false ? 'warning' : (strpos($s['shift_type'], 'Evening') !== false ? 'info' : 'secondary'); ?> text-white p-3">
                                <h5><?php echo htmlspecialchars($s['shift_type']); ?></h5>
                                <p class="mb-1"><i class="fas fa-sign-in-alt me-2"></i>Start: <?php echo date('H:i', strtotime($s['start_time'])); ?></p>
                                <p class="mb-0"><i class="fas fa-sign-out-alt me-2"></i>End: <?php echo date('H:i', strtotime($s['end_time'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="card shift-card p-4">
                    <div class="text-center">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h3>No Shift Today</h3>
                        <p class="text-muted">You don't have any assigned shift for today. Please contact your supervisor.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- My Shifts -->
            <div class="tab-pane fade" id="shifts">
                <div class="card shift-card p-3">
                    <h5 class="mb-3"><i class="fas fa-list me-2"></i>My Shift History</h5>
                    <?php if (empty($allShifts)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No shifts assigned yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Shift Type</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allShifts as $s): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($s['shift_date'])); ?></td>
                                        <td><span class="badge bg-<?php echo strpos($s['shift_type'], 'Morning') !== false ? 'warning' : (strpos($s['shift_type'], 'Evening') !== false ? 'info' : 'secondary'); ?>"><?php echo htmlspecialchars($s['shift_type']); ?></span></td>
                                        <td><?php echo date('H:i', strtotime($s['start_time'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($s['end_time'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile -->
            <div class="tab-pane fade" id="profile">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shift-card p-4">
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
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
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
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="Security" disabled>
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
</body>
</html>
