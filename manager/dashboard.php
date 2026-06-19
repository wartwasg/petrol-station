<?php
require_once '../config/database.php';
requireRole('manager');

$userId = getCurrentUserId();

// Restrict fuel price updates:
// - Only allowed on the first Wednesday of the current month.
// - Only once per month (based on existing fuel_prices.effective_date rows).
function getFirstWednesdayOfCurrentMonth(): string {
    $tz = new DateTimeZone(date_default_timezone_get());
    $now = new DateTime('now', $tz);
    $year = (int)$now->format('Y');
    $month = (int)$now->format('m');

    $firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month), $tz);
    // PHP: 1 (Mon) ... 7 (Sun)
    $firstDow = (int)$firstDay->format('N');
    $wednesdayDow = 4;

    $daysUntil = ($wednesdayDow - $firstDow + 7) % 7;
    $firstWednesday = (clone $firstDay)->modify('+' . $daysUntil . ' days');

    return $firstWednesday->format('Y-m-d');
}

$userId = getCurrentUserId();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");

$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

// Get all fuel types
$fuelTypes = $pdo->query("SELECT * FROM fuel_types WHERE is_active = 1")->fetchAll();

// Get current fuel prices
$currentPrices = $pdo->query("
    SELECT fp.*, ft.name as fuel_name 
    FROM fuel_prices fp 
    JOIN fuel_types ft ON fp.fuel_type_id = ft.id 
    ORDER BY ft.name
")->fetchAll();

// Get all pumps
$pumps = $pdo->query("
    SELECT p.*, ft.name as fuel_type, u.full_name as attendant_name
    FROM pumps p
    JOIN fuel_types ft ON p.fuel_type_id = ft.id
    LEFT JOIN users u ON p.attendant_id = u.id
    ORDER BY p.pump_number
")->fetchAll();

// Get all tanks
$tanks = $pdo->query("
    SELECT t.*, ft.name as fuel_type
    FROM tanks t
    JOIN fuel_types ft ON t.fuel_type_id = ft.id
    ORDER BY t.tank_number
")->fetchAll();

// Get all users for role assignment
$users = $pdo->query("SELECT * FROM users WHERE role != 'chief_manager' ORDER BY full_name")->fetchAll();

// Get pump attendants
$attendants = $pdo->query("SELECT * FROM users WHERE role = 'pump_attendant' AND is_active = 1")->fetchAll();

// Get today's readings - calculate litres sold and income dynamically
$today = date('Y-m-d');
$todayReadings = $pdo->prepare("
    SELECT 
        pr.*, 
        p.pump_number, 
        ft.name as fuel_type,
        COALESCE(
            (SELECT pr2.meter_reading 
             FROM pump_readings pr2 
             WHERE pr2.pump_id = pr.pump_id 
             AND pr2.reading_type = 'closing'
             AND ((pr2.shift = pr.shift AND pr2.reading_date < pr.reading_date) OR (pr2.shift != pr.shift AND pr2.reading_date <= pr.reading_date))
             ORDER BY pr2.reading_date DESC, pr2.id DESC LIMIT 1),
            (SELECT pr2.meter_reading 
             FROM pump_readings pr2 
             WHERE pr2.pump_id = pr.pump_id 
             AND pr2.reading_date < pr.reading_date
             ORDER BY pr2.reading_date DESC, pr2.id DESC LIMIT 1)
        ) as prev_reading
    FROM pump_readings pr
    JOIN pumps p ON pr.pump_id = p.id
    JOIN fuel_types ft ON p.fuel_type_id = ft.id
    WHERE pr.reading_date = ?
    ORDER BY p.pump_number, pr.shift, pr.reading_type
");
$todayReadings->execute([$today]);
$todayReadingsData = $todayReadings->fetchAll();

// Get current fuel prices for income calculation
$fuelPrices = [];
$priceStmt = $pdo->query("SELECT fuel_type_id, price_per_litre FROM fuel_prices ORDER BY effective_date DESC");
while ($row = $priceStmt->fetch()) {
    if (!isset($fuelPrices[$row['fuel_type_id']])) {
        $fuelPrices[$row['fuel_type_id']] = $row['price_per_litre'];
    }
}

// Get archived readings
$archivedReadings = $pdo->query("
    SELECT pr.*, p.pump_number, ft.name as fuel_type
    FROM pump_readings pr
    JOIN pumps p ON pr.pump_id = p.id
    JOIN fuel_types ft ON p.fuel_type_id = ft.id
    WHERE pr.reading_date < CURDATE()
    ORDER BY pr.reading_date DESC, pr.shift DESC, pr.reading_type DESC
    LIMIT 100
")->fetchAll();

// Handle fuel price update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_price') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $fuelTypeId = (int)$_POST['fuel_type_id'];
        $price = (float)$_POST['price'];

        if ($fuelTypeId > 0 && $price > 0) {
            try {
                $today = date('Y-m-d');
                $firstWednesday = getFirstWednesdayOfCurrentMonth();

                // Enforce allowed window: only first Wednesday of the current month
                if ($today !== $firstWednesday) {
                    $error = 'Fuel prices can only be changed on the first Wednesday of every month.';
                } else {
                    // Enforce only once per month: if any fuel_prices row exists for this month, block.
                    $nowYearMonth = date('Y-m');
                    $monthStart = date('Y-m-01');
                    $monthEnd = date('Y-m-t');

                    $existsStmt = $pdo->prepare(
                        "SELECT 1 FROM fuel_prices WHERE effective_date >= ? AND effective_date <= ? LIMIT 1"
                    );
                    $existsStmt->execute([$monthStart, $monthEnd]);
                    $alreadyUpdatedThisMonth = (bool)$existsStmt->fetch();

                    if ($alreadyUpdatedThisMonth) {
                        $error = 'Fuel prices have already been updated for this month.';
                    } else {
                        $insertStmt = $pdo->prepare("INSERT INTO fuel_prices (fuel_type_id, price_per_litre, effective_date, created_by) VALUES (?, ?, ?, ?)");
                        $insertStmt->execute([$fuelTypeId, $price, $today, $userId]);
                        $success = 'Fuel price updated successfully';

                        // Log activity
                        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                        $logStmt->execute([$userId, 'update_price', "Updated fuel price to: $price", $_SERVER['REMOTE_ADDR']]);

                        $currentPrices = $pdo->query("SELECT fp.*, ft.name as fuel_name FROM fuel_prices fp JOIN fuel_types ft ON fp.fuel_type_id = ft.id ORDER BY ft.name")->fetchAll();
                    }
                }
            } catch (PDOException $e) {
                $error = 'Error updating price';
            }
        }
    }
}


// Handle pump reading entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_reading') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $pumpId = (int)$_POST['pump_id'];
        $shift = sanitize($_POST['shift']);
        $readingType = sanitize($_POST['reading_type']);
        $reading = (float)$_POST['reading'];
        
        // Validate reading_type
        if (!in_array($readingType, ['opening', 'closing'])) {
            $error = 'Invalid reading type';
        } else {
            // Get previous reading based on reading type
            if ($readingType === 'opening') {
                // For opening reading, get previous closing reading from same shift or previous day
                $prevStmt = $pdo->prepare("
                    SELECT meter_reading FROM pump_readings 
                    WHERE pump_id = ? AND reading_type = 'closing' 
                    AND ((shift = ? AND reading_date < ?) OR (shift != ? AND reading_date <= ?))
                    ORDER BY reading_date DESC, id DESC LIMIT 1
                ");
                $prevStmt->execute([$pumpId, $shift, $today, $shift, $today]);
            } else {
                // For closing reading, get today's opening reading for the same shift
                $prevStmt = $pdo->prepare("
                    SELECT meter_reading FROM pump_readings 
                    WHERE pump_id = ? AND shift = ? AND reading_date = ? AND reading_type = 'opening'
                    ORDER BY id DESC LIMIT 1
                ");
                $prevStmt->execute([$pumpId, $shift, $today]);
            }
            $prevReading = $prevStmt->fetch();
            
            $litresSold = 0;
            if ($prevReading) {
                $litresSold = $reading - $prevReading['meter_reading'];
                // Handle negative litres (meter reset or error)
                if ($litresSold < 0) {
                    $litresSold = 0;
                }
            }
            
            // Get current fuel price (most recent price)
            $priceStmt = $pdo->prepare("
                SELECT fp.price_per_litre FROM fuel_prices fp
                JOIN pumps p ON p.fuel_type_id = fp.fuel_type_id
                WHERE p.id = ?
                ORDER BY fp.effective_date DESC LIMIT 1
            ");
            $priceStmt->execute([$pumpId]);
            $priceData = $priceStmt->fetch();
            $price = $priceData ? $priceData['price_per_litre'] : 0;
            
            // Calculate income
            $income = $litresSold * $price;
            
            try {
                $pdo->beginTransaction();
                
                $insertStmt = $pdo->prepare("
                    INSERT INTO pump_readings (pump_id, shift, reading_type, reading_date, meter_reading, previous_reading, litres_sold, income, recorded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([$pumpId, $shift, $readingType, $today, $reading, $prevReading['meter_reading'] ?? null, $litresSold, $income, $userId]);
                
                // If this is a closing reading and litres were sold, decrease tank volume
                if ($readingType === 'closing' && $litresSold > 0) {
                    // Get the fuel type for this pump
                    $fuelTypeStmt = $pdo->prepare("SELECT fuel_type_id FROM pumps WHERE id = ?");
                    $fuelTypeStmt->execute([$pumpId]);
                    $pumpInfo = $fuelTypeStmt->fetch();
                    
                    if ($pumpInfo) {
                        // Find the tank with this fuel type and decrease its volume
                        $tankStmt = $pdo->prepare("
                            SELECT id, current_volume FROM tanks 
                            WHERE fuel_type_id = ? AND current_volume >= ?
                            ORDER BY current_volume DESC
                            LIMIT 1
                        ");
                        $tankStmt->execute([$pumpInfo['fuel_type_id'], $litresSold]);
                        $tank = $tankStmt->fetch();
                        
                        if ($tank) {
                            // Decrease tank volume
                            $updateTankStmt = $pdo->prepare("
                                UPDATE tanks SET current_volume = current_volume - ? WHERE id = ?
                            ");
                            $updateTankStmt->execute([$litresSold, $tank['id']]);
                            
                            // Log tank volume change
                            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                            $logStmt->execute([$userId, 'tank_volume_decrease', "Decreased tank ID: {$tank['id']} volume by $litresSold litres", $_SERVER['REMOTE_ADDR']]);
                        }
                    }
                }
                
                $pdo->commit();
                $success = 'Reading recorded successfully';
                
                // Log activity
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $logStmt->execute([$userId, 'add_reading', "Added $shift $readingType reading for pump ID: $pumpId", $_SERVER['REMOTE_ADDR']]);
                
                $todayReadings->execute([$today]);
                $todayReadingsData = $todayReadings->fetchAll();
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($e->getCode() == 23000) {
                    $error = 'Reading for this pump, shift, and type already exists today';
                } else {
                    $error = 'Error recording reading';
                }
            }
        }
    }
}

// Handle sales recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_sales') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $pumpId = (int)$_POST['pump_id'];
        $attendantId = isset($_POST['attendant_id']) ? (int)$_POST['attendant_id'] : 0;

        $shift = sanitize($_POST['sales_shift']);
        $cashSales = (float)$_POST['cash_sales'];
        $bankSales = (float)$_POST['bank_sales'];
        $mobileSales = (float)$_POST['mobile_sales'];
        $totalSales = $cashSales + $bankSales + $mobileSales;
        
        // Get litres sold from readings
        $litresStmt = $pdo->prepare("SELECT COALESCE(SUM(litres_sold), 0) as litres FROM pump_readings WHERE pump_id = ? AND shift = ? AND reading_date = ?");
        $litresStmt->execute([$pumpId, $shift, $today]);
        $litresSold = $litresStmt->fetch()['litres'];
        
        try {
            $pdo->beginTransaction();
            
            $insertStmt = $pdo->prepare("
                INSERT INTO sales (pump_id, attendant_id, sale_date, shift, cash_sales, bank_sales, mobile_sales, total_sales, litres_sold)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([$pumpId, $attendantId, $today, $shift, $cashSales, $bankSales, $mobileSales, $totalSales, $litresSold]);
            
            // Decrease tank volume based on litres sold
            if ($litresSold > 0) {
                // Get the fuel type for this pump
                $fuelTypeStmt = $pdo->prepare("SELECT fuel_type_id FROM pumps WHERE id = ?");
                $fuelTypeStmt->execute([$pumpId]);
                $pumpInfo = $fuelTypeStmt->fetch();
                
                if ($pumpInfo) {
                    // Find the tank with this fuel type and decrease its volume
                    $tankStmt = $pdo->prepare("
                        SELECT id, current_volume FROM tanks 
                        WHERE fuel_type_id = ? AND current_volume >= ?
                        ORDER BY current_volume DESC
                        LIMIT 1
                    ");
                    $tankStmt->execute([$pumpInfo['fuel_type_id'], $litresSold]);
                    $tank = $tankStmt->fetch();
                    
                    if ($tank) {
                        // Decrease tank volume
                        $updateTankStmt = $pdo->prepare("
                            UPDATE tanks SET current_volume = current_volume - ? WHERE id = ?
                        ");
                        $updateTankStmt->execute([$litresSold, $tank['id']]);
                        
                        // Log tank volume change
                        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                        $logStmt->execute([$userId, 'tank_volume_decrease', "Decreased tank ID: {$tank['id']} volume by $litresSold litres", $_SERVER['REMOTE_ADDR']]);
                    }
                }
            }
            
            $pdo->commit();
            $success = 'Sales recorded successfully';
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'record_sales', "Recorded sales for pump ID: $pumpId", $_SERVER['REMOTE_ADDR']]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = 'Sales already recorded for this pump and shift today';
            } else {
                $error = 'Error recording sales';
            }
        }
    }
}

// Handle pump creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_pump') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $pumpNumber = sanitize($_POST['pump_number']);
        $fuelTypeId = (int)$_POST['fuel_type_id'];
        $attendantId = !empty($_POST['attendant_id'] ?? '') ? (int)$_POST['attendant_id'] : null;

        
        try {
            $insertStmt = $pdo->prepare("INSERT INTO pumps (pump_number, fuel_type_id, attendant_id) VALUES (?, ?, ?)");
            $insertStmt->execute([$pumpNumber, $fuelTypeId, $attendantId]);
            $success = 'Pump created successfully';
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'create_pump', "Created pump: $pumpNumber", $_SERVER['REMOTE_ADDR']]);
            
            $pumps = $pdo->query("SELECT p.*, ft.name as fuel_type, u.full_name as attendant_name FROM pumps p JOIN fuel_types ft ON p.fuel_type_id = ft.id LEFT JOIN users u ON p.attendant_id = u.id ORDER BY p.pump_number")->fetchAll();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Pump number already exists';
            } else {
                $error = 'Error creating pump';
            }
        }
    }
}

// Handle tank creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_tank') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $tankNumber = sanitize($_POST['tank_number']);
        $fuelTypeId = (int)$_POST['fuel_type_id'];
        $maxCapacity = (float)$_POST['max_capacity'];
        $currentVolume = (float)$_POST['current_volume'];
        
        try {
            $insertStmt = $pdo->prepare("INSERT INTO tanks (tank_number, fuel_type_id, max_capacity, current_volume) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$tankNumber, $fuelTypeId, $maxCapacity, $currentVolume]);
            $success = 'Tank created successfully';
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'create_tank', "Created tank: $tankNumber", $_SERVER['REMOTE_ADDR']]);
            
            $tanks = $pdo->query("SELECT t.*, ft.name as fuel_type FROM tanks t JOIN fuel_types ft ON t.fuel_type_id = ft.id ORDER BY t.tank_number")->fetchAll();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Tank number already exists';
            } else {
                $error = 'Error creating tank';
            }
        }
    }
}

