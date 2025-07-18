<?php
require_once '../includes/session_config.php';
require_once '../config.php';

// Check if admin is logged in
requireAdminLogin();

$site_name = getSetting('site_name', 'Star Router Rent');
$success = '';
$error = '';

// Handle device actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $device_id = generateUUID();
                    $stmt = $pdo->prepare("
                        INSERT INTO devices (id, device_id, name, model, location, status, daily_rate, setup_fee, max_speed_down, max_speed_up, uptime_percentage, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $device_id,
                        $_POST['device_id'],
                        $_POST['name'],
                        $_POST['model'],
                        $_POST['location'],
                        $_POST['status'],
                        $_POST['daily_rate'],
                        $_POST['setup_fee'],
                        $_POST['max_speed_down'],
                        $_POST['max_speed_up'],
                        $_POST['uptime_percentage']
                    ]);
                    $success = 'Device created successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to create device: ' . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE devices 
                        SET device_id = ?, name = ?, model = ?, location = ?, status = ?, daily_rate = ?, setup_fee = ?, max_speed_down = ?, max_speed_up = ?, uptime_percentage = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['device_id'],
                        $_POST['name'],
                        $_POST['model'],
                        $_POST['location'],
                        $_POST['status'],
                        $_POST['daily_rate'],
                        $_POST['setup_fee'],
                        $_POST['max_speed_down'],
                        $_POST['max_speed_up'],
                        $_POST['uptime_percentage'],
                        $_POST['id']
                    ]);
                    $success = 'Device updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update device: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
                    $stmt->execute([$_POST['device_id']]);
                    $success = 'Device deleted successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to delete device: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all devices
