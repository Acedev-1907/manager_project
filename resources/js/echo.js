import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Constants
const STORAGE_KEY = 'userData'
const AUTH_ENDPOINT = '/broadcasting/auth'

// State management
let isInitializing = false
let lastInitToken = null
let initPromise = null

// Setup Pusher global
if (typeof window !== 'undefined') {
    window.Pusher = Pusher
}

/**
 * Lấy token từ localStorage
 */
function getToken() {
    try {
        const data = localStorage.getItem(STORAGE_KEY)
        return data ? JSON.parse(data)?.token : null
    } catch {
        return null
    }
}

/**
 * Disconnect Echo instance hiện tại
 */
function disconnectEcho() {
    if (!window.Echo) return
    try {
        window.Echo.disconnect()
    } catch {
        // Ignore disconnect errors
    } finally {
        window.Echo = null
    }
}

/**
 * Lấy cấu hình Reverb từ environment
 */
function getConfig() {
    const appKey = import.meta.env.VITE_REVERB_APP_KEY
    if (!appKey) {
        console.warn('VITE_REVERB_APP_KEY is not set')
        return null
    }

    const isProd = import.meta.env.PROD
    const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname
    const defaultPort = window.location.protocol === 'https:' ? 443 : 80
    const wsPort = Number(import.meta.env.VITE_REVERB_PORT) || defaultPort
    const scheme = import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(':', '')
    
    return {
        appKey,
        wsHost,
        wsPort,
        forceTLS: scheme === 'https',
        wsPath: isProd ? '/ws' : '',
    }
}

/**
 * Tạo Echo instance
 */
function createEcho(config, token) {
    try {
        return new Echo({
            broadcaster: 'reverb',
            key: config.appKey,
            wsHost: config.wsHost,
            wsPort: config.wsPort,
            wssPort: config.wsPort,
            forceTLS: config.forceTLS,
            wsPath: config.wsPath,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: AUTH_ENDPOINT,
            auth: {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json',
                },
            },
        })
    } catch (error) {
        console.error('Failed to create Echo:', error)
        return null
    }
}

/**
 * Khởi tạo Laravel Echo với Reverb
 * @returns {Promise<boolean>}
 */
export function initEcho() {
    const token = getToken()
    if (!token) {
        console.warn('No token found')
        return Promise.resolve(false)
    }

    // Đang khởi tạo với cùng token → đợi
    if (isInitializing && lastInitToken === token && initPromise) {
        return initPromise
    }

    // Đã khởi tạo với cùng token → skip
    if (window.Echo && lastInitToken === token) {
        return Promise.resolve(true)
    }

    // Bắt đầu khởi tạo
    isInitializing = true
    lastInitToken = token

    initPromise = (async () => {
        try {
            const config = getConfig()
            if (!config) return false

            disconnectEcho()
            
            const echo = createEcho(config, token)
            if (!echo) return false

            window.Echo = echo

            if (import.meta.env.DEV) {
                console.log('Echo initialized:', { wsHost: config.wsHost, wsPort: config.wsPort })
            }

            return true
        } catch (error) {
            console.error('Error initializing Echo:', error)
            return false
        } finally {
            setTimeout(() => {
                isInitializing = false
                initPromise = null
            }, 1000)
        }
    })()

    return initPromise
}

export default initEcho
