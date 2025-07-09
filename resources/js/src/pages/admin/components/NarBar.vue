<script lang="ts" setup>
import { ref } from "vue";
import { RouterLink, useRouter, useRoute } from "vue-router";
import { APP } from "../../../App/APP";

const navigation = ref([
    { name: "Dashboard", link: "/dashboard", icon: "bi bi-speedometer2" },
    { name: "Projects", link: "/projects", icon: "bi bi-kanban" },
    { name: "Members", link: "/members", icon: "bi bi-people" },
]);

const emit = defineEmits<{ (e: 'logout'): Promise<void> }>()
defineProps<{ loggedInUserEmail: string | undefined, logoutLoading?: boolean }>()

const menuOpen = ref(false)
function toggleMenu() { menuOpen.value = !menuOpen.value }
function closeMenu() { menuOpen.value = false }

const router = useRouter()
const route = useRoute()
function isActive(link: string) {
    return route.path.startsWith(link)
}
</script>
<template>
    <nav class="top-navbar">
        <div class="navbar-left">
            <img :src="`${APP.baseURL}/others/logo.png`" class="navbar-logo" alt="TaskMgr Logo">
            <span class="navbar-app-name">TaskMgr</span>
        </div>
        <button class="navbar-hamburger d-md-none" @click="toggleMenu">
            <span></span><span></span><span></span>
        </button>
        <ul class="navbar-menu d-none d-md-flex">
            <li v-for="nav in navigation" :key="nav.name">
                <RouterLink :to="nav.link" class="navbar-link" :class="{ active: isActive(nav.link) }">
                    <i :class="nav.icon"></i> <span>{{ nav.name }}</span>
                </RouterLink>
            </li>
        </ul>
        <div class="navbar-user d-none d-md-flex">
            <span class="navbar-email">{{ loggedInUserEmail }}</span>
            <button class="navbar-logout" :disabled="logoutLoading" @click="!logoutLoading && emit('logout')">
                <i v-if="!logoutLoading" class="bi bi-box-arrow-right"></i>
                <i v-else class="bi bi-arrow-clockwise spinning"></i>
                <span>{{ logoutLoading ? 'Logging out...' : 'Logout' }}</span>
            </button>
        </div>
        <!-- Mobile menu -->
        <transition name="fade">
            <div v-if="menuOpen" class="navbar-mobile-menu d-md-none">
                <ul>
                    <li v-for="nav in navigation" :key="nav.name">
                        <RouterLink :to="nav.link" class="navbar-link" :class="{ active: isActive(nav.link) }"
                            @click="closeMenu">
                            <i :class="nav.icon"></i> <span>{{ nav.name }}</span>
                        </RouterLink>
                    </li>
                    <li>
                        <button class="navbar-logout" :disabled="logoutLoading"
                            @click="!logoutLoading && emit('logout'); closeMenu()">
                            <i v-if="!logoutLoading" class="bi bi-box-arrow-right"></i>
                            <i v-else class="bi bi-arrow-clockwise spinning"></i>
                            <span>{{ logoutLoading ? 'Logging out...' : 'Logout' }}</span>
                        </button>
                    </li>
                </ul>
                <div class="navbar-mobile-user">{{ loggedInUserEmail }}</div>
            </div>
        </transition>
        <div v-if="menuOpen" class="navbar-mobile-overlay d-md-none" @click="closeMenu"></div>
    </nav>
</template>
<style>
.top-navbar {
    width: 100%;
    position: sticky;
    top: 0;
    z-index: 1200;
    background: #fff;
    box-shadow: 0 2px 12px rgba(36, 112, 220, 0.10), 0 1.5px 8px rgba(36, 112, 220, 0.07);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 0.75rem;
    min-height: 64px;
    border-radius: 0 0 1.2rem 1.2rem;
}

.navbar-left {
    display: flex;
    align-items: center;
    gap: 0.7rem;
}

.navbar-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    background: #fff;
    border: 2px solid #e0e7ef;
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.10);
}

.navbar-app-name {
    font-size: 1.25rem;
    font-weight: 800;
    color: #2563eb;
    letter-spacing: 0.04em;
    text-shadow: 0 2px 8px #e0e7ef;
}

.navbar-menu {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.navbar-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.08rem;
    font-weight: 500;
    color: #222;
    background: none;
    border: none;
    border-radius: 8px;
    padding: 0.7rem 1.2rem;
    text-decoration: none;
    transition: background 0.18s, color 0.18s;
}

