<?php
require_once '../includes/session_config.php';
require_once '../config.php';

// Check if admin is logged in
requireAdminLogin();

$site_name = getSetting('site_name', 'Star Router Rent');
$success = '';
$error = '';

// Handle plan actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $plan_id = generateUUID();
                    $stmt = $pdo->prepare("
                        INSERT INTO investment_plans (id, name, duration_days, daily_rate, min_amount, max_amount, description, features, is_active, is_featured, sort_order, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $features = json_encode(array_filter(explode("\n", $_POST['features'])));
                    $stmt->execute([
                        $plan_id,
                        $_POST['name'],
                        $_POST['duration_days'],
                        $_POST['daily_rate'],
                        $_POST['min_amount'],
                        $_POST['max_amount'],
                        $_POST['description'],
                        $features,
                        isset($_POST['is_active']) ? 1 : 0,
                        isset($_POST['is_featured']) ? 1 : 0,
                        $_POST['sort_order']
                    ]);
                    $success = 'Investment plan created successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to create plan: ' . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE investment_plans 
                        SET name = ?, duration_days = ?, daily_rate = ?, min_amount = ?, max_amount = ?, description = ?, features = ?, is_active = ?, is_featured = ?, sort_order = ?
                        WHERE id = ?
                    ");
                    $features = json_encode(array_filter(explode("\n", $_POST['features'])));
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['duration_days'],
                        $_POST['daily_rate'],
                        $_POST['min_amount'],
                        $_POST['max_amount'],
                        $_POST['description'],
                        $features,
                        isset($_POST['is_active']) ? 1 : 0,
                        isset($_POST['is_featured']) ? 1 : 0,
                        $_POST['sort_order'],
                        $_POST['plan_id']
                    ]);
                    $success = 'Investment plan updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update plan: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM investment_plans WHERE id = ?");
                    $stmt->execute([$_POST['plan_id']]);
                    $success = 'Investment plan deleted successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to delete plan: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all investment plans
