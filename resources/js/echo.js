import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

function getCurrentToken() {
    const userDataRaw = localStorage.getItem('userData');
    const token = userDataRaw ? JSON.parse(userDataRaw)?.token : '';

    return token;
}

export function initEcho() {
    if (window.Echo) {
        window.Echo.disconnect();
        window.Echo = null;
    }

    const token = getCurrentToken();

    // Avoid Permissions-Policy warning: skip Pusher unload listener
    if (Pusher?.Runtime && typeof Pusher.Runtime.addUnloadListener === 'function') {
        Pusher.Runtime.addUnloadListener = () => {};
    }

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
        enabledTransports: ['ws'], // ép dùng ws, tránh xhr-polling
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json'
            }
        }
    });
}

initEcho();

export default initEcho;