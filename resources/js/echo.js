import Echo from 'laravel-echo'

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
        host: import.meta.env.VITE_REVERB_HOST,
        port: Number(import.meta.env.VITE_REVERB_PORT),
        scheme: import.meta.env.VITE_REVERB_SCHEME || 'https',
        path: '/ws/app',
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
