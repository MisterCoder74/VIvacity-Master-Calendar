<?php
/**
 * Utility Functions
 * Common functions used throughout the application.
 *
 * Data relationships:
 * - Tasks can be related to Events via tasks.relatedEventId (stores an event ID).
 * - Events can reference Tasks as actionItems (array of task IDs).
 * - TimeBlocks represent reserved/blocked periods and should prevent scheduling during those periods.
 * - User Preferences control default behaviors and AI scheduling decisions.
 */

/**
 * Read and decode a JSON file.
 *
 * @param string $filepath Path to JSON file
 * @return array Decoded JSON data or empty array on failure
 */
function readJsonFile($filepath) {
    if (!file_exists($filepath)) {
        logEvent("JSON file not found: $filepath", 'error');
        return [];
    }

    $content = file_get_contents($filepath);
    if ($content === false) {
        logEvent("Failed to read JSON file: $filepath", 'error');
        return [];
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logEvent('JSON decode error: ' . json_last_error_msg(), 'error');
        return [];
    }

    return $data;
}

/**
 * Write data to a JSON file.
 * Note: No file locking as per specification.
 *
 * @param string $filepath Path to JSON file
 * @param array $data Data to encode and write
 * @return bool True on success, false on failure
 */
function writeJsonFile($filepath, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        logEvent('JSON encode error: ' . json_last_error_msg(), 'error');
        return false;
    }

    $result = file_put_contents($filepath, $json);
    if ($result === false) {
        logEvent("Failed to write JSON file: $filepath", 'error');
        return false;
    }

    return true;
}

/**
 * Consistent response format for CRUD/validation operations.
 *
 * @param bool $success
 * @param mixed $data
 * @param string|null $error
 * @return array
 */
function response($success, $data = null, $error = null) {
    return [
        'success' => (bool)$success,
        'data' => $data,
        'error' => $error
    ];
}

/**
 * Generate a unique ID with optional prefix.
 *
 * @param string $prefix Prefix for the ID (default: 'id')
 * @return string Unique identifier
 */
function generateUniqueId($prefix = 'id') {
    return uniqid($prefix . '_', true);
}

/**
 * Get current timestamp in ISO 8601 format (UTC).
 *
 * @return string
 */
function getCurrentTimestamp() {
    return gmdate('c');
}

/**
 * Sanitize input to prevent XSS attacks.
 *
 * @param mixed $input Raw input
 * @return mixed Sanitized string (if string), otherwise original
 */
function sanitizeInput($input) {
    if (!is_string($input)) {
        return $input;
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format.
 *
 * @param string $email Email address
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get current logged-in user from session.
 *
 * @return array|null User data array or null if not logged in
 */
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'google_id' => $_SESSION['google_id'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'name' => $_SESSION['name'] ?? null,
        'profile_picture' => $_SESSION['profile_picture'] ?? null
    ];
}

/**
 * Log events.
 *
 * @param string $message
 * @param string $level info|warning|error
 * @return void
 */
function logEvent($message, $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] [$level] $message");
}

/**
 * Find user by Google ID in users.json.
 *
 * @param string $googleId
 * @return array|null
 */
function findUserByGoogleId($googleId) {
    $data = readJsonFile(USERS_JSON_PATH);

    if (!isset($data['users']) || !is_array($data['users'])) {
        return null;
    }

    foreach ($data['users'] as $user) {
        if (isset($user['google_id']) && $user['google_id'] === $googleId) {
            return $user;
        }
    }

    return null;
}

/**
 * Find user by email in users.json.
 *
 * @param string $email
 * @return array|null
 */
function findUserByEmail($email) {
    $data = readJsonFile(USERS_JSON_PATH);

    if (!isset($data['users']) || !is_array($data['users'])) {
        return null;
    }

    foreach ($data['users'] as $user) {
        if (isset($user['email']) && $user['email'] === $email) {
            return $user;
        }
    }

    return null;
}

/**
 * Create a new user in users.json.
 *
 * @param array $userData User data from Google OAuth
 * @return array|null Created user data or null on failure
 */
function createUser($userData) {
    $data = readJsonFile(USERS_JSON_PATH);

    if (!isset($data['users'])) {
        $data['users'] = [];
    }

    $newUser = [
        'id' => generateUniqueId('user'),
        'google_id' => sanitizeInput($userData['google_id']),
        'name' => sanitizeInput($userData['name']),
        'email' => sanitizeInput($userData['email']),
        'profile_picture' => sanitizeInput($userData['profile_picture']),
        'timezone' => 'UTC',
        'created_at' => getCurrentTimestamp(),
        'last_login' => getCurrentTimestamp(),
        'preferences' => [
            'notifications_enabled' => true,
            'default_view' => 'month'
        ]
    ];

    $data['users'][] = $newUser;

    if (writeJsonFile(USERS_JSON_PATH, $data)) {
        logEvent("New user created: {$newUser['email']}", 'info');
        return $newUser;
    }

    return null;
}

