<?php
require_once 'config.php';
requireRole('developer');

$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on status filter and search
$whereClause = "WHERE bt.assigned_dev_id = ?";
$params = [$_SESSION['user_id']];

if ($status !== 'all') {
    $whereClause .= " AND bt.status = ?";
    $params[] = $status;
}

// Add search functionality
if (!empty($search)) {
    $whereClause .= " AND (
        bt.title LIKE ? OR 
        bt.description LIKE ? OR 
        bt.priority LIKE ? OR 
        bt.module LIKE ? OR 
        bt.submodule LIKE ? OR 
        u.name LIKE ? OR 
        p.name LIKE ?
    )";
    $searchParam = "%$search%";
    $params = array_merge($params, array_fill(0, 7, $searchParam));
}

// Get bugs
$bugs = [];
$totalBugs = 0;

try {
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM bug_tickets bt 
                   JOIN users u ON bt.created_by = u.id 
                   LEFT JOIN projects p ON bt.project_id = p.id 
                   $whereClause";
    $stmt = $pdo->prepare($countQuery);
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

// Helper function to highlight search terms in text
function highlightSearchTerm($text, $search) {
    if (empty($search) || empty($text)) {
        return $text;
    }
    
    // Escape special regex characters in search term
    $searchEscaped = preg_quote($search, '/');
    
    // Perform case-insensitive highlighting
    return preg_replace('/(' . $searchEscaped . ')/i', '<span class="search-highlight">$1</span>', $text);
}
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
        /* Search Bar Styling */
        .search-container {
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input-group {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem;
            padding-left: 2.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #f9fafb;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .search-btn {
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .search-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .clear-search {
            padding: 0.75rem 1rem;
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .clear-search:hover {
            background: #e5e7eb;
            color: #374151;
        }
        
        .search-info {
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            border-radius: 4px;
            font-size: 0.875rem;
            color: #1e40af;
        }
        
        .search-results-info {
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .search-highlight {
            background: #fef3c7;
            padding: 0.1rem 0.2rem;
            border-radius: 3px;
            font-weight: 500;
        }
        
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

        /* Responsive design */
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input-group {
                min-width: auto;
            }
            
            .search-btn,
            .clear-search {
                width: 100%;
                text-align: center;
            }
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

            <!-- Search Container -->
            <div class="search-container">
                <form method="GET" class="search-form">
                    <div class="search-input-group">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Search by title, description, priority, module, tester, or project..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                    </div>
                    <button type="submit" class="search-btn">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="?status=<?php echo htmlspecialchars($status); ?>" class="clear-search">Clear</a>
                    <?php endif; ?>
                </form>
                
                <?php if (empty($search)): ?>
                    <div class="search-info">
                        💡 <strong>Search Tips:</strong> You can search by bug title, description, priority (high/medium/low), module name, tester name, or project name.
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($search)): ?>
                <div class="search-results-info">
                    Found <strong><?php echo $totalBugs; ?></strong> result(s) for "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php if ($status !== 'all'): ?>
                        with status "<strong><?php echo htmlspecialchars($status); ?></strong>"
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="filter-tabs">
                <a href="?status=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo ($status == 'all') ? 'active' : ''; ?>">
                    All Bugs (<?php echo $totalBugs; ?>)
                </a>
                <a href="?status=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo ($status == 'pending') ? 'active' : ''; ?>">
                    Pending
                </a>
                <a href="?status=in_progress<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo ($status == 'in_progress') ? 'active' : ''; ?>">
                    In Progress
                </a>
                <a href="?status=fixed<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo ($status == 'fixed') ? 'active' : ''; ?>">
                    Fixed
                </a>
                <a href="?status=approved<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo ($status == 'approved') ? 'active' : ''; ?>">
                    Approved
                </a>
                <a href="?status=rejected<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo ($status == 'rejected') ? 'active' : ''; ?>">
                    Rejected
                </a>
            </div>

            <div class="bugs-container">
                <?php if (empty($bugs)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔧</div>
                        <h3>No bugs found</h3>
                        <p>
                            <?php if (!empty($search)): ?>
                                No bugs found matching your search criteria. Try adjusting your search terms.
                            <?php elseif ($status == 'all'): ?>
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
                                        <h3><?php echo highlightSearchTerm(htmlspecialchars($bug['title']), $search); ?></h3>
                                        <div class="bug-id">#<?php echo $bug['id']; ?></div>
                                    </div>
                                    <div class="bug-badges">
                                        <span class="priority-badge" style="background-color: <?php echo getPriorityColor($bug['priority']); ?>">
                                            <?php echo highlightSearchTerm($bug['priority'], $search); ?>
                                        </span>
                                        <span class="status-badge" style="background-color: <?php echo getStatusColor($bug['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $bug['status'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="bug-description">
                                    <p><?php echo highlightSearchTerm(html_entity_decode(substr($bug['description'], 0, 200)), $search) . (strlen($bug['description']) > 200 ? '...' : ''); ?></p>
                                    
                                    <?php if ($bug['visible_impact']): ?>
                                        <div class="visible-impact">
                                            <strong>Visible Impact:</strong>
                                            <p><?php echo highlightSearchTerm(htmlspecialchars($bug['visible_impact']), $search); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="bug-module-info">
                                        <strong>Module:</strong> <?php echo highlightSearchTerm(htmlspecialchars($bug['module'] ?? 'N/A'), $search); ?> → <?php echo highlightSearchTerm(htmlspecialchars($bug['submodule'] ?? 'N/A'), $search); ?>
                                    </div>
                                    <?php if ($bug['project_name']): ?>
                                        <div class="bug-project-info">
                                            <strong>Project:</strong> <?php echo highlightSearchTerm(htmlspecialchars($bug['project_name']), $search); ?>
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
                                        <strong>Reported by:</strong> <?php echo highlightSearchTerm(htmlspecialchars($bug['tester_name']), $search); ?>
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
                                <a href="?status=<?php echo htmlspecialchars($status); ?>&page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">← Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?status=<?php echo htmlspecialchars($status); ?>&page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?status=<?php echo htmlspecialchars($status); ?>&page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">Next →</a>
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

        // Auto-submit search form on Enter key
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('.search-form').submit();
            }
        });
    </script>
</body>
</html>
