<?php
require_once 'config.php';
requireAuth();

$userRole = getUserRole();

// Get user stats
$totalBugs = 0;
$pendingBugs = 0;
$resolvedBugs = 0;

try {
    if ($userRole == 'tester') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bug_tickets WHERE created_by = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $totalBugs = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM bug_tickets WHERE created_by = ? AND status IN ('pending', 'in_progress', 'fixed')");
        $stmt->execute([$_SESSION['user_id']]);
        $pendingBugs = $stmt->fetch()['pending'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as resolved FROM bug_tickets WHERE created_by = ? AND status = 'approved'");
        $stmt->execute([$_SESSION['user_id']]);
        $resolvedBugs = $stmt->fetch()['resolved'];
    } elseif ($userRole == 'developer') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bug_tickets WHERE assigned_dev_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $totalBugs = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM bug_tickets WHERE assigned_dev_id = ? AND status IN ('pending', 'in_progress')");
        $stmt->execute([$_SESSION['user_id']]);
        $pendingBugs = $stmt->fetch()['pending'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as resolved FROM bug_tickets WHERE assigned_dev_id = ? AND status = 'approved'");
        $stmt->execute([$_SESSION['user_id']]);
        $resolvedBugs = $stmt->fetch()['resolved'];
    } elseif ($userRole == 'admin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bug_tickets");
        $stmt->execute();
        $totalBugs = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM bug_tickets WHERE status IN ('pending', 'in_progress', 'fixed')");
        $stmt->execute();
        $pendingBugs = $stmt->fetch()['pending'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as resolved FROM bug_tickets WHERE status = 'approved'");
        $stmt->execute();
        $resolvedBugs = $stmt->fetch()['resolved'];
    }
} catch (PDOException $e) {
    // Handle error
}

// Get recent bugs
$recentBugs = [];
try {
    if ($userRole == 'tester') {
        $stmt = $pdo->prepare("
            SELECT bt.*, u.name as developer_name 
            FROM bug_tickets bt 
            LEFT JOIN users u ON bt.assigned_dev_id = u.id 
            WHERE bt.created_by = ? 
            ORDER BY bt.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } elseif ($userRole == 'developer') {
        $stmt = $pdo->prepare("
            SELECT bt.*, u.name as tester_name 
            FROM bug_tickets bt 
            LEFT JOIN users u ON bt.created_by = u.id 
            WHERE bt.assigned_dev_id = ? 
            ORDER BY bt.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } elseif ($userRole == 'admin') {
        $stmt = $pdo->prepare("
            SELECT bt.*, 
                   u1.name as tester_name, 
                   u2.name as developer_name 
            FROM bug_tickets bt 
            LEFT JOIN users u1 ON bt.created_by = u1.id 
            LEFT JOIN users u2 ON bt.assigned_dev_id = u2.id 
            ORDER BY bt.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
    }
    $recentBugs = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .status-columns {
            display: flex;
            flex-direction: row;
            gap: 1.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .status-box {
            background: var(--surface-color);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            flex: 1;
            min-width: 250px;
        }
        .status-box h3 {
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-size: 1.2rem;
        }
        .bug-card {
            background: #fff;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 300px;
            height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }
        .bug-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        .bug-meta {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .priority-badge, .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            color: white;
            white-space: nowrap;
        }
        .bug-description {
            margin: 0.5rem 0;
            color: var(--text-secondary);
            font-size: 0.8rem;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .bug-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: var(--text-secondary);
            flex-shrink: 0;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            background: var(--surface-color);
            border-radius: var(--border-radius);
            width: 200px;
            height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        @media (max-width: 768px) {
            .status-columns {
                flex-direction: column;
                gap: 1rem;
            }
            .status-box {
                min-width: 100%;
            }
            .bug-card {
                width: 150px;
                height: 150px;
            }
            .empty-state {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/navbar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <p>Here's your system-wide bug tracking overview</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php echo $totalBugs; ?></h3>
                        <p>Total Bugs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3><?php echo $pendingBugs; ?></h3>
                        <p>Pending Resolution</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo $resolvedBugs; ?></h3>
                        <p>Resolved Bugs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3><?php echo $totalBugs > 0 ? round(($resolvedBugs / $totalBugs) * 100) : 0; ?>%</h3>
                        <p>Resolution Rate</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-actions">
                <?php if ($userRole == 'admin'): ?>
                    <a href="bug_shift.php" class="btn btn-primary">
                        <span>🌐</span> Bug Shift
                    </a>
                    <a href="admin_report.php" class="btn btn-outline">
                        <span>📊</span> Generate System Report
                    </a>
                <?php elseif ($userRole == 'tester'): ?>
                    <a href="create_bug.php" class="btn btn-primary">
                        <span>🐛</span> Report New Bug
                    </a>
                    <a href="my_bugs.php" class="btn btn-outline">
                        <span>📋</span> My Bug Reports
                    </a>
                    <a href="generate_report.php" class="btn btn-outline">
                        <span>📊</span> Generate Report
                    </a>
                <?php elseif ($userRole == 'developer'): ?>
                    <a href="developer_bugs.php" class="btn btn-primary">
                        <span>🔧</span> View Assigned Bugs
                    </a>
                    <a href="developer_bugs.php?status=pending" class="btn btn-outline">
                        <span>⏳</span> Pending Bugs
                    </a>
                    <a href="developer_bugs.php?status=fixed" class="btn btn-outline">
                        <span>✅</span> Fixed Bugs
                    </a>
                <?php endif; ?>
            </div>

            <div class="recent-bugs">
                <h2>Recent Activity</h2>
                <div class="status-columns">
                    <?php
                    // Group bugs by status
                    $bugsByStatus = [
                        'pending' => [],
                        'in_progress' => [],
                        'fixed' => [],
                        'approved' => [],
                        'rejected' => []
                    ];
                    foreach ($recentBugs as $bug) {
                        $bugsByStatus[$bug['status']][] = $bug;
                    }

                    // Display each status box
                    $statusLabels = [
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'fixed' => 'Fixed',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected'
                    ];

                    foreach ($statusLabels as $status => $label) {
                        if (!empty($bugsByStatus[$status])) {
                            echo '<div class="status-box">';
                            echo '<h3>' . htmlspecialchars($label) . ' (' . count($bugsByStatus[$status]) . ')</h3>';
                            foreach ($bugsByStatus[$status] as $bug) {
                                echo '<div class="bug-card">';
                                echo '<div class="bug-header">';
                                echo '<h3>' . htmlspecialchars($bug['title']) . '</h3>';
                                echo '<div class="bug-meta">';
                                echo '<span class="priority-badge" style="background-color: ' . getPriorityColor($bug['priority']) . '">';
                                echo $bug['priority'] . '</span>';
                                echo '<span class="status-badge" style="background-color: ' . getStatusColor($bug['status']) . '">';
                                echo ucfirst($bug['status']) . '</span>';
                                echo '</div>';
                                echo '</div>';
                                echo '<p class="bug-description">' . htmlspecialchars(substr($bug['description'], 0, 120)) . '...</p>';
                                echo '<div class="bug-footer">';
                                echo '<span class="bug-date">' . formatDate($bug['created_at']) . '</span>';
                                if (isset($bug['tester_name'])) {
                                    echo '<span class="bug-assignee">Reported by: ' . htmlspecialchars($bug['tester_name']) . '</span>';
                                }
                                if (isset($bug['developer_name'])) {
                                    echo '<span class="bug-assignee">Assigned to: ' . htmlspecialchars($bug['developer_name']) . '</span>';
                                }
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                    }

                    // Display empty state if no bugs in any status
                    if (empty($recentBugs)) {
                        echo '<div class="status-box empty-state">';
                        echo '<div class="empty-icon">🐛</div>';
                        echo '<h3>No bugs yet</h3>';
                        echo '<p>No bugs in the system yet. Check back later or assign new bugs.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>