/**
 * Update user's last login timestamp.
 *
 * @param string $googleId
 * @return bool
 */
function updateLastLogin($googleId) {
    $data = readJsonFile(USERS_JSON_PATH);

    if (!isset($data['users']) || !is_array($data['users'])) {
        return false;
    }

    $updated = false;
    foreach ($data['users'] as &$user) {
        if (isset($user['google_id']) && $user['google_id'] === $googleId) {
            $user['last_login'] = getCurrentTimestamp();
            $updated = true;
            break;
        }
    }

    if ($updated && writeJsonFile(USERS_JSON_PATH, $data)) {
        logEvent("Updated last login for Google ID: $googleId", 'info');
        return true;
    }

    return false;
}

/**
 * Send JSON response and exit.
 *
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 * @return void
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo json_encode($data);
    exit;
}

// ==================== DATE/TIME & GENERAL HELPERS ====================

/**
 * Validate date format (YYYY-MM-DD).
 */
function isValidDate($date) {
    if (!is_string($date)) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate time format (HH:MM, 24-hour).
 */
function isValidTime($time) {
    if (!is_string($time)) {
        return false;
    }
    $t = DateTime::createFromFormat('H:i', $time);
    return $t && $t->format('H:i') === $time;
}

/**
 * Validate a date/time combination.
 */
function isValidDateTime($date, $time) {
    return isValidDate($date) && isValidTime($time);
}

/**
 * Validate hex color code (e.g. #FF5733).
 */
function isValidColor($color) {
    if (!is_string($color)) {
        return false;
    }
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
}

/**
 * Convert HH:MM to minutes.
 */
function convertTimeToMinutes($time) {
    if (!isValidTime($time)) {
        return 0;
    }
    [$hours, $minutes] = explode(':', $time);
    return ((int)$hours * 60) + (int)$minutes;
}

/**
 * Convert minutes to HH:MM.
 */
function convertMinutesToTime($minutes) {
    $minutes = (int)$minutes;
    $hours = (int)floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}

/**
 * Get difference between two dates (in days).
 */
function getDateDifference($date1, $date2) {
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    return (int)$d1->diff($d2)->days;
}

/**
 * Get all dates in inclusive range.
 */
function getDatesInRange($startDate, $endDate) {
    $dates = [];
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day');

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);

    foreach ($period as $date) {
        $dates[] = $date->format('Y-m-d');
    }

    return $dates;
}

/**
 * Check if a time is within working hours.
 */
function isWithinWorkingHours($time, $userPreferences) {
    if (!isset($userPreferences['workingHoursStart'], $userPreferences['workingHoursEnd'])) {
        return true;
    }

    $timeMinutes = convertTimeToMinutes($time);
    $startMinutes = convertTimeToMinutes($userPreferences['workingHoursStart']);
    $endMinutes = convertTimeToMinutes($userPreferences['workingHoursEnd']);

    return $timeMinutes >= $startMinutes && $timeMinutes <= $endMinutes;
}

// ==================== DATA FILE HELPERS ====================

/**
 * Ensure a JSON data file exists and has the expected root structure.
 * This is intentionally done without file locking (per specification).
 *
 * @param string $filepath
 * @param array $defaultData
 * @return array
 */
function ensureJsonFileStructure($filepath, $defaultData) {
    if (!file_exists($filepath)) {
        writeJsonFile($filepath, $defaultData);
        return $defaultData;
    }

    $data = readJsonFile($filepath);
    if (empty($data) || !is_array($data)) {
        writeJsonFile($filepath, $defaultData);
        return $defaultData;
    }

    return $data;
}

function loadTasksData() {
    return ensureJsonFileStructure(TASKS_JSON_PATH, ['tasks' => []]);
}

function loadEventsData() {
    return ensureJsonFileStructure(EVENTS_JSON_PATH, ['events' => []]);
}

function loadTimeBlocksData() {
    return ensureJsonFileStructure(TIMEBLOCKS_JSON_PATH, ['timeBlocks' => []]);
}

function loadPreferencesData() {
    return ensureJsonFileStructure(USER_PREFS_JSON_PATH, ['preferences' => []]);
}

// ==================== USER-SPECIFIC DATA RETRIEVAL ====================

/**
 * Get all tasks for a user.
 */
function getUserTasks($userId) {
    $data = loadTasksData();
    $tasks = array_values(array_filter($data['tasks'], fn($t) => ($t['userId'] ?? null) === $userId));
    return response(true, $tasks, null);
}

/**
 * Get all events for a user.
 */
function getUserEvents($userId) {
    $data = loadEventsData();
    $events = array_values(array_filter($data['events'], fn($e) => ($e['userId'] ?? null) === $userId));
    return response(true, $events, null);
}

/**
 * Get all time blocks for a user.
 */
