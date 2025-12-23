import './bootstrap';

// Custom JavaScript for Kre8iv Client Portal

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert.classList.contains('show')) {
                $(alert).alert('close');
            }
        }, 5000);
    });
});

// Custom file input label update
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('custom-file-input')) {
        const fileName = e.target.files.length > 1 
            ? e.target.files.length + ' files selected'
            : e.target.files[0]?.name || 'Choose file';
        const label = e.target.nextElementSibling;
        if (label) {
            label.textContent = fileName;
        }
    }
});

// Confirm before dangerous actions
document.addEventListener('click', function(e) {
    if (e.target.matches('[data-confirm]')) {
        const message = e.target.getAttribute('data-confirm') || 'Are you sure?';
        if (!confirm(message)) {
            e.preventDefault();
        }
    }
});

// Format currency inputs
function formatCurrency(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    if (parts[1] && parts[1].length > 2) {
        value = parseFloat(value).toFixed(2);
    }
    input.value = value;
}

// Export utilities for use in other scripts
window.ClientPortal = {
    formatCurrency: formatCurrency,
    
    showLoading: function() {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.id = 'loading-overlay';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>';
        document.body.appendChild(overlay);
    },
    
    hideLoading: function() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    },
    
    showNotification: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 70px; right: 20px; z-index: 9999; max-width: 350px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(function() {
            $(alertDiv).alert('close');
        }, 5000);
    }
};
