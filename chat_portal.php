<?php
require_once 'config.php';
requireAuth();

$userRole = getUserRole();
$selectedBugId = intval($_GET['bug_id'] ?? 0);
$error = '';
$success = '';

// Get bugs based on user role
$bugs = [];
try {
    if ($userRole == 'tester') {
        $stmt = $pdo->prepare("
            SELECT bt.id, bt.title, bt.module, bt.submodule, bt.status, bt.priority, u.name as developer_name
            FROM bug_tickets bt 
            LEFT JOIN users u ON bt.assigned_dev_id = u.id 
            WHERE bt.created_by = ? 
            ORDER BY bt.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } elseif ($userRole == 'developer') {
        $stmt = $pdo->prepare("
            SELECT bt.id, bt.title, bt.module, bt.submodule, bt.status, bt.priority, u.name as tester_name
            FROM bug_tickets bt 
            JOIN users u ON bt.created_by = u.id 
            WHERE bt.assigned_dev_id = ? 
            ORDER BY bt.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $bugs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load bugs';
}

// Get selected bug details
$selectedBug = null;
if ($selectedBugId > 0) {
    try {
        if ($userRole == 'tester') {
            $stmt = $pdo->prepare("
                SELECT bt.*, u.name as developer_name
                FROM bug_tickets bt 
                LEFT JOIN users u ON bt.assigned_dev_id = u.id 
                WHERE bt.id = ? AND bt.created_by = ?
            ");
            $stmt->execute([$selectedBugId, $_SESSION['user_id']]);
        } elseif ($userRole == 'developer') {
            $stmt = $pdo->prepare("
                SELECT bt.*, u.name as tester_name
                FROM bug_tickets bt 
                JOIN users u ON bt.created_by = u.id 
                WHERE bt.id = ? AND bt.assigned_dev_id = ?
            ");
            $stmt->execute([$selectedBugId, $_SESSION['user_id']]);
        }
        $selectedBug = $stmt->fetch();
    } catch (PDOException $e) {
        // Handle error
    }
}

// Get chat messages for selected bug
$messages = [];
if ($selectedBug) {
    try {
        $stmt = $pdo->prepare("
            SELECT br.*, u.name as user_name, u.role as user_role
            FROM bug_remarks br 
            JOIN users u ON br.user_id = u.id 
            WHERE br.bug_id = ? 
            ORDER BY br.timestamp ASC
        ");
        $stmt->execute([$selectedBugId]);
        $messages = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Handle error
    }
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && $selectedBug) {
    $message = sanitizeInput($_POST['message']);
    
    if (!empty($message)) {
        // Handle file upload
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filesize = $_FILES['image']['size'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($filetype, $allowed) && $filesize < 5000000) {
                $image = time() . '_' . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $image);
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO bug_remarks (bug_id, user_id, remark, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$selectedBugId, $_SESSION['user_id'], $message, $image]);
            
            // Redirect to prevent form resubmission
            header("Location: chat_portal.php?bug_id=$selectedBugId&success=1");
            exit();
        } catch (PDOException $e) {
            $error = 'Failed to send message';
        }
    }
}

$success = isset($_GET['success']) ? 'Message sent successfully!' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Portal - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    
    <div class="app-container">
        <?php include 'includes/navbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>💬 Bug Chat Portal</h1>
                <p>Select a bug to start chatting with your team</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="chat-portal-container">
                <div class="bugs-sidebar">
                    <h3>Select Bug to Chat</h3>
                    <div class="bugs-list-sidebar">
                        <?php if (empty($bugs)): ?>
                            <div class="no-bugs-sidebar">
                                <p>No bugs available for chat</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($bugs as $bug): ?>
                                <div class="bug-item-sidebar <?php echo ($bug['id'] == $selectedBugId) ? 'active' : ''; ?>" 
                                     onclick="selectBug(<?php echo $bug['id']; ?>)">
                                    <div class="bug-item-header">
                                        <span class="bug-id">#<?php echo $bug['id']; ?></span>
                                        <div class="bug-badges-small">
                                            <span class="priority-badge-small" style="background-color: <?php echo getPriorityColor($bug['priority']); ?>">
                                                <?php echo $bug['priority']; ?>
                                            </span>
                                            <span class="status-badge-small" style="background-color: <?php echo getStatusColor($bug['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $bug['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <h4><?php echo htmlspecialchars($bug['title']); ?></h4>
                                    <div class="bug-module-info">
                                        <span class="module-tag"><?php echo htmlspecialchars($bug['module']); ?></span>
                                        <span class="submodule-tag"><?php echo htmlspecialchars($bug['submodule']); ?></span>
                                    </div>
                                    <p class="bug-assignee-small">
                                        <?php if ($userRole == 'tester'): ?>
                                            Dev: <?php echo $bug['developer_name'] ? htmlspecialchars($bug['developer_name']) : 'Unassigned'; ?>
                                        <?php else: ?>
                                            Tester: <?php echo htmlspecialchars($bug['tester_name']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="chat-area">
                    <?php if (!$selectedBug): ?>
                        <div class="no-bug-selected">
                            <div class="no-bug-icon">💬</div>
                            <h3>Select a Bug to Start Chatting</h3>
                            <p>Choose a bug from the sidebar to begin the conversation with your team member.</p>
                        </div>
                    <?php else: ?>
                        <div class="chat-header">
                            <div class="selected-bug-info">
                                <h3>Bug #<?php echo $selectedBug['id']; ?>: <?php echo htmlspecialchars($selectedBug['title']); ?></h3>
                                <div class="bug-meta-chat">
                                    <span class="module-info"><?php echo htmlspecialchars($selectedBug['module']); ?> → <?php echo htmlspecialchars($selectedBug['submodule']); ?></span>
                                    <div class="bug-badges">
                                        <span class="status-badge" style="background-color: <?php echo getStatusColor($selectedBug['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $selectedBug['status'])); ?>
                                        </span>
                                        <span class="priority-badge" style="background-color: <?php echo getPriorityColor($selectedBug['priority']); ?>">
                                            <?php echo $selectedBug['priority']; ?>
                                        </span>
                                    </div>
                                </div>
                                <p class="chat-with">
                                    Chatting with: 
                                    <?php if ($userRole == 'tester'): ?>
                                        <?php echo $selectedBug['developer_name'] ? htmlspecialchars($selectedBug['developer_name']) : 'Unassigned Developer'; ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($selectedBug['tester_name']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="chat-actions">
                                <a href="view_bug.php?id=<?php echo $selectedBug['id']; ?>" class="btn btn-outline btn-small">
                                    <span>👁️</span> View Details
                                </a>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <?php if (empty($messages)): ?>
                                <div class="no-messages">
                                    <div class="no-messages-icon">💬</div>
                                    <h4>Start the conversation</h4>
                                    <p>No messages yet. Be the first to start discussing this bug!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message <?php echo $message['user_role']; ?> <?php echo ($message['user_id'] == $_SESSION['user_id']) ? 'own-message' : 'other-message'; ?>">
                                        <div class="message-header">
                                            <div class="message-author">
                                                <span class="author-avatar"><?php echo strtoupper(substr($message['user_name'], 0, 2)); ?></span>
                                                <span class="author-name"><?php echo htmlspecialchars($message['user_name']); ?></span>
                                                <span class="author-role"><?php echo ucfirst($message['user_role']); ?></span>
                                            </div>
                                            <span class="message-time"><?php echo formatDate($message['timestamp']); ?></span>
                                        </div>
                                        <div class="message-content">
                                            <p><?php echo nl2br(htmlspecialchars($message['remark'])); ?></p>
                                            <?php if ($message['image']): ?>
                                                <div class="message-image">
                                                    <img src="<?php echo UPLOAD_DIR . $message['image']; ?>" alt="Attached Image" onclick="openModal(this.src)">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="chat-input">
                            <form method="POST" enctype="multipart/form-data" class="message-form">
                                <div class="input-group">
                                    <textarea name="message" placeholder="Type your message..." rows="3" required></textarea>
                                    <div class="input-actions">
                                        <label for="image" class="file-upload-btn">
                                            <span>📎</span> Attach
                                            <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                                        </label>
                                        <button type="submit" class="btn btn-primary">
                                            <span>📤</span> Send
                                        </button>
                                    </div>
                                </div>
                                <div id="filePreview" class="file-preview"></div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <img id="modalImage" src="" alt="Image">
        </div>
    </div>

    <script>
        function selectBug(bugId) {
            window.location.href = 'chat_portal.php?bug_id=' + bugId;
        }

        function openModal(src) {
            document.getElementById('imageModal').style.display = 'block';
            document.getElementById('modalImage').src = src;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // File preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('filePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="preview-item">
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" onclick="clearFilePreview()" class="remove-preview">×</button>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });

        function clearFilePreview() {
            document.getElementById('image').value = '';
            document.getElementById('filePreview').innerHTML = '';
        }

        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Scroll to bottom on page load
        window.addEventListener('load', scrollToBottom);
    </script>

    <style>
        .chat-portal-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            height: calc(100vh - 200px);
            gap: 0;
            background: var(--surface-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .bugs-sidebar {
            background: var(--background-color);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }

        .bugs-sidebar h3 {
            padding: 1.5rem;
            margin: 0;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            background: var(--surface-color);
        }

        .bugs-list-sidebar {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .no-bugs-sidebar {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .bug-item-sidebar {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .bug-item-sidebar:hover {
            background: var(--surface-color);
            border-color: var(--border-color);
        }

        .bug-item-sidebar.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .bug-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .bug-id {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .bug-badges-small {
            display: flex;
            gap: 0.25rem;
        }

        .priority-badge-small,
        .status-badge-small {
            padding: 0.125rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
            color: white;
        }

        .bug-item-sidebar h4 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            line-height: 1.3;
        }

        .bug-module-info {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .module-tag,
        .submodule-tag {
            padding: 0.125rem 0.5rem;
            background: var(--surface-color);
            border-radius: 4px;
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .bug-item-sidebar.active .module-tag,
        .bug-item-sidebar.active .submodule-tag {
            background: rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.9);
        }

        .bug-assignee-small {
            font-size: 0.8rem;
            margin: 0;
            opacity: 0.8;
        }

        .chat-area {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .no-bug-selected {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
            text-align: center;
        }

        .no-bug-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--surface-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .selected-bug-info h3 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
            font-size: 1.1rem;
        }

        .bug-meta-chat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .module-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .chat-with {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .no-messages {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
            text-align: center;
        }

        .no-messages-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .message {
            display: flex;
            flex-direction: column;
            max-width: 70%;
        }

        .message.own-message {
            align-self: flex-end;
        }

        .message.other-message {
            align-self: flex-start;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .message-author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .author-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.7rem;
        }

        .author-name {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .author-role {
            padding: 0.125rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
            background: var(--primary-color);
            color: white;
        }

        .message.tester .author-role {
            background: var(--success-color);
        }

        .message-time {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .message-content {
            background: var(--background-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
        }

        .message.own-message .message-content {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .message-content p {
            margin: 0;
            line-height: 1.5;
        }

        .message-image {
            margin-top: 1rem;
        }

        .message-image img {
            max-width: 200px;
            height: auto;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .message-image img:hover {
            transform: scale(1.05);
        }

        .chat-input {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--background-color);
        }

        .message-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .input-group {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .input-group textarea {
            flex: 1;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
        }

        .input-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .input-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .file-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }

        .file-upload-btn:hover {
            background: var(--background-color);
        }

        .file-preview {
            margin-top: 1rem;
        }

        .preview-item {
            position: relative;
            display: inline-block;
        }

        .preview-item img {
            max-width: 100px;
            height: auto;
            border-radius: var(--border-radius);
        }

        .remove-preview {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--error-color);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .chat-portal-container {
                grid-template-columns: 1fr;
                height: auto;
            }

            .bugs-sidebar {
                max-height: 300px;
            }

            .chat-area {
                min-height: 500px;
            }

            .message {
                max-width: 90%;
            }

            .input-group {
                flex-direction: column;
                align-items: stretch;
            }

            .input-actions {
                flex-direction: row;
                justify-content: space-between;
            }
        }
    </style>
</body>
</html>