function getUserTimeblocks($userId) {
    $data = loadTimeBlocksData();
    $blocks = array_values(array_filter($data['timeBlocks'], fn($b) => ($b['userId'] ?? null) === $userId));
    return response(true, $blocks, null);
}

/**
 * Get user preferences for a userId.
 */
function getUserPreferences($userId) {
    $data = loadPreferencesData();

    foreach ($data['preferences'] as $prefs) {
        if (($prefs['userId'] ?? null) === $userId) {
            return response(true, $prefs, null);
        }
    }

    $default = getDefaultPreferences();
    $default['userId'] = $userId;
    $default['updatedAt'] = getCurrentTimestamp();
    return response(true, $default, null);
}

// ==================== TASKS (tasks.json) ====================

/**
 * Create a new task.
 * Required: title
 * Optional: description, dueDate, dueTime, priority, status, category, tags, assignedTo, relatedEventId, notes
 */
function createTask($userId, $taskData) {
    $validation = validateTask($taskData);
    if (!$validation['success']) {
        return $validation;
    }

    $data = loadTasksData();
    $now = getCurrentTimestamp();

    $newTask = [
        'id' => generateUniqueId('task'),
        'userId' => $userId,
        'title' => sanitizeInput($taskData['title']),
        'description' => sanitizeInput($taskData['description'] ?? ''),
        'dueDate' => $taskData['dueDate'] ?? null,
        'dueTime' => $taskData['dueTime'] ?? null,
        'priority' => $taskData['priority'] ?? 'medium',
        'status' => $taskData['status'] ?? 'pending',
        'category' => $taskData['category'] ?? 'other',
        'tags' => $taskData['tags'] ?? [],
        'createdAt' => $now,
        'updatedAt' => $now,
        'completedAt' => null,
        'assignedTo' => $taskData['assignedTo'] ?? null,
        'relatedEventId' => $taskData['relatedEventId'] ?? null,
        'notes' => sanitizeInput($taskData['notes'] ?? '')
    ];

    $data['tasks'][] = $newTask;

    if (!writeJsonFile(TASKS_JSON_PATH, $data)) {
        return response(false, null, 'Failed to create task');
    }

    return response(true, $newTask, null);
}

/**
 * Get a single task by id (scoped to user).
 */
function getTask($taskId, $userId) {
    $data = loadTasksData();

    foreach ($data['tasks'] as $task) {
        if (($task['id'] ?? null) === $taskId && ($task['userId'] ?? null) === $userId) {
            return response(true, $task, null);
        }
    }

    return response(false, null, 'Task not found');
}

/**
 * Update a task by id (scoped to user).
 */
function updateTask($taskId, $userId, $taskData) {
    $validation = validateTask($taskData, true);
    if (!$validation['success']) {
        return $validation;
    }

    $data = loadTasksData();

    foreach ($data['tasks'] as $i => $task) {
        if (($task['id'] ?? null) !== $taskId || ($task['userId'] ?? null) !== $userId) {
            continue;
        }

        $updated = $task;
        foreach (['title', 'description', 'notes'] as $field) {
            if (array_key_exists($field, $taskData)) {
                $updated[$field] = sanitizeInput($taskData[$field]);
            }
        }

        foreach (['dueDate', 'dueTime', 'priority', 'status', 'category', 'tags', 'assignedTo', 'relatedEventId'] as $field) {
            if (array_key_exists($field, $taskData)) {
                $updated[$field] = $taskData[$field];
            }
        }

        $updated['updatedAt'] = getCurrentTimestamp();

        if (array_key_exists('status', $taskData)) {
            if ($taskData['status'] === 'completed' && ($updated['completedAt'] ?? null) === null) {
                $updated['completedAt'] = getCurrentTimestamp();
            }
            if ($taskData['status'] !== 'completed') {
                $updated['completedAt'] = null;
            }
        }

        $data['tasks'][$i] = $updated;

        if (!writeJsonFile(TASKS_JSON_PATH, $data)) {
            return response(false, null, 'Failed to update task');
        }

        return response(true, $updated, null);
    }

    return response(false, null, 'Task not found');
}

/**
 * Delete a task by id (scoped to user).
 */
function deleteTask($taskId, $userId) {
    $data = loadTasksData();

    $before = count($data['tasks']);
    $data['tasks'] = array_values(array_filter(
        $data['tasks'],
        fn($task) => !(($task['id'] ?? null) === $taskId && ($task['userId'] ?? null) === $userId)
    ));

    if (count($data['tasks']) === $before) {
        return response(false, null, 'Task not found');
    }

    if (!writeJsonFile(TASKS_JSON_PATH, $data)) {
        return response(false, null, 'Failed to delete task');
    }

    return response(true, ['id' => $taskId], null);
}

/**
 * List tasks for a user with optional filters.
 * Supported filters:
 * - status, priority, category
 * - dueDate
 * - dueDateFrom, dueDateTo (YYYY-MM-DD)
 */
