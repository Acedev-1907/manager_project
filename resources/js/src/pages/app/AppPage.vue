<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue';
import NarBar from './components/NarBar.vue';
import MobileFooter from './components/MobileFooter.vue';
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
    // Lấy userId từ user-store (ưu tiên) hoặc userData (fallback)
    // @ts-expect-error - Pinia store type inference issue
    const userId = userStore.user?.id || (userData?.user as any)?.id;
    
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
        // @ts-expect-error - Pinia store type inference issue
        userStore.clearUser();
        // @ts-expect-error - Pinia store type inference issue
        dashboardStore.clearAll();
        // @ts-expect-error - Pinia store type inference issue
        projectStore.clearProjects();
        // @ts-expect-error - Pinia store type inference issue
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

// Computed properties for template to avoid TypeScript errors
const loggedInUserName = computed(() => {
    // @ts-expect-error - Pinia store type inference issue
    return userStore.user?.name;
});

const userAvatar = computed(() => {
    // @ts-expect-error - Pinia store type inference issue
    return userStore.userAvatar;
});
</script>

<template>
    <div class="admin-layout">
        <NarBar :loggedInUserName="loggedInUserName" :avatar="userAvatar" :logoutLoading="loading"
            @logout="logoutUser" />
        <div class="admin-content">
            <router-view v-slot="{ Component, route }">
                <transition name="fade" mode="out-in">
                    <keep-alive :include="['MemberPage', 'ProjectPage', 'DashboardPage']">
                        <component :is="Component" :key="route.fullPath" />
                    </keep-alive>
                </transition>
            </router-view>
        </div>
        <MobileFooter />
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
    padding-left: 0;
    padding-right: 0;
}

@media (max-width: 767.98px) {
    .admin-content {
        padding-top: 10px;
        padding-left: 0;
        padding-right: 0;
        padding-bottom: 80px; /* Space for mobile footer */
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