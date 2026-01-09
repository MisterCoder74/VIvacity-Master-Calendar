/**
 * VIvacity Master Calendar - Calendar Logic
 */

let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let allEvents = [];
let allTasks = [];
let allTimeBlocks = [];

let dayModal, eventModal, timeBlockModal;
let currentDayModalDateISO = null;

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

    calendarGrid.innerHTML = '';

    const date = new Date(currentYear, currentMonth);
    const monthName = date.toLocaleString('default', { month: 'long' });
    monthYearDisplay.textContent = `${monthName} ${currentYear}`;

    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    days.forEach((day) => {
        const header = document.createElement('div');
        header.className = 'calendar-header';
        header.textContent = day;
        calendarGrid.appendChild(header);
    });

    const firstDay = getFirstDayOfMonth(currentYear, currentMonth);
    const daysInMonth = getDaysInMonth(currentYear, currentMonth);
    const daysInPrevMonth = getDaysInMonth(currentYear, currentMonth - 1);

    for (let i = firstDay - 1; i >= 0; i--) {
        const dayNum = daysInPrevMonth - i;
        const prevMonthDate = new Date(currentYear, currentMonth - 1, dayNum);
        calendarGrid.appendChild(renderDayCell(prevMonthDate, true));
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const currentDate = new Date(currentYear, currentMonth, i);
        calendarGrid.appendChild(renderDayCell(currentDate, false));
    }

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
    if (
        date.getDate() === today.getDate() &&
        date.getMonth() === today.getMonth() &&
        date.getFullYear() === today.getFullYear()
    ) {
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
        ...events.map((e) => ({ ...e, calendarType: 'event' })),
        ...tasks.map((t) => ({ ...t, calendarType: 'task' })),
        ...timeBlocks.map((tb) => ({ ...tb, calendarType: 'focus' }))
    ];

    const displayItems = allItems.slice(0, 3);
    displayItems.forEach((item) => {
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

    dayCell.onclick = () => openDayModal(date);

    return dayCell;
}

function getEventsForDate(date) {
    const dateStr = formatDateToISO(date);
    return allEvents.filter((event) => event.startDate === dateStr);
}

function getTasksForDate(date) {
    const dateStr = formatDateToISO(date);
    return allTasks.filter((task) => task.dueDate === dateStr);
}

function getTimeBlocksForDate(date) {
    const dateStr = formatDateToISO(date);
    return allTimeBlocks.filter((tb) => tb.startDate === dateStr);
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

function closeDayModal() {
    dayModal?.hide();
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

    currentDayModalDateISO = formatDateToISO(date);
    window.selectedDate = currentDayModalDateISO;

    const eventsSection = document.getElementById('eventsSection');
    const tasksSection = document.getElementById('tasksSection');
    const timeBlocksSection = document.getElementById('timeBlocksSection');

    if (eventsSection) {
        const eventsHtml = events
            .map((event) => {
                return `
          <div class="day-modal-item">
            <div>
              <div class="day-modal-item-title">${sanitize(event.title)}</div>
              <div class="day-modal-item-meta">${sanitize(event.startTime)} - ${sanitize(event.endTime)}</div>
            </div>
            <div class="day-modal-item-actions">
              <button class="btn btn-sm btn-outline-primary" onclick="editEvent('${event.id}')">Edit</button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteEvent('${event.id}')">Delete</button>
            </div>
          </div>
        `;
            })
            .join('');

        eventsSection.innerHTML = `
        <h6 class="mt-1 mb-2">Events & Meetings</h6>
        ${eventsHtml || '<p class="text-muted">No events</p>'}
      `;
    }

    if (tasksSection) {
        const tasksHtml = tasks
            .map((task) => {
                const priorityClass = `task-${task.priority || 'medium'}`;
                const statusBadge = `<span class="badge bg-info">${sanitize(task.status || 'pending')}</span>`;
                const time = task.dueTime ? ` ${sanitize(task.dueTime)}` : '';

                return `
          <div class="day-modal-item">
            <div>
              <div class="day-modal-item-title ${priorityClass}">${sanitize(task.title)}</div>
              <div class="day-modal-item-meta">${statusBadge}${time}</div>
            </div>
            <div class="day-modal-item-actions">
              <button class="btn btn-sm btn-outline-success" onclick="completeTask('${task.id}')">Complete</button>
              <button class="btn btn-sm btn-outline-primary" onclick="closeDayModal(); openEditTaskModal('${task.id}')">Edit</button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteTaskQuick('${task.id}')">Delete</button>
            </div>
          </div>
        `;
            })
            .join('');

        tasksSection.innerHTML = `
        <h6 class="mt-1 mb-2">Tasks</h6>
        ${tasksHtml || '<p class="text-muted">No tasks</p>'}
      `;
    }

    if (timeBlocksSection) {
        const timeBlocksHtml = timeBlocks
            .map((tb) => {
                return `
          <div class="day-modal-item">
            <div>
              <div class="day-modal-item-title event-focus">${sanitize(tb.title)}</div>
              <div class="day-modal-item-meta">${sanitize(tb.startTime)} - ${sanitize(tb.endTime)} (${sanitize(tb.type)})</div>
            </div>
            <div class="day-modal-item-actions">
              <button class="btn btn-sm btn-outline-primary" onclick="editTimeBlock('${tb.id}')">Edit</button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteTimeBlock('${tb.id}')">Delete</button>
            </div>
          </div>
        `;
            })
            .join('');

        timeBlocksSection.innerHTML = `
        <h6 class="mt-1 mb-2">Focus & Time Blocks</h6>
        ${timeBlocksHtml || '<p class="text-muted">No time blocks</p>'}
      `;
    }

    dayModal?.show();
}

async function deleteTaskQuick(taskId) {
    if (!confirm('Delete this task?')) return;

    try {
        const response = await fetch('/php/tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: `action=delete&taskId=${encodeURIComponent(taskId)}`,
            cache: 'no-store'
        });

        const result = await response.json();
        if (result.success) {
            await loadAllData();
            renderCalendar();
            closeDayModal();
            showNotification('Task deleted', 'success');
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function sanitize(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

// Global functions for edit/delete buttons (placeholders)
window.editEvent = (id) => console.log('Edit event', id);
window.deleteEvent = (id) => console.log('Delete event', id);
window.editTimeBlock = (id) => console.log('Edit timeblock', id);
window.deleteTimeBlock = (id) => console.log('Delete timeblock', id);

document.addEventListener('DOMContentLoaded', async function () {
    try {
        const user = await Auth.checkSession();
        if (!user) {
            window.location.href = '/index.html';
            return;
        }

        await loadAllData();

        dayModal = new bootstrap.Modal(document.getElementById('dayModal'));

        // Task modal (Task 6)
        initializeTaskModal();

        // Placeholder modals
        eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        timeBlockModal = new bootstrap.Modal(document.getElementById('timeBlockModal'));

        renderCalendar();

        document.getElementById('prevMonth').addEventListener('click', previousMonth);
        document.getElementById('nextMonth').addEventListener('click', nextMonth);
        document.getElementById('todayBtn').addEventListener('click', goToToday);

        const taskSaveBtn = document.getElementById('taskSaveBtn');
        if (taskSaveBtn) taskSaveBtn.addEventListener('click', saveTask);

        const taskDeleteBtn = document.getElementById('taskDeleteBtn');
        if (taskDeleteBtn) taskDeleteBtn.addEventListener('click', deleteTask);

        const addTaskBtn = document.getElementById('addTaskBtn');
        if (addTaskBtn) {
            addTaskBtn.addEventListener('click', function () {
                const selectedDate = currentDayModalDateISO || window.selectedDate || null;
                closeDayModal();
                setTimeout(() => openNewTaskModal(selectedDate), 300);
            });
        }

        const addEventBtn = document.getElementById('addEventBtn');
        if (addEventBtn) {
            addEventBtn.addEventListener('click', () => {
                closeDayModal();
                eventModal?.show();
            });
        }
    } catch (error) {
        console.error('Initialization error:', error);
    }
});
