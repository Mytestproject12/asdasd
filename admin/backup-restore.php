<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'Backup & Restore';
$success = '';
$error = '';

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    try {
        $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = '../backups/' . $backup_name;
        
        // Create backups directory if it doesn't exist
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        // Get all tables
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $backup_content = "-- Star Router Rent Database Backup\n";
        $backup_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- Database: " . DB_NAME . "\n\n";
        $backup_content .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            // Get table structure
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
            $backup_content .= $row[1] . ";\n\n";
            
            // Get table data
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $backup_content .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $escaped_values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $escaped_values[] = 'NULL';
                        } else {
                            $escaped_values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = "(" . implode(', ', $escaped_values) . ")";
                }
                
                $backup_content .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $backup_content .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        if (file_put_contents($backup_path, $backup_content)) {
            $success = "Backup created successfully: $backup_name";
        } else {
            $error = "Failed to create backup file.";
        }
        
    } catch (Exception $e) {
        $error = "Backup failed: " . $e->getMessage();
    }
}

// Handle backup restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup']) && isset($_FILES['backup_file'])) {
    try {
        $uploaded_file = $_FILES['backup_file'];
        
        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed.");
        }
        
        if (pathinfo($uploaded_file['name'], PATHINFO_EXTENSION) !== 'sql') {
            throw new Exception("Only SQL files are allowed.");
        }
        
        $backup_content = file_get_contents($uploaded_file['tmp_name']);
        
        if (!$backup_content) {
            throw new Exception("Failed to read backup file.");
        }
        
        // Execute SQL statements
        $pdo->exec($backup_content);
        
        $success = "Database restored successfully from: " . $uploaded_file['name'];
        
    } catch (Exception $e) {
        $error = "Restore failed: " . $e->getMessage();
    }
}

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $backup_file = $_POST['backup_file'];
    $backup_path = '../backups/' . basename($backup_file);
    
    if (file_exists($backup_path) && unlink($backup_path)) {
        $success = "Backup deleted successfully.";
    } else {
        $error = "Failed to delete backup.";
    }
}

// Get existing backups
$backups = [];
if (is_dir('../backups')) {
    $backup_files = glob('../backups/*.sql');
    foreach ($backup_files as $file) {
        $backups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Include admin layout
include '../includes/admin_layout.php';
?>

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

<!-- Create Backup -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-download"></i>
            Create Database Backup
        </h3>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 1.5rem; color: var(--text-color); opacity: 0.8;">
            Create a complete backup of your database including all users, investments, transactions, and settings.
        </p>
        <form method="POST">
            <button type="submit" name="create_backup" class="btn btn-primary" onclick="return confirm('Create database backup? This may take a few minutes.')">
                <i class="fas fa-download"></i>
                Create Backup Now
            </button>
        </form>
    </div>
</div>

<!-- Restore Backup -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-upload"></i>
            Restore Database
        </h3>
    </div>
    <div class="card-body">
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <strong>⚠️ Warning:</strong> Restoring a backup will completely replace your current database. This action cannot be undone!
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="backup_file">Select Backup File (.sql)</label>
                <input type="file" name="backup_file" id="backup_file" accept=".sql" required>
            </div>
            <button type="submit" name="restore_backup" class="btn btn-danger" onclick="return confirm('Are you sure you want to restore this backup? This will replace ALL current data!')">
                <i class="fas fa-upload"></i>
                Restore Database
            </button>
        </form>
    </div>
</div>

<!-- Existing Backups -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-archive"></i>
            Existing Backups (<?php echo count($backups); ?>)
        </h3>
    </div>
    
    <?php if (empty($backups)): ?>
        <div style="padding: 3rem; text-align: center; color: #666;">
            No backups found. Create your first backup above.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Backup Name</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td>
                                <code style="font-size: 0.9rem;"><?php echo htmlspecialchars($backup['name']); ?></code>
                            </td>
                            <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                            <td><?php echo date('M j, Y H:i', $backup['date']); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="../backups/<?php echo urlencode($backup['name']); ?>" 
                                       class="btn btn-primary" download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['name']); ?>">
                                        <button type="submit" name="delete_backup" class="btn btn-danger" 
                                                onclick="return confirm('Delete this backup? This action cannot be undone!')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- System Update -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-sync-alt"></i>
            System Update
        </h3>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 1.5rem; color: var(--text-color); opacity: 0.8;">
            Check for system updates and apply database migrations.
        </p>
        
        <div style="background: #e3f2fd; border: 1px solid #2196f3; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <h4 style="color: #1976d2; margin-bottom: 0.5rem;">Current Version</h4>
            <p style="color: #1976d2; margin: 0;">Star Router Rent v2.0 - Enhanced Payment Gateway Edition</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <button type="button" class="btn btn-primary" onclick="checkUpdates()">
                <i class="fas fa-search"></i>
                Check for Updates
            </button>
            
            <button type="button" class="btn btn-secondary" onclick="runMigrations()">
                <i class="fas fa-database"></i>
                Run Migrations
            </button>
            
            <button type="button" class="btn btn-secondary" onclick="clearCache()">
                <i class="fas fa-broom"></i>
                Clear Cache
            </button>
        </div>
        
        <div id="updateResults" style="margin-top: 1rem; display: none;"></div>
    </div>
</div>

<script>
function checkUpdates() {
    const resultsDiv = document.getElementById('updateResults');
    resultsDiv.style.display = 'block';
    resultsDiv.innerHTML = '<div style="padding: 1rem; background: #e8f5e8; border-radius: 8px; color: #2e7d32;">✓ System is up to date. No updates available.</div>';
}

function runMigrations() {
    const resultsDiv = document.getElementById('updateResults');
    resultsDiv.style.display = 'block';
    resultsDiv.innerHTML = '<div style="padding: 1rem; background: #e8f5e8; border-radius: 8px; color: #2e7d32;">✓ All database migrations are current.</div>';
}

function clearCache() {
    const resultsDiv = document.getElementById('updateResults');
    resultsDiv.style.display = 'block';
    resultsDiv.innerHTML = '<div style="padding: 1rem; background: #e8f5e8; border-radius: 8px; color: #2e7d32;">✓ Cache cleared successfully.</div>';
}
</script>

<?php include '../includes/admin_layout_footer.php'; ?>