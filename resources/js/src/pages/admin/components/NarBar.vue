<script lang="ts" setup>
import { ref, onMounted, onUnmounted } from "vue";
import { RouterLink, useRouter, useRoute } from "vue-router";
import { APP } from "../../../App/APP";
import { eventBus } from '../../../helper/eventBus';

const navigation = ref([
    {
        name: "Dashboard",
        link: "/dashboard",
        icon: "bi bi-speedometer2",
    },
    {
        name: "Projects",
        link: "/projects",
        icon: "bi bi-kanban",
    },
    {
        name: "Members",
        link: "/members",
        icon: "bi bi-people",
    },
]);

const emit = defineEmits<{
    (e: 'logout'): Promise<void>,
}>()

defineProps<{
    loggedInUserEmail: string | undefined,
    logoutLoading?: boolean
}>()

const sidebarOpen = ref(false)
function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
}
function closeSidebar() {
    sidebarOpen.value = false
}

// Đóng sidebar sau khi chuyển route (chỉ ở mobile)
const router = useRouter()
const route = useRoute()
let removeAfterEach: any = null
onMounted(() => {
    removeAfterEach = router.beforeEach((to, from, next) => {
        eventBus.emit('show-loading');
        next();
    });
    router.afterEach(() => {
        sidebarOpen.value = false;
        eventBus.emit('hide-loading');
    })
})
onUnmounted(() => {
    if (removeAfterEach) removeAfterEach()
})

function isActive(link: string) {
    // Dashboard chỉ active khi đúng /admin hoặc /admin/
    if (link === "/admin") {
        return route.path === "/admin" || route.path === "/admin/"
    }
    // Các menu khác active khi path bắt đầu đúng link
    return route.path.startsWith(link)
}
</script>
<template>
    <!-- Mobile Top Navbar -->
    <nav class="mobile-navbar d-md-none beautiful-mobile-navbar">
        <button class="hamburger modern-hamburger" @click="toggleSidebar">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="mobile-title beautiful-mobile-title">
            <img :src="`${APP.baseURL}/others/logo.png`" class="mobile-logo" alt="TaskMgr Logo">
            <span class="mobile-app-name">TaskMgr</span>
        </div>
    </nav>
    <!-- Sidebar: hiện đại, bo góc, shadow, icon lớn, avatar, nút đóng -->
    <nav :class="['sidebar', { 'sidebar-open': sidebarOpen, 'sidebar-mobile-modern': true }]" id="sidebarMenu">
        <div class="sidebar-mobile-header d-md-none">
            <div class="sidebar-mobile-avatar">
                <img :src="`${APP.baseURL}/others/logo.png`" alt="avatar" />
            </div>
            <div class="sidebar-mobile-info">
                <span class="sidebar-mobile-app">TaskMgr</span>
                <span class="sidebar-mobile-email">{{ loggedInUserEmail }}</span>
            </div>
            <button class="sidebar-mobile-close" @click="closeSidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="sidebar-mobile-menu d-md-none">
            <ul>
                <li v-for="nav in navigation" :key="nav.name">
                    <RouterLink class="sidebar-mobile-link" :class="{ active: isActive(nav.link) }" :to="nav.link">
                        <i :class="nav.icon + ' sidebar-mobile-icon'"></i>
                        <span>{{ nav.name }}</span>
                    </RouterLink>
                </li>
                <li>
                    <a class="sidebar-mobile-link logout" :class="{ 'loading': logoutLoading }"
                        @click="!logoutLoading && emit('logout'); closeSidebar()">
                        <i v-if="!logoutLoading" class="bi bi-box-arrow-right sidebar-mobile-icon"></i>
                        <i v-else class="bi bi-arrow-clockwise sidebar-mobile-icon spinning"></i>
                        <span>{{ logoutLoading ? 'Logging out...' : 'Logout' }}</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- Desktop giữ nguyên -->
        <div class="position-sticky pt-3 d-none d-md-block">
            <div align="center">
                <img :src="`${APP.baseURL}/others/logo.png`" alt="">
                <h4>TaskMgr</h4>
                {{ loggedInUserEmail }}
            </div>
            <br />
            <h6
                class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted menu-title">
                <span><i class="bi bi-list-task"></i> MENU</span>
                <a class="link-secondary" href="#" aria-label="Add a new report">
                    <span data-feather="plus-circle"></span>
                </a>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item" v-for="nav in navigation" :key="nav.name">
                    <RouterLink class="nav-link" :to="nav.link" :class="{ active: isActive(nav.link) }">
                        <i :class="nav.icon"></i>
                        {{ nav.name }}
                    </RouterLink>
                </li>
                <li class="nav-item" style="cursor: pointer" @click="!logoutLoading && emit('logout'); closeSidebar()">
                    <a class="nav-link" :class="{ 'loading': logoutLoading }">
                        <i v-if="!logoutLoading" class="bi bi-box-arrow-right"></i>
                        <i v-else class="bi bi-arrow-clockwise spinning"></i>
                        {{ logoutLoading ? 'Logging out...' : 'Logout' }}
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <!-- Overlay for mobile (đặt SAU sidebar để không che sidebar) -->
    <div v-if="sidebarOpen" class="sidebar-overlay d-md-none" @click="closeSidebar"></div>
