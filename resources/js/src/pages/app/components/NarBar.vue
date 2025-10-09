<script lang="ts" setup>
import { ref, onMounted, onBeforeUnmount, computed } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { APP } from "../../../App/APP";
import { useUserStore } from '../../../state/userStore';
import { getAvatarSrc } from '../../../helper/avatar';
import BellNotification from './BellNotification.vue';
import { notifications, notificationCount, listenRealtime, fetchNotifications } from '../../../state/notificationStore';
import eventBus from '../../../helper/eventBus';

const navigation = ref([
    { name: "Dashboard", link: "/dashboard", icon: "bi bi-speedometer2" },
    { name: "Projects", link: "/projects", icon: "bi bi-kanban" },
    { name: "Members", link: "/members", icon: "bi bi-people" },
]);

const emit = defineEmits<{ (e: 'logout'): Promise<void> }>()
defineProps<{ loggedInUserName: string | undefined, avatar?: string | undefined, logoutLoading?: boolean }>()

const menuOpen = ref(false)
function toggleMenu(event?: MouseEvent) {
    if (event) event.stopPropagation();
    setTimeout(() => {
        // Đóng chuông nếu đang mở
        eventBus.emit('close-bell-notification');
        menuOpen.value = !menuOpen.value;
    }, 0);
}
function closeMenu() { menuOpen.value = false }

const dropdownRef = ref<HTMLElement | null>(null);

// Đóng dropdown khi click ra ngoài
function handleClickOutside(event: MouseEvent) {
    if (!menuOpen.value) return;
    const dropdown = dropdownRef.value;
    const avatarBtn = document.querySelector('.navbar-avatar-btn');
    if (
        dropdown &&
        avatarBtn &&
        !dropdown.contains(event.target as Node) &&
        !avatarBtn.contains(event.target as Node)
    ) {
        closeMenu();
    }
}
onMounted(() => {
    // Lắng nghe notification realtime khi user đăng nhập
    listenRealtime();
    // Lấy danh sách notification khi load trang
    fetchNotifications();
    document.addEventListener('click', handleClickOutside, false); // bubbling phase
    eventBus.on('close-avatar-dropdown', closeMenu);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside, false);
    eventBus.off('close-avatar-dropdown', closeMenu);
});

const route = useRoute()
function isActive(link: string) {
    return route.path.startsWith(link)
}

// Thêm biến để kiểm soát hover label
const hoverNavIndex = ref<number | null>(null)
function handleNavMouseEnter(idx: number) {
    hoverNavIndex.value = idx
}
function handleNavMouseLeave() {
    hoverNavIndex.value = null
}
function handleNavClick() {
    hoverNavIndex.value = null
}

const userStore = useUserStore();