function listUserTasks($userId, $filters = []) {
    $tasksResp = getUserTasks($userId);
    if (!$tasksResp['success']) {
        return $tasksResp;
    }

    $tasks = $tasksResp['data'];

    foreach (['status', 'priority', 'category', 'dueDate'] as $field) {
        if (!empty($filters[$field])) {
            $tasks = array_values(array_filter($tasks, fn($t) => ($t[$field] ?? null) === $filters[$field]));
        }
    }

    if (!empty($filters['dueDateFrom'])) {
        $tasks = array_values(array_filter($tasks, fn($t) => ($t['dueDate'] ?? '9999-12-31') >= $filters['dueDateFrom']));
    }

    if (!empty($filters['dueDateTo'])) {
        $tasks = array_values(array_filter($tasks, fn($t) => ($t['dueDate'] ?? '0000-01-01') <= $filters['dueDateTo']));
    }

    return response(true, $tasks, null);
}

// ==================== EVENTS (events.json) ====================

/**
 * Create a new event.
 * Required: title, startDate, startTime, endDate, endTime
 */
function createEvent($userId, $eventData) {
    $validation = validateEvent($eventData);
    if (!$validation['success']) {
        return $validation;
    }

    $data = loadEventsData();
    $now = getCurrentTimestamp();

    $startTs = strtotime("{$eventData['startDate']} {$eventData['startTime']}");
    $endTs = strtotime("{$eventData['endDate']} {$eventData['endTime']}");
    $duration = (int)(($endTs - $startTs) / 60);

    $newEvent = [
        'id' => generateUniqueId('event'),
        'userId' => $userId,
        'title' => sanitizeInput($eventData['title']),
        'description' => sanitizeInput($eventData['description'] ?? ''),
        'type' => $eventData['type'] ?? 'other',
        'startDate' => $eventData['startDate'],
        'startTime' => $eventData['startTime'],
        'endDate' => $eventData['endDate'],
        'endTime' => $eventData['endTime'],
        'duration' => isset($eventData['duration']) ? (int)$eventData['duration'] : $duration,
        'location' => sanitizeInput($eventData['location'] ?? ''),
        'attendees' => $eventData['attendees'] ?? [],
        'platform' => $eventData['platform'] ?? 'none',
        'platformLink' => sanitizeInput($eventData['platformLink'] ?? null),
        'status' => $eventData['status'] ?? 'scheduled',
        'isRecurring' => (bool)($eventData['isRecurring'] ?? false),
        'recurringPattern' => $eventData['recurringPattern'] ?? null,
        'notes' => sanitizeInput($eventData['notes'] ?? ''),
        'hasNotes' => !empty($eventData['notes'] ?? ''),
        'actionItems' => $eventData['actionItems'] ?? [],
        'createdAt' => $now,
        'updatedAt' => $now,
        'reminders' => $eventData['reminders'] ?? []
    ];

    $data['events'][] = $newEvent;

    if (!writeJsonFile(EVENTS_JSON_PATH, $data)) {
        return response(false, null, 'Failed to create event');
    }

    return response(true, $newEvent, null);
}

/**
 * Get a single event by id (scoped to user).
 */
function getEvent($eventId, $userId) {
    $data = loadEventsData();

    foreach ($data['events'] as $event) {
        if (($event['id'] ?? null) === $eventId && ($event['userId'] ?? null) === $userId) {
            return response(true, $event, null);
        }
    }

    return response(false, null, 'Event not found');
}

/**
 * Update an event by id (scoped to user).
 */
function updateEvent($eventId, $userId, $eventData) {
    $validation = validateEvent($eventData, true);
    if (!$validation['success']) {
        return $validation;
    }

    $data = loadEventsData();

    foreach ($data['events'] as $i => $event) {
        if (($event['id'] ?? null) !== $eventId || ($event['userId'] ?? null) !== $userId) {
            continue;
        }

        $updated = $event;

        foreach (['title', 'description', 'location', 'platformLink', 'notes'] as $field) {
            if (array_key_exists($field, $eventData)) {
                $updated[$field] = sanitizeInput($eventData[$field]);
            }
        }

        foreach ([
            'type', 'startDate', 'startTime', 'endDate', 'endTime', 'duration', 'attendees',
            'platform', 'status', 'isRecurring', 'recurringPattern', 'actionItems', 'reminders'
        ] as $field) {
            if (array_key_exists($field, $eventData)) {
                $updated[$field] = $eventData[$field];
            }
        }

        $updated['hasNotes'] = !empty($updated['notes'] ?? '');
        $updated['updatedAt'] = getCurrentTimestamp();

        if (
            isset($updated['startDate'], $updated['startTime'], $updated['endDate'], $updated['endTime']) &&
            isValidDateTime($updated['startDate'], $updated['startTime']) &&
            isValidDateTime($updated['endDate'], $updated['endTime'])
        ) {
            $startTs = strtotime("{$updated['startDate']} {$updated['startTime']}");
            $endTs = strtotime("{$updated['endDate']} {$updated['endTime']}");
            $updated['duration'] = (int)(($endTs - $startTs) / 60);
        }

        $data['events'][$i] = $updated;

        if (!writeJsonFile(EVENTS_JSON_PATH, $data)) {
            return response(false, null, 'Failed to update event');
        }

        return response(true, $updated, null);
    }

    return response(false, null, 'Event not found');
}

