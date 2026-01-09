/**
 * Event management (CRUD) - Frontend
 */

let eventModal = null;
let currentEditingEventId = null;

function initializeEventModal() {
    const modalEl = document.getElementById('eventModal');
    if (!modalEl) return;

    eventModal = new bootstrap.Modal(modalEl);
}

function openNewEventModal(selectedDate = null) {
    currentEditingEventId = null;

    document.querySelector('#eventModal .modal-title').textContent = 'New Event';

    const form = document.getElementById('eventForm');
    form.reset();

    document.getElementById('eventId')?.remove();
    document.getElementById('eventDeleteBtn')?.remove();

    if (selectedDate) {
        document.querySelector('#eventForm input[name="startDate"]')?.value = selectedDate;
    }

    eventModal?.show();
}

async function openEditEventModal(eventId) {
    currentEditingEventId = eventId;

    try {
        const response = await fetch(`php/events.php?action=get&eventId=${encodeURIComponent(eventId)}`, {
            cache: 'no-store'
        });
        const result = await response.json();

        if (!result.success) {
            alert('Failed to load event');
            return;
        }

        const event = result.data;

        document.querySelector('#eventModal .modal-title').textContent = 'Edit Event';
        
        // Populate form fields based on event data
        populateEventForm(event);

        // Add delete button if it doesn't exist
        const modalFooter = document.querySelector('#eventModal .modal-footer');
        if (modalFooter && !document.getElementById('eventDeleteBtn')) {
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn btn-danger';
            deleteBtn.id = 'eventDeleteBtn';
            deleteBtn.textContent = 'Delete';
            deleteBtn.onclick = deleteEvent;
            modalFooter.insertBefore(deleteBtn, modalFooter.lastElementChild);
        }

        // Add hidden event ID field
        if (!document.getElementById('eventId')) {
            const eventIdInput = document.createElement('input');
            eventIdInput.type = 'hidden';
            eventIdInput.id = 'eventId';
            eventIdInput.name = 'eventId';
            eventIdInput.value = eventId;
            document.getElementById('eventForm').appendChild(eventIdInput);
        }

        eventModal?.show();
    } catch (error) {
        console.error('Error loading event:', error);
        alert('Error loading event');
    }
}

function populateEventForm(event) {
    const form = document.getElementById('eventForm');
    if (!form) return;

    // For now, just handle basic fields. Full form implementation would be in Task 8
    const titleInput = form.querySelector('input[name="title"]');
    if (titleInput) titleInput.value = event.title || '';

    // Add more fields as needed in Task 8
}

async function saveEvent() {
    const eventId = document.getElementById('eventId')?.value;
    const title = document.querySelector('#eventForm input[name="title"]')?.value.trim();

    if (!title) {
        alert('Title is required');
        return;
    }

    // For now, create a minimal event structure
    // Full implementation with all fields would be in Task 8
    const eventData = {
        title,
        description: document.querySelector('#eventForm textarea[name="description"]')?.value || '',
        startDate: document.querySelector('#eventForm input[name="startDate"]')?.value || null,
        startTime: document.querySelector('#eventForm input[name="startTime"]')?.value || null,
        endDate: document.querySelector('#eventForm input[name="endDate"]')?.value || null,
        endTime: document.querySelector('#eventForm input[name="endTime"]')?.value || null,
        type: document.querySelector('#eventForm select[name="type"]')?.value || 'other',
        status: 'scheduled'
    };

    if (eventId) {
        eventData.eventId = eventId;
    }

    try {
        const endpoint = eventId ? 'update' : 'create';
        const response = await fetch(`php/events.php?action=${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(eventData),
            cache: 'no-store'
        });

        const result = await response.json();

        if (!result.success) {
            alert('Error: ' + (result.error || 'Unknown error'));
            return;
        }

        eventModal?.hide();
        
        // Trigger sync system
        if (window.Sync && typeof Sync.onDataChanged === 'function') {
            Sync.onDataChanged('event', eventId ? 'update' : 'create');
        } else {
            // Fallback to direct refresh
            await loadAllData();
            renderCalendar();
        }

        showNotification(eventId ? 'Event updated' : 'Event created', 'success');
    } catch (error) {
        console.error('Error saving event:', error);
        alert('Error saving event');
    }
}

async function deleteEvent() {
    const eventId = document.getElementById('eventId')?.value;

    if (!eventId) {
        alert('No event selected');
        return;
    }

    if (!confirm('Are you sure you want to delete this event?')) {
        return;
    }

    try {
        const response = await fetch('php/events.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: `action=delete&eventId=${encodeURIComponent(eventId)}`,
            cache: 'no-store'
        });

        const result = await response.json();

        if (!result.success) {
            alert('Error: ' + (result.error || 'Unknown error'));
            return;
        }

        eventModal?.hide();
        
        // Trigger sync system
        if (window.Sync && typeof Sync.onDataChanged === 'function') {
            Sync.onDataChanged('event', 'delete');
        } else {
            // Fallback to direct refresh
            await loadAllData();
            renderCalendar();
        }

        showNotification('Event deleted', 'success');
    } catch (error) {
        console.error('Error deleting event:', error);
        alert('Error deleting event');
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

function closeEventModal() {
    eventModal?.hide();
    currentEditingEventId = null;
}