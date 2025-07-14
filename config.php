<?php
// Database configuration
define('DB_HOST', '192.168.0.100');
define('DB_USER', 'thekarti_yuvrajbugfix');
define('DB_PASS', '12345678');
define('DB_NAME', 'thekarti_yuvrajbugfix');

// Site configuration
define('SITE_NAME', 'Bug Tracker Pro');
define('UPLOAD_DIR', 'uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Authentication helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function requireRole($role) {
    requireAuth();
    if (getUserRole() !== $role) {
        header('Location: dashboard.php');
        exit();
    }
}

// Helper functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function formatDate($date) {
    return date('M d, Y g:i A', strtotime($date));
}

function getPriorityColor($priority) {
    switch($priority) {
        case 'P1': return '#ef4444'; // red
        case 'P2': return '#f97316'; // orange
        case 'P3': return '#eab308'; // yellow
        case 'P4': return '#22c55e'; // green
        default: return '#6b7280'; // gray
    }
}

function getStatusColor($status) {
    switch($status) {
        case 'pending': return '#6b7280'; // gray
        case 'in_progress': return '#3b82f6'; // blue
        case 'fixed': return '#f59e0b'; // amber
        case 'awaiting_approval': return '#1d98c4ff'; // amber
        case 'approved': return '#10b981'; // green
        case 'rejected': return '#ef4444'; // red
        default: return '#6b7280'; // gray
    }
}
?>
