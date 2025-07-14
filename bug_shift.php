<?php
require_once 'config.php';
requireAuth();

// Handle AJAX request for updating bug status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    header('Content-Type: application/json');
    
    $bugId = $_POST['bug_id'] ?? '';
    $newStatus = $_POST['new_status'] ?? '';
    $userRole = getUserRole();
    $userId = $_SESSION['user_id'];

    // Validate input
    if (empty($bugId) || empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    // Validate status values
    $validStatuses = ['pending', 'in_progress', 'fixed', 'approved', 'rejected'];
    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    try {
        // Get current bug details
        $stmt = $pdo->prepare("SELECT * FROM bug_tickets WHERE id = ?");
        $stmt->execute([$bugId]);
        $bug = $stmt->fetch();

        if (!$bug) {
            echo json_encode(['success' => false, 'message' => 'Bug not found']);
            exit;
        }

        $currentStatus = $bug['status'];

        // Check if user has permission to update this bug
        $hasPermission = false;
        if ($userRole === 'admin') {
            $hasPermission = true;
        } elseif ($userRole === 'tester') {
            // Testers can approve/reject bugs they created that are awaiting approval
            $hasPermission = ($bug['created_by'] == $userId && $currentStatus === 'fixed' && 
                             in_array($newStatus, ['approved', 'rejected']));
        } elseif ($userRole === 'developer') {
            // Developers can work on bugs assigned to them
            $hasPermission = ($bug['assigned_dev_id'] == $userId && 
                             (($currentStatus === 'pending' && $newStatus === 'in_progress') ||
                              ($currentStatus === 'in_progress' && $newStatus === 'fixed') ||
                              ($currentStatus === 'rejected' && $newStatus === 'in_progress')));
        }

        if (!$hasPermission) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to update this bug status']);
            exit;
        }

        // Validate status transition
        $validTransitions = [
            'pending' => ['in_progress'],
            'in_progress' => ['fixed'],
            'fixed' => ['approved', 'rejected'],
            'approved' => [],
            'rejected' => ['in_progress']
        ];

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status transition from ' . $currentStatus . ' to ' . $newStatus]);
            exit;
        }

        // Update the bug status
        $stmt = $pdo->prepare("UPDATE bug_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$newStatus, $bugId]);

        if ($result) {
            // Log the status change (only once)
            try {
                $stmt = $pdo->prepare("INSERT INTO bug_status_logs (bug_id, status, updated_by) VALUES (?, ?, ?)");
                $stmt->execute([$bugId, $newStatus, $userId]);
            } catch (PDOException $e) {
                // Log error but don't fail the main operation
                error_log('Failed to log status change: ' . $e->getMessage());
            }

            echo json_encode([
                'success' => true, 
                'message' => 'Bug status updated successfully',
                'bug_id' => $bugId,
                'new_status' => $newStatus,
                'old_status' => $currentStatus
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update bug status']);
        }

    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
    exit;
}

$userRole = getUserRole();

// Get all bugs for the user based on role
$bugs = [];
try {
    if ($userRole == 'tester') {
        $stmt = $pdo->prepare("SELECT bt.*, u.name as developer_name FROM bug_tickets bt LEFT JOIN users u ON bt.assigned_dev_id = u.id WHERE bt.created_by = ? ORDER BY bt.created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
    } elseif ($userRole == 'developer') {
        $stmt = $pdo->prepare("SELECT bt.*, u.name as tester_name FROM bug_tickets bt LEFT JOIN users u ON bt.created_by = u.id WHERE bt.assigned_dev_id = ? ORDER BY bt.created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
    } elseif ($userRole == 'admin') {
        $stmt = $pdo->prepare("SELECT bt.*, u.name as tester_name, u2.name as developer_name FROM bug_tickets bt LEFT JOIN users u ON bt.created_by = u.id LEFT JOIN users u2 ON bt.assigned_dev_id = u2.id ORDER BY bt.created_at DESC");
        $stmt->execute([]);
    }
    $bugs = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Status Shift - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
.app-container {
    max-width: 1350px;
    width: 100%;
    margin: 0 auto;
    padding: 1rem;
}

.main-content {
    padding: 1rem;
    background: var(--background-color, #f9fafb);
}

.status-columns {
    display: flex;
    flex-direction: row;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: nowrap;
}

.status-box {
    background: var(--surface-color, #ffffff);
    padding: 1.5rem;
    border-radius: var(--border-radius, 8px);
    box-shadow: var(--box-shadow, 0 2px 4px rgba(0, 0, 0, 0.1));
    flex: 1;
    min-width: 250px;
    max-width: 300px;
    min-height: 300px;
    position: relative;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    overflow-y: auto; /* Enable vertical scrolling within each status box */
    max-height: calc(100vh - 200px); /* Adjust max-height to fit within viewport, accounting for header and padding */
}

.status-box h3 {
    position: sticky; /* Makes the status label sticky within its box */
    top: 0; /* Sticks to the top of the status box */
    background: var(--surface-color, #ffffff); /* Matches box background to avoid content bleed */
    z-index: 5; /* Ensures it stays above bug cards */
    margin: -1.5rem -1.5rem 1rem -1.5rem; /* Adjust to overlap padding and maintain layout */
    padding: 1.5rem 1.5rem 0.5rem 1.5rem; /* Maintains padding while sticky */
}

.count {
    font-size: 0.9rem;
    color: var(--text-secondary, #6b7280);
    font-weight: normal;
}

.bug-card {
    background: #fff;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 280px;
    height: auto;
    min-height: 180px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    cursor: move;
    transition: all 0.2s ease;
    border: 1px solid #e5e7eb;
}

.bug-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.bug-card.dragging {
    opacity: 0.6;
    transform: rotate(5deg) scale(0.95);
    z-index: 1000;
}

.bug-card.moving {
    transition: all 0.3s ease;
    opacity: 0.7;
}

.bug-header {
    margin-bottom: 0.5rem;
}

.bug-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.bug-id {
    font-size: 0.8rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.bug-meta {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.priority-badge, .status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    color: white;
    white-space: nowrap;
    font-weight: 500;
}

.bug-description {
    margin: 0.5rem 0;
    color: var(--text-secondary, #6b7280);
    font-size: 0.8rem;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    line-height: 1.4;
    flex-grow: 1;
}

.bug-footer {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    color: var(--text-secondary, #6b7280);
    flex-shrink: 0;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #f3f4f6;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    background: #f9fafb;
    border-radius: var(--border-radius, 8px);
    border: 2px dashed #d1d5db;
    color: #9ca3af;
    font-style: italic;
}

.empty-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

/* Role-based styling */
.status-box[data-status="pending"] h3 { color: #6b7280; }
.status-box[data-status="in_progress"] h3 { color: #3b82f6; }
.status-box[data-status="fixed"] h3 { color: #f59e0b; }
.status-box[data-status="approved"] h3 { color: #10b981; }
.status-box[data-status="rejected"] h3 { color: #ef4444; }

/* Responsive design */
@media (max-width: 1350px) {
    .status-columns {
        flex-wrap: wrap;
    }
    .status-box {
        flex: 1 1 200px;
        min-width: 200px;
        max-height: calc(50vh - 100px); /* Adjust for wrapped layout */
    }
}

@media (max-width: 768px) {
    .status-columns {
        flex-direction: column;
        gap: 1rem;
    }
    .status-box {
        min-width: 100%;
        max-height: calc(100vh - 200px); /* Full height for stacked layout */
    }
    .bug-card {
        max-width: 100%;
    }
}

/* Loading state */
.bug-card.updating {
    pointer-events: none;
    opacity: 0.7;
}

/* Success animation */
.bug-card.success {
    animation: successPulse 0.5s ease-in-out;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* User role indicator */
.role-indicator {
    background: var(--primary-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-bottom: 1rem;
    display: inline-block;
}
    </style>
</head>
<body>
     <?php include 'includes/navbar.php'; ?>
    <div class="app-container">
       

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Bug Status Management</h1>
                <p>Drag and drop bugs to change their status</p>
                <div class="role-indicator">
                    Role: <?php echo ucfirst($userRole); ?>
                </div>
            </div>

            <div class="status-columns">
                <?php
                $statusLabels = [
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'fixed' => 'Fixed',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected'
                ];
                
                $bugsByStatus = [];
                foreach ($bugs as $bug) {
                    $bugsByStatus[$bug['status']][] = $bug;
                }

                foreach ($statusLabels as $status => $label) {
                    $bugCount = isset($bugsByStatus[$status]) ? count($bugsByStatus[$status]) : 0;
                    echo '<div class="status-box" data-status="' . $status . '">';
                    echo '<h3>' . htmlspecialchars($label) . ' <span class="count">(' . $bugCount . ')</span></h3>';
                    
                    if (isset($bugsByStatus[$status]) && count($bugsByStatus[$status]) > 0) {
                        foreach ($bugsByStatus[$status] as $bug) {
                            echo '<div class="bug-card" data-bug-id="' . $bug['id'] . '" data-current-status="' . $bug['status'] . '">';
                            echo '<div class="bug-header">';
                            echo '<div class="bug-id">#' . $bug['id'] . '</div>';
                            echo '<div class="bug-title">' . htmlspecialchars($bug['title']) . '</div>';
                            echo '<div class="bug-meta">';
                            echo '<span class="priority-badge" style="background-color: ' . getPriorityColor($bug['priority']) . '">';
                            echo $bug['priority'] . '</span>';
                            if (!empty($bug['module'])) {
                                echo '<span class="status-badge" style="background-color: #6b7280;">';
                                echo htmlspecialchars($bug['module']) . '</span>';
                            }
                            echo '</div>';
                            echo '</div>';
                            echo '<p class="bug-description">' . htmlspecialchars(substr($bug['description'], 0, 120));
                            if (strlen($bug['description']) > 120) echo '...';
                            echo '</p>';
                            echo '<div class="bug-footer">';
                            echo '<span class="bug-date">' . formatDate($bug['created_at']) . '</span>';
                            if ($userRole == 'tester' && isset($bug['developer_name'])) {
                                echo '<span class="bug-assignee">Dev: ' . htmlspecialchars($bug['developer_name']) . '</span>';
                            } elseif ($userRole == 'developer' && isset($bug['tester_name'])) {
                                echo '<span class="bug-assignee">By: ' . htmlspecialchars($bug['tester_name']) . '</span>';
                            }
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">';
                        echo '<div class="empty-icon">🐛</div>';
                        echo '<p>No bugs in this state</p>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const bugCards = document.querySelectorAll('.bug-card');
            const statusBoxes = document.querySelectorAll('.status-box');
            const userRole = '<?php echo $userRole; ?>';
            
            let draggedElement = null;
            let draggedBugId = null;

            console.log('Initializing drag and drop for', bugCards.length, 'bug cards');
            console.log('User role:', userRole);

            // Initialize drag functionality for bug cards
            bugCards.forEach(card => {
                card.setAttribute('draggable', true);

                card.addEventListener('dragstart', (e) => {
                    draggedElement = card;
                    draggedBugId = card.getAttribute('data-bug-id');
                    
                    e.dataTransfer.setData('text/plain', draggedBugId);
                    e.dataTransfer.effectAllowed = 'move';
                    
                    card.classList.add('dragging');
                    
                    console.log('Drag started for bug ID:', draggedBugId);
                });

                card.addEventListener('dragend', (e) => {
                    card.classList.remove('dragging');
                    draggedElement = null;
                    draggedBugId = null;
                    
                    console.log('Drag ended');
                });
            });

            // Initialize drop functionality for status boxes
            statusBoxes.forEach(box => {
                box.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                });

                box.addEventListener('dragenter', (e) => {
                    e.preventDefault();
                    if (draggedElement) {
                        box.classList.add('dragover');
                    }
                });

                box.addEventListener('dragleave', (e) => {
                    // Only remove dragover if we're actually leaving the box
                    if (!box.contains(e.relatedTarget)) {
                        box.classList.remove('dragover');
                    }
                });

                box.addEventListener('drop', (e) => {
                    e.preventDefault();
                    box.classList.remove('dragover');

                    const bugId = e.dataTransfer.getData('text/plain');
                    const newStatus = box.getAttribute('data-status');
                    const card = draggedElement;
                    
                    if (!card || !bugId || !newStatus) {
                        console.log('Invalid drop - missing data');
                        return;
                    }

                    const currentStatus = card.getAttribute('data-current-status');
                    
                    if (currentStatus === newStatus) {
                        console.log('Same status, ignoring drop');
                        return;
                    }

                    console.log('Attempting to move bug', bugId, 'from', currentStatus, 'to', newStatus);

                    // Validate transition based on user role
                    if (!isValidTransition(userRole, currentStatus, newStatus)) {
                        alert('You are not authorized to move this bug from "' + formatStatus(currentStatus) + '" to "' + formatStatus(newStatus) + '"');
                        return;
                    }

                    // Add updating state
                    card.classList.add('updating');

                    // Send update request to the same file
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `ajax_update=1&bug_id=${encodeURIComponent(bugId)}&new_status=${encodeURIComponent(newStatus)}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        card.classList.remove('updating');
                        
                        if (data.success) {
                            // Move the card to the new status box
                            box.appendChild(card);
                            card.setAttribute('data-current-status', newStatus);
                            
                            // Add success animation
                            card.classList.add('success');
                            setTimeout(() => card.classList.remove('success'), 500);
                            
                            // Update counts
                            updateStatusCounts();
                            
                            console.log('Bug moved successfully to:', newStatus);
                        } else {
                            console.error('Failed to update:', data.message);
                            alert('Failed to update bug status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        card.classList.remove('updating');
                        alert('An error occurred while updating the bug status. Please try again.');
                    });
                });
            });

            function isValidTransition(userRole, currentStatus, newStatus) {
                const transitions = {
                    'tester': {
                        'fixed': ['approved', 'rejected']
                    },
                    'developer': {
                        'pending': ['in_progress'],
                        'in_progress': ['fixed'],
                        'rejected': ['in_progress']
                    },
                    'admin': {
                        'pending': ['in_progress', 'rejected'],
                        'in_progress': ['fixed', 'pending'],
                        'fixed': ['approved', 'rejected', 'in_progress'],
                        'approved': ['rejected'],
                        'rejected': ['in_progress', 'pending']
                    }
                };

                if (!transitions[userRole] || !transitions[userRole][currentStatus]) {
                    return false;
                }

                return transitions[userRole][currentStatus].includes(newStatus);
            }

            function formatStatus(status) {
                return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }

            function updateStatusCounts() {
                statusBoxes.forEach(box => {
                    const count = box.querySelectorAll('.bug-card').length;
                    const header = box.querySelector('h3');
                    const countSpan = header.querySelector('.count');
                    if (countSpan) {
                        countSpan.textContent = `(${count})`;
                    }
                });
            }
        });
    </script>
</body>
</html>