import { onMounted, onUnmounted } from 'vue';
import eventBus from '../helper/eventBus';
import { getCurrentUserId } from '../helper/getUserData';
import { useDashboardStore } from '../pages/app/dashboard/store/dashboardStore';
import { createDebouncedFunction } from '../helper/utils';

/**
 * Dashboard Realtime Composable
 * 
 * Quản lý tất cả realtime events cho Dashboard
 * Tách biệt logic realtime khỏi component chính
 */
export function useDashboardRealtime(
    refreshCallback: () => Promise<void>,
    projectIdGetter: () => number | undefined
) {
    const dashboardStore = useDashboardStore();
    
    // Debounced refresh để tránh call API quá nhiều
    const debouncedRefresh = createDebouncedFunction(refreshCallback, 1000);

    /**
     * Setup count project listener
     */
    const setupCountProjectListener = () => {
        try {
            const currentUserId = getCurrentUserId();
            if (!currentUserId) return;

            window.Echo.private(`user.${currentUserId}`).listen(
                "UserProjectCountUpdated",
                (e: { countProject: number; userId: number }) => {
                    const newCount = { count: e.countProject };
                    // @ts-expect-error - Pinia store type inference issue
                    dashboardStore.setCountProject(newCount);
                    // Timestamp được quản lý bởi DashboardPage với memory storage
                }
            );
        } catch (error) {
            console.error('Failed to setup count project listener:', error);
        }
    };

    /**
     * Setup event bus listeners
     */
    const setupEventListeners = () => {
        // Force cache clear events
        eventBus.on('force-cache-clear', async (eventData: any) => {
            const projectId = projectIdGetter();
            if (projectId && eventData?.projectId === projectId) {
                await debouncedRefresh();
            }
        });

        // Task comment events
        eventBus.on('task-comment-created', async (eventData: any) => {
            const projectId = projectIdGetter();
            if (projectId && eventData?.projectId === projectId) {
                await debouncedRefresh();
            }
        });

        // Project pinned events
        eventBus.on('project-pinned', async () => {
            await refreshCallback();
        });

        // Count project updated
        eventBus.on('count-project-updated', (newCount: any) => {
            // @ts-expect-error - Pinia store type inference issue
            dashboardStore.setCountProject(newCount);
        });
    };

    /**
     * Cleanup event listeners
     */
    const cleanupEventListeners = () => {
        eventBus.off('force-cache-clear');
        eventBus.off('task-comment-created');
        eventBus.off('project-pinned');
        eventBus.off('count-project-updated');
    };

    /**
     * Initialize realtime listeners
     */
    const initializeRealtime = () => {
        setupCountProjectListener();
        setupEventListeners();
    };

    /**
     * Auto setup on mount
     */
    onMounted(() => {
        initializeRealtime();
    });

    /**
     * Auto cleanup on unmount
     */
    onUnmounted(() => {
        cleanupEventListeners();
    });

    return {
        initializeRealtime,
        cleanupEventListeners,
        debouncedRefresh,
    };
}

