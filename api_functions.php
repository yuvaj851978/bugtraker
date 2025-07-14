<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_changes";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to get all projects
function getProjects() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all clients
function getClients() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get modules by project
function getModulesByProject($projectId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE project_id = ? ORDER BY name");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get submodules by module
function getSubmodulesByModule($moduleId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM submodules WHERE module_id = ? ORDER BY name");
    $stmt->execute([$moduleId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add new project
function addProject($name) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO projects (name) VALUES (?)");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}

// Function to add new client
function addClient($name) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO clients (name) VALUES (?)");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}

// Function to add new module
function addModule($name, $projectId) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO modules (name, project_id) VALUES (?, ?)");
    $stmt->execute([$name, $projectId]);
    return $pdo->lastInsertId();
}

// Function to add new submodule
function addSubmodule($name, $moduleId) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO submodules (name, module_id) VALUES (?, ?)");
    $stmt->execute([$name, $moduleId]);
    return $pdo->lastInsertId();
}

// Function to save meeting
function saveMeeting($projectId, $clientId, $date, $changes) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Insert meeting
        $stmt = $pdo->prepare("INSERT INTO meetings (project_id, client_id, meeting_date) VALUES (?, ?, ?)");
        $stmt->execute([$projectId, $clientId, $date]);
        $meetingId = $pdo->lastInsertId();
        
        // Insert changes
        $stmt = $pdo->prepare("INSERT INTO changes (meeting_id, module_id, submodule_id, description) VALUES (?, ?, ?, ?)");
        foreach ($changes as $change) {
            $stmt->execute([$meetingId, $change['moduleId'], $change['submoduleId'], $change['description']]);
        }
        
        $pdo->commit();
        return $meetingId;
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

// Function to get all meetings with basic info
function getAllMeetings() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT 
            m.id,
            m.meeting_date,
            m.created_at,
            p.name as project_name,
            c.name as client_name,
            COUNT(ch.id) as changes_count
        FROM meetings m
        JOIN projects p ON m.project_id = p.id
        JOIN clients c ON m.client_id = c.id
        LEFT JOIN changes ch ON m.id = ch.meeting_id
        GROUP BY m.id, m.meeting_date, m.created_at, p.name, c.name
        ORDER BY m.meeting_date DESC, m.created_at DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get meeting with changes
function getMeetingWithChanges($meetingId) {
    global $pdo;
    
    // Get meeting info
    $stmt = $pdo->prepare("
        SELECT 
            m.*, 
            p.name as project_name, 
            c.name as client_name
        FROM meetings m
        JOIN projects p ON m.project_id = p.id
        JOIN clients c ON m.client_id = c.id
        WHERE m.id = ?
    ");
    
    $stmt->execute([$meetingId]);
    $meeting = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$meeting) {
        return null;
    }
    
    // Get changes for this meeting
    $stmt = $pdo->prepare("
        SELECT 
            ch.*,
            mod.name as module_name,
            sub.name as submodule_name
        FROM changes ch
        JOIN modules mod ON ch.module_id = mod.id
        JOIN submodules sub ON ch.submodule_id = sub.id
        WHERE ch.meeting_id = ?
        ORDER BY ch.created_at
    ");
    
    $stmt->execute([$meetingId]);
    $changes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $meeting['changes'] = $changes;
    return $meeting;
}

// Function to delete meeting
function deleteMeeting($meetingId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Delete changes first (due to foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM changes WHERE meeting_id = ?");
        $stmt->execute([$meetingId]);
        
        // Delete meeting
        $stmt = $pdo->prepare("DELETE FROM meetings WHERE id = ?");
        $stmt->execute([$meetingId]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    header('Content-Type: application/json');
    
    try {
        switch ($action) {
            case 'getProjects':
                echo json_encode(['success' => true, 'data' => getProjects()]);
                break;
                
            case 'getClients':
                echo json_encode(['success' => true, 'data' => getClients()]);
                break;
                
            case 'getModules':
                echo json_encode(['success' => true, 'data' => getModulesByProject($input['projectId'])]);
                break;
                
            case 'getSubmodules':
                echo json_encode(['success' => true, 'data' => getSubmodulesByModule($input['moduleId'])]);
                break;
                
            case 'addProject':
                $id = addProject($input['name']);
                echo json_encode(['success' => true, 'data' => ['id' => $id, 'name' => $input['name']]]);
                break;
                
            case 'addClient':
                $id = addClient($input['name']);
                echo json_encode(['success' => true, 'data' => ['id' => $id, 'name' => $input['name']]]);
                break;
                
            case 'addModule':
                $id = addModule($input['name'], $input['projectId']);
                echo json_encode(['success' => true, 'data' => ['id' => $id, 'name' => $input['name']]]);
                break;
                
            case 'addSubmodule':
                $id = addSubmodule($input['name'], $input['moduleId']);
                echo json_encode(['success' => true, 'data' => ['id' => $id, 'name' => $input['name']]]);
                break;
                
            case 'saveMeeting':
                $meetingId = saveMeeting(
                    $input['projectId'], 
                    $input['clientId'], 
                    $input['date'], 
                    $input['changes']
                );
                echo json_encode(['success' => true, 'data' => ['meetingId' => $meetingId]]);
                break;
                
            case 'getAllMeetings':
                echo json_encode(['success' => true, 'data' => getAllMeetings()]);
                break;
                
            case 'getMeeting':
                $meeting = getMeetingWithChanges($input['meetingId']);
                if ($meeting) {
                    echo json_encode(['success' => true, 'data' => $meeting]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Meeting not found']);
                }
                break;
                
            case 'deleteMeeting':
                deleteMeeting($input['meetingId']);
                echo json_encode(['success' => true, 'message' => 'Meeting deleted successfully']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle GET requests for simple data retrieval
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    header('Content-Type: application/json');
    
    try {
        switch ($action) {
            case 'getProjects':
                echo json_encode(['success' => true, 'data' => getProjects()]);
                break;
                
            case 'getClients':
                echo json_encode(['success' => true, 'data' => getClients()]);
                break;
                
            case 'getAllMeetings':
                echo json_encode(['success' => true, 'data' => getAllMeetings()]);
                break;
                
            case 'getMeeting':
                if (isset($_GET['id'])) {
                    $meeting = getMeetingWithChanges($_GET['id']);
                    if ($meeting) {
                        echo json_encode(['success' => true, 'data' => $meeting]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Meeting not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Meeting ID required']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>