/**
 * Delete an event by id (scoped to user).
 */
function deleteEvent($eventId, $userId) {
    $data = loadEventsData();

    $before = count($data['events']);
    $data['events'] = array_values(array_filter(
        $data['events'],
        fn($event) => !(($event['id'] ?? null) === $eventId && ($event['userId'] ?? null) === $userId)
    ));

    if (count($data['events']) === $before) {
        return response(false, null, 'Event not found');
    }

    if (!writeJsonFile(EVENTS_JSON_PATH, $data)) {
        return response(false, null, 'Failed to delete event');
    }

    return response(true, ['id' => $eventId], null);
}

/**
 * List user events with optional filters.
 * Supported filters:
 * - status, type
 * - startDateFrom, startDateTo
 */
function listUserEvents($userId, $filters = []) {
    $eventsResp = getUserEvents($userId);
    if (!$eventsResp['success']) {
        return $eventsResp;
    }

    $events = $eventsResp['data'];

    foreach (['status', 'type'] as $field) {
        if (!empty($filters[$field])) {
            $events = array_values(array_filter($events, fn($e) => ($e[$field] ?? null) === $filters[$field]));
        }
    }

    if (!empty($filters['startDateFrom'])) {
        $events = array_values(array_filter($events, fn($e) => ($e['startDate'] ?? '9999-12-31') >= $filters['startDateFrom']));
    }

    if (!empty($filters['startDateTo'])) {
        $events = array_values(array_filter($events, fn($e) => ($e['startDate'] ?? '0000-01-01') <= $filters['startDateTo']));
    }

    return response(true, $events, null);
}

// ==================== TIME BLOCKS (timeblocks.json) ====================

/**
 * Create a time block.
 * Required: title, startDate, startTime, endDate, endTime
 */
function createTimeBlock($userId, $blockData) {
    $validation = validateTimeBlock($blockData);
    if (!$validation['success']) {
        return $validation;
    }

    $data = loadTimeBlocksData();
    $now = getCurrentTimestamp();

    $startTs = strtotime("{$blockData['startDate']} {$blockData['startTime']}");
    $endTs = strtotime("{$blockData['endDate']} {$blockData['endTime']}");
    $duration = (int)(($endTs - $startTs) / 60);

    $newBlock = [
        'id' => generateUniqueId('timeblock'),
        'userId' => $userId,
        'title' => sanitizeInput($blockData['title']),
        'type' => $blockData['type'] ?? 'blocked',
        'startDate' => $blockData['startDate'],
        'startTime' => $blockData['startTime'],
        'endDate' => $blockData['endDate'],
        'endTime' => $blockData['endTime'],
        'duration' => isset($blockData['duration']) ? (int)$blockData['duration'] : $duration,
        'color' => $blockData['color'] ?? null,
        'isRecurring' => (bool)($blockData['isRecurring'] ?? false),
        'recurringDays' => $blockData['recurringDays'] ?? [],
        'description' => sanitizeInput($blockData['description'] ?? ''),
        'status' => $blockData['status'] ?? 'active',
        'createdAt' => $now,
        'updatedAt' => $now
    ];

    $data['timeBlocks'][] = $newBlock;

    if (!writeJsonFile(TIMEBLOCKS_JSON_PATH, $data)) {
        return response(false, null, 'Failed to create time block');
    }

    return response(true, $newBlock, null);
}

/**
 * Get a single time block by id (scoped to user).
 */
function getTimeBlock($blockId, $userId) {
    $data = loadTimeBlocksData();

    foreach ($data['timeBlocks'] as $block) {
        if (($block['id'] ?? null) === $blockId && ($block['userId'] ?? null) === $userId) {
            return response(true, $block, null);
        }
    }

    return response(false, null, 'Time block not found');
}

/**
 * Update a time block by id (scoped to user).
 */
