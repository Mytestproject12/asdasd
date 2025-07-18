<?php
/**
 * Admin Layout Component
 * Provides consistent sidebar and layout for all admin pages
 */

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get admin data
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

$site_name = getSetting('site_name', 'Star Router Rent');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo htmlspecialchars($site_name); ?> Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --bg-color: #ffffff;
            --text-color: #333333;
            --border-color: #e9ecef;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --sidebar-width: 280px;
        }
        
        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333333;
            --shadow-color: rgba(255, 255, 255, 0.1);
            --light-color: #2d2d2d;
            --dark-color: #0d1117;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
            transform: translateX(0);
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-section {
            margin-bottom: 2rem;
        }
        
        .nav-section-title {
            padding: 0 1.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 0.5rem;
        }
        
        .nav-item {
            margin-bottom: 0.25rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 1rem;
            font-size: 1.1rem;
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            position: relative;
        }
        
        .top-bar {
            background: var(--bg-color);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px var(--shadow-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .theme-toggle {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }
        
        .theme-toggle:hover {
            background: var(--border-color);
            transform: translateY(-2px);
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .content-area {
            padding: 2rem;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .mobile-menu-btn {
            display: none;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .mobile-overlay.active {
            opacity: 1;
        }
        
        /* Desktop auto-hide functionality */
        .sidebar-toggle {
            position: fixed;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            z-index: 1003;
            display: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar-toggle:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-toggle.show {
            display: block;
        }
        
        /* Responsive breakpoints */
        @media (max-width: 1200px) {
            :root {
                --sidebar-width: 260px;
            }
        }
        
        @media (max-width: 1024px) {
            :root {
                --sidebar-width: 240px;
            }
            
            .content-area {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .mobile-overlay.active {
                display: block;
            }
            
            .top-bar {
                padding: 1rem;
                margin-left: 4rem;
                position: relative;
            }
            
            .content-area {
                padding: 1rem;
            }
            
            .admin-info {
                display: none;
            }
            
            .page-title {
                font-size: 1.25rem;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
            }
            
            .top-bar {
                padding: 0.75rem;
                margin-left: 3.5rem;
            }
            
            .content-area {
                padding: 0.75rem;
            }
            
            .page-title {
                font-size: 1.1rem;
            }
        }
        
        /* Auto-hide for desktop */
        @media (min-width: 769px) {
            .sidebar.auto-hide {
                transform: translateX(-220px);
            }
            
            .sidebar.auto-hide:hover {
                transform: translateX(0);
            }
            
            .main-content.sidebar-hidden {
                margin-left: 60px;
            }
            
            .sidebar.auto-hide .nav-link {
                padding: 1rem 0.75rem;
                justify-content: center;
            }
            
            .sidebar.auto-hide .nav-link i {
                margin-right: 0;
            }
            
            .sidebar.auto-hide .nav-link span {
                display: none;
            }
            
            .sidebar.auto-hide .sidebar-header {
                text-align: center;
                padding: 1rem;
            }
            
            .sidebar.auto-hide .sidebar-header h2,
            .sidebar.auto-hide .sidebar-header p {
                display: none;
            }
            
            .sidebar.auto-hide .nav-section-title {
                display: none;
            }
        }
        
        /* Common styles for all admin pages */
        .card {
            background: var(--bg-color);
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 2rem;
            width: 100%;
            max-width: 100%;
        }
        
        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--light-color);
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .card-body {
            padding: 2rem;
            overflow-x: auto;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-secondary {
            background: var(--light-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-color);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-color);
            color: var(--text-color);
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }
        
        .table th {
            background: var(--light-color);
            font-weight: 600;
            color: var(--text-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table tbody tr:hover {
            background: var(--light-color);
        }
        
        /* Table responsiveness */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }
        
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: var(--light-color);
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--text-color);
        }
        
        /* Grid responsiveness */
        .stats-grid,
        .dashboard-grid,
        .content-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        
        @media (max-width: 768px) {
            .stats-grid,
            .dashboard-grid,
            .content-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .card-header {
                padding: 1rem 1.5rem;
                flex-direction: column;
                align-items: stretch;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .card-header {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.8rem;
            }
            
            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
        }
        
        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid var(--border-color);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Accessibility improvements */
        @media (prefers-reduced-motion: reduce) {
            .sidebar,
            .main-content,
            .mobile-overlay,
            .sidebar-toggle,
            .nav-link,
            .btn {
                transition: none;
            }
            
            .nav-link::before {
                display: none;
            }
        }
        
        /* Focus states */
        .nav-link:focus,
        .mobile-menu-btn:focus,
        .sidebar-toggle:focus,
        .btn:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .sidebar {
                border-right: 2px solid white;
            }
            
            .nav-link {
                border: 1px solid transparent;
            }
            
            .nav-link:hover,
            .nav-link.active {
                border-color: white;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="mobile-overlay" id="mobileOverlay"></div>
        
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-star"></i> Admin Panel</h2>
                <p>Welcome, <?php echo htmlspecialchars($admin['username']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <div class="nav-item">
                        <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">User Management</div>
                    <div class="nav-item">
                        <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            Users
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="referrals.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'referrals.php' ? 'active' : ''; ?>">
                            <i class="fas fa-share-alt"></i>
                            Referrals
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Financial</div>
                    <div class="nav-item">
                        <a href="investments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'investments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i>
                            Investments
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="payments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-credit-card"></i>
                            Payments
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="withdrawals.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'withdrawals.php' ? 'active' : ''; ?>">
                            <i class="fas fa-money-bill-wave"></i>
                            Withdrawals
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Platform</div>
                    <div class="nav-item">
                        <a href="devices.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'devices.php' ? 'active' : ''; ?>">
                            <i class="fas fa-server"></i>
                            Devices
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="plans.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'plans.php' ? 'active' : ''; ?>">
                            <i class="fas fa-layer-group"></i>
                            Investment Plans
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Content</div>
                    <div class="nav-item">
                        <a href="pages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pages.php' ? 'active' : ''; ?>">
                            <i class="fas fa-file-alt"></i>
                            Pages
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="notifications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                            <i class="fas fa-bell"></i>
                            Notifications
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="email-templates.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'email-templates.php' ? 'active' : ''; ?>">
                            <i class="fas fa-envelope"></i>
                            Email Templates
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="dashboard-settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard-settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard Settings
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <div class="nav-item">
                        <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="payment-gateways.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payment-gateways.php' ? 'active' : ''; ?>">
                            <i class="fas fa-credit-card"></i>
                            Payment Gateways
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="backup-restore.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'backup-restore.php' ? 'active' : ''; ?>">
                            <i class="fas fa-database"></i>
                            Backup & Restore
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="activity-logs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'activity-logs.php' ? 'active' : ''; ?>">
                            <i class="fas fa-history"></i>
                            Activity Logs
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title"><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></h1>
                <div class="top-bar-actions">
                    <button class="theme-toggle" onclick="toggleTheme()">🌓</button>
                    <div class="admin-info">
                        <div class="admin-avatar">
                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($admin['username']); ?></div>
                            <div style="font-size: 0.8rem; opacity: 0.7;"><?php echo ucfirst($admin['role']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-area">