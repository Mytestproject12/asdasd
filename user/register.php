<?php
require_once '../includes/session_config.php';
require_once '../config.php';
require_once '../includes/NotificationSystem.php';

// Get system settings
$site_name = getSetting('site_name', 'Star Router Rent');
$registration_enabled = getSetting('registration_enabled', 'true');

// Check if registration is enabled
if ($registration_enabled !== 'true') {
    $error = 'Registration is currently disabled. Please contact support.';
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $registration_enabled === 'true') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $country = trim($_POST['country']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $referral_code = trim($_POST['referral_code'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($password)) {
        $error = 'Username, email, first name, last name, and password are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                // Generate unique referral code
                do {
                    $user_referral_code = strtoupper(substr(md5(uniqid()), 0, 8));
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                    $stmt->execute([$user_referral_code]);
                } while ($stmt->fetch());
                
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $pdo->beginTransaction();
                
                try {
                    // Generate UUID for user
                    $user_id = generateUUID();
                    
                    // Insert user
                    $stmt = $pdo->prepare("INSERT INTO users (id, username, email, first_name, last_name, country, password, referral_code, referred_by, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
                    $stmt->execute([$user_id, $username, $email, $first_name, $last_name, $country, $password_hash, $user_referral_code, $referral_code ?: null]);
                    
                    // Handle referral if provided
                    if ($referral_code) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                        $stmt->execute([$referral_code]);
                        $referrer = $stmt->fetch();
                        
                        if ($referrer) {
                            // Create referral relationship
                            $referral_rate = getSetting('referral_level_1_rate', '10.0');
                            $referral_id = generateUUID();
                            $stmt = $pdo->prepare("INSERT INTO referrals (id, referrer_id, referred_id, level, commission_rate, status, created_at) VALUES (?, ?, ?, 1, ?, 'active', NOW())");
                            $stmt->execute([$referral_id, $referrer['id'], $user_id, $referral_rate]);
                        }
                    }
                    
                    $pdo->commit();
                    
                } catch (Exception $e) {
                    $pdo->rollback();
                    throw $e;
                }
                
                // Send welcome email
                try {
                    $notifications = new NotificationSystem($pdo);
                    
                    // Send welcome email
                    $user_data = [
                        'email' => $email,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'username' => $username
                    ];
                    
                    $notifications->sendWelcomeEmail($user_id, $user_data);
                } catch (Exception $e) {
                    error_log('Failed to send welcome email: ' . $e->getMessage());
                    // Don't fail registration if email fails
                }
                
                // Log registration activity
                logActivity($user_id, 'user_registered', 'New user registration completed');
                
                $success = 'Account created successfully! You can now login.';
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
            error_log('Registration error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo htmlspecialchars($site_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #51cf66;
            --success-secondary: #40c057;
            --error-color: #ff6b6b;
            --error-secondary: #ee5a52;
            --bg-color: #ffffff;
            --text-color: #333333;
            --border-color: #e1e5e9;
            --shadow-color: rgba(0, 0, 0, 0.15);
            --input-bg: #fafbfc;
            --light-bg: #f8f9fa;
        }
        
        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333333;
            --shadow-color: rgba(255, 255, 255, 0.1);
            --input-bg: #2d2d2d;
            --light-bg: #2d2d2d;
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
        
        .register-container {
            background: var(--bg-color);
            border-radius: 20px;
            box-shadow: 0 25px 50px var(--shadow-color);
            max-width: 600px;
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
            
            .register-container {
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
            
            .form-group input,
            .form-group select {
                padding: 0.875rem;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .btn {
                padding: 1rem;
                font-size: 1rem;
            }
            
            .benefits {
                padding: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .benefits h3 {
                font-size: 1rem;
            }
            
            .benefits li {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .register-container {
                padding: 1.5rem 1rem;
            }
            
            .header {
                margin-bottom: 1.5rem;
            }
            
            .form-group {
                margin-bottom: 1.25rem;
            }
        }
        .register-container::before {
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
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--input-bg);
            color: var(--text-color);
        }
        
        .form-group input:focus,
        .form-group select:focus {
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
        }
        
        .alert-error {
            background: linear-gradient(135deg, var(--error-color), var(--error-secondary));
            color: white;
        }
        
        .alert-success {
            background: linear-gradient(135deg, var(--success-color), var(--success-secondary));
            color: white;
        }
        
        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .benefits {
            background: var(--light-bg);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .benefits h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        
        .benefits ul {
            list-style: none;
            padding: 0;
        }
        
        .benefits li {
            padding: 0.3rem 0;
            color: var(--text-color);
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .benefits li::before {
            content: '✓';
            color: var(--success-color);
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
    </style>
</head>
<body>
    <div class="register-container">
        <button class="theme-toggle" onclick="toggleTheme()">🌓</button>
        <div class="header">
            <h1>Join <?php echo htmlspecialchars($site_name); ?></h1>
            <p>Start earning daily profits today</p>
        </div>
        
        <div class="benefits">
            <h3>🚀 What You Get:</h3>
            <ul>
                <li>Up to 2% daily returns on investments</li>
                <li>Access to premium router devices</li>
                <li>Multi-level referral commissions</li>
                <li>24/7 customer support</li>
                <li>Instant withdrawals</li>
            </ul>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required 
                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required 
                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="country">Country</label>
                <select id="country" name="country" required>
                    <option value="">Select Country</option>
                    <!-- European Countries -->
                    <option value="AD" <?php echo ($_POST['country'] ?? '') === 'AD' ? 'selected' : ''; ?>>🇦🇩 Andorra</option>
                    <option value="AL" <?php echo ($_POST['country'] ?? '') === 'AL' ? 'selected' : ''; ?>>🇦🇱 Albania</option>
                    <option value="AT" <?php echo ($_POST['country'] ?? '') === 'AT' ? 'selected' : ''; ?>>🇦🇹 Austria</option>
                    <option value="BA" <?php echo ($_POST['country'] ?? '') === 'BA' ? 'selected' : ''; ?>>🇧🇦 Bosnia and Herzegovina</option>
                    <option value="BE" <?php echo ($_POST['country'] ?? '') === 'BE' ? 'selected' : ''; ?>>🇧🇪 Belgium</option>
                    <option value="BG" <?php echo ($_POST['country'] ?? '') === 'BG' ? 'selected' : ''; ?>>🇧🇬 Bulgaria</option>
                    <option value="BY" <?php echo ($_POST['country'] ?? '') === 'BY' ? 'selected' : ''; ?>>🇧🇾 Belarus</option>
                    <option value="CH" <?php echo ($_POST['country'] ?? '') === 'CH' ? 'selected' : ''; ?>>🇨🇭 Switzerland</option>
                    <option value="CY" <?php echo ($_POST['country'] ?? '') === 'CY' ? 'selected' : ''; ?>>🇨🇾 Cyprus</option>
                    <option value="CZ" <?php echo ($_POST['country'] ?? '') === 'CZ' ? 'selected' : ''; ?>>🇨🇿 Czech Republic</option>
                    <option value="US" <?php echo ($_POST['country'] ?? '') === 'US' ? 'selected' : ''; ?>>United States</option>
                    <option value="CA" <?php echo ($_POST['country'] ?? '') === 'CA' ? 'selected' : ''; ?>>Canada</option>
                    <option value="GB" <?php echo ($_POST['country'] ?? '') === 'GB' ? 'selected' : ''; ?>>United Kingdom</option>
                    <option value="DE" <?php echo ($_POST['country'] ?? '') === 'DE' ? 'selected' : ''; ?>>Germany</option>
                    <option value="DK" <?php echo ($_POST['country'] ?? '') === 'DK' ? 'selected' : ''; ?>>🇩🇰 Denmark</option>
                    <option value="EE" <?php echo ($_POST['country'] ?? '') === 'EE' ? 'selected' : ''; ?>>🇪🇪 Estonia</option>
                    <option value="ES" <?php echo ($_POST['country'] ?? '') === 'ES' ? 'selected' : ''; ?>>🇪🇸 Spain</option>
                    <option value="FI" <?php echo ($_POST['country'] ?? '') === 'FI' ? 'selected' : ''; ?>>🇫🇮 Finland</option>
                    <option value="FR" <?php echo ($_POST['country'] ?? '') === 'FR' ? 'selected' : ''; ?>>France</option>
                    <option value="GB" <?php echo ($_POST['country'] ?? '') === 'GB' ? 'selected' : ''; ?>>🇬🇧 United Kingdom</option>
                    <option value="GR" <?php echo ($_POST['country'] ?? '') === 'GR' ? 'selected' : ''; ?>>🇬🇷 Greece</option>
                    <option value="HR" <?php echo ($_POST['country'] ?? '') === 'HR' ? 'selected' : ''; ?>>🇭🇷 Croatia</option>
                    <option value="HU" <?php echo ($_POST['country'] ?? '') === 'HU' ? 'selected' : ''; ?>>🇭🇺 Hungary</option>
                    <option value="IE" <?php echo ($_POST['country'] ?? '') === 'IE' ? 'selected' : ''; ?>>🇮🇪 Ireland</option>
                    <option value="IS" <?php echo ($_POST['country'] ?? '') === 'IS' ? 'selected' : ''; ?>>🇮🇸 Iceland</option>
                    <option value="IT" <?php echo ($_POST['country'] ?? '') === 'IT' ? 'selected' : ''; ?>>🇮🇹 Italy</option>
                    <option value="XK" <?php echo ($_POST['country'] ?? '') === 'XK' ? 'selected' : ''; ?>>🇽🇰 Kosovo</option>
                    <option value="LI" <?php echo ($_POST['country'] ?? '') === 'LI' ? 'selected' : ''; ?>>🇱🇮 Liechtenstein</option>
                    <option value="LT" <?php echo ($_POST['country'] ?? '') === 'LT' ? 'selected' : ''; ?>>🇱🇹 Lithuania</option>
                    <option value="LU" <?php echo ($_POST['country'] ?? '') === 'LU' ? 'selected' : ''; ?>>🇱🇺 Luxembourg</option>
                    <option value="LV" <?php echo ($_POST['country'] ?? '') === 'LV' ? 'selected' : ''; ?>>🇱🇻 Latvia</option>
                    <option value="MC" <?php echo ($_POST['country'] ?? '') === 'MC' ? 'selected' : ''; ?>>🇲🇨 Monaco</option>
                    <option value="MD" <?php echo ($_POST['country'] ?? '') === 'MD' ? 'selected' : ''; ?>>🇲🇩 Moldova</option>
                    <option value="ME" <?php echo ($_POST['country'] ?? '') === 'ME' ? 'selected' : ''; ?>>🇲🇪 Montenegro</option>
                    <option value="MK" <?php echo ($_POST['country'] ?? '') === 'MK' ? 'selected' : ''; ?>>🇲🇰 North Macedonia</option>
                    <option value="MT" <?php echo ($_POST['country'] ?? '') === 'MT' ? 'selected' : ''; ?>>🇲🇹 Malta</option>
                    <option value="NL" <?php echo ($_POST['country'] ?? '') === 'NL' ? 'selected' : ''; ?>>🇳🇱 Netherlands</option>
                    <option value="NO" <?php echo ($_POST['country'] ?? '') === 'NO' ? 'selected' : ''; ?>>🇳🇴 Norway</option>
                    <option value="PL" <?php echo ($_POST['country'] ?? '') === 'PL' ? 'selected' : ''; ?>>🇵🇱 Poland</option>
                    <option value="PT" <?php echo ($_POST['country'] ?? '') === 'PT' ? 'selected' : ''; ?>>🇵🇹 Portugal</option>
                    <option value="RO" <?php echo ($_POST['country'] ?? '') === 'RO' ? 'selected' : ''; ?>>🇷🇴 Romania</option>
                    <option value="RS" <?php echo ($_POST['country'] ?? '') === 'RS' ? 'selected' : ''; ?>>🇷🇸 Serbia</option>
                    <option value="RU" <?php echo ($_POST['country'] ?? '') === 'RU' ? 'selected' : ''; ?>>🇷🇺 Russia</option>
                    <option value="SE" <?php echo ($_POST['country'] ?? '') === 'SE' ? 'selected' : ''; ?>>🇸🇪 Sweden</option>
                    <option value="SI" <?php echo ($_POST['country'] ?? '') === 'SI' ? 'selected' : ''; ?>>🇸🇮 Slovenia</option>
                    <option value="SK" <?php echo ($_POST['country'] ?? '') === 'SK' ? 'selected' : ''; ?>>🇸🇰 Slovakia</option>
                    <option value="SM" <?php echo ($_POST['country'] ?? '') === 'SM' ? 'selected' : ''; ?>>🇸🇲 San Marino</option>
                    <option value="UA" <?php echo ($_POST['country'] ?? '') === 'UA' ? 'selected' : ''; ?>>🇺🇦 Ukraine</option>
                    <option value="AU" <?php echo ($_POST['country'] ?? '') === 'AU' ? 'selected' : ''; ?>>Australia</option>
                    <option value="JP" <?php echo ($_POST['country'] ?? '') === 'JP' ? 'selected' : ''; ?>>Japan</option>
                    <option value="IN" <?php echo ($_POST['country'] ?? '') === 'IN' ? 'selected' : ''; ?>>India</option>
                    <option value="BR" <?php echo ($_POST['country'] ?? '') === 'BR' ? 'selected' : ''; ?>>Brazil</option>
                    <option value="MX" <?php echo ($_POST['country'] ?? '') === 'MX' ? 'selected' : ''; ?>>Mexico</option>
                    <option value="CN" <?php echo ($_POST['country'] ?? '') === 'CN' ? 'selected' : ''; ?>>China</option>
                    <option value="KR" <?php echo ($_POST['country'] ?? '') === 'KR' ? 'selected' : ''; ?>>South Korea</option>
                    <option value="SG" <?php echo ($_POST['country'] ?? '') === 'SG' ? 'selected' : ''; ?>>Singapore</option>
                    <option value="AE" <?php echo ($_POST['country'] ?? '') === 'AE' ? 'selected' : ''; ?>>UAE</option>
                    <option value="ZA" <?php echo ($_POST['country'] ?? '') === 'ZA' ? 'selected' : ''; ?>>South Africa</option>
                    <option value="NG" <?php echo ($_POST['country'] ?? '') === 'NG' ? 'selected' : ''; ?>>Nigeria</option>
                    <option value="EG" <?php echo ($_POST['country'] ?? '') === 'EG' ? 'selected' : ''; ?>>Egypt</option>
                    <option value="OTHER" <?php echo ($_POST['country'] ?? '') === 'OTHER' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="referral_code">Referral Code (Optional)</label>
                <input type="text" id="referral_code" name="referral_code" 
                       value="<?php echo htmlspecialchars($_GET['ref'] ?? $_POST['referral_code'] ?? ''); ?>"
                       placeholder="Enter referral code to earn bonus">
            </div>
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
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