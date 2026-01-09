/**
 * Task management (CRUD) - Frontend
 */

let taskModal = null;
let currentEditingTaskId = null;

function initializeTaskModal() {
    const modalEl = document.getElementById('taskModal');
    if (!modalEl) return;

    taskModal = new bootstrap.Modal(modalEl);
}

function openNewTaskModal(selectedDate = null) {
    currentEditingTaskId = null;

    document.getElementById('taskModalTitle').textContent = 'New Task';

    const form = document.getElementById('taskForm');
    form.reset();

    document.getElementById('taskId').value = '';
    document.getElementById('taskDeleteBtn').style.display = 'none';

    if (selectedDate) {
        document.getElementById('taskDueDate').value = selectedDate;
    }

    taskModal?.show();
}

async function openEditTaskModal(taskId) {
    currentEditingTaskId = taskId;

    try {
        const response = await fetch(`/php/tasks.php?action=get&taskId=${encodeURIComponent(taskId)}`, {
            cache: 'no-store'
        });
        const result = await response.json();

        if (!result.success) {
            alert('Failed to load task');
            return;
        }

        const task = result.data;

        document.getElementById('taskModalTitle').textContent = 'Edit Task';
        document.getElementById('taskTitle').value = task.title || '';
        document.getElementById('taskDescription').value = task.description || '';
        document.getElementById('taskDueDate').value = task.dueDate || '';
        document.getElementById('taskDueTime').value = task.dueTime || '';
        document.getElementById('taskPriority').value = task.priority || 'medium';
        document.getElementById('taskStatus').value = task.status || 'pending';
        document.getElementById('taskCategory').value = task.category || 'other';
        document.getElementById('taskTags').value = Array.isArray(task.tags) ? task.tags.join(', ') : '';
        document.getElementById('taskNotes').value = task.notes || '';
        document.getElementById('taskId').value = task.id;

        document.getElementById('taskDeleteBtn').style.display = 'block';

        taskModal?.show();
    } catch (error) {
        console.error('Error loading task:', error);
        alert('Error loading task');
    }
}

async function saveTask() {
    const taskId = document.getElementById('taskId').value;
    const title = document.getElementById('taskTitle').value.trim();
    const description = document.getElementById('taskDescription').value.trim();
    const dueDate = document.getElementById('taskDueDate').value;
    const dueTime = document.getElementById('taskDueTime').value;
    const priority = document.getElementById('taskPriority').value;
    const status = document.getElementById('taskStatus').value;
    const category = document.getElementById('taskCategory').value;
    const tagsInput = document.getElementById('taskTags').value.trim();
    const notes = document.getElementById('taskNotes').value.trim();

    if (!title) {
        alert('Title is required');
        return;
    }

    if (title.length > 255) {
        alert('Title must be 255 characters or less');
        return;
    }

    const tags = tagsInput
        ? tagsInput.split(',').map((t) => t.trim()).filter((t) => t)
        : [];

    const taskData = {
        title,
        description,
        dueDate: dueDate || null,
        dueTime: dueTime || null,
        priority,
        status,
        category,
        tags,
        notes
    };

    if (taskId) {
        taskData.taskId = taskId;
    }

    try {
        const endpoint = taskId ? 'update' : 'create';
        const response = await fetch(`/php/tasks.php?action=${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(taskData),
            cache: 'no-store'
        });

        const result = await response.json();

        if (!result.success) {
            alert('Error: ' + (result.error || 'Unknown error'));
            return;
        }

        taskModal?.hide();
        
        // Trigger sync system instead of direct refresh
        if (window.Sync && typeof Sync.onDataChanged === 'function') {
            Sync.onDataChanged('task', taskId ? 'update' : 'create');
        } else {
            // Fallback to direct refresh if sync not available
            await loadAllData();
            renderCalendar();
            showNotification(taskId ? 'Task updated' : 'Task created', 'success');
        }
    } catch (error) {
        console.error('Error saving task:', error);
        alert('Error saving task');
    }
}

async function deleteTask() {
    const taskId = document.getElementById('taskId').value;

    if (!taskId) {
        alert('No task selected');
        return;
    }

    if (!confirm('Are you sure you want to delete this task?')) {
        return;
    }

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

        if (!result.success) {
            alert('Error: ' + (result.error || 'Unknown error'));
            return;
        }

        taskModal?.hide();
        
        // Trigger sync system instead of direct refresh
        if (window.Sync && typeof Sync.onDataChanged === 'function') {
            Sync.onDataChanged('task', 'delete');
        } else {
            // Fallback to direct refresh if sync not available
            await loadAllData();
            renderCalendar();
            showNotification('Task deleted', 'success');
        }
    } catch (error) {
        console.error('Error deleting task:', error);
        alert('Error deleting task');
    }
}

async function completeTask(taskId) {
    try {
        const response = await fetch('/php/tasks.php?action=update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                taskId,
                status: 'completed'
            }),
            cache: 'no-store'
        });

        const result = await response.json();

        if (!result.success) {
            alert('Error: ' + (result.error || 'Unknown error'));
            return;
        }

        // Trigger sync system instead of direct refresh
        if (window.Sync && typeof Sync.onDataChanged === 'function') {
            Sync.onDataChanged('task', 'update');
        } else {
            // Fallback to direct refresh if sync not available
            await loadAllData();
            renderCalendar();
            showNotification('Task marked as completed', 'success');
        }
    } catch (error) {
        console.error('Error completing task:', error);
        alert('Error completing task');
    }
}

function sanitize(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
    if (window.Auth && typeof Auth.showToast === 'function') {
        const toastType = type === 'error' ? 'error' : 'success';
        Auth.showToast(message, toastType);
        return;
    }

    console.log(`[${String(type).toUpperCase()}] ${message}`);
}

function closeTaskModal() {
    taskModal?.hide();
    currentEditingTaskId = null;
}