</template>
<style>
/* Mobile Top Navbar */
.mobile-navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 1050;
}

.mobile-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: bold;
    font-size: 1.1rem;
}

.hamburger {
    background: none;
    border: none;
    display: flex;
    flex-direction: column;
    gap: 4px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    justify-content: center;
}

.hamburger span {
    display: block;
    height: 3px;
    width: 24px;
    background: #333;
    border-radius: 2px;
}

/* Sidebar overlay for mobile */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1049;
}

/* Sidebar responsive hiện đại cho mobile */
.sidebar.sidebar-mobile-modern {
    background: #fff;
    border-top-right-radius: 24px;
    border-bottom-right-radius: 24px;
    box-shadow: 4px 0 24px 0 rgba(0, 0, 0, 0.10);
    transition: transform 0.3s cubic-bezier(.4, 2, .6, 1), box-shadow 0.2s;
    width: 85vw;
    max-width: 340px;
    min-width: 220px;
    z-index: 1052;
    padding: 0;
    overflow-y: auto;
    margin-left: 0 !important;
    left: 0 !important;
}

@media (max-width: 767.98px) {
    .sidebar.sidebar-mobile-modern {
        position: fixed !important;
        top: 0;
        left: 0;
        height: 100vh;
        transform: translateX(-100%);
        display: block !important;
    }

    .sidebar.sidebar-open.sidebar-mobile-modern {
        transform: translateX(0);
        box-shadow: 4px 0 24px 0 rgba(0, 0, 0, 0.18);
    }

    .sidebar-mobile-header {
        display: flex;
        align-items: center;
        padding: 1.2rem 1.2rem 0.5rem 1.2rem;
        border-bottom: 1px solid #f0f0f0;
        position: relative;
        background: #f8fafd;
        border-top-right-radius: 24px;
    }

    .sidebar-mobile-avatar img {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e0e7ef;
        background: #fff;
    }

    .sidebar-mobile-info {
        flex: 1;
        margin-left: 1rem;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .sidebar-mobile-app {
        font-weight: bold;
        font-size: 1.1rem;
        color: #2470dc;
    }

    .sidebar-mobile-email {
        font-size: 0.95rem;
        color: #888;
    }

    .sidebar-mobile-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #888;
        cursor: pointer;
        position: absolute;
        right: 1.2rem;
        top: 1.2rem;
        z-index: 2;
    }

    .sidebar-mobile-menu {
        padding: 1.2rem 0.5rem 1.2rem 0.5rem;
    }

    .sidebar-mobile-menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-mobile-menu li {
        margin-bottom: 0.7rem;
    }

    .sidebar-mobile-link {
        display: flex;
        align-items: center;
        gap: 1.1rem;
        font-size: 1.15rem;
        font-weight: 500;
        color: #222;
        background: #f5f8ff;
        border-radius: 12px;
        padding: 0.85rem 1.2rem;
        text-decoration: none;
        transition: background 0.18s, color 0.18s;
    }

    .sidebar-mobile-link:hover,
    .sidebar-mobile-link.router-link-active {
        background: #2470dc;
        color: #fff;
    }

    .sidebar-mobile-icon {
        font-size: 1.5rem !important;
        min-width: 1.5rem !important;
        color: #2470dc !important;
        transition: color 0.18s !important;
    }

    .sidebar-mobile-link:hover .sidebar-mobile-icon,
    .sidebar-mobile-link.router-link-active .sidebar-mobile-icon {
        color: #fff;
    }

    .sidebar-mobile-link.logout {
        background: #ffeaea;
        color: #d32f2f;
    }

    .sidebar-mobile-link.logout:hover {
        background: #d32f2f;
        color: #fff;
    }

    .sidebar-mobile-link.logout .sidebar-mobile-icon {
        color: #d32f2f;
    }

    .sidebar-mobile-link.logout:hover .sidebar-mobile-icon {
        color: #fff;
    }
}

