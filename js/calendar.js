/**
 * VIvacity Master Calendar - Calendar Logic
 */

let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let allEvents = [];
let allTasks = [];
let allTimeBlocks = [];

let dayModal, taskModal, eventModal, timeBlockModal;

/**
 * Load all tasks, events, and timeblocks from the backend
 */
async function loadAllData() {
    try {
        // These endpoints will be fully implemented in subsequent tasks
        // For now, we fetch and handle potential 404s or empty responses
        const [tasksRes, eventsRes, timeBlocksRes] = await Promise.all([
            fetch('/php/tasks.php?action=list', { cache: 'no-store' }).catch(() => ({ json: () => ({ success: false }) })),
            fetch('/php/events.php?action=list', { cache: 'no-store' }).catch(() => ({ json: () => ({ success: false }) })),
            fetch('/php/timeblocks.php?action=list', { cache: 'no-store' }).catch(() => ({ json: () => ({ success: false }) }))
        ]);

        const tasksData = await tasksRes.json();
        const eventsData = await eventsRes.json();
        const timeBlocksData = await timeBlocksRes.json();

        if (tasksData.success) allTasks = tasksData.data || [];
        if (eventsData.success) allEvents = eventsData.data || [];
        if (timeBlocksData.success) allTimeBlocks = timeBlocksData.data || [];

    } catch (error) {
        console.error('Error loading calendar data:', error);
    }
}

function getCurrentDate() {
    return new Date(currentYear, currentMonth, 1);
}