.navbar-link.active,
.navbar-link.router-link-active {
    background: #2470dc;
    color: #fff;
}

.navbar-link:hover {
    background: #e3edfa;
    color: #2470dc;
}

.navbar-user {
    display: flex;
    align-items: center;
    gap: 1.2rem;
}

.navbar-email {
    font-size: 1rem;
    color: #888;
    font-weight: 500;
    margin-right: 0.5rem;
}

.navbar-logout {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #ffeaea;
    color: #d32f2f;
    border: none;
    border-radius: 8px;
    padding: 0.6rem 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s, color 0.18s;
}

.navbar-logout:hover:not(:disabled) {
    background: #d32f2f;
    color: #fff;
}

.navbar-logout:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.navbar-hamburger {
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
    margin-left: auto;
}

.navbar-hamburger span {
    display: block;
    height: 3.5px;
    width: 22px;
    background: #2563eb;
    border-radius: 2px;
    transition: background 0.18s, width 0.18s;
}

.navbar-mobile-menu {
    position: fixed;
    top: 64px;
    left: 0;
    right: 0;
    margin: 0 auto;
    width: 94vw;
    max-width: 420px;
    background: #fff;
    box-shadow: 0 8px 32px rgba(36, 112, 220, 0.13), 0 1.5px 8px rgba(36, 112, 220, 0.07);
    border-radius: 0 0 1.5rem 1.5rem;
    z-index: 1100;
    padding: 1.5rem 1.2rem 1.2rem 1.2rem;
    animation: fadeInDown 0.28s cubic-bezier(.4, 1.4, .6, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
}

@keyframes fadeInDown {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.navbar-mobile-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
    width: 100%;
}

.navbar-mobile-menu li {
    margin-bottom: 1rem;
    width: 100%;
}

.navbar-mobile-menu .navbar-link {
    width: 100%;
    justify-content: flex-start;
    font-size: 1.15rem;
    padding: 0.95rem 1.2rem;
    border-radius: 14px;
    box-shadow: 0 1.5px 8px rgba(36, 112, 220, 0.04);
    font-weight: 600;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
}

.navbar-mobile-menu .navbar-link.active {
    background: #2563eb;
    color: #fff;
    box-shadow: 0 2px 12px rgba(36, 112, 220, 0.10);
}

.navbar-mobile-menu .navbar-link:hover {
    background: #e3edfa;
    color: #2563eb;
}

.navbar-mobile-menu .navbar-logout {
    width: 100%;
    justify-content: flex-start;
    margin-top: 0.5rem;
    background: #ffeaea;
    color: #d32f2f;
    border-radius: 14px;
    font-weight: 700;
    box-shadow: 0 1.5px 8px rgba(211, 47, 47, 0.07);
    transition: background 0.18s, color 0.18s;
    padding: 0.95rem 1.2rem;
}

.navbar-mobile-menu .navbar-logout:hover:not(:disabled) {
    background: #d32f2f;
    color: #fff;
}

.navbar-mobile-user {
    margin-top: 1.2rem;
    font-size: 1.01rem;
    color: #888;
    text-align: center;
    width: 100%;
    font-weight: 500;
    letter-spacing: 0.01em;
}

.navbar-mobile-overlay {
    position: fixed;
    top: 64px;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.18);
    z-index: 1000;
}

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

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}

.fade-enter,
.fade-leave-to {
    opacity: 0;
}

@media (max-width: 767.98px) {
    .top-navbar {
        padding: 0 0.4rem;
        min-height: 56px;
        box-shadow: 0 4px 18px rgba(36, 112, 220, 0.13), 0 1.5px 8px rgba(36, 112, 220, 0.10);
    }

    .navbar-app-name {
        font-size: 1.1rem;
    }

    .navbar-logo {
        width: 34px;
        height: 34px;
    }

    .navbar-mobile-menu {
        top: 56px;
        left: 0;
        right: 0;
        margin: 0 auto;
        padding: 1.1rem 0.7rem 1rem 0.7rem;
        max-width: 98vw;
    }

    .navbar-mobile-menu .navbar-link,
    .navbar-mobile-menu .navbar-logout {
        font-size: 1.08rem;
        padding: 0.85rem 1rem;
    }

    .navbar-mobile-overlay {
        top: 56px;
    }
}
</style>