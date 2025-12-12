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
        // broadcaster: 'reverb',
        // key: import.meta.env.VITE_REVERB_APP_KEY,
        // wsHost: import.meta.env.VITE_REVERB_HOST,
        // wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        // wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        // forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        // enabledTransports: ['ws', 'wss'],
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,
        encrypted: true,
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