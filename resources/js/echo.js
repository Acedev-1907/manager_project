import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Đảm bảo Pusher được gán global để Echo (và Reverb connector) có thể sử dụng
if (typeof window !== 'undefined') {
    window.Pusher = Pusher
}

function getCurrentToken() {
    const userDataRaw = localStorage.getItem('userData')
    const token = userDataRaw ? JSON.parse(userDataRaw)?.token : ''

    return token
}

export function initEcho() {
    if (window.Echo) {
        window.Echo.disconnect()
        window.Echo = null
    }

    const token = getCurrentToken()

    // Cấu hình Reverb client dùng giao thức Pusher
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,

        // QUAN TRỌNG: ép Pusher client kết nối tới Reverb server, không dùng ws-.pusher.com
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT || 80,
        wssPort: import.meta.env.VITE_REVERB_PORT || 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
        wsPath: '/ws',
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