$stmt = $pdo->prepare("SELECT * FROM devices ORDER BY location, name");
$stmt->execute();
$devices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devices Management - <?php echo htmlspecialchars($site_name); ?></title>
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
        
        .devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            padding: 2rem;
        }
        
        .device-card {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .device-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px var(--shadow-color);
        }
        
        .device-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .device-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .device-location {
            color: var(--text-color);
            opacity: 0.7;
            font-size: 0.9rem;
        }
        
        .device-rate {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .device-specs {
            background: var(--light-color);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .spec-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .spec-row:last-child {
            margin-bottom: 0;
        }
        
        .spec-label {
            color: var(--text-color);
            opacity: 0.7;
            font-size: 0.9rem;
        }
        
        .spec-value {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .uptime-bar {
            background: var(--border-color);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .uptime-fill {
            background: linear-gradient(90deg, var(--success-color), #20c997);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .device-actions {
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
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.available { background: #d4edda; color: #155724; }
        .status-badge.rented { background: #fff3cd; color: #856404; }
        .status-badge.maintenance { background: #f8d7da; color: #721c24; }
        .status-badge.offline { background: #f5c6cb; color: #721c24; }
        
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
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-color);
            color: var(--text-color);
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
            
            .devices-grid {
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
                        <a href="devices.php" class="nav-link active">
                            <i class="fas fa-server"></i>
                            Devices
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="plans.php" class="nav-link">
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
                <h1 class="page-title">Devices Management</h1>
                <div class="top-bar-actions">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i>
                        Add Device
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
                        <h3 class="card-title">All Devices (<?php echo count($devices); ?>)</h3>
                    </div>
                    
                    <div class="devices-grid">
                        <?php foreach ($devices as $device): ?>
                            <div class="device-card">
                                <div class="device-header">
                                    <div>
                                        <div class="device-name"><?php echo htmlspecialchars($device['name']); ?></div>
                                        <div class="device-location">📍 <?php echo htmlspecialchars($device['location']); ?></div>
                                    </div>
                                    <div class="device-rate">$<?php echo number_format($device['daily_rate'], 2); ?>/day</div>
                                </div>
                                
                                <div class="device-specs">
                                    <div class="spec-row">
                                        <span class="spec-label">Device ID:</span>
                                        <span class="spec-value"><?php echo htmlspecialchars($device['device_id']); ?></span>
                                    </div>
                                    <div class="spec-row">
                                        <span class="spec-label">Model:</span>
                                        <span class="spec-value"><?php echo htmlspecialchars($device['model']); ?></span>
                                    </div>
                                    <div class="spec-row">
                                        <span class="spec-label">Download Speed:</span>
                                        <span class="spec-value"><?php echo $device['max_speed_down']; ?> Mbps</span>
                                    </div>
                                    <div class="spec-row">
                                        <span class="spec-label">Upload Speed:</span>
                                        <span class="spec-value"><?php echo $device['max_speed_up']; ?> Mbps</span>
                                    </div>
                                    <div class="spec-row">
                                        <span class="spec-label">Uptime:</span>
                                        <span class="spec-value"><?php echo $device['uptime_percentage']; ?>%</span>
                                    </div>
                                    <div class="uptime-bar">
                                        <div class="uptime-fill" style="width: <?php echo $device['uptime_percentage']; ?>%"></div>
                                    </div>
                                    <div class="spec-row" style="margin-top: 1rem;">
                                        <span class="spec-label">Status:</span>
                                        <span class="status-badge <?php echo $device['status']; ?>">
                                            <?php echo ucfirst($device['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="device-actions">
                                    <button type="button" class="btn btn-primary" onclick="editDevice('<?php echo $device['id']; ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('Delete this device? This action cannot be undone!')">
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
    
    <!-- Create/Edit Device Modal -->
    <div class="modal" id="deviceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Device</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="deviceForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="deviceIdHidden">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Device ID</label>
                        <input type="text" name="device_id" id="deviceId" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="deviceStatus" required>
                            <option value="available">Available</option>
                            <option value="rented">Rented</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="offline">Offline</option>
                            <option value="reserved">Reserved</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Device Name</label>
                    <input type="text" name="name" id="deviceName" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" name="model" id="deviceModel" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" id="deviceLocation" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Daily Rate ($)</label>
                        <input type="number" name="daily_rate" id="deviceDailyRate" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Setup Fee ($)</label>
                        <input type="number" name="setup_fee" id="deviceSetupFee" step="0.01" value="0">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Download Speed (Mbps)</label>
                        <input type="number" name="max_speed_down" id="deviceSpeedDown" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Speed (Mbps)</label>
                        <input type="number" name="max_speed_up" id="deviceSpeedUp" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Uptime (%)</label>
                        <input type="number" name="uptime_percentage" id="deviceUptime" step="0.01" min="0" max="100" value="99.9" required>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Device
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Store devices data for editing
        const devicesData = <?php echo json_encode($devices); ?>;
        
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
            document.getElementById('modalTitle').textContent = 'Add Device';
            document.getElementById('formAction').value = 'create';
            document.getElementById('deviceForm').reset();
            document.getElementById('deviceUptime').value = '99.9';
            document.getElementById('deviceSetupFee').value = '0';
            document.getElementById('deviceModal').classList.add('active');
        }
        
        function editDevice(deviceId) {
            const device = devicesData.find(d => d.id === deviceId);
            if (!device) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Device';
            document.getElementById('formAction').value = 'update';
            document.getElementById('deviceIdHidden').value = device.id;
            document.getElementById('deviceId').value = device.device_id;
            document.getElementById('deviceName').value = device.name;
            document.getElementById('deviceModel').value = device.model;
            document.getElementById('deviceLocation').value = device.location;
            document.getElementById('deviceStatus').value = device.status;
            document.getElementById('deviceDailyRate').value = device.daily_rate;
            document.getElementById('deviceSetupFee').value = device.setup_fee;
            document.getElementById('deviceSpeedDown').value = device.max_speed_down;
            document.getElementById('deviceSpeedUp').value = device.max_speed_up;
            document.getElementById('deviceUptime').value = device.uptime_percentage;
            
            document.getElementById('deviceModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('deviceModal').classList.remove('active');
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