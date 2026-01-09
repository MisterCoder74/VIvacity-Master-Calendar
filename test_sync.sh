#!/bin/bash

# Test Script for Real-Time Calendar Synchronization (Task 9)

echo "Testing Real-Time Calendar Synchronization..."
echo "==========================================="

# Test 1: Check if all required files exist
echo "1. Checking required files..."
required_files=(
    "/home/engine/project/js/events.js"
    "/home/engine/project/js/sync.js"
    "/home/engine/project/js/tasks.js"
    "/home/engine/project/js/calendar.js"
    "/home/engine/project/dashboard.html"
)

for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        echo "✓ $file exists"
    else
        echo "✗ $file missing"
    fi
done

# Test 2: Check if sync.js contains required functions
echo ""
echo "2. Checking sync.js functions..."
functions=(
    "initialize"
    "onDataChanged"
    "refreshAllData"
    "syncTaskData"
    "syncEventData"
    "updateCalendarDisplay"
    "showToast"
)

for func in "${functions[@]}"; do
    if grep -q "$func" "/home/engine/project/js/sync.js"; then
        echo "✓ Function $func found"
    else
        echo "✗ Function $func missing"
    fi
done

# Test 3: Check if events.js contains required functions
echo ""
echo "3. Checking events.js functions..."
events_functions=(
    "saveEvent"
    "deleteEvent"
    "openEditEventModal"
)

for func in "${events_functions[@]}"; do
    if grep -q "$func" "/home/engine/project/js/events.js"; then
        echo "✓ Function $func found"
    else
        echo "✗ Function $func missing"
    fi
done

# Test 4: Check if task CRUD functions are modified for sync
echo ""
echo "4. Checking task CRUD sync integration..."
if grep -q "Sync.onDataChanged" "/home/engine/project/js/tasks.js"; then
    echo "✓ Task CRUD functions use sync system"
else
    echo "✗ Task CRUD functions not integrated with sync"
fi

# Test 5: Check dashboard.html includes new scripts
echo ""
echo "5. Checking dashboard.html script inclusions..."
scripts=(
    "js/events.js"
    "js/sync.js"
)

for script in "${scripts[@]}"; do
    if grep -q "$script" "/home/engine/project/dashboard.html"; then
        echo "✓ Script $script included"
    else
        echo "✗ Script $script missing"
    fi
done

# Test 6: Check CSS enhancements
echo ""
echo "6. Checking CSS enhancements..."
css_classes=(
    "toast-container"
    "task-indicator"
    "event-indicator"
    "sync-loading"
)

for class in "${css_classes[@]}"; do
    if grep -q "$class" "/home/engine/project/css/style.css"; then
        echo "✓ CSS class $class found"
    else
        echo "✗ CSS class $class missing"
    fi
done

# Test 7: Check if server is running
echo ""
echo "7. Checking if development server is running..."
if curl -s http://localhost:8000 > /dev/null; then
    echo "✓ Development server is running on localhost:8000"
else
    echo "✗ Development server is not responding"
fi

echo ""
echo "Test completed!"
echo ""
echo "Manual testing recommendations:"
echo "1. Open http://localhost:8000/dashboard.html in browser"
echo "2. Create a new task - verify sync triggers and calendar updates"
echo "3. Create a new event - verify sync triggers and calendar updates"
echo "4. Edit an existing task/event - verify changes sync immediately"
echo "5. Delete a task/event - verify removal syncs immediately"
echo "6. Wait 30 seconds - verify background polling refreshes data"
echo "7. Switch browser tabs and return - verify visibility API triggers refresh"
echo "8. Check browser console for sync logs and any errors"