</script>
<template>
    <nav class="top-navbar">
        <div class="navbar-top-row d-flex d-md-none">
            <div class="navbar-left">
                <img :src="`${APP.baseURL}/others/logo.png`" class="navbar-logo" alt="TaskMgr Logo">
                <span class="navbar-app-name">TaskMgr</span>
            </div>
            <div
                style="display: flex; align-items: center; margin-left: auto; position: relative; gap: 0.4rem; padding-right: 0.5rem;">
                <BellNotification />
                <div class="navbar-avatar-btn" @click="toggleMenu($event)" style="position: relative;">
                    <template v-if="userStore.user && userStore.user.name">
                        <span v-if="userStore.user.avatar && userStore.user.avatar.length > 0" class="avatar-circle">
                            <img :src="getAvatarSrc(userStore.user.avatar, userStore.user.name)" alt="avatar"
                                style="width:100%;height:100%;object-fit:cover;border-radius:50%;" loading="lazy" />
                        </span>
                        <span v-else-if="userStore.user.name && userStore.user.name.length > 0" class="avatar-circle">
                            {{ userStore.user.name.charAt(0).toUpperCase() }}
                        </span>
                        <span v-else class="avatar-circle">?</span>
                    </template>
                    <span class="avatar-caret">
                        <i class="bi bi-caret-down-fill"></i>
                    </span>
                </div>
            </div>
        </div>
        <ul class="navbar-menu d-flex d-md-none">
            <li v-for="(nav, idx) in navigation" :key="nav.name" class="navbar-icon-item">
                <RouterLink :to="nav.link" class="navbar-link" :class="{ active: isActive(nav.link) }"
                    @mouseenter="handleNavMouseEnter(idx)" @mouseleave="handleNavMouseLeave" @click="handleNavClick">
                    <div class="navbar-icon-bg">
                        <i :class="nav.icon"></i>
                    </div>
                    <span class="navbar-underline"></span>
                </RouterLink>
            </li>
        </ul>
        <!-- Dropdown menu khi bấm avatar (mobile & desktop dùng chung) -->
        <transition name="fade">
            <div v-if="menuOpen" class="custom-dropdown d-md-none" ref="dropdownRef">
                <!-- Tài khoản -->
                <div class="custom-user-list">
                    <div class="custom-user-item">
                        <img v-if="userStore.user?.avatar"
                            :src="getAvatarSrc(userStore.user.avatar, userStore.user.name)" class="custom-avatar-img" />
                        <span v-else-if="userStore.user?.name" class="avatar-circle">{{
                            userStore.user.name.charAt(0).toUpperCase()
                            }}</span>
                        <span v-else class="avatar-circle">?</span>
                        <div class="user-info-block">
                            <span v-if="userStore.user?.name" class="custom-user-name">{{ userStore.user.name }}</span>
                            <span v-if="userStore.user?.friend_code" class="friend-code-text">
                                Friend code: {{ userStore.user?.friend_code }}
                            </span>
                        </div>
                    </div>
                </div>
                <hr class="custom-divider" />
                <!-- Menu -->
                <div class="custom-menu">
                    <RouterLink to="/profile" class="custom-menu-item" @click="closeMenu"><i class="bi bi-person"></i>
                        Profile
                    </RouterLink>
                    <div class="custom-menu-item" @click="closeMenu(); emit('logout')"><i
                            class="bi bi-box-arrow-right"></i>
                        Logout</div>
                </div>
            </div>
        </transition>
        <div v-if="menuOpen" class="navbar-mobile-overlay d-md-none" @click="closeMenu"></div>
        <!-- Desktop layout -->
        <div class="navbar-left d-none d-md-flex">
            <img :src="`${APP.baseURL}/others/logo.png`" class="navbar-logo" alt="TaskMgr Logo">
            <span class="navbar-app-name">TaskMgr</span>
        </div>
        <ul class="navbar-menu d-none d-md-flex">
            <li v-for="(nav, idx) in navigation" :key="nav.name" class="navbar-icon-item">
                <RouterLink :to="nav.link" class="navbar-link" :class="{ active: isActive(nav.link) }"
                    @mouseenter="handleNavMouseEnter(idx)" @mouseleave="handleNavMouseLeave" @click="handleNavClick">
                    <div class="navbar-icon-bg">
                        <i :class="nav.icon"></i>
                    </div>
                    <span class="navbar-underline"></span>
                    <span class="navbar-label" v-if="hoverNavIndex === idx">{{ nav.name }}</span>
                </RouterLink>
            </li>
        </ul>
        <!-- Avatar + bell + dropdown desktop -->
        <div class="navbar-user d-none d-md-flex" style="position: relative;">
            <BellNotification />
            <div class="navbar-avatar-btn" @click="toggleMenu($event)" style="position: relative;">
                <template v-if="userStore.user && userStore.user.name">
                    <span v-if="userStore.user.avatar && userStore.user.avatar.length > 0" class="avatar-circle">
                        <img :src="getAvatarSrc(userStore.user.avatar, userStore.user.name)" alt="avatar"
                            style="width:100%;height:100%;object-fit:cover;border-radius:50%;" loading="lazy" />
                    </span>
                    <span v-else-if="userStore.user.name && userStore.user.name.length > 0" class="avatar-circle">
                        {{ userStore.user.name.charAt(0).toUpperCase() }}
                    </span>
                    <span v-else class="avatar-circle">?</span>
                </template>
                <span class="avatar-caret">
                    <i class="bi bi-caret-down-fill"></i>
                </span>
            </div>
            <transition name="fade">
                <div v-if="menuOpen" class="custom-dropdown" ref="dropdownRef">
                    <!-- Tài khoản -->
                    <div class="custom-user-list">
                        <div class="custom-user-item">
                            <img v-if="userStore.user?.avatar"
                                :src="getAvatarSrc(userStore.user.avatar, userStore.user.name)"
                                class="custom-avatar-img" />
                            <span v-else-if="userStore.user?.name" class="avatar-circle">{{
                                userStore.user.name.charAt(0).toUpperCase()
                                }}</span>
                            <span v-else class="avatar-circle">?</span>
                            <div class="user-info-block"
                                style="display: flex; flex-direction: column; align-items: flex-start;">
                                <span v-if="userStore.user?.name" class="custom-user-name">{{
                                    userStore.user.name
                                }}</span>
                                <span v-if="userStore.user?.friend_code"
                                    style="color: #2563eb; font-size: 0.95em; display: block; line-height: 1.2; margin-left: 0; margin-top: 2px;">
                                    Friend code: {{ userStore.user?.friend_code }}
                                </span>
                                <span v-else class="custom-user-name">Unknown</span>
                            </div>
                        </div>
                    </div>
                    <hr class="custom-divider" />
                    <!-- Menu -->
                    <div class="custom-menu">
                        <RouterLink to="/profile" class="custom-menu-item" @click="closeMenu"><i
                                class="bi bi-person"></i>
                            Profile</RouterLink>
                        <div class="custom-menu-item" @click="closeMenu(); emit('logout')"><i
                                class="bi bi-box-arrow-right"></i>
                            Logout</div>
                    </div>
                </div>
            </transition>
        </div>
    </nav>
