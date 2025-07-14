
<?php
require_once 'config.php';
requireRole('developer');

$status = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on status filter
$whereClause = "WHERE bt.assigned_dev_id = ?";
$params = [$_SESSION['user_id']];

if ($status !== 'all') {
    $whereClause .= " AND bt.status = ?";
    $params[] = $status;
}

// Get bugs
$bugs = [];
$totalBugs = 0;

try {
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bug_tickets bt $whereClause");
    $stmt->execute($params);
    $totalBugs = $stmt->fetch()['total'];

    // Get bugs with pagination and project name
    $stmt = $pdo->prepare("
        SELECT bt.*, u.name as tester_name, u.email as tester_email, p.name as project_name
        FROM bug_tickets bt 
        JOIN users u ON bt.created_by = u.id 
        LEFT JOIN projects p ON bt.project_id = p.id 
        $whereClause 
        ORDER BY bt.created_at DESC 
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $bugs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load bugs';
}

$totalPages = ceil($totalBugs / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Bugs - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Professional image styling */
        .bug-screenshot {
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .bug-screenshot-thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .bug-screenshot-thumbnail:hover {
            border-color: #3b82f6;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .screenshot-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .screenshot-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .screenshot-action {
            font-size: 0.75rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .screenshot-action:hover {
            text-decoration: underline;
        }
        
        /* Enhanced modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            position: relative;
            margin: 2% auto;
            padding: 0;
            max-width: 90%;
            max-height: 90vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-image {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .close:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }
        
        /* Image icon for visual enhancement */
        .screenshot-icon {
            display: inline-block;
            margin-right: 0.5rem;
            color: #6b7280;
        }
        
        /* Developer-specific styling */
        .bug-reporter {
            color: #059669;
            font-weight: 500;
        }
        
        .bug-actions .btn-primary {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            border: none;
        }
        
        .bug-actions .btn-primary:hover {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
            transform: translateY(-2px);
        }

        /* Project name styling */
        .bug-project-info {
            margin-top: 0.5rem;
            font-size: 0.95rem;
            color: #059669;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/navbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Assigned Bugs</h1>
                <p>Manage and resolve bugs assigned to you</p>
            </div>

            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo ($status == 'all') ? 'active' : ''; ?>">
                    All Bugs
                </a>
                <a href="?status=pending" class="filter-tab <?php echo ($status == 'pending') ? 'active' : ''; ?>">
                    Pending
                </a>
                <a href="?status=in_progress" class="filter-tab <?php echo ($status == 'in_progress') ? 'active' : ''; ?>">
                    In Progress
                </a>
                <a href="?status=fixed" class="filter-tab <?php echo ($status == 'fixed') ? 'active' : ''; ?>">
                    Fixed
                </a>
                <a href="?status=approved" class="filter-tab <?php echo ($status == 'approved') ? 'active' : ''; ?>">
                    Approved
                </a>
                <a href="?status=rejected" class="filter-tab <?php echo ($status == 'rejected') ? 'active' : ''; ?>">
                    Rejected
                </a>
            </div>

            <div class="bugs-container">
                <?php if (empty($bugs)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔧</div>
                        <h3>No bugs found</h3>
                        <p>
                            <?php if ($status == 'all'): ?>
                                No bugs have been assigned to you yet.
                            <?php else: ?>
                                No bugs with "<?php echo htmlspecialchars($status); ?>" status found.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="bugs-grid">
                        <?php foreach ($bugs as $bug): ?>
                            <div class="bug-card-detailed">
                                <div class="bug-card-header">
                                    <div class="bug-title">
                                        <h3><?php echo htmlspecialchars($bug['title']); ?></h3>
                                        <div class="bug-id">#<?php echo $bug['id']; ?></div>
                                    </div>
                                    <div class="bug-badges">
                                        <span class="priority-badge" style="background-color: <?php echo getPriorityColor($bug['priority']); ?>">
                                            <?php echo $bug['priority']; ?>
                                        </span>
                                        <span class="status-badge" style="background-color: <?php echo getStatusColor($bug['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $bug['status'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="bug-description">
                                    <p><?php echo htmlspecialchars($bug['description']); ?></p>
                                    
                                    <?php if ($bug['visible_impact']): ?>
                                        <div class="visible-impact">
                                            <strong>Visible Impact:</strong>
                                            <p><?php echo htmlspecialchars($bug['visible_impact']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="bug-module-info">
                                        <strong>Module:</strong> <?php echo htmlspecialchars($bug['module'] ?? 'N/A'); ?> → <?php echo htmlspecialchars($bug['submodule'] ?? 'N/A'); ?>
                                    </div>
                                    <?php if ($bug['project_name']): ?>
                                        <div class="bug-project-info">
                                            <strong>Project:</strong> <?php echo htmlspecialchars($bug['project_name']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($bug['screenshot']): ?>
                                        <div class="bug-screenshot">
                                            <img src="<?php echo UPLOAD_DIR . $bug['screenshot']; ?>" 
                                                 alt="Bug Screenshot" 
                                                 class="bug-screenshot-thumbnail"
                                                 onclick="openModal('<?php echo UPLOAD_DIR . $bug['screenshot']; ?>')">
                                            <div class="screenshot-info">
                                                <span class="screenshot-label">
                                                    <span class="screenshot-icon">📷</span>Screenshot attached
                                                </span>
                                                <a href="javascript:void(0)" 
                                                   onclick="openModal('<?php echo UPLOAD_DIR . $bug['screenshot']; ?>')" 
                                                   class="screenshot-action">Click to view full size</a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="bug-meta">
                                    <div class="bug-reporter">
                                        <strong>Reported by:</strong> <?php echo htmlspecialchars($bug['tester_name']); ?>
                                    </div>
                                    <div class="bug-date">
                                        <strong>Created:</strong> <?php echo formatDate($bug['created_at']); ?>
                                    </div>
                                </div>

                                <div class="bug-actions">
                                    <?php if ($bug['status'] == 'pending' || $bug['status'] == 'rejected'): ?>
                                        <a href="fix_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-primary">
                                            <span>🔧</span> <?php echo $bug['status'] == 'rejected' ? 'Restart Work' : 'Start Working'; ?>
                                        </a>
                                        
                                    <?php elseif ($bug['status'] == 'in_progress'): ?>
                                        <a href="fix_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-primary">
                                            <span>✅</span> Mark as Fixed
                                        </a>
                                        <a href="chat_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-outline">
                                            <span>💬</span> Chat
                                        </a>
                                    <?php else: ?>
                                        <a href="fix_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-outline">
                                            <span>👁️</span> View Details
                                        </a>
                                        <a href="chat_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-outline">
                                            <span>💬</span> Chat
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?status=<?php echo htmlspecialchars($status); ?>&page=<?php echo $page - 1; ?>" class="pagination-btn">← Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?status=<?php echo htmlspecialchars($status); ?>&page=<?php echo $i; ?>" 
                                   class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?status=<?php echo htmlspecialchars($status); ?>&page=<?php echo $page + 1; ?>" class="pagination-btn">Next →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Enhanced Image Modal -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <img id="modalImage" src="" alt="Bug Screenshot" class="modal-image">
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

        // Close modal when clicking outside
        window.onclick = function(event) {
            const imageModal = document.getElementById('imageModal');
            if (event.target === imageModal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
