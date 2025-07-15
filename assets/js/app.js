/**
 * CatatYuk - Main JavaScript File
 */

// Global variables
let notificationInterval;

// Document ready
$(document).ready(function() {
    // Initialize application
    initializeApp();
    
    // Load notifications every 30 seconds
    if (typeof CURRENT_USER !== 'undefined' && CURRENT_USER) {
        loadNotifications();
        notificationInterval = setInterval(loadNotifications, 30000);
    }
});

// Initialize application
function initializeApp() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Format currency inputs
    $('.currency-input').on('input', function() {
        formatCurrencyInput(this);
    });
    
    // Date picker initialization
    $('.date-picker').attr('type', 'date');
    
    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            e.preventDefault();
        }
    });
    
    // Form validation
    $('form').on('submit', function() {
        return validateForm(this);
    });
}

// Load notifications
function loadNotifications() {
    $.ajax({
        url: APP_URL + '/api/notifications.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateNotificationUI(response.data);
            }
        },
        error: function() {
            console.log('Failed to load notifications');
        }
    });
}

// Update notification UI
function updateNotificationUI(notifications) {
    const unreadCount = notifications.filter(n => !n.is_read).length;
    const $badge = $('#notification-count');
    const $list = $('#notification-list');
    
    // Update badge
    if (unreadCount > 0) {
        $badge.text(unreadCount).show();
    } else {
        $badge.hide();
    }
    
    // Update notification list
    if (notifications.length > 0) {
        let html = '';
        notifications.slice(0, 5).forEach(function(notification) {
            html += `
                <li>
                    <a class="dropdown-item notification-item ${!notification.is_read ? 'unread' : ''}" 
                       href="#" onclick="markAsRead(${notification.id})">
                        <div class="d-flex justify-content-between">
                            <strong class="text-truncate">${notification.title}</strong>
                            <small class="text-muted">${formatDate(notification.created_at)}</small>
                        </div>
                        <div class="text-muted small">${notification.message}</div>
                    </a>
                </li>
            `;
        });
        $list.html(html);
    } else {
        $list.html('<li><span class="dropdown-item-text text-muted">Tidak ada notifikasi</span></li>');
    }
}

// Mark notification as read
function markAsRead(notificationId) {
    $.ajax({
        url: APP_URL + '/api/notifications.php',
        method: 'POST',
        data: {
            action: 'mark_read',
            id: notificationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                loadNotifications();
            }
        }
    });
}

// Format currency input
function formatCurrencyInput(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        value = parseInt(value).toLocaleString('id-ID');
        input.value = value;
    }
}

// Get numeric value from formatted currency
function getCurrencyValue(formattedValue) {
    return parseInt(formattedValue.replace(/[^\d]/g, '')) || 0;
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) {
        return 'Kemarin';
    } else if (diffDays < 7) {
        return diffDays + ' hari lalu';
    } else {
        return date.toLocaleDateString('id-ID');
    }
}

// Show loading spinner
function showLoading() {
    const spinner = `
        <div class="spinner-overlay">
            <div class="spinner-border spinner-border-custom text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    $('body').append(spinner);
}

// Hide loading spinner
function hideLoading() {
    $('.spinner-overlay').remove();
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Create toast container if not exists
    if (!$('#toast-container').length) {
        $('body').append('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
    }
    
    const $toast = $(toastHtml);
    $('#toast-container').append($toast);
    
    const toast = new bootstrap.Toast($toast[0]);
    toast.show();
    
    // Remove toast element after it's hidden
    $toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// Validate form
function validateForm(form) {
    let isValid = true;
    const $form = $(form);
    
    // Remove previous error messages
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').remove();
    
    // Validate required fields
    $form.find('[required]').each(function() {
        const $field = $(this);
        const value = $field.val().trim();
        
        if (!value) {
            showFieldError($field, 'Field ini wajib diisi');
            isValid = false;
        }
    });
    
    // Validate email fields
    $form.find('input[type="email"]').each(function() {
        const $field = $(this);
        const value = $field.val().trim();
        
        if (value && !isValidEmail(value)) {
            showFieldError($field, 'Format email tidak valid');
            isValid = false;
        }
    });
    
    // Validate phone fields
    $form.find('input[data-type="phone"]').each(function() {
        const $field = $(this);
        const value = $field.val().trim();
        
        if (value && !isValidPhone(value)) {
            showFieldError($field, 'Format nomor telepon tidak valid');
            isValid = false;
        }
    });
    
    // Validate currency fields
    $form.find('.currency-input').each(function() {
        const $field = $(this);
        const value = getCurrencyValue($field.val());
        
        if ($field.attr('required') && value <= 0) {
            showFieldError($field, 'Jumlah harus lebih dari 0');
            isValid = false;
        }
    });
    
    return isValid;
}

// Show field error
function showFieldError($field, message) {
    $field.addClass('is-invalid');
    $field.after(`<div class="invalid-feedback">${message}</div>`);
}

// Validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validate phone
function isValidPhone(phone) {
    const phoneRegex = /^(\+62|62|0)8[1-9][0-9]{6,9}$/;
    return phoneRegex.test(phone);
}

// AJAX helper function
function ajaxRequest(url, data, method = 'POST') {
    return $.ajax({
        url: url,
        method: method,
        data: data,
        dataType: 'json',
        beforeSend: function() {
            showLoading();
        },
        complete: function() {
            hideLoading();
        }
    });
}

// Export data to CSV
function exportToCSV(data, filename) {
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Convert array to CSV
function convertToCSV(data) {
    if (!data.length) return '';
    
    const headers = Object.keys(data[0]);
    const csvHeaders = headers.join(',');
    
    const csvRows = data.map(row => {
        return headers.map(header => {
            const value = row[header];
            return typeof value === 'string' ? `"${value.replace(/"/g, '""')}"` : value;
        }).join(',');
    });
    
    return [csvHeaders, ...csvRows].join('\n');
}

// Print page
function printPage() {
    window.print();
}

// Confirm action
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Auto-save form data to localStorage
function autoSaveForm(formId) {
    const $form = $('#' + formId);
    const storageKey = 'autosave_' + formId;
    
    // Load saved data
    const savedData = localStorage.getItem(storageKey);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(function(key) {
            $form.find('[name="' + key + '"]').val(data[key]);
        });
    }
    
    // Save data on input change
    $form.on('input change', function() {
        const formData = {};
        $form.find('input, select, textarea').each(function() {
            const $field = $(this);
            if ($field.attr('name')) {
                formData[$field.attr('name')] = $field.val();
            }
        });
        localStorage.setItem(storageKey, JSON.stringify(formData));
    });
    
    // Clear saved data on successful submit
    $form.on('submit', function() {
        localStorage.removeItem(storageKey);
    });
}

// Cleanup on page unload
$(window).on('beforeunload', function() {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
});