function updateTimeBlock($blockId, $userId, $blockData) {
    $validation = validateTimeBlock($blockData, true);
    if (!$validation['success']) {
        return $validation;
    }

    $data = loadTimeBlocksData();

    foreach ($data['timeBlocks'] as $i => $block) {
        if (($block['id'] ?? null) !== $blockId || ($block['userId'] ?? null) !== $userId) {
            continue;
        }

        $updated = $block;

        foreach (['title', 'description'] as $field) {
            if (array_key_exists($field, $blockData)) {
                $updated[$field] = sanitizeInput($blockData[$field]);
            }
        }

        foreach (['type', 'startDate', 'startTime', 'endDate', 'endTime', 'duration', 'color', 'isRecurring', 'recurringDays', 'status'] as $field) {
            if (array_key_exists($field, $blockData)) {
                $updated[$field] = $blockData[$field];
            }
        }

        $updated['updatedAt'] = getCurrentTimestamp();

        if (
            isset($updated['startDate'], $updated['startTime'], $updated['endDate'], $updated['endTime']) &&
            isValidDateTime($updated['startDate'], $updated['startTime']) &&
            isValidDateTime($updated['endDate'], $updated['endTime'])
        ) {
            $startTs = strtotime("{$updated['startDate']} {$updated['startTime']}");
            $endTs = strtotime("{$updated['endDate']} {$updated['endTime']}");
            $updated['duration'] = (int)(($endTs - $startTs) / 60);
        }

        $data['timeBlocks'][$i] = $updated;

        if (!writeJsonFile(TIMEBLOCKS_JSON_PATH, $data)) {
            return response(false, null, 'Failed to update time block');
        }

        return response(true, $updated, null);
    }

    return response(false, null, 'Time block not found');
}

/**
 * Delete a time block by id (scoped to user).
 */
function deleteTimeBlock($blockId, $userId) {
    $data = loadTimeBlocksData();

    $before = count($data['timeBlocks']);
    $data['timeBlocks'] = array_values(array_filter(
        $data['timeBlocks'],
        fn($block) => !(($block['id'] ?? null) === $blockId && ($block['userId'] ?? null) === $userId)
    ));

    if (count($data['timeBlocks']) === $before) {
        return response(false, null, 'Time block not found');
    }

    if (!writeJsonFile(TIMEBLOCKS_JSON_PATH, $data)) {
        return response(false, null, 'Failed to delete time block');
    }

    return response(true, ['id' => $blockId], null);
}

/**
 * List user time blocks.
 */
function listUserTimeBlocks($userId) {
    return getUserTimeblocks($userId);
}

// ==================== PREFERENCES (user_prefs.json) ====================

/**
 * Default user preferences structure.
 */
function getDefaultPreferences() {
    return [
        'timezone' => 'UTC',
        'language' => 'en',
        'defaultView' => 'month',
        'workingHoursStart' => '09:00',
        'workingHoursEnd' => '18:00',
        'workingDays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'notificationsEnabled' => true,
        'notificationTypes' => [
            'emailReminders' => true,
            'pushNotifications' => true,
            'taskReminders' => true,
            'meetingReminders' => true
        ],
        'focusTimePreferences' => [
            'automaticFocusBlocks' => true,
            'focusBlockDuration' => 90,
            'breakDuration' => 15,
            'breakFrequency' => 4
        ],
        'aiPreferences' => [
            'autoSchedule' => true,
            'conflictResolution' => 'auto',
            'suggestFocusTime' => true,
            'extractActionItems' => true
        ]
    ];
}

/**
 * Update user preferences (upsert).
 */
function updateUserPreferences($userId, $preferences) {
    $validation = validatePreferences($preferences);
    if (!$validation['success']) {
        return $validation;
    }

    $data = loadPreferencesData();

    $now = getCurrentTimestamp();
    $found = false;

    foreach ($data['preferences'] as $i => $prefs) {
        if (($prefs['userId'] ?? null) !== $userId) {
            continue;
        }

        $found = true;
        $merged = array_replace_recursive($prefs, $preferences);
        $merged['userId'] = $userId;
        $merged['updatedAt'] = $now;
        $data['preferences'][$i] = $merged;
        break;
    }

    if (!$found) {
        $merged = array_replace_recursive(getDefaultPreferences(), $preferences);
        $merged['userId'] = $userId;
        $merged['updatedAt'] = $now;
        $data['preferences'][] = $merged;
    }

    if (!writeJsonFile(USER_PREFS_JSON_PATH, $data)) {
        return response(false, null, 'Failed to update preferences');
    }

    return getUserPreferences($userId);
}

// ==================== VALIDATION ====================

/**
 * Task validation rules:
 * - title required, max 255
 * - dueDate must be valid date or null
 * - dueTime must be valid time or null
 * - priority: high|medium|low
 * - status: pending|in_progress|completed|cancelled
 * - category: work|personal|health|other
 */
