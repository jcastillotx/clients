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

// PWA: service worker + install prompt + offline indicator + basic push subscription helper
document.addEventListener('DOMContentLoaded', function () {
    // Offline indicator
    const offlineIndicator = document.getElementById('offline-indicator');
    const setOfflineUi = () => {
        if (!offlineIndicator) return;
        if (navigator.onLine) offlineIndicator.classList.add('d-none');
        else offlineIndicator.classList.remove('d-none');
    };
    window.addEventListener('online', setOfflineUi);
    window.addEventListener('offline', setOfflineUi);
    setOfflineUi();

    // Install prompt (browser-controlled)
    let deferredPrompt = null;
    const banner = document.getElementById('pwa-install-banner');
    const installBtn = document.getElementById('pwa-install-btn');
    const dismissBtn = document.getElementById('pwa-install-dismiss');
    const dismissedKey = 'pwa_install_dismissed';

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if (banner && !localStorage.getItem(dismissedKey)) {
            banner.classList.remove('d-none');
        }
    });

    dismissBtn?.addEventListener('click', () => {
        localStorage.setItem(dismissedKey, '1');
        banner?.classList.add('d-none');
    });

    installBtn?.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        try { await deferredPrompt.userChoice; } catch {}
        deferredPrompt = null;
        banner?.classList.add('d-none');
    });

    // Register service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
});

async function getSwRegistration() {
    if (!('serviceWorker' in navigator)) return null;
    return await navigator.serviceWorker.ready.catch(() => null);
}

function base64UrlToUint8Array(base64Url) {
    const padding = '='.repeat((4 - (base64Url.length % 4)) % 4);
    const base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    const out = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
}

async function subscribeToPush() {
    const reg = await getSwRegistration();
    if (!reg || !('PushManager' in window)) return { ok: false, error: 'Push not supported' };

    const vapid = document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content') || '';
    if (!vapid) return { ok: false, error: 'Missing VAPID public key' };

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return { ok: false, error: 'Notification permission not granted' };

    const existing = await reg.pushManager.getSubscription();
    const sub = existing || (await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: base64UrlToUint8Array(vapid),
    }));

    // Send subscription to server
    const payload = sub.toJSON();
    const resp = await fetch('/push/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(payload),
    });

    if (!resp.ok) return { ok: false, error: 'Failed to save subscription' };
    return { ok: true };
}

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
    subscribeToPush: subscribeToPush,
    
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
