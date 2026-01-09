<?php
/**
 * Events API Endpoint
 * Handles all event/meeting-related CRUD operations (create, read, update, delete, list)
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
      // List all events for user with optional filtering
      $filters = isset($_GET['filters']) ? json_decode($_GET['filters'], true) : [];
      $result = listUserEvents($userId, $filters);
      http_response_code($result['success'] ? 200 : 500);
      echo json_encode($result);
      break;
    
    case 'get':
      // Get single event by ID
      $eventId = $_REQUEST['eventId'] ?? null;
      if (!$eventId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Event ID required']);
        exit;
      }
      $result = getEvent($eventId, $userId);
      http_response_code($result['success'] ? 200 : 404);
      echo json_encode($result);
      break;
    
    case 'create':
      // Create new event
      $data = json_decode(file_get_contents('php://input'), true);
      if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
        exit;
      }
      
      // Validate input
      $validation = validateEvent($data);
      if (!$validation['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        exit;
      }
      
      // Prepare event data with sanitization
      $eventData = [
        'title' => sanitizeInput($data['title']),
        'description' => sanitizeInput($data['description'] ?? ''),
        'type' => $data['type'] ?? 'other',
        'startDate' => $data['startDate'],
        'startTime' => $data['startTime'],
        'endDate' => $data['endDate'],
        'endTime' => $data['endTime'],
        'duration' => $data['duration'] ?? null,
        'location' => sanitizeInput($data['location'] ?? ''),
        'platform' => $data['platform'] ?? 'none',
        'platformLink' => sanitizeInput($data['platformLink'] ?? ''),
        'attendees' => is_array($data['attendees'] ?? []) ? $data['attendees'] : [],
        'status' => $data['status'] ?? 'scheduled',
        'isRecurring' => $data['isRecurring'] ?? false,
        'recurringPattern' => $data['recurringPattern'] ?? null,
        'notes' => sanitizeInput($data['notes'] ?? ''),
        'actionItems' => is_array($data['actionItems'] ?? []) ? $data['actionItems'] : [],
        'reminders' => is_array($data['reminders'] ?? []) ? $data['reminders'] : []
      ];
      
      $result = createEvent($userId, $eventData);
      
      if ($result['success']) {
        http_response_code(201);
      }
      echo json_encode($result);
      break;
    
    case 'update':
      // Update existing event
      $data = json_decode(file_get_contents('php://input'), true);
      if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
        exit;
      }
      $eventId = $data['eventId'] ?? null;
      
      if (!$eventId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Event ID required']);
        exit;
      }
      
      // Verify event exists and belongs to user
      $existingEvent = getEvent($eventId, $userId);
      if (!$existingEvent['success']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Event not found']);
        exit;
      }
      
      // Validate input
      $validation = validateEvent($data, true);
      if (!$validation['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        exit;
      }
      
      // Prepare event data with sanitization (partial updates supported)
      $eventData = [];

      foreach (['title', 'description', 'location', 'platformLink', 'notes'] as $field) {
        if (array_key_exists($field, $data)) {
          $eventData[$field] = sanitizeInput($data[$field]);
        }
      }

      foreach ([
        'type', 'startDate', 'startTime', 'endDate', 'endTime', 'duration', 'attendees',
        'platform', 'status', 'isRecurring', 'recurringPattern', 'actionItems', 'reminders'
      ] as $field) {
        if (array_key_exists($field, $data)) {
          $eventData[$field] = $data[$field];
        }
      }
      
      $result = updateEvent($eventId, $userId, $eventData);
      http_response_code($result['success'] ? 200 : 500);
      echo json_encode($result);
      break;
    
    case 'delete':
      // Delete event
      $eventId = $_REQUEST['eventId'] ?? null;
      
      if (!$eventId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Event ID required']);
        exit;
      }
      
      // Verify event exists and belongs to user
      $existingEvent = getEvent($eventId, $userId);
      if (!$existingEvent['success']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Event not found']);
        exit;
      }
      
      $result = deleteEvent($eventId, $userId);
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