// Handle tank refill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tank_refill') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $tankId = (int)$_POST['tank_id'];
        $refillVolume = (float)$_POST['refill_volume'];
        $cost = (float)$_POST['cost'];
        
        // Handle receipt image upload
        $receiptImage = null;
        if (!empty($_FILES['receipt_image']['name'])) {
            $uploadDir = '../uploads/receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileExt = strtolower(pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                $newFileName = uniqid('receipt_') . '.' . $fileExt;
                if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $uploadDir . $newFileName)) {
                    $receiptImage = $uploadDir . $newFileName;
                }
            }
        }
        
        try {
            $pdo->beginTransaction();
            
            // Insert refill record
            $insertStmt = $pdo->prepare("INSERT INTO tank_refills (tank_id, refill_volume, cost, receipt_image, refill_date, created_by) VALUES (?, ?, ?, ?, NOW(), ?)");
            $insertStmt->execute([$tankId, $refillVolume, $cost, $receiptImage, $userId]);
            
            // Update tank current volume
            $updateStmt = $pdo->prepare("UPDATE tanks SET current_volume = current_volume + ? WHERE id = ?");
            $updateStmt->execute([$refillVolume, $tankId]);
            
            $pdo->commit();
            $success = 'Tank refilled successfully';
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'tank_refill', "Refilled tank ID: $tankId with $refillVolume litres", $_SERVER['REMOTE_ADDR']]);
            
            $tanks = $pdo->query("SELECT t.*, ft.name as fuel_type FROM tanks t JOIN fuel_types ft ON t.fuel_type_id = ft.id ORDER BY t.tank_number")->fetchAll();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error recording refill';
        }
    }
}

// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_role') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $userIdToUpdate = (int)$_POST['user_id'];
        $newRole = sanitize($_POST['new_role']);
        
        if ($userIdToUpdate > 0 && in_array($newRole, ['manager', 'accountant', 'pump_attendant', 'security'])) {
            try {
                $updateStmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND role != 'chief_manager'");
                $updateStmt->execute([$newRole, $userIdToUpdate]);
                $success = 'Role updated successfully';
                
                // Log activity
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $logStmt->execute([$userId, 'assign_role', "Assigned role: $newRole to user ID: $userIdToUpdate", $_SERVER['REMOTE_ADDR']]);
                
                $users = $pdo->query("SELECT * FROM users WHERE role != 'chief_manager' ORDER BY full_name")->fetchAll();
            } catch (PDOException $e) {
                $error = 'Error updating role';
            }
        }
    }
}

// Handle profile edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_profile') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $editUserId = (int)$_POST['user_id'];
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address'] ?? '');
        
        try {
            $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $updateStmt->execute([$fullName, $email, $phone, $address, $editUserId]);
            $success = 'Profile updated successfully';
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'edit_profile', "Updated profile for user ID: $editUserId", $_SERVER['REMOTE_ADDR']]);
            
            $users = $pdo->query("SELECT * FROM users WHERE role != 'chief_manager' ORDER BY full_name")->fetchAll();
            if ($editUserId == $userId) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $currentUser = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = 'Error updating profile';
        }
    }
}

