/**
 * Real-Time Calendar Synchronization System
 * Handles polling, data refresh, and UI updates
 */

const Sync = (() => {
    // Configuration
    const POLL_INTERVAL = 30000; // 30 seconds
    const MOBILE_POLL_INTERVAL = 60000; // 60 seconds for mobile
    const MAX_RETRIES = 3;
    const RETRY_DELAY = 2000; // 2 seconds

    // State
    let pollInterval = null;
    let syncTimeout = null;
    let isPolling = false;
    let lastSyncTime = null;
    let retryCount = 0;
    let currentCalendarState = null; // To preserve navigation state

    // Mobile detection
    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    /**
     * Initialize the synchronization system
     */
    function initialize() {
        // Preserve current calendar state
        preserveCalendarState();

        // Set up 30-second polling interval
        setupPolling();

        // Set up visibility change listeners
        setupVisibilityHandling();

        // Set up beforeunload cleanup
        setupCleanup();

        console.log('Calendar synchronization initialized');
    }

    /**
     * Preserve current calendar navigation state
     */
    function preserveCalendarState() {
        if (typeof currentMonth !== 'undefined' && typeof currentYear !== 'undefined') {
            currentCalendarState = {
                month: currentMonth,
                year: currentYear
            };
        }
    }

    /**
     * Restore calendar navigation state
     */
    function restoreCalendarState() {
        if (currentCalendarState && typeof window !== 'undefined') {
            if (typeof currentMonth !== 'undefined') {
                window.currentMonth = currentCalendarState.month;
            }
            if (typeof currentYear !== 'undefined') {
                window.currentYear = currentCalendarState.year;
            }
        }
    }

    /**
     * Set up periodic polling
     */
    function setupPolling() {
        const interval = isMobile() ? MOBILE_POLL_INTERVAL : POLL_INTERVAL;
        
        if (pollInterval) {
            clearInterval(pollInterval);
        }

        pollInterval = setInterval(async () => {
            if (!document.hidden && !isPolling) {
                console.log('Background sync: Polling for updates...');
                await refreshAllData();
            }
        }, interval);
    }

    /**
     * Set up visibility change handling for tab switching
     */
    function setupVisibilityHandling() {
        document.addEventListener('visibilitychange', async () => {
            if (!document.hidden) {
                // User returned to tab - refresh data
                console.log('Tab became visible - triggering refresh');
                await refreshAllData();
            }
        });
    }

    /**
     * Set up cleanup handlers
     */
    function setupCleanup() {
        window.addEventListener('beforeunload', () => {
            if (pollInterval) {
                clearInterval(pollInterval);
            }
            if (syncTimeout) {
                clearTimeout(syncTimeout);
            }
        });
    }

    /**
     * Main sync trigger function - called after CRUD operations
     * @param {string} type - 'task' or 'event'
     * @param {string} action - 'create', 'update', or 'delete'
     */
    async function onDataChanged(type, action) {
        try {
            console.log(`Data changed: ${type} ${action}`);
            
            // Show toast notification
            showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} ${action}ed`, 'success');

            // Update cached data and sync
            await syncData(type);

            // Refresh calendar display
            updateCalendarDisplay();

            // Reset retry count on successful sync
            retryCount = 0;

        } catch (error) {
            console.error('Error in sync after data change:', error);
            handleSyncError(error);
        }
    }

    /**
     * Refresh all data from APIs
     */
    async function refreshAllData() {
        if (isPolling) return;
        
        isPolling = true;
        
        try {
            // Preserve calendar state before refresh
            preserveCalendarState();

            // Load fresh data
            await loadAllData();

            // Update all displays
            updateCalendarDisplay();

            lastSyncTime = new Date();
            retryCount = 0;

            console.log('All data refreshed successfully');

        } catch (error) {
            console.error('Error refreshing data:', error);
            handleSyncError(error);
        } finally {
            isPolling = false;
        }
    }

    /**
     * Sync data for a specific type
     * @param {string} type - 'task' or 'event'
     */
    async function syncData(type) {
        try {
            if (type === 'task') {
                await syncTaskData();
            } else if (type === 'event') {
                await syncEventData();
            }
        } catch (error) {
            console.error(`Error syncing ${type} data:`, error);
            throw error;
        }
    }

    /**
     * Sync task data from API
     */
    async function syncTaskData() {
        try {
            const response = await fetch('php/tasks.php?action=list', {
                cache: 'no-store'
            });
            const result = await response.json();

            if (result.success) {
                window.allTasks = result.data || [];
                console.log('Tasks synced:', window.allTasks.length, 'tasks');
            } else {
                throw new Error('Failed to fetch tasks');
            }
        } catch (error) {
            console.error('Error syncing task data:', error);
            throw error;
        }
    }

    /**
     * Sync event data from API
     */
    async function syncEventData() {
        try {
            const response = await fetch('php/events.php?action=list', {
                cache: 'no-store'
            });
            const result = await response.json();

            if (result.success) {
                window.allEvents = result.data || [];
                console.log('Events synced:', window.allEvents.length, 'events');
            } else {
                throw new Error('Failed to fetch events');
            }
        } catch (error) {
            console.error('Error syncing event data:', error);
            throw error;
        }
    }

    /**
     * Update calendar display
     */
    function updateCalendarDisplay() {
        try {
            // Restore calendar navigation state
            restoreCalendarState();

            // Re-render calendar
            if (typeof window.renderCalendar === 'function') {
                window.renderCalendar();
            }

            // Update day modal if it's open
            updateDayModalDisplay();

        } catch (error) {
            console.error('Error updating calendar display:', error);
        }
    }

    /**
     * Update day modal display if open
     */
    function updateDayModalDisplay() {
        const dayModal = document.getElementById('dayModal');
        if (dayModal && dayModal.classList.contains('show')) {
            // If day modal is open, we need to refresh it
            const dayModalTitle = document.getElementById('dayModalTitle');
            if (dayModalTitle) {
                const titleText = dayModalTitle.textContent;
                // Parse the date from title and refresh modal
                const dateMatch = titleText.match(/(\w+), (\w+) (\d+), (\d+)/);
                if (dateMatch) {
                    const [, , month, day, year] = dateMatch;
                    const date = new Date(`${month} ${day}, ${year}`);
                    if (typeof window.openDayModal === 'function') {
                        window.openDayModal(date);
                    }
                }
            }
        }
    }

    /**
     * Handle sync errors with retry logic
     */
    function handleSyncError(error) {
        retryCount++;
        
        if (retryCount <= MAX_RETRIES) {
            console.log(`Sync failed, retry ${retryCount}/${MAX_RETRIES} in ${RETRY_DELAY}ms`);
            
            syncTimeout = setTimeout(() => {
                refreshAllData();
            }, RETRY_DELAY * retryCount); // Exponential backoff
            
        } else {
            console.error('Max sync retries exceeded');
            showToast('Sync error. Please refresh the page.', 'error');
            retryCount = 0; // Reset for next time
        }
    }

    /**
     * Show toast notification
     * @param {string} message - Message to display
     * @param {string} type - 'success', 'error', 'info'
     */
    function showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Show toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    /**
     * Debounced sync to prevent excessive API calls
     */
    function debouncedSync(callback, delay = 500) {
        clearTimeout(syncTimeout);
        syncTimeout = setTimeout(callback, delay);
    }

    /**
     * Get last sync time
     */
    function getLastSyncTime() {
        return lastSyncTime;
    }

    /**
     * Check if sync is currently running
     */
    function isSyncRunning() {
        return isPolling;
    }

    /**
     * Manual sync trigger (for debugging or manual refresh)
     */
    async function manualSync() {
        console.log('Manual sync triggered');
        await refreshAllData();
        showToast('Data refreshed', 'info');
    }

    // Public API
    return {
        initialize,
        onDataChanged,
        refreshAllData,
        syncData,
        syncTaskData,
        syncEventData,
        updateCalendarDisplay,
        showToast,
        debouncedSync,
        getLastSyncTime,
        isSyncRunning,
        manualSync
    };
})();

// Initialize sync when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Wait a bit for other scripts to load
        setTimeout(() => {
            Sync.initialize();
        }, 1000);
    });
} else {
    // DOM is already ready
    setTimeout(() => {
        Sync.initialize();
    }, 1000);
}

// Export for global access
window.Sync = Sync;