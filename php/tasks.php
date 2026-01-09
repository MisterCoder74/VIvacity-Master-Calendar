<?php
// MUST be first line
session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

// Determine action
$action = $_GET['action'] ?? '';

// Data file path (relative to this PHP file)
$dataFile = __DIR__ . '/../data/tasks.json';

// Ensure data directory exists
$dataDir = dirname($dataFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Initialize file if it doesn't exist
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode(['tasks' => []]));
}

try {
    switch ($action) {
        case 'list':
            // Get all tasks for current user
            $allTasks = json_decode(file_get_contents($dataFile), true)['tasks'] ?? [];
            $userTasks = array_filter($allTasks, fn($task) => $task['userId'] === $userId);
            echo json_encode([
                'success' => true,
                'data' => array_values($userTasks)
            ]);
            break;

        case 'get':
            // Get single task
            $taskId = $_GET['id'] ?? $_GET['taskId'] ?? '';
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Task ID required']);
                break;
            }

            $data = json_decode(file_get_contents($dataFile), true);
            $task = null;
            foreach ($data['tasks'] ?? [] as $t) {
                if ($t['id'] === $taskId && $t['userId'] === $userId) {
                    $task = $t;
                    break;
                }
            }

            if ($task) {
                echo json_encode(['success' => true, 'data' => $task]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Task not found']);
            }
            break;

        case 'create':
            // Create new task
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['title']) || empty($input['dueDate'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Title and due date required']);
                break;
            }

            $newTask = [
                'id' => uniqid('task_'),
                'userId' => $userId,
                'title' => htmlspecialchars($input['title']),
                'description' => htmlspecialchars($input['description'] ?? ''),
                'priority' => $input['priority'] ?? 'medium',
                'status' => $input['status'] ?? 'pending',
                'dueDate' => $input['dueDate'],
                'dueTime' => $input['dueTime'] ?? '00:00',
                'createdAt' => date('c'),
                'updatedAt' => date('c')
            ];

            $data = json_decode(file_get_contents($dataFile), true);
            $data['tasks'][] = $newTask;
            file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            echo json_encode(['success' => true, 'data' => $newTask]);
            break;

        case 'update':
            // Update task
            $input = json_decode(file_get_contents('php://input'), true);
            $taskId = $_GET['id'] ?? $_GET['taskId'] ?? $input['taskId'] ?? '';

            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Task ID required']);
                break;
            }

            $data = json_decode(file_get_contents($dataFile), true);
            $updated = false;

            foreach ($data['tasks'] ?? [] as &$task) {
                if ($task['id'] === $taskId && $task['userId'] === $userId) {
                    // Update allowed fields
                    if (isset($input['title'])) $task['title'] = htmlspecialchars($input['title']);
                    if (isset($input['description'])) $task['description'] = htmlspecialchars($input['description']);
                    if (isset($input['priority'])) $task['priority'] = $input['priority'];
                    if (isset($input['status'])) $task['status'] = $input['status'];
                    if (isset($input['dueDate'])) $task['dueDate'] = $input['dueDate'];
                    if (isset($input['dueTime'])) $task['dueTime'] = $input['dueTime'];
                    $task['updatedAt'] = date('c');
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                echo json_encode(['success' => true, 'data' => 'Task updated']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Task not found or unauthorized']);
            }
            break;

        case 'delete':
            // Delete task
            $taskId = $_GET['id'] ?? $_GET['taskId'] ?? $_POST['taskId'] ?? '';
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Task ID required']);
                break;
            }

            $data = json_decode(file_get_contents($dataFile), true);
            $deleted = false;

            foreach ($data['tasks'] ?? [] as $index => $task) {
                if ($task['id'] === $taskId && $task['userId'] === $userId) {
                    unset($data['tasks'][$index]);
                    $deleted = true;
                    break;
                }
            }

            if ($deleted) {
                $data['tasks'] = array_values($data['tasks']); // Re-index array
                file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                echo json_encode(['success' => true, 'data' => 'Task deleted']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Task not found or unauthorized']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