// Handle own profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_own_profile') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address'] ?? '');
        
        // Handle profile image upload
        $profileImage = $currentUser['profile_image'];
        if (!empty($_FILES['profile_image']['name'])) {
            $uploadDir = '../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileExt = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                $newFileName = uniqid('profile_') . '.' . $fileExt;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $newFileName)) {
                    $profileImage = $uploadDir . $newFileName;
                }
            }
        }
        
        try {
            $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, profile_image = ? WHERE id = ?");
            $updateStmt->execute([$fullName, $email, $phone, $address, $profileImage, $userId]);
            $success = 'Your profile updated successfully';
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentUser = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Error updating profile';
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
    <title>Manager Dashboard - Petrol Station</title>
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
        .sidebar-toggle { cursor: pointer; font-size: 1.2rem; }
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .table-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .nav-tabs .nav-link { border: none; color: #6c757d; }
        .nav-tabs .nav-link.active { border: none; border-bottom: 3px solid var(--primary); color: var(--primary); }
        
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
            .sidebar-toggle { display: block !important; }
            .mobile-toggle { display: block !important; }
            .stat-card h4 { font-size: 1.1rem; }
            .stat-card small { font-size: 0.7rem; }
            .stat-card i { font-size: 1.2rem !important; }
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
            .progress { height: 10px !important; width: 60px !important; }
            .sidebar-text { display: inline !important; }
            .user-info { display: none; }
        }
        
        @media (max-width: 576px) {
            .row { margin-left: -5px; margin-right: -5px; }
            .row > .col-md-3, .row > .col-md-4, .row > .col-md-6, .row > .col-md-8 { padding-left: 5px; padding-right: 5px; }
            .stat-card { padding: 10px 8px !important; margin-bottom: 10px; }
            .stat-card h4 { font-size: 1rem; }
            .stat-card .opacity-50 { opacity: 0.4; font-size: 1rem !important; }
            .card { border-radius: 10px; }
            h4.mb-0 { font-size: 1.1rem; }
            h5 { font-size: 0.95rem; }
            .d-flex.justify-content-between { flex-direction: column; align-items: flex-start !important; }
            .table-responsive { font-size: 0.8rem; }
            .modal-content { border-radius: 8px; }
            input[type="number"], select { min-height: 38px; }
        }
        
        .mobile-toggle { display: none; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
        .sidebar-overlay.show { display: block; }
    </style>
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="d-flex justify-content-between align-items-center px-3 py-3">
            <h4 class="text-white mb-0"><i class="fas fa-gas-pump me-2"></i><span class="sidebar-text">Petrol Station</span></h4>
            <i class="fas fa-bars sidebar-toggle text-white" onclick="toggleSidebar()"></i>
        </div>
        <small class="text-white-50 px-3 d-block mb-2"><span class="sidebar-text">Manager</span></small>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link active" data-bs-toggle="tab"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="nav-item"><a href="#fuel-prices" class="nav-link" data-bs-toggle="tab"><i class="fas fa-tag"></i> Fuel Prices</a></li>
            <li class="nav-item"><a href="#pump-readings" class="nav-link" data-bs-toggle="tab"><i class="fas fa-tachometer-alt"></i> Pump Readings</a></li>
            <li class="nav-item"><a href="#sales" class="nav-link" data-bs-toggle="tab"><i class="fas fa-cash-register"></i> Record Sales</a></li>
            <li class="nav-item"><a href="#pumps" class="nav-link" data-bs-toggle="tab"><i class="fas fa-gas-pump"></i> Pumps</a></li>
            <li class="nav-item"><a href="#tanks" class="nav-link" data-bs-toggle="tab"><i class="fas fa-inbox"></i> Tanks</a></li>
            <li class="nav-item"><a href="#refills" class="nav-link" data-bs-toggle="tab"><i class="fas fa-truck-loading"></i> Tank Refills</a></li>
            <li class="nav-item"><a href="#users" class="nav-link" data-bs-toggle="tab"><i class="fas fa-users"></i> User Management</a></li>
            <li class="nav-item"><a href="#archived" class="nav-link" data-bs-toggle="tab"><i class="fas fa-archive"></i> Archived Data</a></li>
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
                <img src="<?php echo $currentUser['profile_image'] ?? 'https://via.placeholder.com/40'; ?>" class="rounded-circle me-3" width="40" height="40" alt="Profile">
                <a href="../logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>

<?php if (isset($error)): ?>
            <div class="alert alert-warning alert-dismissible fade show border border-warning shadow-sm" role="alert" id="managerFuelErrorAlert">
                <div class="d-flex align-items-start">
                    <div class="me-2" style="font-size:1.25rem; color:#b02a37;">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <strong class="me-2" style="color:#b02a37;">Caution</strong>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
<?php endif; ?>
<?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
<?php endif; ?>



        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Dashboard -->
            <div class="tab-pane fade show active" id="dashboard">
                <div class="row mb-4">
                    <?php
                    // Get today's sales from pump readings - calculate dynamically from readings
                    $todayReadingsQuery = $pdo->prepare("
                        SELECT pr.*, p.fuel_type_id, p.pump_number
                        FROM pump_readings pr
                        JOIN pumps p ON pr.pump_id = p.id
                        WHERE pr.reading_date = ?
                    ");
                    $todayReadingsQuery->execute([$today]);
                    $todayReadingsList = $todayReadingsQuery->fetchAll();
                    
                    // Get fuel prices for income calculation
                    $fuelPriceMap = [];
                    $priceQ = $pdo->query("SELECT fuel_type_id, price_per_litre FROM fuel_prices ORDER BY effective_date DESC");
                    while ($pr = $priceQ->fetch()) {
                        if (!isset($fuelPriceMap[$pr['fuel_type_id']])) {
                            $fuelPriceMap[$pr['fuel_type_id']] = $pr['price_per_litre'];
                        }
                    }
                    
                    // Calculate total litres sold and income from pump readings
                    $totalLitres = 0;
                    $totalIncome = 0;
                    $pumpLastReadings = [];
                    
                    // Sort readings by pump and shift for proper calculation
                    usort($todayReadingsList, function($a, $b) {
                        if ($a['pump_number'] != $b['pump_number']) {
                            return strcmp($a['pump_number'], $b['pump_number']);
                        }
                        return $a['shift'] === 'morning' ? -1 : 1;
                    });
                    
                    foreach ($todayReadingsList as $reading) {
                        $litres = 0;
                        if (isset($pumpLastReadings[$reading['pump_id']])) {
                            $litres = $reading['meter_reading'] - $pumpLastReadings[$reading['pump_id']];
                            if ($litres < 0) $litres = 0;
                        }
                        $totalLitres += $litres;
                        $price = isset($fuelPriceMap[$reading['fuel_type_id']]) ? $fuelPriceMap[$reading['fuel_type_id']] : 0;
                        $totalIncome += $litres * $price;
                        $pumpLastReadings[$reading['pump_id']] = $reading['meter_reading'];
                    }
                    
                    // Get active pumps count
                    $activePumpsStmt = $pdo->query("SELECT COUNT(*) as count FROM pumps WHERE is_active = 1");
                    $activePumps = $activePumpsStmt->fetch()['count'];
                    
                    // Get tanks count
                    $tanksStmt = $pdo->query("SELECT COUNT(*) as count FROM tanks");
                    $tanksCount = $tanksStmt->fetch()['count'];
                    ?>
                    <div class="col-md-3">
                        <div class="card stat-card p-3 text-white bg-primary">
                            <div class="d-flex justify-content-between">
                                <div><small>Today's Sales</small><h4>TSh <?php echo number_format($totalIncome); ?></h4></div>
                                <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-3 text-white bg-success">
                            <div class="d-flex justify-content-between">
                                <div><small>Litres Sold</small><h4><?php echo number_format($totalLitres); ?> L</h4></div>
                                <i class="fas fa-gas-pump fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-3 text-white bg-warning">
                            <div class="d-flex justify-content-between">
                                <div><small>Active Pumps</small><h4><?php echo $activePumps; ?></h4></div>
                                <i class="fas fa-pump-soap fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-3 text-white bg-info">
                            <div class="d-flex justify-content-between">
                                <div><small>Tanks</small><h4><?php echo $tanksCount; ?></h4></div>
                                <i class="fas fa-inbox fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card table-card p-3">
                    <h5 class="mb-3"><i class="fas fa-chart-line me-2"></i>Today's Pump Readings</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Pump</th>
                                    <th>Fuel Type</th>
                                    <th>Shift</th>
                                    <th>Type</th>
                                    <th>Reading</th>
                                    <th>Litres Sold</th>
                                    <th>Income</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todayReadingsData)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No readings recorded today</td></tr>
                                <?php else: ?>
                                <?php 
                                // Process readings to calculate dynamic values
                                $processedReadings = [];
                                $pumpLastReading = []; // Track last reading per pump for dynamic calculation
                                
                                // First, sort by pump, shift, and reading type to calculate properly
                                usort($todayReadingsData, function($a, $b) {
                                    if ($a['pump_number'] != $b['pump_number']) {
                                        return strcmp($a['pump_number'], $b['pump_number']);
                                    }
                                    if ($a['shift'] != $b['shift']) {
                                        return $a['shift'] === 'morning' ? -1 : 1;
                                    }
                                    return $a['reading_type'] === 'opening' ? -1 : 1;
                                });
                                
                                foreach ($todayReadingsData as $reading): 
                                    // Get fuel price for this pump
                                    $pumpStmt = $pdo->prepare("SELECT fuel_type_id FROM pumps WHERE id = ?");
                                    $pumpStmt->execute([$reading['pump_id']]);
                                    $pumpInfo = $pumpStmt->fetch();
                                    $pricePerLitre = isset($fuelPrices[$pumpInfo['fuel_type_id']]) ? $fuelPrices[$pumpInfo['fuel_type_id']] : 0;
                                    
                                    // Calculate litres sold dynamically
                                    $litresSold = 0;
                                    if (isset($pumpLastReading[$reading['pump_id']])) {
                                        $litresSold = $reading['meter_reading'] - $pumpLastReading[$reading['pump_id']];
                                        if ($litresSold < 0) $litresSold = 0;
                                    }
                                    
                                    // Calculate income
                                    $income = $litresSold * $pricePerLitre;
                                    
                                    // Update tracking
                                    $pumpLastReading[$reading['pump_id']] = $reading['meter_reading'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reading['pump_number']); ?></td>
                                        <td><?php echo htmlspecialchars($reading['fuel_type']); ?></td>
                                        <td><span class="badge bg-<?php echo $reading['shift'] === 'morning' ? 'warning' : 'info'; ?>"><?php echo ucfirst($reading['shift']); ?></span></td>
                                        <td><span class="badge bg-<?php echo $reading['reading_type'] === 'opening' ? 'success' : 'primary'; ?>"><?php echo ucfirst($reading['reading_type']); ?></span></td>
                                        <td><?php echo number_format($reading['meter_reading']); ?> L</td>
                                        <td><?php echo number_format($litresSold); ?> L</td>
                                        <td>TSh <?php echo number_format($income); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Fuel Prices -->
            <div class="tab-pane fade" id="fuel-prices">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-tag me-2"></i>Update Fuel Price</h5>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="update_price">
                                <div class="mb-3">
                                    <label class="form-label">Fuel Type</label>
                                    <select class="form-select" name="fuel_type_id" required>
                                        <?php foreach ($fuelTypes as $ft): ?>
                                        <option value="<?php echo $ft['id']; ?>"><?php echo htmlspecialchars($ft['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price per Litre (TSh)</label>
                                    <input type="number" class="form-control" name="price" step="0.01" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Update Price</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card table-card p-3">
                            <h5 class="mb-3"><i class="fas fa-list me-2"></i>Current Fuel Prices</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fuel Type</th>
                                            <th>Price per Litre</th>
                                            <th>Effective Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($currentPrices as $price): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($price['fuel_name']); ?></td>
                                            <td><strong>TSh <?php echo number_format($price['price_per_litre'], 2); ?></strong></td>
                                            <td><?php echo date('M j, Y', strtotime($price['effective_date'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pump Readings -->
            <div class="tab-pane fade" id="pump-readings">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-tachometer-alt me-2"></i>Record Reading</h5>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="add_reading">
                                <div class="mb-3">
                                    <label class="form-label">Pump</label>
                                    <select class="form-select" name="pump_id" required>
                                        <?php foreach ($pumps as $pump): ?>
                                        <option value="<?php echo $pump['id']; ?>"><?php echo htmlspecialchars($pump['pump_number']); ?> - <?php echo htmlspecialchars($pump['fuel_type']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Shift</label>
                                    <select class="form-select" name="shift" required>
                                        <option value="morning">Morning Shift</option>
                                        <option value="evening">Evening Shift</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reading Type</label>
                                    <select class="form-select" name="reading_type" required>
                                        <option value="opening">Opening Reading</option>
                                        <option value="closing">Closing Reading</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Meter Reading (Litres)</label>
                                    <input type="number" class="form-control" name="reading" step="0.01" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Record Reading</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card table-card p-3">
                            <h5 class="mb-3"><i class="fas fa-history me-2"></i>Today's Readings</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pump</th>
                                            <th>Shift</th>
                                            <th>Type</th>
                                            <th>Reading</th>
                                            <th>Litres Sold</th>
                                            <th>Income</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($todayReadingsData)): ?>
                                            <tr><td colspan="6" class="text-center text-muted">No readings recorded today</td></tr>
                                        <?php else: ?>
                                        <?php 
                                        // Process readings for pump readings tab
                                        $processedReadingsPR = [];
                                        $pumpLastReadingPR = [];
                                        
                                        usort($todayReadingsData, function($a, $b) {
                                            if ($a['pump_number'] != $b['pump_number']) {
                                                return strcmp($a['pump_number'], $b['pump_number']);
                                            }
                                            if ($a['shift'] != $b['shift']) {
                                                return $a['shift'] === 'morning' ? -1 : 1;
                                            }
                                            return $a['reading_type'] === 'opening' ? -1 : 1;
                                        });
                                        
                                        foreach ($todayReadingsData as $reading): 
                                            // Get fuel price for this pump
                                            $pumpStmt = $pdo->prepare("SELECT fuel_type_id FROM pumps WHERE id = ?");
                                            $pumpStmt->execute([$reading['pump_id']]);
                                            $pumpInfo = $pumpStmt->fetch();
                                            $pricePerLitre = isset($fuelPrices[$pumpInfo['fuel_type_id']]) ? $fuelPrices[$pumpInfo['fuel_type_id']] : 0;
                                            
                                            // Calculate litres sold dynamically
                                            $litresSold = 0;
                                            if (isset($pumpLastReadingPR[$reading['pump_id']])) {
                                                $litresSold = $reading['meter_reading'] - $pumpLastReadingPR[$reading['pump_id']];
                                                if ($litresSold < 0) $litresSold = 0;
                                            }
                                            
                                            // Calculate income
                                            $income = $litresSold * $pricePerLitre;
                                            
                                            // Update tracking
                                            $pumpLastReadingPR[$reading['pump_id']] = $reading['meter_reading'];
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reading['pump_number']); ?></td>
                                            <td><?php echo ucfirst($reading['shift']); ?></td>
                                            <td><span class="badge bg-<?php echo $reading['reading_type'] === 'opening' ? 'success' : 'primary'; ?>"><?php echo ucfirst($reading['reading_type']); ?></span></td>
                                            <td><?php echo number_format($reading['meter_reading']); ?> L</td>
                                            <td><?php echo number_format($litresSold); ?> L</td>
                                            <td>TSh <?php echo number_format($income); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales -->
            <div class="tab-pane fade" id="sales">
                <div class="card table-card p-4">
                    <h5 class="mb-3"><i class="fas fa-cash-register me-2"></i>Record Sales</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="record_sales">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Pump</label>
                                    <select class="form-select" name="pump_id" required>
                                        <?php foreach ($pumps as $pump): ?>
                                        <option value="<?php echo $pump['id']; ?>"><?php echo htmlspecialchars($pump['pump_number']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Attendant</label>
                                    <select class="form-select" name="attendant_id" required>
                                        <?php foreach ($attendants as $att): ?>
                                        <option value="<?php echo $att['id']; ?>"><?php echo htmlspecialchars($att['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Shift</label>
                                    <select class="form-select" name="sales_shift" required>
                                        <option value="morning">Morning</option>
                                        <option value="evening">Evening</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Cash (TSh)</label>
                                    <input type="number" class="form-control" name="cash_sales" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Bank (TSh)</label>
                                    <input type="number" class="form-control" name="bank_sales" step="0.01" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Mobile (TSh)</label>
                                    <input type="number" class="form-control" name="mobile_sales" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Record Sales</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Pumps -->
            <div class="tab-pane fade" id="pumps">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-plus-circle me-2"></i>Create Pump</h5>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="create_pump">
                                <div class="mb-3">
                                    <label class="form-label">Pump Number</label>
                                    <input type="text" class="form-control" name="pump_number" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Fuel Type</label>
                                    <select class="form-select" name="fuel_type_id" required>
                                        <?php foreach ($fuelTypes as $ft): ?>
                                        <option value="<?php echo $ft['id']; ?>"><?php echo htmlspecialchars($ft['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Assign Attendant (Optional)</label>
                                    <select class="form-select" name="attendant_id">
                                        <option value="">-- Select --</option>
                                        <?php foreach ($attendants as $att): ?>
                                        <option value="<?php echo $att['id']; ?>"><?php echo htmlspecialchars($att['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Create Pump</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card table-card p-3">
                            <h5 class="mb-3"><i class="fas fa-list me-2"></i>All Pumps</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pump #</th>
                                            <th>Fuel Type</th>
                                            <th>Attendant</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pumps as $pump): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pump['pump_number']); ?></td>
                                            <td><?php echo htmlspecialchars($pump['fuel_type']); ?></td>
                                            <td><?php echo $pump['attendant_name'] ? htmlspecialchars($pump['attendant_name']) : '<span class="text-muted">Unassigned</span>'; ?></td>
                                            <td><span class="badge bg-<?php echo $pump['is_active'] ? 'success' : 'danger'; ?>"><?php echo $pump['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tanks -->
            <div class="tab-pane fade" id="tanks">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-plus-circle me-2"></i>Create Tank</h5>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="create_tank">
                                <div class="mb-3">
                                    <label class="form-label">Tank Number</label>
                                    <input type="text" class="form-control" name="tank_number" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Fuel Type</label>
                                    <select class="form-select" name="fuel_type_id" required>
                                        <?php foreach ($fuelTypes as $ft): ?>
                                        <option value="<?php echo $ft['id']; ?>"><?php echo htmlspecialchars($ft['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Max Capacity (Litres)</label>
                                    <input type="number" class="form-control" name="max_capacity" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Current Volume (Litres)</label>
                                    <input type="number" class="form-control" name="current_volume" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Create Tank</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card table-card p-3">
                            <h5 class="mb-3"><i class="fas fa-inbox me-2"></i>All Tanks</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tank #</th>
                                            <th>Fuel Type</th>
                                            <th>Capacity</th>
                                            <th>Current Volume</th>
                                            <th>Level</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tanks as $tank): 
                                            $percentage = ($tank['current_volume'] / $tank['max_capacity']) * 100;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($tank['tank_number']); ?></td>
                                            <td><?php echo htmlspecialchars($tank['fuel_type']); ?></td>
                                            <td><?php echo number_format($tank['max_capacity']); ?> L</td>
                                            <td><?php echo number_format($tank['current_volume']); ?> L</td>
                                            <td>
                                                <div class="progress" style="height: 15px; width: 100px;">
                                                    <div class="progress-bar <?php echo $percentage > 50 ? 'bg-success' : ($percentage > 20 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                         style="width: <?php echo $percentage; ?>%">
                                                    </div>
                                                </div>
                                                <small><?php echo number_format($percentage); ?>%</small>
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

            <!-- Tank Refills -->
            <div class="tab-pane fade" id="refills">
                <div class="card table-card p-4">
                    <h5 class="mb-3"><i class="fas fa-truck-loading me-2"></i>Record Tank Refill</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="tank_refill">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Tank</label>
                                    <select class="form-select" name="tank_id" required>
                                        <?php foreach ($tanks as $tank): ?>
                                        <option value="<?php echo $tank['id']; ?>"><?php echo htmlspecialchars($tank['tank_number']); ?> - <?php echo htmlspecialchars($tank['fuel_type']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Volume Added (Litres)</label>
                                    <input type="number" class="form-control" name="refill_volume" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Cost (TSh)</label>
                                    <input type="number" class="form-control" name="cost" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Receipt Image (Optional)</label>
                                    <input type="file" class="form-control" name="receipt_image" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Record Refill</button>
                    </form>
                </div>
            </div>

            <!-- User Management -->
            <div class="tab-pane fade" id="users">
                <div class="card table-card p-3">
                    <h5 class="mb-3"><i class="fas fa-users me-2"></i>User Management</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><span class="badge bg-<?php 
                                        echo $user['role'] === 'manager' ? 'primary' : 
                                            ($user['role'] === 'accountant' ? 'info' : 
                                            ($user['role'] === 'pump_attendant' ? 'warning' : 'secondary'));
                                    ?>"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#roleModal<?php echo $user['id']; ?>"><i class="fas fa-user-cog"></i></button>
                                        
                                        <!-- Edit User Modal -->
                                        <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header"><h5 class="modal-title">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                            <input type="hidden" name="action" value="edit_profile">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <div class="mb-2"><label class="form-label">Full Name</label><input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
                                                            <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                                                            <div class="mb-2"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>"></div>
                                                            <div class="mb-2"><label class="form-label">Address</label><textarea class="form-control" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea></div>
                                                        </div>
                                                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Role Modal -->
                                        <div class="modal fade" id="roleModal<?php echo $user['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header"><h5 class="modal-title">Assign Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
                                                                    <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                                                    <option value="accountant" <?php echo $user['role'] === 'accountant' ? 'selected' : ''; ?>>Accountant</option>
                                                                    <option value="pump_attendant" <?php echo $user['role'] === 'pump_attendant' ? 'selected' : ''; ?>>Pump Attendant</option>
                                                                    <option value="security" <?php echo $user['role'] === 'security' ? 'selected' : ''; ?>>Security</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update Role</button></div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Archived Data -->
            <div class="tab-pane fade" id="archived">
                <div class="card table-card p-3">
                    <h5 class="mb-3"><i class="fas fa-archive me-2"></i>Archived Pump Readings</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Shift</th>
                                    <th>Type</th>
                                    <th>Pump</th>
                                    <th>Fuel</th>
                                    <th>Reading</th>
                                    <th>Litres Sold</th>
                                    <th>Income</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archivedReadings as $reading): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($reading['reading_date'])); ?></td>
                                    <td><span class="badge bg-<?php echo $reading['shift'] === 'morning' ? 'warning' : 'info'; ?>"><?php echo ucfirst($reading['shift']); ?></span></td>
                                    <td><span class="badge bg-<?php echo $reading['reading_type'] === 'opening' ? 'success' : 'primary'; ?>"><?php echo ucfirst($reading['reading_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($reading['pump_number']); ?></td>
                                    <td><?php echo htmlspecialchars($reading['fuel_type']); ?></td>
                                    <td><?php echo number_format($reading['meter_reading']); ?> L</td>
                                    <td><?php echo number_format($reading['litres_sold']); ?> L</td>
                                    <td>TSh <?php echo number_format($reading['income']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Profile -->
            <div class="tab-pane fade" id="profile">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card table-card p-4">
                            <h5 class="mb-3"><i class="fas fa-user-edit me-2"></i>My Profile</h5>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="update_own_profile">
                                <div class="text-center mb-3">
                                    <img src="<?php echo $currentUser['profile_image'] ?? 'https://via.placeholder.com/100'; ?>" class="rounded-circle" width="100" height="100" alt="Profile">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Profile Picture</label>
                                    <input type="file" class="form-control" name="profile_image" accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Profile</button>
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
            const mainContent = document.querySelector('.main-content');
            const sidebarTexts = document.querySelectorAll('.sidebar-text');
            
            // Check if mobile view
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                if (sidebar.classList.contains('collapsed')) {
                    sidebarTexts.forEach(el => el.style.display = 'none');
                } else {
                    sidebarTexts.forEach(el => el.style.display = 'inline');
                }
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
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
            else {}
        });
    </script>
</body>
</html>
