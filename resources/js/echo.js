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
    // Kiểm tra xem có đủ env variables không
    const appKey = import.meta.env.VITE_REVERB_APP_KEY
    if (!appKey) {
        console.warn('VITE_REVERB_APP_KEY is not set. Echo will not be initialized.')
        return
    }

    // Disconnect Echo cũ nếu có
    if (window.Echo) {
        try {
            window.Echo.disconnect()
        } catch (err) {
            // Ignore disconnect errors
        }
        window.Echo = null
    }

    const token = getCurrentToken()
    if (!token) {
        console.warn('No token found. Echo will not be initialized.')
        return
    }

    // Lấy config từ env, với fallback hợp lý
    const isProd = import.meta.env.PROD
    const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname
    const wsPort = Number(import.meta.env.VITE_REVERB_PORT) || (window.location.protocol === 'https:' ? 443 : 80)
    const scheme = import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(':', '')
    const forceTLS = scheme === 'https'

    // LƯU Ý:
    // - Ở môi trường production phía sau Nginx, client sẽ gọi /ws/app/... rồi Nginx mới strip /ws.
    // - Ở local (kết nối trực tiếp tới Reverb server port 8080), URL đúng là /app/... (không có /ws).
    const wsPath = isProd ? '/ws' : '' // '' nghĩa là để mặc định /app/{key}

    try {
        // Cấu hình Reverb client dùng giao thức Pusher
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: appKey,

            // QUAN TRỌNG: wsPath phải là '/ws' (không có '/app'), Reverb sẽ tự thêm '/app/{key}'
            wsHost,
            wsPort,
            wssPort: wsPort,
            forceTLS,
            wsPath,
            enabledTransports: ['ws', 'wss'],

            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json',
                },
            },
        })

        // Log để debug (chỉ trong dev)
        if (import.meta.env.DEV) {
            console.log('Echo initialized:', {
                wsHost,
                wsPort,
                scheme,
                wsPath,
            })
        }
    } catch (error) {
        console.error('Failed to initialize Echo:', error)
        window.Echo = null
    }
}

export default initEcho
