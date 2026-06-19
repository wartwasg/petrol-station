<?php
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    switch ($role) {
        case 'chief_manager':
            header('Location: chief_manager/dashboard.php');
            break;
        case 'manager':
            header('Location: manager/dashboard.php');
            break;
        case 'accountant':
            header('Location: accountant/dashboard.php');
            break;
        case 'pump_attendant':
            header('Location: pump_attendant/dashboard.php');
            break;
        case 'security':
            header('Location: security/dashboard.php');
            break;
        default:
            header('Location: index.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = sanitize($_POST['full_name'] ?? '');
        $role = sanitize($_POST['role'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name) || empty($role)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters long.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!in_array($role, ['chief_manager', 'manager', 'accountant', 'pump_attendant', 'security'])) {
            $error = 'Invalid role selected.';
        } else {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists. Please choose a different one.';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already registered. Please use a different email.';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Insert new user
                    $insertStmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role, phone, address, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    
                    if ($insertStmt->execute([$username, $hashed_password, $email, $full_name, $role, $phone, $address])) {
                        $success = 'Registration successful! You can now <a href="index.php">login</a> with your credentials.';
                        
                        // Log the registration activity
                        $new_user_id = $pdo->lastInsertId();
                        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                        $logStmt->execute([$new_user_id, 'register', 'New user registered', $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        }
    }
}

// Generate CSRF token for the form
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Petrol Station Management System - Register">
    <title>Register - Petrol Station Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e31837;
            --secondary-color: #2c3e50;
            --accent-color: #f8f9fa;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .register-container {
            max-width: 550px;
            width: 100%;
            padding: 20px;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, var(--primary-color), #c41230);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .register-header i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .register-header h2 {
            margin: 0;
            font-weight: 600;
        }
        .register-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .register-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 18px;
            position: relative;
        }
        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .form-group.textarea-group i {
            top: 20px;
            transform: none;
        }
        .form-control, .form-select {
            padding: 12px 15px 12px 45px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(227, 24, 55, 0.1);
        }
        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), #c41230);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(227, 24, 55, 0.4);
        }
        .alert {
            border-radius: 10px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .required-mark {
            color: var(--primary-color);
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-gas-pump"></i>
                <h2>Petrol Station</h2>
                <p>Management System - Registration</p>
            </div>
            <div class="register-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" autocomplete="on">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <i class="fas fa-user"></i>
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Username" required autocomplete="username">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Email Address" required autocomplete="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-id-card"></i>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               placeholder="Full Name" required autocomplete="name">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required autocomplete="new-password">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm Password" required autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <i class="fas fa-user-tag"></i>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="" selected disabled>Select Role</option>
                                    <option value="chief_manager">Chief Manager</option>
                                    <option value="manager">Manager</option>
                                    <option value="accountant">Accountant</option>
                                    
                                    
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <i class="fas fa-phone"></i>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="Phone Number" autocomplete="tel">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group textarea-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <textarea class="form-control" id="address" name="address" 
                                  placeholder="Address (Optional)" rows="2" autocomplete="street-address"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </button>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="index.php">Login here</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
