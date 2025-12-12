<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import NarBar from './components/NarBar.vue';
import { useLogOutUser } from './actions/Logout';
import { getUserData } from '../../helper/getUserData';
import { clearCacheOnLogout } from '../../composables/useLocalStorage';
import { clearPersistedStores } from '../../plugins/piniaPersist';
import { useUserStore } from '../../state/userStore';
import { useDashboardStore } from './dashboard/store/dashboardStore';
import { useProjectStore } from './project/store/projectStore';
import { useMemberStore } from './member/store/MemberStore';
import { useAuth } from '../../composables/useAuth';
import eventBus from '../../helper/eventBus';

const { logout, loading } = useLogOutUser();
const { handleLogout } = useAuth();
const userStore = useUserStore();
const dashboardStore = useDashboardStore();
const projectStore = useProjectStore();
const memberStore = useMemberStore();

const userData = getUserData();
const isLoading = ref(true);

/**
 * Handle user logout
 * Uses centralized logout handler
 */
async function logoutUser() {
    const userId = userData?.user?.id;
    
    // Call logout API if user ID exists
    if (typeof userId !== 'undefined') {
        try {
            await logout(userId);
        } catch (error) {
            // Continue with logout even if API call fails
            console.error('Logout API error:', error);
        }
    }
    
    // Clear all stores
    const clearStores = () => {
        userStore.clearUser();
        dashboardStore.clearAll();
        projectStore.clearProjects();
        memberStore.clearAll();
        clearPersistedStores();
        clearCacheOnLogout();
    };
    
    // Use centralized logout handler
    await handleLogout(clearStores);
}

const showLoading = () => { isLoading.value = true; };
const hideLoading = () => { isLoading.value = false; };

onMounted(() => {
    eventBus.on('show-loading', showLoading);
    eventBus.on('hide-loading', hideLoading);
    isLoading.value = false;
});

onUnmounted(() => {
    eventBus.off('show-loading', showLoading);
    eventBus.off('hide-loading', hideLoading);
});
</script>

<template>
    <div class="admin-layout">
        <NarBar :loggedInUserName="userData?.user.name" :avatar="userData?.user.avatar" :logoutLoading="loading"
            @logout="logoutUser" />
        <div class="admin-content">
            <router-view v-slot="{ Component, route }">
                <transition name="fade" mode="out-in">
                    <keep-alive :include="['MemberPage', 'ProjectPage', 'DashboardPage']">
                        <component :is="Component" :key="route.path" />
                    </keep-alive>
                </transition>
            </router-view>
        </div>
    </div>
</template>

<style>
.admin-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8fafd;
}

.admin-content {
    flex: 1;
    min-width: 0;
    position: relative;
    padding-top: 10px;
    padding-left: 0;
    padding-right: 0;
}

@media (max-width: 767.98px) {
    .admin-content {
        padding-top: 10px;
        padding-left: 0;
        padding-right: 0;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition-duration: 0.3s;
    transition-property: opacity;
    transition-timing-function: ease;
}

.fade-enter,
.fade-leave-active {
    opacity: 0;
}

.loading-overlay {
    position: absolute;
    inset: 0;
    z-index: 10;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>