</template>
<style>
.top-navbar {
    width: 100%;
    position: sticky;
    top: 0;
    z-index: 50;
    background: #fff;
    box-shadow: 0 2px 12px rgba(36, 112, 220, 0.10), 0 1.5px 8px rgba(36, 112, 220, 0.07);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.25rem;
    min-height: 64px;
    border-radius: 0 0 1.2rem 1.2rem;
}

.navbar-top-row {
    width: 100%;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
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
    justify-content: center;
    gap: 3.5rem;
    margin: 0 auto;
    padding: 0;
    list-style: none;
    width: auto;
}

.navbar-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    box-shadow: none;
    text-decoration: none;
    min-width: 80px;
    min-height: 30px;
    position: relative;
    padding: 0;
}

.navbar-link.active {
    pointer-events: none;
}

.navbar-link:not(.active):hover,
.navbar-link:not(.active):focus {
    color: #2563eb;
    background: #e3edfa;
    border-radius: 7%;
}

.navbar-link:not(.active):hover .navbar-icon-bg,
.navbar-link:not(.active):focus .navbar-icon-bg {
    background: #e3edfa;
}

.navbar-link:not(.active):hover .navbar-icon-bg i,
.navbar-link:not(.active):focus .navbar-icon-bg i {
    color: #2563eb;
}

.navbar-link .navbar-icon-bg {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: none;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 4px;
}

.navbar-link .navbar-icon-bg i {
    font-size: 1.5rem;
    color: #888;
    transition: color 0.2s;
}

.navbar-link.active .navbar-icon-bg i {
    color: #2563eb;
}

.navbar-label {
    position: absolute;
    left: 50%;
    top: calc(100% + 8px);
    transform: translateX(-50%);
    background: #fff;
    color: #222;
    padding: 0.18rem 0.8rem;
    border-radius: 1.2rem;
    font-size: 0.98rem;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.08);
    transition: opacity 0.18s, top 0.18s;
    z-index: 9999;
    display: block;
}

.navbar-link:hover .navbar-label,
.navbar-link:focus .navbar-label {
    opacity: 1;
    top: calc(100% + 16px);
}

.navbar-link:hover {
    background: #e3edfa;
    color: #2470dc;
}

.navbar-user {
    display: flex;
    align-items: center;
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
    top: 112px;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.18);
    z-index: 1000;
}

.navbar-mobile-dropdown {
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
    gap: 0.8rem;
}

.navbar-mobile-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e7ef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 600;
    color: #2563eb;
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.10);
}

.avatar-circle,
.custom-avatar-img {
    width: 40px;
    height: 40px;
    min-width: 40px;
    min-height: 40px;
    max-width: 40px;
    max-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #e0e7ef;
    color: #2563eb;
    font-size: 1.3rem;
    font-weight: 700;
}

.custom-avatar-img {
    object-fit: cover;
}

.custom-user-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.2rem 0 0.2rem 0;
}

.custom-user-name {
    font-weight: 600;
    font-size: 1.08rem;
    color: #222;
    margin-left: 0.2rem;
    white-space: nowrap;
    max-width: 160px;
    display: inline-block;
    overflow: hidden;
    text-overflow: ellipsis;
}

.custom-dropdown {
    min-width: 200px;
    max-width: 260px;
    word-break: break-word;
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
    transition: opacity 0.02s;
}

.fade-enter,
.fade-leave-to {
    opacity: 0;
}

.navbar-underline {
    display: block;
    position: absolute;
    left: 0;
    bottom: -8px;
    width: 100%;
    height: 4px;
    background: transparent;
    border-radius: 2px 2px 8px 8px;
    margin: 0;
    transition: background 0.18s;
}

.navbar-link.active .navbar-underline {
    background: #2563eb;
}

.navbar-link:not(.active) .navbar-underline {
    display: none;
}

.navbar-icon-stack {
    margin-top: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    width: 100%;
}

