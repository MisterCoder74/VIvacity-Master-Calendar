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

// Data file path
$dataFile = __DIR__ . '/../data/events.json';

// Ensure data directory exists
$dataDir = dirname($dataFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Initialize file if it doesn't exist
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode(['events' => []]));
}

try {
    switch ($action) {
        case 'list':
            // Get all events for current user
            $allEvents = json_decode(file_get_contents($dataFile), true)['events'] ?? [];
            $userEvents = array_filter($allEvents, fn($event) => $event['userId'] === $userId);
            echo json_encode([
                'success' => true,
                'data' => array_values($userEvents)
            ]);
            break;

        case 'get':
            // Get single event
            $eventId = $_GET['id'] ?? $_GET['eventId'] ?? '';
            if (!$eventId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Event ID required']);
                break;
            }

            $data = json_decode(file_get_contents($dataFile), true);
            $event = null;
            foreach ($data['events'] ?? [] as $e) {
                if ($e['id'] === $eventId && $e['userId'] === $userId) {
                    $event = $e;
                    break;
                }
            }

            if ($event) {
                echo json_encode(['success' => true, 'data' => $event]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Event not found']);
            }
            break;

        case 'create':
            // Create new event
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['title']) || empty($input['startDate'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Title and start date required']);
                break;
            }

            $newEvent = [
                'id' => uniqid('event_'),
                'userId' => $userId,
                'title' => htmlspecialchars($input['title']),
                'description' => htmlspecialchars($input['description'] ?? ''),
                'type' => $input['type'] ?? 'personal',
                'startDate' => $input['startDate'],
                'startTime' => $input['startTime'] ?? '00:00',
                'endDate' => $input['endDate'] ?? $input['startDate'],
                'endTime' => $input['endTime'] ?? '01:00',
                'location' => htmlspecialchars($input['location'] ?? ''),
                'platform' => $input['platform'] ?? 'in_person',
                'platformLink' => htmlspecialchars($input['platformLink'] ?? ''),
                'attendees' => $input['attendees'] ?? [],
                'notes' => htmlspecialchars($input['notes'] ?? ''),
                'status' => $input['status'] ?? 'scheduled',
                'isRecurring' => $input['isRecurring'] ?? false,
                'recurrencePattern' => $input['recurrencePattern'] ?? null,
                'createdAt' => date('c'),
                'updatedAt' => date('c')
            ];

            $data = json_decode(file_get_contents($dataFile), true);
            $data['events'][] = $newEvent;
            file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            echo json_encode(['success' => true, 'data' => $newEvent]);
            break;

        case 'update':
            // Update event
            $input = json_decode(file_get_contents('php://input'), true);
            $eventId = $_GET['id'] ?? $_GET['eventId'] ?? $input['eventId'] ?? '';

            if (!$eventId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Event ID required']);
                break;
            }

            $data = json_decode(file_get_contents($dataFile), true);
            $updated = false;

            foreach ($data['events'] ?? [] as &$event) {
                if ($event['id'] === $eventId && $event['userId'] === $userId) {
                    // Update allowed fields
                    if (isset($input['title'])) $event['title'] = htmlspecialchars($input['title']);
                    if (isset($input['description'])) $event['description'] = htmlspecialchars($input['description']);
                    if (isset($input['type'])) $event['type'] = $input['type'];
                    if (isset($input['startDate'])) $event['startDate'] = $input['startDate'];
                    if (isset($input['startTime'])) $event['startTime'] = $input['startTime'];
                    if (isset($input['endDate'])) $event['endDate'] = $input['endDate'];
                    if (isset($input['endTime'])) $event['endTime'] = $input['endTime'];
                    if (isset($input['location'])) $event['location'] = htmlspecialchars($input['location']);
                    if (isset($input['platform'])) $event['platform'] = $input['platform'];
                    if (isset($input['platformLink'])) $event['platformLink'] = htmlspecialchars($input['platformLink']);
                    if (isset($input['attendees'])) $event['attendees'] = $input['attendees'];
                    if (isset($input['notes'])) $event['notes'] = htmlspecialchars($input['notes']);
                    if (isset($input['status'])) $event['status'] = $input['status'];
                    if (isset($input['isRecurring'])) $event['isRecurring'] = $input['isRecurring'];
                    if (isset($input['recurrencePattern'])) $event['recurrencePattern'] = $input['recurrencePattern'];
                    $event['updatedAt'] = date('c');
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                echo json_encode(['success' => true, 'data' => 'Event updated']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Event not found or unauthorized']);
            }
            break;

        case 'delete':
            // Delete event
            $eventId = $_GET['id'] ?? $_GET['eventId'] ?? $_POST['eventId'] ?? '';
            if (!$eventId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Event ID required']);
                break;
            }

            $data = json_decode(file_get_contents($dataFile), true);
            $deleted = false;

            foreach ($data['events'] ?? [] as $index => $event) {
                if ($event['id'] === $eventId && $event['userId'] === $userId) {
                    unset($data['events'][$index]);
                    $deleted = true;
                    break;
                }
            }

            if ($deleted) {
                $data['events'] = array_values($data['events']); // Re-index array
                file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                echo json_encode(['success' => true, 'data' => 'Event deleted']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Event not found or unauthorized']);
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
