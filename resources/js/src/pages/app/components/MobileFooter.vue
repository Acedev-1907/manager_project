<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const navItems = [
    { 
        name: 'Home', 
        path: '/newsfeed', 
        icon: 'bi-house-fill',
        activeIcon: 'bi-house-fill'
    },
    { 
        name: 'Add Friend', 
        path: '/members', 
        icon: 'bi-person-plus',
        activeIcon: 'bi-person-plus-fill'
    },
    { 
        name: 'Friends', 
        path: '/members', 
        icon: 'bi-people',
        activeIcon: 'bi-people-fill'
    },
    { 
        name: 'Messages', 
        path: '/chat', 
        icon: 'bi-chat-dots',
        activeIcon: 'bi-chat-dots-fill'
    },
    { 
        name: 'Notifications', 
        path: '/notifications', 
        icon: 'bi-bell',
        activeIcon: 'bi-bell-fill'
    }
];

const isActive = (path: string) => {
    return route.path.startsWith(path);
};

const handleNavClick = (path: string) => {
    router.push(path);
};
</script>

<template>
    <nav class="mobile-footer-nav">
        <div 
            v-for="item in navItems" 
            :key="item.name"
            class="nav-item"
            :class="{ active: isActive(item.path) }"
            @click="handleNavClick(item.path)"
        >
            <i :class="isActive(item.path) ? item.activeIcon : item.icon"></i>
        </div>
    </nav>
</template>

<style scoped>
.mobile-footer-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    background: #1877f2;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 12px 0;
    box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.nav-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: rgba(255, 255, 255, 0.8);
    position: relative;
}

.nav-item i {
    font-size: 1.5rem;
    transition: all 0.2s ease;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    transform: translateY(-2px);
}

.nav-item.active {
    color: #fff;
    background: rgba(255, 255, 255, 0.15);
}

.nav-item.active i {
    transform: scale(1.1);
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

/* Hide on desktop */
@media (min-width: 768px) {
    .mobile-footer-nav {
        display: none;
    }
}

/* Safe area for devices with notch */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
    .mobile-footer-nav {
        padding-bottom: calc(12px + env(safe-area-inset-bottom));
    }
}
</style>