.navbar-avatar-btn {
    width: 44px;
    height: 44px;
    min-width: 44px;
    min-height: 44px;
    max-width: 44px;
    max-height: 44px;
    border-radius: 50%;
    background: #e0e7ef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 600;
    color: #2563eb;
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.10);
    cursor: pointer;
    transition: box-shadow 0.18s;
    z-index: 2100;
    position: relative;
}

.navbar-avatar-btn:hover {
    box-shadow: 0 4px 16px rgba(36, 112, 220, 0.18);
}

.navbar-desktop-dropdown {
    position: absolute;
    top: 48px;
    right: 0;
    min-width: 180px;
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 8px 32px rgba(36, 112, 220, 0.13), 0 1.5px 8px rgba(36, 112, 220, 0.07);
    padding: 1.2rem 1.2rem 1rem 1.2rem;
    z-index: 1200;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.7rem;
}

.navbar-desktop-avatar .avatar-lg {
    width: 60px;
    height: 60px;
    font-size: 2rem;
}

.navbar-desktop-user {
    font-size: 1.05rem;
    color: #222;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-align: center;
}

.avatar-caret {
    position: absolute;
    right: 0;
    top: 31px;
    width: 18px;
    height: 18px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #444;
    font-size: 13px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    border: 2px solid #fff;
    z-index: 2;
}

.navbar-profile-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    color: #2563eb;
    font-weight: 600;
    padding: 10px 0;
    margin: 10px 0 16px 0;
    text-decoration: none;
    border: none;
    background: #f1f5fd;
    border-radius: 8px;
    font-size: 1.08rem;
    cursor: pointer;
    transition: background 0.18s, color 0.18s;
    box-shadow: 0 1px 4px rgba(36, 112, 220, 0.04);
}

.navbar-profile-link:hover {
    background: #e0e7ff;
    color: #1d4ed8;
    text-decoration: none;
}

.profile-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #2563eb;
    font-size: 1.25rem;
    margin-right: 4px;
}

.profile-link-text {
    display: inline-block;
    vertical-align: middle;
}

.navbar-desktop-dropdown .navbar-profile-link,
.navbar-mobile-dropdown .navbar-profile-link {
    width: 90%;
    margin-left: 5%;
    margin-right: 5%;
}

.navbar-desktop-dropdown .navbar-logout,
.navbar-mobile-dropdown .navbar-logout {
    margin-top: 0;
}

.custom-dropdown {
    position: absolute;
    top: 48px;
    right: 0;
    min-width: 320px;
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 8px 32px rgba(36, 112, 220, 0.13), 0 1.5px 8px rgba(36, 112, 220, 0.07);
    padding: 1.2rem 0.8rem 1rem 0.8rem;
    z-index: 1200;
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
}

.custom-user-list {
    margin-bottom: 0.2rem;
    margin-top: 0.2rem;
}

.custom-user-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.2rem 0 0.2rem 0;
}

.custom-avatar-img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.custom-user-name {
    font-weight: 600;
}

.custom-divider {
    border: none;
    border-top: 2px solid #afacac;
    margin: 0.2rem 0 0.3rem 0;
}

.custom-menu {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.custom-menu-item {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.7rem 0.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;
    color: #222;
    text-decoration: none;
    font-size: 1.05rem;
}

.custom-menu-item:hover {
    background: #f0f2f5;
}

.user-info-block {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.friend-code-text {
    color: #2563eb;
    font-size: 0.95em;
    display: block;
    line-height: 1.2;
    margin-left: 0;
    margin-top: 2px;
}

@media (max-width: 767.98px) {
    .top-navbar {
        flex-direction: column;
        align-items: stretch;
        padding: 0;
    }

    .navbar-top-row {
        display: flex;
        flex-direction: row;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        padding: 0.8rem 0.5rem 0 0.5rem;
        min-height: 40px;
    }

    .navbar-menu {
        display: flex;
        flex-direction: row;
        width: 100%;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0.2rem 0 0.2rem;
        background: none;
        box-shadow: none;
        gap: 0;
        clear: both;
    }

    .navbar-icon-item {
        flex: 1 1 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-width: 0;
    }

    .navbar-avatar-btn {
        width: 44px;
        height: 44px;
        min-width: 44px;
        min-height: 44px;
        max-width: 44px;
        max-height: 44px;
    }

    .avatar-circle {
        width: 44px;
        height: 44px;
        min-width: 44px;
        min-height: 44px;
        max-width: 44px;
        max-height: 44px;
        font-size: 1.45rem;
    }

    .navbar-top-row>div:last-child {
        padding-right: 0.5rem;
    }

    .navbar-link:focus,
    .navbar-link:hover {
        background: none !important;
        color: #2563eb;
    }

    .custom-dropdown {
        top: 68px;
    }

    .navbar-underline {
        bottom: -2px;
    }
}
</style>