@media (min-width: 768px) {
    .sidebar.sidebar-mobile-modern {
        border-radius: 0;
        box-shadow: none;
        width: 260px;
        min-width: 200px;
        max-width: 320px;
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        margin-left: 0 !important;
        z-index: 1052;
    }

    .sidebar-mobile-header,
    .sidebar-mobile-menu {
        display: none;
    }
}

/* Desktop giữ nguyên */
a.router-link-active.router-link-exact-active.nav-link {
    color: white;
}

.nav-item .router-link-exact-active {
    border-radius: 5px;
    box-shadow: 1px 1px 5px 1px #69757d;
    background: #2470dc;
    color: #fff;
}

.sidebar-mobile-link.active {
    background: #2470dc !important;
    color: #fff !important;
}

.sidebar-mobile-link.router-link-active {
    background: #f5f8ff;
    color: #222;
}

.sidebar-mobile-link.active .sidebar-mobile-icon {
    color: #fff !important;
}

.nav-link.active {
    background: #2470dc !important;
    color: #fff !important;
    border-radius: 8px;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    box-shadow: 1px 1px 8px 1px #b3c6e0;
}

.nav-link.router-link-active {
    background: #f5f8ff;
    color: #222;
    border-radius: 8px;
    transition: background 0.18s, color 0.18s;
}

.nav-link {
    border-radius: 8px;
    padding: 0.7rem 1.2rem;
    font-size: 1.08rem;
    font-weight: 500;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
}

.nav-link:hover {
    background: #e3edfa;
    color: #2470dc;
}

.sidebar-mobile-link,
.nav-link {
    border-radius: 12px;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
}

.sidebar-mobile-link.active {
    background: #2470dc !important;
    color: #fff !important;
    border-radius: 12px;
    box-shadow: 1px 1px 8px 1px #b3c6e0;
}

.sidebar-mobile-link.router-link-active {
    background: #f5f8ff;
    color: #222;
    border-radius: 12px;
}

.sidebar-mobile-link:hover {
    background: #e3edfa;
    color: #2470dc;
}

.sidebar-mobile-link {
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
}

.sidebar-mobile-icon,
.nav-link i {
    font-size: 1.5rem;
    min-width: 1.5rem;
    color: #2470dc;
    transition: color 0.18s;
}

.sidebar-mobile-link.active .sidebar-mobile-icon,
.nav-link.active i {
    color: #fff !important;
}

.sidebar-mobile-link.logout {
    background: #ffeaea;
    color: #d32f2f;
    border-radius: 12px;
}

.sidebar-mobile-link.logout:hover {
    background: #d32f2f;
    color: #fff;
}

.sidebar-mobile-link.logout .sidebar-mobile-icon {
    color: #d32f2f;
}

.sidebar-mobile-link.logout:hover .sidebar-mobile-icon {
    color: #fff;
}

/* Avatar/logo/email tối ưu */
.sidebar-mobile-avatar img,
.sidebar .sidebar-mobile-avatar img,
.sidebar img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e0e7ef;
    background: #fff;
    margin-bottom: 0.5rem;
}

