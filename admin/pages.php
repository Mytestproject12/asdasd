<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'Pages Management';
$success = '';
$error = '';

// Handle page actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                if (isset($_POST['title']) && isset($_POST['slug']) && isset($_POST['content'])) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO webpages (title, slug, meta_title, meta_description, content, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $_POST['title'],
                            $_POST['slug'],
                            $_POST['meta_title'] ?? '',
                            $_POST['meta_description'] ?? '',
                            $_POST['content'],
                            $_POST['status'] ?? 'draft'
                        ]);
                        $success = 'Page created successfully!';
                        
                        // Refresh pages data
                        $stmt = $pdo->prepare("SELECT * FROM webpages ORDER BY created_at DESC");
                        $stmt->execute();
                        $pages = $stmt->fetchAll();
                    } catch (Exception $e) {
                        $error = 'Failed to create page: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'update':
                if (isset($_POST['page_id']) && isset($_POST['title']) && isset($_POST['content'])) {
                    try {
                        $stmt = $pdo->prepare("
                            UPDATE webpages 
                            SET title = ?, slug = ?, meta_title = ?, meta_description = ?, content = ?, status = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $_POST['title'],
                            $_POST['slug'],
                            $_POST['meta_title'] ?? '',
                            $_POST['meta_description'] ?? '',
                            $_POST['content'],
                            $_POST['status'] ?? 'draft',
                            $_POST['page_id']
                        ]);
                        $success = 'Page updated successfully!';
                        
                        // Refresh pages data
                        $stmt = $pdo->prepare("SELECT * FROM webpages ORDER BY created_at DESC");
                        $stmt->execute();
                        $pages = $stmt->fetchAll();
                    } catch (Exception $e) {
                        $error = 'Failed to update page: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                if (isset($_POST['page_id'])) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM webpages WHERE id = ?");
                        if ($stmt->execute([$_POST['page_id']])) {
                            $success = 'Page deleted successfully!';
                            
                            // Refresh pages data
                            $stmt = $pdo->prepare("SELECT * FROM webpages ORDER BY created_at DESC");
                            $stmt->execute();
                            $pages = $stmt->fetchAll();
                        } else {
                            $error = 'Failed to delete page.';
                        }
                    } catch (Exception $e) {
                        $error = 'Failed to delete page: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get all pages
$stmt = $pdo->prepare("SELECT * FROM webpages ORDER BY created_at DESC");
$stmt->execute();
$pages = $stmt->fetchAll();

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
        <h3 class="card-title">Website Pages (<?php echo count($pages); ?>)</h3>
        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="fas fa-plus"></i>
            Create Page
        </button>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: #666;">
                            No pages found. Create your first page!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($page['title']); ?></div>
                                <?php if ($page['meta_title']): ?>
                                    <div style="font-size: 0.8rem; opacity: 0.7;">SEO: <?php echo htmlspecialchars($page['meta_title']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size: 0.9rem;">/<?php echo htmlspecialchars($page['slug']); ?></code>
                            </td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; 
                                    <?php 
                                    echo match($page['status']) {
                                        'published' => 'background: #d4edda; color: #155724;',
                                        'draft' => 'background: #fff3cd; color: #856404;',
                                        'private' => 'background: #f8d7da; color: #721c24;',
                                        default => 'background: #f8f9fa; color: #6c757d;'
                                    };
                                    ?>">
                                    <?php echo ucfirst($page['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($page['created_at'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($page['updated_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-primary" onclick="editPage(<?php echo $page['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($page['status'] === 'published'): ?>
                                        <a href="../pages.php?page=<?php echo $page['slug']; ?>" target="_blank" class="btn btn-secondary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('Delete this page? This action cannot be undone!')">
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

<!-- Create/Edit Page Modal -->
<div style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 2000;" id="pageModal">
    <div style="display: flex; align-items: center; justify-content: center; height: 100%; padding: 2rem;">
        <div style="background: var(--bg-color); border-radius: 15px; padding: 2rem; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--text-color);" id="modalTitle">Create Page</h3>
                <button type="button" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST" id="pageForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="page_id" id="pageId">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Page Title</label>
                        <input type="text" name="title" id="pageTitle" required>
                    </div>
                    
                    <div class="form-group">
                        <label>URL Slug</label>
                        <input type="text" name="slug" id="pageSlug" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>SEO Title</label>
                        <input type="text" name="meta_title" id="pageMetaTitle">
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="pageStatus">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="private">Private</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>SEO Description</label>
                    <textarea name="meta_description" id="pageMetaDescription" style="min-height: 60px;"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Content</label>
                    <div id="editor-container" style="border: 1px solid var(--border-color); border-radius: 8px; min-height: 300px;">
                        <div id="editor-toolbar" style="background: var(--light-color); padding: 0.5rem; border-bottom: 1px solid var(--border-color); display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" onclick="formatText('bold')" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;"><b>B</b></button>
                            <button type="button" onclick="formatText('italic')" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;"><i>I</i></button>
                            <button type="button" onclick="formatText('underline')" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;"><u>U</u></button>
                            <button type="button" onclick="insertHeading()" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;">H2</button>
                            <button type="button" onclick="insertList()" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;">List</button>
                            <button type="button" onclick="insertLink()" style="padding: 0.25rem 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); border-radius: 4px; cursor: pointer;">Link</button>
                        </div>
                        <div id="editor-content" contenteditable="true" style="padding: 1rem; min-height: 250px; outline: none;" onkeyup="updateTextarea()"></div>
                    </div>
                    <textarea name="content" id="pageContent" style="display: none;" required></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Page
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Store pages data for editing
    const pagesData = <?php echo json_encode($pages); ?>;
    
    // Auto-generate slug from title
    document.getElementById('pageTitle').addEventListener('input', function() {
        if (document.getElementById('formAction').value === 'create') {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            document.getElementById('pageSlug').value = slug;
        }
    });
    
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Create Page';
        document.getElementById('formAction').value = 'create';
        document.getElementById('pageForm').reset();
        document.getElementById('pageModal').style.display = 'block';
    }
    
    function editPage(pageId) {
        const page = pagesData.find(p => p.id == pageId);
        if (!page) return;
        
        document.getElementById('modalTitle').textContent = 'Edit Page';
        document.getElementById('formAction').value = 'update';
        document.getElementById('pageId').value = page.id;
        document.getElementById('pageTitle').value = page.title;
        document.getElementById('pageSlug').value = page.slug;
        document.getElementById('pageMetaTitle').value = page.meta_title || '';
        document.getElementById('pageMetaDescription').value = page.meta_description || '';
        document.getElementById('editor-content').innerHTML = page.content || '';
        document.getElementById('pageContent').value = page.content || '';
        document.getElementById('pageStatus').value = page.status;
        
        document.getElementById('pageModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('pageModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.id === 'pageModal') {
            closeModal();
        }
    });
    
    // Rich text editor functions
    function formatText(command) {
        document.execCommand(command, false, null);
        updateTextarea();
    }
    
    function insertHeading() {
        const text = prompt('Enter heading text:');
        if (text) {
            document.execCommand('insertHTML', false, '<h2>' + text + '</h2>');
            updateTextarea();
        }
    }
    
    function insertList() {
        document.execCommand('insertUnorderedList', false, null);
        updateTextarea();
    }
    
    function insertLink() {
        const url = prompt('Enter URL:');
        const text = prompt('Enter link text:');
        if (url && text) {
            document.execCommand('insertHTML', false, '<a href="' + url + '">' + text + '</a>');
            updateTextarea();
        }
    }
    
    function updateTextarea() {
        document.getElementById('pageContent').value = document.getElementById('editor-content').innerHTML;
    }
    
    // Initialize editor content
    document.addEventListener('DOMContentLoaded', function() {
        updateTextarea();
    });
</script>

<?php include '../includes/admin_layout_footer.php'; ?>