function getDaysInMonth(year, month) {
    return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfMonth(year, month) {
    return new Date(year, month, 1).getDay();
}

/**
 * Render the calendar grid
 */
function renderCalendar() {
    const calendarGrid = document.getElementById('calendarGrid');
    const monthYearDisplay = document.getElementById('currentMonthYear');
    
    if (!calendarGrid || !monthYearDisplay) return;

    // Clear grid
    calendarGrid.innerHTML = '';

    // Set Month Year text
    const date = new Date(currentYear, currentMonth);
    const monthName = date.toLocaleString('default', { month: 'long' });
    monthYearDisplay.textContent = `${monthName} ${currentYear}`;

    // Add day headers
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    days.forEach(day => {
        const header = document.createElement('div');
        header.className = 'calendar-header';
        header.textContent = day;
        calendarGrid.appendChild(header);
    });

    const firstDay = getFirstDayOfMonth(currentYear, currentMonth);
    const daysInMonth = getDaysInMonth(currentYear, currentMonth);
    const daysInPrevMonth = getDaysInMonth(currentYear, currentMonth - 1);

    // Previous month's days
    for (let i = firstDay - 1; i >= 0; i--) {
        const dayNum = daysInPrevMonth - i;
        const prevMonthDate = new Date(currentYear, currentMonth - 1, dayNum);
        calendarGrid.appendChild(renderDayCell(prevMonthDate, true));
    }

    // Current month's days
    for (let i = 1; i <= daysInMonth; i++) {
        const currentDate = new Date(currentYear, currentMonth, i);
        calendarGrid.appendChild(renderDayCell(currentDate, false));
    }

    // Next month's days to fill the grid (6 rows * 7 days = 42 cells total)
    const totalCellsSoFar = firstDay + daysInMonth;
    const remainingCells = 42 - totalCellsSoFar;
    for (let i = 1; i <= remainingCells; i++) {
        const nextMonthDate = new Date(currentYear, currentMonth + 1, i);
        calendarGrid.appendChild(renderDayCell(nextMonthDate, true));
    }
}

/**
 * Create HTML for a single day cell
 */
function renderDayCell(date, isOtherMonth) {
    const dayCell = document.createElement('div');
    dayCell.className = 'calendar-day';
    if (isOtherMonth) dayCell.classList.add('other-month');
    
    const today = new Date();
    if (date.getDate() === today.getDate() && 
        date.getMonth() === today.getMonth() && 
        date.getFullYear() === today.getFullYear()) {
        dayCell.classList.add('today');
    }

    const dayNumber = document.createElement('div');
    dayNumber.className = 'day-number';
    dayNumber.textContent = date.getDate();
    dayCell.appendChild(dayNumber);

    const itemsContainer = document.createElement('div');
    itemsContainer.className = 'day-items';
    
    const events = getEventsForDate(date);
    const tasks = getTasksForDate(date);
    const timeBlocks = getTimeBlocksForDate(date);

    const allItems = [
        ...events.map(e => ({ ...e, calendarType: 'event' })),
        ...tasks.map(t => ({ ...t, calendarType: 'task' })),
        ...timeBlocks.map(tb => ({ ...tb, calendarType: 'focus' }))
    ];

    // Limit to 3-4 items
    const displayItems = allItems.slice(0, 3);
    displayItems.forEach(item => {
        const itemEl = document.createElement('div');
        itemEl.className = `day-item ${item.calendarType}`;
        
        let prefix = `[${item.calendarType}]`;
        if (item.calendarType === 'event') prefix = '[meeting]';
        
        itemEl.textContent = `${prefix} ${item.title}`;
        itemsContainer.appendChild(itemEl);
    });

    if (allItems.length > 3) {
        const moreLink = document.createElement('div');
        moreLink.className = 'more-items';
        moreLink.textContent = `+${allItems.length - 3} more`;
        itemsContainer.appendChild(moreLink);
    }

    dayCell.appendChild(itemsContainer);

    // Click handler
    dayCell.onclick = () => openDayModal(date);

    return dayCell;
}

function getEventsForDate(date) {
    const dateStr = formatDateToISO(date);
    return allEvents.filter(event => event.startDate === dateStr);
}

function getTasksForDate(date) {
    const dateStr = formatDateToISO(date);
    return allTasks.filter(task => task.dueDate === dateStr);
}

function getTimeBlocksForDate(date) {
    const dateStr = formatDateToISO(date);
    return allTimeBlocks.filter(tb => tb.startDate === dateStr);
}

function formatDateToISO(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function previousMonth() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    renderCalendar();
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    renderCalendar();
}

function goToToday() {
    const today = new Date();
    currentMonth = today.getMonth();
    currentYear = today.getFullYear();
    renderCalendar();
}

/**
 * Open day detail modal
 */
function openDayModal(date) {
    const events = getEventsForDate(date);
    const tasks = getTasksForDate(date);
    const timeBlocks = getTimeBlocksForDate(date);

    const dateStr = date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    document.getElementById('dayModalTitle').textContent = dateStr;
    
    const container = document.getElementById('dayModalContent');
    container.innerHTML = '';

    // Events Section
    if (events.length > 0) {
        const header = document.createElement('h6');
        header.className = 'mt-3 mb-2';
        header.textContent = 'Events & Meetings';
        container.appendChild(header);
        
        events.forEach(event => {
            const item = document.createElement('div');
            item.className = 'day-modal-item';
            item.innerHTML = `
                <div>
                    <div class="day-modal-item-title">${sanitize(event.title)}</div>
                    <div class="day-modal-item-time">${event.startTime} - ${event.endTime}</div>
                </div>
                <div class="day-modal-item-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="editEvent('${event.id}')">Edit</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteEvent('${event.id}')">Delete</button>
                </div>
            `;
            container.appendChild(item);
        });
    }

    // Tasks Section
    if (tasks.length > 0) {
        const header = document.createElement('h6');
        header.className = 'mt-3 mb-2';
        header.textContent = 'Tasks';
        container.appendChild(header);
        
        tasks.forEach(task => {
            const item = document.createElement('div');
            item.className = 'day-modal-item';
            const priorityClass = `task-${task.priority || 'medium'}`;
            item.innerHTML = `
                <div>
                    <div class="day-modal-item-title ${priorityClass}">${sanitize(task.title)}</div>
                    <div class="day-modal-item-time">Due: ${task.dueTime || 'All day'} | Priority: ${task.priority || 'Medium'}</div>
                    <span class="badge bg-secondary">${task.status || 'pending'}</span>
                </div>
                <div class="day-modal-item-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="editTask('${task.id}')">Edit</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTask('${task.id}')">Delete</button>
                </div>
            `;
            container.appendChild(item);
        });
    }

    // Time Blocks Section
    if (timeBlocks.length > 0) {
        const header = document.createElement('h6');
        header.className = 'mt-3 mb-2';
        header.textContent = 'Focus & Time Blocks';
        container.appendChild(header);
        
        timeBlocks.forEach(tb => {
            const item = document.createElement('div');
            item.className = 'day-modal-item';
            item.innerHTML = `
                <div>
                    <div class="day-modal-item-title event-focus">${sanitize(tb.title)}</div>
                    <div class="day-modal-item-time">${tb.startTime} - ${tb.endTime} (${tb.type})</div>
                </div>
                <div class="day-modal-item-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="editTimeBlock('${tb.id}')">Edit</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTimeBlock('${tb.id}')">Delete</button>
                </div>
            `;
            container.appendChild(item);
        });
    }

    if (events.length === 0 && tasks.length === 0 && timeBlocks.length === 0) {
        container.innerHTML = '<p class="text-muted text-center my-4">No items scheduled for this day.</p>';
    }

    // Store selected date for the "Add" buttons
    window.selectedDate = formatDateToISO(date);

    dayModal.show();
}

function openTaskModal() {
    // Will be implemented in Task 6
    taskModal.show();
}

function openEventModal() {
    // Will be implemented in Task 8
    eventModal.show();
}

function openTimeBlockModal() {
    // Will be implemented in Task 10
    timeBlockModal.show();
}

function sanitize(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Global functions for edit/delete buttons (placeholders)
window.editEvent = (id) => console.log('Edit event', id);
window.deleteEvent = (id) => console.log('Delete event', id);
window.editTask = (id) => console.log('Edit task', id);
window.deleteTask = (id) => console.log('Delete task', id);
window.editTimeBlock = (id) => console.log('Edit timeblock', id);
window.deleteTimeBlock = (id) => console.log('Delete timeblock', id);

document.addEventListener('DOMContentLoaded', async function() {
    try {
        const user = await Auth.checkSession();
        if (!user) {
            window.location.href = '/index.html';
            return;
        }

        // Load all data
        await loadAllData();

        // Initialize modals
        dayModal = new bootstrap.Modal(document.getElementById('dayModal'));
        taskModal = new bootstrap.Modal(document.getElementById('taskModal'));
        eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        timeBlockModal = new bootstrap.Modal(document.getElementById('timeBlockModal'));

        // Render calendar
        renderCalendar();

        // Attach event listeners
        document.getElementById('prevMonth').addEventListener('click', previousMonth);
        document.getElementById('nextMonth').addEventListener('click', nextMonth);
        document.getElementById('todayBtn').addEventListener('click', goToToday);
        
        // Add buttons in day modal
        document.getElementById('addEventBtn').addEventListener('click', () => {
            dayModal.hide();
            openEventModal();
        });
        document.getElementById('addTaskBtn').addEventListener('click', () => {
            dayModal.hide();
            openTaskModal();
        });
        document.getElementById('addFocusBtn').addEventListener('click', () => {
            dayModal.hide();
            openTimeBlockModal();
        });

    } catch (error) {
        console.error('Initialization error:', error);
    }
});
