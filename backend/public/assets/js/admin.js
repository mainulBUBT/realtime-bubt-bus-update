/**
 * Admin Panel JavaScript
 * Helper functions for notifications, loading states, and UI interactions
 */

// ============================================
// Notification Helpers
// ============================================

/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {string} type - Type of notification (success, error, warning, info)
 * @param {object} options - Additional toastr options
 */
function showToast(message, type = 'success', options = {}) {
    const settings = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 5000,
        extendedTimeOut: 1000,
        preventDuplicates: true,
        newestOnTop: true,
        ...options
    };

    // Configure toastr
    if (typeof toastr !== 'undefined') {
        Object.assign(toastr.options, settings);

        // Show the toast
        switch(type) {
            case 'success':
                toastr.success(message);
                break;
            case 'error':
                toastr.error(message);
                break;
            case 'warning':
                toastr.warning(message);
                break;
            case 'info':
                toastr.info(message);
                break;
            default:
                toastr.success(message);
        }
    } else {
        // Fallback if toastr is not loaded
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
}

/**
 * Show success notification
 */
function showSuccess(message) {
    showToast(message, 'success');
}

/**
 * Show error notification
 */
function showError(message) {
    showToast(message, 'error');
}

/**
 * Show warning notification
 */
function showWarning(message) {
    showToast(message, 'warning');
}

/**
 * Show info notification
 */
function showInfo(message) {
    showToast(message, 'info');
}

// ============================================
// Loading States
// ============================================

/**
 * Show loading overlay
 * @param {string} message - Optional loading message
 */
function showLoading(message = 'Loading...') {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        const messageEl = overlay.querySelector('p');
        if (messageEl) {
            messageEl.textContent = message;
        }
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }
}

/**
 * Set button loading state
 * @param {HTMLElement} button - The button element
 * @param {boolean} loading - Loading state
 * @param {string} loadingText - Text to show while loading
 */
function setButtonLoading(button, loading = true, loadingText = 'Processing...') {
    if (!button) return;

    if (loading) {
        button.dataset.originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `<i class="bi bi-arrow-clockwise animate-spin mr-2"></i>${loadingText}`;
        button.classList.add('opacity-75', 'cursor-not-allowed');
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
        button.classList.remove('opacity-75', 'cursor-not-allowed');
    }
}

// ============================================
// Dark Mode
// ============================================

/**
 * Toggle dark mode
 */
function toggleDarkMode() {
    const html = document.documentElement;
    html.classList.toggle('dark');

    // Save preference to localStorage
    const isDark = html.classList.contains('dark');
    localStorage.setItem('darkMode', isDark ? 'true' : 'false');

    // Dispatch event for other components
    window.dispatchEvent(new CustomEvent('darkModeChanged', { detail: { isDark } }));
}

/**
 * Initialize dark mode from preference
 */
function initDarkMode() {
    const savedMode = localStorage.getItem('darkMode');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedMode === 'true' || (!savedMode && prefersDark)) {
        document.documentElement.classList.add('dark');
    }
}

// ============================================
// Confirmation Dialogs
// ============================================

/**
 * Show confirmation dialog
 * @param {string} message - Confirmation message
 * @param {function} callback - Function to call if confirmed
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Show sweetalert-style confirmation (if sweetalert available)
 * @param {string} message - Confirmation message
 * @param {string} title - Dialog title
 * @param {function} callback - Function to call if confirmed
 */
function confirmActionSweet(message, title = 'Are you sure?', callback) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, proceed!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    } else {
        // Fallback to native confirm
        confirmAction(message, callback);
    }
}

// ============================================
// Sidebar
// ============================================

/**
 * Toggle sidebar (mobile)
 */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebar && overlay) {
        setSidebarOpen(sidebar.classList.contains('-translate-x-full'));
    }
}

/**
 * Close sidebar
 */
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebar && overlay) {
        setSidebarOpen(false);
    }
}

/**
 * Open sidebar
 */
