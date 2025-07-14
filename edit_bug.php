<?php
require_once 'config.php';
requireRole('tester');

$bugId = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Get bug details and verify ownership
$bug = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM bug_tickets WHERE id = ? AND created_by = ? AND status = 'pending'");
    $stmt->execute([$bugId, $_SESSION['user_id']]);
    $bug = $stmt->fetch();
    
    if (!$bug) {
        header('Location: my_bugs.php');
        exit();
    }
} catch (PDOException $e) {
    $error = 'Failed to load bug details';
}

// Get developers for assignment
$developers = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'developer' ORDER BY name");
    $stmt->execute();
    $developers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load developers';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitizeInput($_POST['title']);
    $module = sanitizeInput($_POST['module']);
    $submodule = sanitizeInput($_POST['submodule']);
    $description = sanitizeInput($_POST['description']);
    $priority = sanitizeInput($_POST['priority']);
    $visible_impact = sanitizeInput($_POST['visible_impact']);
    $assigned_dev_id = sanitizeInput($_POST['assigned_dev_id']);
    
    // Validation
    if (empty($title) || empty($module) || empty($submodule) || empty($description) || empty($priority) || empty($assigned_dev_id)) {
        $error = 'All required fields must be filled';
    }
    
    // Handle file upload
    $screenshot = $bug['screenshot']; // Keep existing screenshot by default
    if (empty($error) && isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['screenshot']['name'];
        $filesize = $_FILES['screenshot']['size'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed) && $filesize < 5000000) { // 5MB limit
            // Delete old screenshot if exists
            if ($bug['screenshot'] && file_exists(UPLOAD_DIR . $bug['screenshot'])) {
                unlink(UPLOAD_DIR . $bug['screenshot']);
            }
            
            $screenshot = time() . '_' . $filename;
            move_uploaded_file($_FILES['screenshot']['tmp_name'], UPLOAD_DIR . $screenshot);
        } else {
            $error = 'Invalid file type or size too large (max 5MB)';
        }
    }
    
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE bug_tickets 
                SET title = ?, module = ?, submodule = ?, description = ?, priority = ?, visible_impact = ?, screenshot = ?, assigned_dev_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND created_by = ?
            ");
            $stmt->execute([$title, $module, $submodule, $description, $priority, $visible_impact, $screenshot, $assigned_dev_id, $bugId, $_SESSION['user_id']]);
            
            $success = 'Bug report updated successfully!';
            
            // Update bug object for display
            $bug['title'] = $title;
            $bug['module'] = $module;
            $bug['submodule'] = $submodule;
            $bug['module'] = $module;
            $bug['submodule'] = $submodule;
            $bug['description'] = $description;
            $bug['priority'] = $priority;
            $bug['visible_impact'] = $visible_impact;
            $bug['screenshot'] = $screenshot;
            $bug['assigned_dev_id'] = $assigned_dev_id;
        } catch (PDOException $e) {
            $error = 'Failed to update bug report';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bug #<?php echo $bug['id']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/navbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Edit Bug Report #<?php echo $bug['id']; ?></h1>
                <p>Update bug details (only available for pending bugs)</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="bug-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Bug Title *</label>
                            <input type="text" id="title" name="title" required 
                                   value="<?php echo htmlspecialchars($bug['title']); ?>"
                                   placeholder="Brief description of the bug">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="module">Module *</label>
                            <select id="module" name="module" required onchange="updateSubmodules()">
                                <option value="">Select Module</option>
                                <option value="Authentication" <?php echo ($bug['module'] == 'Authentication') ? 'selected' : ''; ?>>Authentication</option>
                                <option value="User Management" <?php echo ($bug['module'] == 'User Management') ? 'selected' : ''; ?>>User Management</option>
                                <option value="Dashboard" <?php echo ($bug['module'] == 'Dashboard') ? 'selected' : ''; ?>>Dashboard</option>
                                <option value="Reports" <?php echo ($bug['module'] == 'Reports') ? 'selected' : ''; ?>>Reports</option>
                                <option value="Settings" <?php echo ($bug['module'] == 'Settings') ? 'selected' : ''; ?>>Settings</option>
                                <option value="API" <?php echo ($bug['module'] == 'API') ? 'selected' : ''; ?>>API</option>
                                <option value="Database" <?php echo ($bug['module'] == 'Database') ? 'selected' : ''; ?>>Database</option>
                                <option value="UI/UX" <?php echo ($bug['module'] == 'UI/UX') ? 'selected' : ''; ?>>UI/UX</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="submodule">Submodule *</label>
                            <select id="submodule" name="submodule" required>
                                <option value="">Select Submodule</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="description">Detailed Description *</label>
                            <textarea id="description" name="description" required rows="5" 
                                      placeholder="Provide detailed steps to reproduce the bug, expected behavior, and actual behavior"><?php echo htmlspecialchars($bug['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="priority">Priority Level *</label>
                            <select id="priority" name="priority" required>
                                <option value="">Select Priority</option>
                                <option value="P1" <?php echo ($bug['priority'] == 'P1') ? 'selected' : ''; ?>>P1 - Critical (System crash, data loss)</option>
                                <option value="P2" <?php echo ($bug['priority'] == 'P2') ? 'selected' : ''; ?>>P2 - High (Major feature broken)</option>
                                <option value="P3" <?php echo ($bug['priority'] == 'P3') ? 'selected' : ''; ?>>P3 - Medium (Minor feature issues)</option>
                                <option value="P4" <?php echo ($bug['priority'] == 'P4') ? 'selected' : ''; ?>>P4 - Low (Cosmetic issues)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="assigned_dev_id">Assign Developer *</label>
                            <select id="assigned_dev_id" name="assigned_dev_id" required>
                                <option value="">Select Developer</option>
                                <?php foreach ($developers as $dev): ?>
                                    <option value="<?php echo $dev['id']; ?>" <?php echo ($bug['assigned_dev_id'] == $dev['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dev['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="visible_impact">Visible Impact</label>
                            <textarea id="visible_impact" name="visible_impact" rows="3" 
                                      placeholder="Describe the visible impact on users or functionality"><?php echo htmlspecialchars($bug['visible_impact']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="screenshot">Screenshot/Evidence</label>
                            <input type="file" id="screenshot" name="screenshot" accept="image/*">
                            <small>Upload a new screenshot to replace the existing one (max 5MB)</small>
                            
                            <?php if ($bug['screenshot']): ?>
                                <div class="current-screenshot" style="margin-top: 1rem;">
                                    <p><strong>Current Screenshot:</strong></p>
                                    <img src="<?php echo UPLOAD_DIR . $bug['screenshot']; ?>" alt="Current Screenshot" 
                                         style="max-width: 200px; height: auto; border-radius: 4px; cursor: pointer;"
                                         onclick="openModal(this.src)">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span>💾</span> Update Bug Report
                        </button>
                        <a href="my_bugs.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <img id="modalImage" src="" alt="Screenshot">
        </div>
    </div>

    <script>
        function openModal(src) {
            document.getElementById('imageModal').style.display = 'block';
            document.getElementById('modalImage').src = src;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        const submoduleOptions = {
            'Authentication': ['Login', 'Registration', 'Password Reset', 'Two-Factor Auth', 'Session Management'],
            'User Management': ['User Profile', 'Role Management', 'Permissions', 'User List', 'Account Settings'],
            'Dashboard': ['Main Dashboard', 'Analytics', 'Widgets', 'Navigation', 'Quick Actions'],
            'Reports': ['Bug Reports', 'User Reports', 'System Reports', 'Export Functions', 'Filters'],
            'Settings': ['System Settings', 'User Preferences', 'Configuration', 'Integrations', 'Notifications'],
            'API': ['REST API', 'Authentication API', 'Data API', 'File Upload API', 'Third-party Integration'],
            'Database': ['Data Storage', 'Queries', 'Migrations', 'Backup', 'Performance'],
            'UI/UX': ['Layout', 'Forms', 'Buttons', 'Navigation', 'Responsive Design', 'Accessibility']
        };

        function updateSubmodules() {
            const moduleSelect = document.getElementById('module');
            const submoduleSelect = document.getElementById('submodule');
            const selectedModule = moduleSelect.value;

            // Clear existing options
            submoduleSelect.innerHTML = '<option value="">Select Submodule</option>';

            if (selectedModule && submoduleOptions[selectedModule]) {
                submoduleOptions[selectedModule].forEach(function(submodule) {
                    const option = document.createElement('option');
                    option.value = submodule;
                    option.textContent = submodule;
                    submoduleSelect.appendChild(option);
                });
            }
        }

        // Initialize submodules on page load
        document.addEventListener('DOMContentLoaded', function() {
            const moduleSelect = document.getElementById('module');
            if (moduleSelect.value) {
                updateSubmodules();
                // Set the previously selected submodule
                const selectedSubmodule = '<?php echo htmlspecialchars($bug['submodule']); ?>';
                if (selectedSubmodule) {
                    document.getElementById('submodule').value = selectedSubmodule;
                }
            }
        });
    </script>
</body>
</html>