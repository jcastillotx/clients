import './bootstrap';

// Custom JavaScript for Kre8iv Client Portal

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds (vanilla JS)
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Alert dismiss buttons (vanilla JS replacement for Bootstrap)
    document.querySelectorAll('.alert-close, .alert .close').forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.closest('.alert');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        });
    });
});

// PWA: service worker + install prompt + offline indicator + basic push subscription helper
document.addEventListener('DOMContentLoaded', function () {
    // Offline indicator (Alpine.js compatible)
    const offlineIndicator = document.getElementById('offline-indicator');
    const setOfflineUi = () => {
        if (!offlineIndicator) return;
        // Use Alpine.js x-data if available
        if (offlineIndicator.__x) {
            offlineIndicator.__x.$data.show = !navigator.onLine;
        } else {
            // Fallback to vanilla JS
            if (navigator.onLine) {
                offlineIndicator.classList.add('hidden');
            } else {
                offlineIndicator.classList.remove('hidden');
            }
        }
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
            // Use Alpine.js if available
            if (banner.__x) {
                banner.__x.$data.show = true;
            } else {
                banner.classList.remove('hidden', 'd-none');
            }
        }
    });

    dismissBtn?.addEventListener('click', () => {
        localStorage.setItem(dismissedKey, '1');
        if (banner?.__x) {
            banner.__x.$data.show = false;
        } else {
            banner?.classList.add('hidden', 'd-none');
        }
    });

    installBtn?.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        try { await deferredPrompt.userChoice; } catch {}
        deferredPrompt = null;
        if (banner?.__x) {
            banner.__x.$data.show = false;
        } else {
            banner?.classList.add('hidden', 'd-none');
        }
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
        overlay.className = 'fixed inset-0 bg-slate-900 bg-opacity-50 z-50 flex items-center justify-center';
        overlay.id = 'loading-overlay';
        overlay.innerHTML = '<div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>';
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
        const colors = {
            success: 'alert-success',
            error: 'alert-danger',
            danger: 'alert-danger',
            warning: 'alert-warning',
            info: 'alert-info'
        };
        alertDiv.className = `alert ${colors[type] || 'alert-info'} fixed top-20 right-5 z-50 max-w-sm shadow-lg`;
        alertDiv.innerHTML = `
            <div class="flex items-center justify-between gap-3">
                <span>${message}</span>
                <button type="button" class="alert-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        document.body.appendChild(alertDiv);

        // Add close functionality
        alertDiv.querySelector('.alert-close').addEventListener('click', function() {
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 300);
        });

        // Auto-remove after 5 seconds
        setTimeout(function() {
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 300);
        }, 5000);
    }
};
