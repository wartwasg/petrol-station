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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter username and password.';
        } else {
            // Prepared statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, role, is_active FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();

                // Log activity
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $logStmt->execute([$user['id'], 'login', 'User logged in', $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

                // Redirect based on role
                switch ($user['role']) {
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
            } else {
                $error = 'Invalid username or password.';
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
    <meta name="description" content="Petrol Station Management System">
    <title>Login - Petrol Station Management</title>
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
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), #c41230);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .login-header i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }
        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .login-body {
            padding: 40px 30px;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .form-control {
            padding: 12px 15px 12px 45px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(227, 24, 55, 0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), #c41230);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(227, 24, 55, 0.4);
        }
        .alert {
            border-radius: 10px;
        }
        .security-badge {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 0.8rem;
        }
        .security-badge i {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-gas-pump"></i>
                <h2>Petrol Station</h2>
                <p>Management System</p>
            </div>
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" autocomplete="on">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="form-group">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Username" required autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>
                
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i> Secure Login
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
