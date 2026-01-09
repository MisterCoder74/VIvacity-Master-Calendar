<?php
/**
 * Tasks API Endpoint
 * Handles all task-related CRUD operations (create, read, update, delete, list)
 * 
 * All operations are scoped to the authenticated user.
 */

// Initialize session and required files
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Cache-Control: no-store');

// Verify authentication
$user = getCurrentUser();
if (!$user) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

// Get user ID from session
$userId = $user['user_id'];

// Get action from request
$action = $_REQUEST['action'] ?? null;

// Validate action parameter exists
if (!$action) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Action parameter required']);
  exit;
}

// Handle different actions via switch statement
try {
  switch ($action) {
    case 'list':
      // List all tasks for user with optional filtering
      $filters = isset($_GET['filters']) ? json_decode($_GET['filters'], true) : [];
      $result = listUserTasks($userId, $filters);
      http_response_code($result['success'] ? 200 : 500);
      echo json_encode($result);
      break;
    
    case 'get':
      // Get single task by ID
      $taskId = $_REQUEST['taskId'] ?? null;
      if (!$taskId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Task ID required']);
        exit;
      }
      $result = getTask($taskId, $userId);
      http_response_code($result['success'] ? 200 : 404);
      echo json_encode($result);
      break;
    
    case 'create':
      // Create new task
      $data = json_decode(file_get_contents('php://input'), true);
      
      // Validate input
      $validation = validateTask($data);
      if (!$validation['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        exit;
      }
      
      // Prepare task data with sanitization
      $taskData = [
        'title' => sanitizeInput($data['title']),
        'description' => sanitizeInput($data['description'] ?? ''),
        'dueDate' => $data['dueDate'] ?? null,
        'dueTime' => $data['dueTime'] ?? null,
        'priority' => $data['priority'] ?? 'medium',
        'status' => $data['status'] ?? 'pending',
        'category' => $data['category'] ?? 'other',
        'tags' => is_array($data['tags'] ?? []) ? $data['tags'] : [],
        'relatedEventId' => $data['relatedEventId'] ?? null,
        'notes' => sanitizeInput($data['notes'] ?? '')
      ];
      
      $result = createTask($userId, $taskData);
      
      if ($result['success']) {
        http_response_code(201);
      }
      echo json_encode($result);
      break;
    
    case 'update':
      // Update existing task
      $data = json_decode(file_get_contents('php://input'), true);
      $taskId = $data['taskId'] ?? null;
      
      if (!$taskId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Task ID required']);
        exit;
      }
      
      // Verify task exists and belongs to user
      $existingTask = getTask($taskId, $userId);
      if (!$existingTask['success']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
      }
      
      // Validate input
      $validation = validateTask($data, true);
      if (!$validation['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        exit;
      }
      
      // Prepare task data with sanitization
      $taskData = [
        'title' => sanitizeInput($data['title']),
        'description' => sanitizeInput($data['description'] ?? ''),
        'dueDate' => $data['dueDate'] ?? null,
        'dueTime' => $data['dueTime'] ?? null,
        'priority' => $data['priority'] ?? 'medium',
        'status' => $data['status'] ?? 'pending',
        'category' => $data['category'] ?? 'other',
        'tags' => is_array($data['tags'] ?? []) ? $data['tags'] : [],
        'relatedEventId' => $data['relatedEventId'] ?? null,
        'notes' => sanitizeInput($data['notes'] ?? '')
      ];
      
      $result = updateTask($taskId, $userId, $taskData);
      http_response_code($result['success'] ? 200 : 500);
      echo json_encode($result);
      break;
    
    case 'delete':
      // Delete task
      $taskId = $_REQUEST['taskId'] ?? null;
      
      if (!$taskId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Task ID required']);
        exit;
      }
      
      // Verify task exists and belongs to user
      $existingTask = getTask($taskId, $userId);
      if (!$existingTask['success']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
      }
      
      $result = deleteTask($taskId, $userId);
      http_response_code($result['success'] ? 200 : 500);
      echo json_encode($result);
      break;
    
    default:
      // Invalid action
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid action']);
      exit;
  }
} catch (Exception $e) {
  // Handle any unexpected errors
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'Server error: ' . $e->getMessage()
  ]);
}