.sidebar-mobile-info,
.sidebar .sidebar-mobile-info {
    flex: 1;
    margin-left: 1rem;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.sidebar-mobile-app,
.sidebar .sidebar-mobile-app {
    font-weight: bold;
    font-size: 1.1rem;
    color: #2470dc;
}

.sidebar-mobile-email,
.sidebar .sidebar-mobile-email {
    font-size: 0.97rem;
    color: #888;
    word-break: break-all;
}

@media (max-width: 767.98px) {
    .sidebar.sidebar-mobile-modern {
        border-top-right-radius: 24px;
        border-bottom-right-radius: 24px;
        box-shadow: 4px 0 24px 0 rgba(0, 0, 0, 0.10);
        width: 85vw;
        max-width: 340px;
        min-width: 220px;
        left: 0 !important;
        background: #f8fafd;
    }

    .sidebar-mobile-header {
        background: #f8fafd;
        border-top-right-radius: 24px;
    }
}

@media (min-width: 768px) {
    .sidebar.sidebar-mobile-modern {
        border-radius: 0 16px 16px 0;
        box-shadow: 2px 0 16px 0 rgba(0, 0, 0, 0.08);
        width: 260px;
        min-width: 200px;
        max-width: 320px;
        background: #f8fafd;
    }
}

.menu-title span {
    font-weight: 800;
    font-size: 1.08rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #2470dc;
    display: flex;
    align-items: center;
    gap: 0.5em;
    position: relative;
}

.menu-title span::after {
    content: "";
    display: block;
    height: 3px;
    width: 36px;
    background: linear-gradient(90deg, #2470dc 60%, #6bc1ff 100%);
    border-radius: 2px;
    margin-left: 0.5em;
    margin-top: 2px;
}

.beautiful-mobile-navbar {
    background: #fff;
    border-radius: 0 0 1.2rem 1.2rem;
    box-shadow: 0 4px 18px rgba(34, 34, 59, 0.10);
    min-height: 62px;
    height: 62px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 1rem;
    position: sticky;
    top: 0;
    z-index: 1050;
}

.beautiful-mobile-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
    flex: 1 1 auto;
}

.mobile-logo {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.10);
    background: #fff;
    border: 2px solid #e0e7ef;
}

.mobile-app-name {
    font-size: 1.25rem;
    font-weight: 800;
    color: #2563eb;
    letter-spacing: 0.04em;
    text-shadow: 0 2px 8px #e0e7ef;
}

.modern-hamburger {
    background: none;
    border: none;
    display: flex;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
    padding: 0;
    width: 38px;
    height: 38px;
    justify-content: center;
    align-items: center;
    margin-right: 0.5rem;
    margin-left: 0;
    transition: background 0.18s;
    border-radius: 50%;
}

.modern-hamburger:hover {
    background: #f3f7fa;
}

.modern-hamburger span {
    display: block;
    height: 3.5px;
    width: 22px;
    background: #2563eb;
    border-radius: 2px;
    transition: background 0.18s, width 0.18s;
}

@media (max-width: 767.98px) {
    .beautiful-mobile-navbar {
        border-radius: 0 0 1.2rem 1.2rem;
        min-height: 62px;
        height: 62px;
        padding: 0 0.7rem;
    }

    .modern-hamburger {
        margin-right: 0.5rem;
    }

    .beautiful-mobile-title {
        gap: 0.7rem;
    }

    .mobile-logo {
        width: 38px;
        height: 38px;
    }

    .mobile-app-name {
        font-size: 1.18rem;
    }
}

/* Loading animation */
.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

/* Loading state styles */
.sidebar-mobile-link.loading,
.nav-link.loading {
    opacity: 0.7;
    cursor: not-allowed;
    pointer-events: none;
}

.sidebar-mobile-link.loading .sidebar-mobile-icon,
.nav-link.loading i {
    animation: spin 1s linear infinite;
}

/* Disable hover effects when loading */
.sidebar-mobile-link.loading:hover,
.nav-link.loading:hover {
    background: inherit;
    color: inherit;
}

.sidebar-mobile-link.loading:hover .sidebar-mobile-icon,
.nav-link.loading:hover i {
    color: inherit;
}
</style>