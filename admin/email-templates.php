<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'Email Templates';
$success = '';
$error = '';

// Handle template actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                if (isset($_POST['template_id']) && isset($_POST['subject']) && isset($_POST['body'])) {
                    try {
                        $stmt = $pdo->prepare("
                            UPDATE email_templates 
                            SET subject = ?, body = ?, is_active = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $_POST['subject'],
                            $_POST['body'],
                            isset($_POST['is_active']) ? 1 : 0,
                            $_POST['template_id']
                        ]);
                        $success = 'Email template updated successfully!';
                    } catch (Exception $e) {
                        $error = 'Failed to update template: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'create':
                if (isset($_POST['template_key']) && isset($_POST['subject']) && isset($_POST['body'])) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO email_templates (template_key, subject, body, is_active) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['template_key'],
                            $_POST['subject'],
                            $_POST['body'],
                            isset($_POST['is_active']) ? 1 : 0
                        ]);
                        $success = 'Email template created successfully!';
                    } catch (Exception $e) {
                        $error = 'Failed to create template: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                if (isset($_POST['template_id'])) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM email_templates WHERE id = ?");
                        if ($stmt->execute([$_POST['template_id']])) {
                            $success = 'Email template deleted successfully!';
                        } else {
                            $error = 'Failed to delete template.';
                        }
                    } catch (Exception $e) {
                        $error = 'Failed to delete template: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get all email templates
$stmt = $pdo->prepare("SELECT * FROM email_templates ORDER BY template_key");
$stmt->execute();
$templates = $stmt->fetchAll();

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

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Email Templates (<?php echo count($templates); ?>)</h3>
        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="fas fa-plus"></i>
            Create Template
        </button>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Template Key</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: #666;">
                            No email templates found. Create your first template!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                            <td>
                                <code style="font-size: 0.9rem;"><?php echo htmlspecialchars($template['template_key']); ?></code>
                            </td>
                            <td>
                                <div style="font-weight: 600; max-width: 300px; word-wrap: break-word;">
                                    <?php echo htmlspecialchars($template['subject']); ?>
                                </div>
                            </td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; 
                                    <?php echo $template['is_active'] ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;'; ?>">
                                    <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($template['created_at'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($template['updated_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-primary" onclick="editTemplate(<?php echo $template['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('Delete this template? This action cannot be undone!')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Template Modal -->
<div style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 2000;" id="templateModal">
    <div style="display: flex; align-items: center; justify-content: center; height: 100%; padding: 2rem;">
        <div style="background: var(--bg-color); border-radius: 15px; padding: 2rem; max-width: 900px; width: 100%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--text-color);" id="modalTitle">Create Email Template</h3>
                <button type="button" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST" id="templateForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="template_id" id="templateId">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Template Key</label>
                        <input type="text" name="template_key" id="templateKey" required>
                        <div style="font-size: 0.8rem; color: var(--text-color); opacity: 0.7; margin-top: 0.25rem;">
                            Unique identifier (e.g., welcome, deposit_confirmed)
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="is_active" id="templateActive" checked>
                            <label for="templateActive">Active</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" id="templateSubject" required>
                    <div style="font-size: 0.8rem; color: var(--text-color); opacity: 0.7; margin-top: 0.25rem;">
                        Use variables like {{site_name}}, {{username}}, {{amount}}
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Body (HTML)</label>
                    <div id="email-editor-container" style="border: 1px solid var(--border-color); border-radius: 8px; min-height: 400px;">
                        <div id="email-editor-toolbar" style="background: var(--light-color); padding: 0.5rem; border-bottom: 1px solid var(--border-color); display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" onclick="formatEmailText('bold')" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;"><b>B</b></button>
                            <button type="button" onclick="formatEmailText('italic')" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;"><i>I</i></button>
                            <button type="button" onclick="insertEmailHeading()" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;">H2</button>
                            <button type="button" onclick="insertEmailList()" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;">List</button>
                            <button type="button" onclick="insertEmailLink()" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;">Link</button>
                            <button type="button" onclick="insertVariable()" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;">Variable</button>
                        </div>
                        <div id="email-editor-content" contenteditable="true" style="padding: 1rem; min-height: 350px; outline: none;" onkeyup="updateEmailTextarea()"></div>
                    </div>
                    <textarea name="body" id="templateBody" style="display: none;" required></textarea>
                    <div style="font-size: 0.8rem; color: var(--text-color); opacity: 0.7; margin-top: 0.25rem;">
                        Available variables: {{site_name}}, {{username}}, {{first_name}}, {{last_name}}, {{email}}, {{amount}}, {{currency}}, {{transaction_id}}, {{date}}, {{site_url}}, {{support_email}}
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Store templates data for editing
    const templatesData = <?php echo json_encode($templates); ?>;
    
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Create Email Template';
        document.getElementById('formAction').value = 'create';
        document.getElementById('templateForm').reset();
        document.getElementById('templateActive').checked = true;
        document.getElementById('email-editor-content').innerHTML = '';
        document.getElementById('templateModal').style.display = 'block';
    }
    
    function editTemplate(templateId) {
        const template = templatesData.find(t => t.id == templateId);
        if (!template) return;
        
        document.getElementById('modalTitle').textContent = 'Edit Email Template';
        document.getElementById('formAction').value = 'update';
        document.getElementById('templateId').value = template.id;
        document.getElementById('templateKey').value = template.template_key;
        document.getElementById('templateKey').readOnly = true;
        document.getElementById('templateSubject').value = template.subject;
        document.getElementById('email-editor-content').innerHTML = template.body;
        document.getElementById('templateBody').value = template.body;
        document.getElementById('templateActive').checked = template.is_active == 1;
        
        document.getElementById('templateModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('templateModal').style.display = 'none';
        document.getElementById('templateKey').readOnly = false;
    }
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.id === 'templateModal') {
            closeModal();
        }
    });
    
    // Email editor functions
    function formatEmailText(command) {
        document.execCommand(command, false, null);
        updateEmailTextarea();
    }
    
    function insertEmailHeading() {
        const text = prompt('Enter heading text:');
        if (text) {
            document.execCommand('insertHTML', false, '<h2>' + text + '</h2>');
            updateEmailTextarea();
        }
    }
    
    function insertEmailList() {
        document.execCommand('insertUnorderedList', false, null);
        updateEmailTextarea();
    }
    
    function insertEmailLink() {
        const url = prompt('Enter URL:');
        const text = prompt('Enter link text:');
        if (url && text) {
            document.execCommand('insertHTML', false, '<a href="' + url + '">' + text + '</a>');
            updateEmailTextarea();
        }
    }
    
    function insertVariable() {
        const variables = [
            '{{site_name}}', '{{username}}', '{{first_name}}', '{{last_name}}', 
            '{{email}}', '{{amount}}', '{{currency}}', '{{transaction_id}}', 
            '{{date}}', '{{site_url}}', '{{support_email}}'
        ];
        
        const variable = prompt('Choose a variable:\n' + variables.join('\n'));
        if (variable && variables.includes(variable)) {
            document.execCommand('insertHTML', false, variable);
            updateEmailTextarea();
        }
    }
    
    function updateEmailTextarea() {
        document.getElementById('templateBody').value = document.getElementById('email-editor-content').innerHTML;
    }
    
    // Initialize editor content
    document.addEventListener('DOMContentLoaded', function() {
        updateEmailTextarea();
    });
</script>

<?php include '../includes/admin_layout_footer.php'; ?>