function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebar && overlay) {
        setSidebarOpen(true);
    }
}

/**
 * Check if the mobile sidebar is open
 * @returns {boolean}
 */
function isSidebarOpen() {
    const sidebar = document.getElementById('sidebar');
    return !!sidebar && !sidebar.classList.contains('-translate-x-full');
}

/**
 * Sync sidebar and overlay state
 * @param {boolean} isOpen
 */
function setSidebarOpen(isOpen) {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (!sidebar || !overlay) return;

    sidebar.classList.toggle('-translate-x-full', !isOpen);
    overlay.classList.toggle('hidden', !isOpen);
    document.body.classList.toggle('overflow-hidden', isOpen);
}

// ============================================
// Table Helpers
// ============================================

/**
 * Toggle all row checkboxes
 * @param {HTMLInputElement} checkbox - The "select all" checkbox
 */
function toggleAllRows(checkbox) {
    const table = checkbox.closest('table');
    const checkboxes = table.querySelectorAll('.row-checkbox');

    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
        // Toggle row highlight
        const row = cb.closest('tr');
        if (row) {
            row.classList.toggle('bg-emerald-50', cb.checked);
        }
    });

    // Update bulk actions button state
    updateBulkActionsButton();
}

/**
 * Update bulk actions button state
 */
function updateBulkActionsButton() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const button = document.getElementById('bulk-actions-btn');

    if (button) {
        if (checked.length > 0) {
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
}

// ============================================
// Form Helpers
// ============================================

/**
 * Add loading state to all forms
 */
function initFormLoadingStates() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                setButtonLoading(button);
            }
        });
    });
}

/**
 * Show inline validation error
 * @param {HTMLElement} input - The input element
 * @param {string} message - Error message
 */
function showInputError(input, message) {
    // Remove existing error
    clearInputError(input);

    // Add error styles
    input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');

    // Add error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-500 text-xs mt-1 input-error-message';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

/**
 * Clear inline validation error
 * @param {HTMLElement} input - The input element
 */
function clearInputError(input) {
    input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');

    const errorDiv = input.parentNode.querySelector('.input-error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// ============================================
// Utility Functions
// ============================================

/**
 * Debounce function
 * @param {function} func - Function to debounce
 * @param {number} wait - Wait time in ms
 * @returns {function} Debounced function
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Format date to human readable format
 * @param {string} dateString - ISO date string
 * @returns {string} Formatted date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Get relative time (e.g., "2 hours ago")
 * @param {string} dateString - ISO date string
 * @returns {string} Relative time
 */
function timeAgo(dateString) {
    const date = new Date(dateString);
    const seconds = Math.floor((new Date() - date) / 1000);

    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };

    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
        }
    }

    return 'just now';
}

/**
 * Copy text to clipboard
 * @param {string} text - Text to copy
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showSuccess('Copied to clipboard!');
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showSuccess('Copied to clipboard!');
    });
}

// ============================================
// Initialize on DOM ready
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dark mode
    initDarkMode();

    // Initialize form loading states
    initFormLoadingStates();

    // Initialize mobile sidebar controls
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    // Initialize sidebar close on overlay click
    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close the mobile sidebar after a navigation item is tapped
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    closeSidebar();
                }
            });
        });
    }

    // Keep the mobile drawer state consistent when the viewport changes
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            closeSidebar();
        }
    });

    // Allow keyboard users to dismiss the sidebar on mobile
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isSidebarOpen()) {
            closeSidebar();
        }
    });

    // Initialize row checkboxes
    document.querySelectorAll('.row-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('tr');
            if (row) {
                row.classList.toggle('bg-emerald-50', this.checked);
            }
            updateBulkActionsButton();
        });
    });

    // Initialize select all checkbox
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            toggleAllRows(this);
        });
    }
});

// Listen for dark mode changes
window.addEventListener('darkModeChanged', function(e) {
    console.log('Dark mode changed:', e.detail.isDark);
    // Add any custom logic here
});
