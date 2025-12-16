import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

function getCurrentToken() {
    const raw = localStorage.getItem('userData')
    return raw ? JSON.parse(raw)?.token : ''
}

export function initEcho() {
    if (window.Echo) {
        window.Echo.disconnect()
        window.Echo = null
    }

    const token = getCurrentToken()

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,

        wsHost: import.meta.env.VITE_REVERB_HOST, // domain
        wsPort: Number(import.meta.env.VITE_REVERB_PORT), // 8080
        wssPort: Number(import.meta.env.VITE_REVERB_PORT),

        forceTLS: (import.meta.env.VITE_REVERB_SCHEME === 'https'),
        enabledTransports: ['ws', 'wss'],

        authEndpoint: '/broadcasting/auth',

        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
        },
    })
}

export default initEcho