function validateTask($data, $isUpdate = false) {
    if (!is_array($data)) {
        return response(false, null, 'Invalid task payload');
    }

    if (!$isUpdate && (!isset($data['title']) || trim((string)$data['title']) === '')) {
        return response(false, null, 'Task title is required');
    }

    if (isset($data['title']) && strlen((string)$data['title']) > 255) {
        return response(false, null, 'Task title must be 255 characters or less');
    }

    if (array_key_exists('dueDate', $data) && $data['dueDate'] !== null && !isValidDate($data['dueDate'])) {
        return response(false, null, 'Invalid due date format. Use YYYY-MM-DD');
    }

    if (array_key_exists('dueTime', $data) && $data['dueTime'] !== null && !isValidTime($data['dueTime'])) {
        return response(false, null, 'Invalid due time format. Use HH:MM');
    }

    $validPriorities = ['high', 'medium', 'low'];
    if (isset($data['priority']) && !in_array($data['priority'], $validPriorities, true)) {
        return response(false, null, 'Priority must be one of: high, medium, low');
    }

    $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (isset($data['status']) && !in_array($data['status'], $validStatuses, true)) {
        return response(false, null, 'Status must be one of: pending, in_progress, completed, cancelled');
    }

    $validCategories = ['work', 'personal', 'health', 'other'];
    if (isset($data['category']) && !in_array($data['category'], $validCategories, true)) {
        return response(false, null, 'Category must be one of: work, personal, health, other');
    }

    return response(true, null, null);
}

/**
 * Event validation rules:
 * - title required, max 255
 * - startDate/startTime required (create)
 * - endDate/endTime required (create)
 * - startDateTime before endDateTime
 * - type: meeting|personal|focus_time|other
 * - platform: zoom|google_meet|teams|in_person|none
 * - duration (if provided) must be positive integer
 * - attendee emails must be valid
 */
function validateEvent($data, $isUpdate = false) {
    if (!is_array($data)) {
        return response(false, null, 'Invalid event payload');
    }

    if (!$isUpdate && (!isset($data['title']) || trim((string)$data['title']) === '')) {
        return response(false, null, 'Event title is required');
    }

    if (isset($data['title']) && strlen((string)$data['title']) > 255) {
        return response(false, null, 'Event title must be 255 characters or less');
    }

    if (!$isUpdate) {
        foreach (['startDate', 'startTime', 'endDate', 'endTime'] as $field) {
            if (!isset($data[$field])) {
                return response(false, null, 'Start and end date/time are required');
            }
        }
    }

    foreach ([['startDate', 'date'], ['endDate', 'date'], ['startTime', 'time'], ['endTime', 'time']] as [$field, $type]) {
        if (!isset($data[$field])) {
            continue;
        }
        if ($type === 'date' && !isValidDate($data[$field])) {
            return response(false, null, "Invalid $field format. Use YYYY-MM-DD");
        }
        if ($type === 'time' && !isValidTime($data[$field])) {
            return response(false, null, "Invalid $field format. Use HH:MM");
        }
    }

    if (isset($data['startDate'], $data['startTime'], $data['endDate'], $data['endTime'])) {
        $start = strtotime("{$data['startDate']} {$data['startTime']}");
        $end = strtotime("{$data['endDate']} {$data['endTime']}");
        if ($end <= $start) {
            return response(false, null, 'End date/time must be after start date/time');
        }
    }

    $validTypes = ['meeting', 'personal', 'focus_time', 'other'];
    if (isset($data['type']) && !in_array($data['type'], $validTypes, true)) {
        return response(false, null, 'Type must be one of: meeting, personal, focus_time, other');
    }

    $validPlatforms = ['zoom', 'google_meet', 'teams', 'in_person', 'none'];
    if (isset($data['platform']) && !in_array($data['platform'], $validPlatforms, true)) {
        return response(false, null, 'Platform must be one of: zoom, google_meet, teams, in_person, none');
    }

    $validStatuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
    if (isset($data['status']) && !in_array($data['status'], $validStatuses, true)) {
        return response(false, null, 'Status must be one of: scheduled, in_progress, completed, cancelled');
    }

    if (isset($data['duration'])) {
        if (!is_int($data['duration']) && !ctype_digit((string)$data['duration'])) {
            return response(false, null, 'Duration must be a positive integer');
        }
        if ((int)$data['duration'] <= 0) {
            return response(false, null, 'Duration must be a positive integer');
        }
    }

    if (isset($data['attendees'])) {
        if (!is_array($data['attendees'])) {
            return response(false, null, 'Attendees must be an array');
        }
        $validAttendeeStatuses = ['pending', 'accepted', 'declined'];
        foreach ($data['attendees'] as $attendee) {
            if (!is_array($attendee)) {
                return response(false, null, 'Invalid attendee format');
            }
            if (isset($attendee['email']) && !validateEmail($attendee['email'])) {
                return response(false, null, 'Invalid attendee email format');
            }
            if (isset($attendee['status']) && !in_array($attendee['status'], $validAttendeeStatuses, true)) {
                return response(false, null, 'Attendee status must be one of: pending, accepted, declined');
            }
        }
    }

    return response(true, null, null);
}

/**
 * TimeBlock validation rules:
 * - title required
 * - type: focus|break|lunch|personal|blocked|unavailable
 * - startDate/startTime required (create)
 * - endDate/endTime required (create)
 * - startDateTime before endDateTime
 * - duration (if provided) positive integer
 * - color must be valid hex or null
 */