$stmt = $pdo->prepare("SELECT * FROM investment_plans ORDER BY sort_order, created_at");
$stmt->execute();
$plans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Plans - <?php echo htmlspecialchars($site_name); ?></title>
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
        }
        
        .top-bar {
            background: var(--bg-color);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px var(--shadow-color);
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
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
        
        .content-area {
            padding: 2rem;
        }
        
        .card {
            background: var(--bg-color);
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            padding: 2rem;
        }
        
        .plan-card {
            background: var(--bg-color);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .plan-card.featured {
            border-color: var(--primary-color);
            box-shadow: 0 10px 25px var(--shadow-color);
        }
        
        .plan-card.featured::before {
            content: 'FEATURED';
            position: absolute;
            top: -10px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .plan-name {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-color);
        }
        
        .plan-rate {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .plan-details {
            margin-bottom: 1.5rem;
        }
        
        .plan-details p {
            margin-bottom: 0.5rem;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .plan-features {
            background: var(--light-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .plan-features ul {
            list-style: none;
            padding: 0;
        }
        
        .plan-features li {
            padding: 0.25rem 0;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .plan-features li::before {
            content: '✓';
            color: var(--success-color);
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .plan-actions {
            display: flex;
            gap: 0.5rem;
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
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-color);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--bg-color);
            border-radius: 15px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-color);
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
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
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
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.active { background: #d4edda; color: #155724; }
        .status-badge.inactive { background: #f8d7da; color: #721c24; }
        
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
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
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
            }
            
            .content-area {
                padding: 1rem;
            }
            
            .plans-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
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
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <div class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">User Management</div>
                    <div class="nav-item">
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            Users
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="referrals.php" class="nav-link">
                            <i class="fas fa-share-alt"></i>
                            Referrals
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Financial</div>
                    <div class="nav-item">
                        <a href="investments.php" class="nav-link">
                            <i class="fas fa-chart-line"></i>
                            Investments
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="payments.php" class="nav-link">
                            <i class="fas fa-credit-card"></i>
                            Payments
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="withdrawals.php" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i>
                            Withdrawals
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Platform</div>
                    <div class="nav-item">
                        <a href="devices.php" class="nav-link">
                            <i class="fas fa-server"></i>
                            Devices
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="plans.php" class="nav-link active">
                            <i class="fas fa-layer-group"></i>
                            Investment Plans
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Content</div>
                    <div class="nav-item">
                        <a href="pages.php" class="nav-link">
                            <i class="fas fa-file-alt"></i>
                            Pages
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="notifications.php" class="nav-link">
                            <i class="fas fa-bell"></i>
                            Notifications
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <div class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="payment-gateways.php" class="nav-link">
                            <i class="fas fa-credit-card"></i>
                            Payment Gateways
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="activity-logs.php" class="nav-link">
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
                <h1 class="page-title">Investment Plans</h1>
                <div class="top-bar-actions">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i>
                        Create Plan
                    </button>
                    <button class="theme-toggle" onclick="toggleTheme()">🌓</button>
                </div>
            </div>
            
            <div class="content-area">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Investment Plans (<?php echo count($plans); ?>)</h3>
                    </div>
                    
                    <div class="plans-grid">
                        <?php foreach ($plans as $plan): ?>
                            <div class="plan-card <?php echo $plan['is_featured'] ? 'featured' : ''; ?>">
                                <div class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></div>
                                <div class="plan-rate"><?php echo $plan['daily_rate']; ?>%</div>
                                <div class="plan-details">
                                    <p><strong>Duration:</strong> <?php echo $plan['duration_days']; ?> days</p>
                                    <p><strong>Range:</strong> $<?php echo number_format($plan['min_amount']); ?> - $<?php echo number_format($plan['max_amount']); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="status-badge <?php echo $plan['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $plan['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <?php if ($plan['description']): ?>
                                    <p style="margin-bottom: 1rem; color: var(--text-color); opacity: 0.8;">
                                        <?php echo htmlspecialchars($plan['description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($plan['features']): ?>
                                    <div class="plan-features">
                                        <ul>
                                            <?php 
                                            $features = json_decode($plan['features'], true);
                                            if ($features) {
                                                foreach ($features as $feature) {
                                                    echo '<li>' . htmlspecialchars($feature) . '</li>';
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="plan-actions">
                                    <button type="button" class="btn btn-primary" onclick="editPlan('<?php echo $plan['id']; ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('Delete this plan? This action cannot be undone!')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Create/Edit Plan Modal -->
    <div class="modal" id="planModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Create Investment Plan</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="planForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="plan_id" id="planId">
                
                <div class="form-group">
                    <label>Plan Name</label>
                    <input type="text" name="name" id="planName" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Duration (Days)</label>
                        <input type="number" name="duration_days" id="planDuration" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Daily Rate (%)</label>
                        <input type="number" name="daily_rate" id="planRate" step="0.1" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Minimum Amount ($)</label>
                        <input type="number" name="min_amount" id="planMinAmount" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Maximum Amount ($)</label>
                        <input type="number" name="max_amount" id="planMaxAmount" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="planDescription"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Features (one per line)</label>
                    <textarea name="features" id="planFeatures" placeholder="Daily returns&#10;24/7 Support&#10;Instant activation"></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="planActive" checked>
                        <label for="planActive">Active</label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_featured" id="planFeatured">
                        <label for="planFeatured">Featured</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" id="planSortOrder" value="0">
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Store plans data for editing
        const plansData = <?php echo json_encode($plans); ?>;
        
        // Theme management
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
            
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = newTheme === 'dark' ? '☀️' : '🌓';
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('admin-theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = savedTheme === 'dark' ? '☀️' : '🌓';
        });
        
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        });
        
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            mobileOverlay.classList.remove('active');
        });
        
        // Modal functions
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Create Investment Plan';
            document.getElementById('formAction').value = 'create';
            document.getElementById('planForm').reset();
            document.getElementById('planActive').checked = true;
            document.getElementById('planModal').classList.add('active');
        }
        
        function editPlan(planId) {
            const plan = plansData.find(p => p.id === planId);
            if (!plan) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Investment Plan';
            document.getElementById('formAction').value = 'update';
            document.getElementById('planId').value = plan.id;
            document.getElementById('planName').value = plan.name;
            document.getElementById('planDuration').value = plan.duration_days;
            document.getElementById('planRate').value = plan.daily_rate;
            document.getElementById('planMinAmount').value = plan.min_amount;
            document.getElementById('planMaxAmount').value = plan.max_amount;
            document.getElementById('planDescription').value = plan.description || '';
            document.getElementById('planSortOrder').value = plan.sort_order;
            document.getElementById('planActive').checked = plan.is_active == 1;
            document.getElementById('planFeatured').checked = plan.is_featured == 1;
            
            // Handle features
            if (plan.features) {
                try {
                    const features = JSON.parse(plan.features);
                    document.getElementById('planFeatures').value = features.join('\n');
                } catch (e) {
                    document.getElementById('planFeatures').value = '';
                }
            }
            
            document.getElementById('planModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('planModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>
</body>
</html>