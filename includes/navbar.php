<nav class="navbar">
    <div class="navbar-brand">
        <h1><?php echo SITE_NAME; ?></h1>
    </div>
    
    <div class="navbar-nav">
        <a href="dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <span>🏠</span> Dashboard
        </a>
        
        <?php if (getUserRole() == 'tester'): ?>
            <a href="create_bug.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'create_bug.php') ? 'active' : ''; ?>">
                <span>🐛</span> Report Bug
            </a>
            <a href="my_bugs.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'my_bugs.php') ? 'active' : ''; ?>">
                <span>📋</span> My Bugs
            </a>
            <a href="chat_portal.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'chat_portal.php') ? 'active' : ''; ?>">
                <span>💬</span> View Chat
            </a>
            <a href="generate_report.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'generate_report.php') ? 'active' : ''; ?>">
                <span>📊</span> Reports
            </a>
            <a href="bug_shift.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'chat_portal.php') ? 'active' : ''; ?>">
                <span>💬</span> Bug Shift
            </a>
        <?php elseif (getUserRole() == 'developer'): ?>
            <a href="developer_bugs.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'developer_bugs.php') ? 'active' : ''; ?>">
                <span>🔧</span> Assigned Bugs
            </a>
            <a href="chat_portal.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'chat_portal.php') ? 'active' : ''; ?>">
                <span>💬</span> View Chat
            </a>
             <a href="bug_shift.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'chat_portal.php') ? 'active' : ''; ?>">
                <span>💬</span> Bug Shift
            </a>
        <?php endif; ?>
    </div>
    
    <div class="navbar-user">
        <div class="user-menu">
            <button class="user-button" onclick="toggleUserMenu()">
                <span class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?></span>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <span class="user-role"><?php echo ucfirst(getUserRole()); ?></span>
            </button>
            <div class="user-dropdown" id="userDropdown">
                <a href="profile.php" class="dropdown-item">
                    <span>👤</span> Profile
                </a>
                <a href="settings.php" class="dropdown-item">
                    <span>⚙️</span> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item">
                    <span>🚪</span> Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userDropdown');
    
    if (!userMenu.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});
</script>