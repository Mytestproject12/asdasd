<?php
require_once '../includes/session_config.php';
require_once '../config.php';

// Get system settings
$site_name = getSetting('site_name', 'Star Router Rent');
$maintenance_mode = getSetting('maintenance_mode', 'false');

// Check if site is in maintenance mode
if ($maintenance_mode === 'true') {
    $error = 'Site is currently under maintenance. Please try again later.';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $maintenance_mode !== 'true') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, email, password, status FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'active') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Log login activity
                    logActivity($user['id'], 'user_login', 'User logged in successfully');
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Your account is suspended. Please contact support.';
                }
            } else {
                $error = 'Invalid username or password.';
                // Log failed login attempt
                if ($user) {
                    logActivity($user['id'], 'login_failed', 'Failed login attempt');
                }
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($site_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --bg-color: #ffffff;
            --text-color: #333333;
            --border-color: #e1e5e9;
            --shadow-color: rgba(0, 0, 0, 0.15);
            --input-bg: #fafbfc;
        }
        
        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333333;
            --shadow-color: rgba(255, 255, 255, 0.1);
            --input-bg: #2d2d2d;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            transition: background-color 0.3s ease;
        }
        
        .login-container {
            background: var(--bg-color);
            border-radius: 20px;
            box-shadow: 0 25px 50px var(--shadow-color);
            max-width: 500px;
            width: 100%;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .theme-toggle {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }
        
        .theme-toggle:hover {
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .login-container {
                padding: 2rem 1.5rem;
                margin: 0;
                border-radius: 15px;
                max-width: 100%;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .header p {
                font-size: 0.9rem;
            }
            
            .form-group input {
                padding: 0.875rem;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .btn {
                padding: 1rem;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem 1rem;
            }
            
            .header {
                margin-bottom: 1.5rem;
            }
            
            .form-group {
                margin-bottom: 1.25rem;
            }
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: var(--text-color);
            opacity: 0.7;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--input-bg);
            color: var(--text-color);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--bg-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px var(--shadow-color);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .register-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        
        .forgot-password a {
            color: var(--text-color);
            opacity: 0.7;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
    </style>
</head>
<body>
    <div class="login-container">
        <button class="theme-toggle" onclick="toggleTheme()">🌓</button>
        <div class="header">
            <h1>Welcome Back</h1>
            <p>Login to your <?php echo htmlspecialchars($site_name); ?> account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="forgot-password">
            <a href="forgot-password.php">Forgot your password?</a>
        </div>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
    
    <script>
        // Theme management
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update theme toggle icon
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = newTheme === 'dark' ? '☀️' : '🌓';
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Update theme toggle icon
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = savedTheme === 'dark' ? '☀️' : '🌓';
        });
    </script>
</body>
</html>