function validateTimeBlock($data, $isUpdate = false) {
    if (!is_array($data)) {
        return response(false, null, 'Invalid time block payload');
    }

    if (!$isUpdate && (!isset($data['title']) || trim((string)$data['title']) === '')) {
        return response(false, null, 'Time block title is required');
    }

    $validTypes = ['focus', 'break', 'lunch', 'personal', 'blocked', 'unavailable'];
    if (isset($data['type']) && !in_array($data['type'], $validTypes, true)) {
        return response(false, null, 'Type must be one of: focus, break, lunch, personal, blocked, unavailable');
    }

    if (!$isUpdate) {
        foreach (['startDate', 'startTime', 'endDate', 'endTime'] as $field) {
            if (!isset($data[$field])) {
                return response(false, null, 'Start and end date/time are required');
            }
        }
    }

    foreach ([['startDate', 'date'], ['endDate', 'date'], ['startTime', 'time'], ['endTime', 'time']] as [$field, $type]) {
        if (!isset($data[$field])) {
            continue;
        }
        if ($type === 'date' && !isValidDate($data[$field])) {
            return response(false, null, "Invalid $field format. Use YYYY-MM-DD");
        }
        if ($type === 'time' && !isValidTime($data[$field])) {
            return response(false, null, "Invalid $field format. Use HH:MM");
        }
    }

    if (isset($data['startDate'], $data['startTime'], $data['endDate'], $data['endTime'])) {
        $start = strtotime("{$data['startDate']} {$data['startTime']}");
        $end = strtotime("{$data['endDate']} {$data['endTime']}");
        if ($end <= $start) {
            return response(false, null, 'End date/time must be after start date/time');
        }
    }

    if (array_key_exists('color', $data) && $data['color'] !== null && !isValidColor($data['color'])) {
        return response(false, null, 'Invalid color format. Use hex format like #FF5733');
    }

    if (isset($data['duration'])) {
        if (!is_int($data['duration']) && !ctype_digit((string)$data['duration'])) {
            return response(false, null, 'Duration must be a positive integer');
        }
        if ((int)$data['duration'] <= 0) {
            return response(false, null, 'Duration must be a positive integer');
        }
    }

    return response(true, null, null);
}

/**
 * Preferences validation rules:
 * - timezone must be valid (PHP supported)
 * - workingHoursStart/End must be valid time
 * - workingDays must be valid day names
 * - focusBlockDuration must be positive integer
 */
function validatePreferences($data) {
    if (!is_array($data)) {
        return response(false, null, 'Invalid preferences payload');
    }

    if (isset($data['timezone'])) {
        $validTimezones = DateTimeZone::listIdentifiers();
        if (!in_array($data['timezone'], $validTimezones, true)) {
            return response(false, null, 'Invalid timezone');
        }
    }

    foreach (['workingHoursStart', 'workingHoursEnd'] as $field) {
        if (isset($data[$field]) && !isValidTime($data[$field])) {
            return response(false, null, "Invalid $field format. Use HH:MM");
        }
    }

    $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    if (isset($data['workingDays'])) {
        if (!is_array($data['workingDays'])) {
            return response(false, null, 'workingDays must be an array');
        }
        foreach ($data['workingDays'] as $day) {
            if (!in_array($day, $validDays, true)) {
                return response(false, null, 'Invalid working day. Must be lowercase day name');
            }
        }
    }

    $validViews = ['month', 'week', 'day'];
    if (isset($data['defaultView']) && !in_array($data['defaultView'], $validViews, true)) {
        return response(false, null, 'Default view must be one of: month, week, day');
    }

    if (isset($data['focusTimePreferences']['focusBlockDuration'])) {
        $duration = $data['focusTimePreferences']['focusBlockDuration'];
        if (!is_int($duration) && !ctype_digit((string)$duration)) {
            return response(false, null, 'Focus block duration must be a positive integer');
        }
        if ((int)$duration <= 0) {
            return response(false, null, 'Focus block duration must be a positive integer');
        }
    }

    return response(true, null, null);
}

// ==================== SCHEDULING HELPERS ====================

/**
 * Get conflicting events for a user in a time range.
 *
 * Note: This checks only events (not time blocks). Time blocks can be checked separately
 * by the scheduling layer when Task 4/5 are implemented.
 */
function getConflictingEvents($userId, $startDate, $startTime, $endDate, $endTime) {
    $eventsResp = getUserEvents($userId);
    if (!$eventsResp['success']) {
        return [];
    }

    $events = $eventsResp['data'];

    $newStart = strtotime("$startDate $startTime");
    $newEnd = strtotime("$endDate $endTime");

    $conflicts = [];
    foreach ($events as $event) {
        if (($event['status'] ?? null) === 'cancelled') {
            continue;
        }

        $eventStart = strtotime("{$event['startDate']} {$event['startTime']}");
        $eventEnd = strtotime("{$event['endDate']} {$event['endTime']}");

        if ($newStart < $eventEnd && $newEnd > $eventStart) {
            $conflicts[] = $event;
        }
    }

    return $conflicts;
}
