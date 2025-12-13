import { ref, computed } from 'vue';
import { useDashboardStore } from '../pages/app/dashboard/store/dashboardStore';
import { useGetPinnedProject } from '../pages/app/dashboard/actions/GetPinnedProject';
import { useGetTotalProject } from '../pages/app/dashboard/actions/countProject';

/**
 * Dashboard Data Composable
 * 
 * Tập trung quản lý data và logic cho Dashboard
 * Giảm duplicated code và tối ưu API calls
 * 
 * Note: Cache được quản lý bởi DashboardPage với memory storage
 */
export function useDashboardData() {
    const dashboardStore = useDashboardStore();
    const { project, getPinnedProject } = useGetPinnedProject();
    const { countProject, getTotalProject } = useGetTotalProject();
    
    const isLoading = ref(false);
    const chartRenderKey = ref(0);

    /**
     * Load pinned project data
     * Cache được quản lý bởi DashboardPage với memory storage
     */
    const loadPinnedProject = async (useCache: boolean = true): Promise<void> => {
        try {
            // @ts-expect-error - Pinia store type inference issue
            if (useCache && dashboardStore.pinnedProject) {
                // Sử dụng cache từ store (đã được quản lý bởi DashboardPage)
                // @ts-expect-error - Pinia store type inference issue
                project.value = dashboardStore.pinnedProject;
                chartRenderKey.value++;
                return;
            }

            await getPinnedProject();
            // @ts-expect-error - Pinia store type inference issue
            dashboardStore.setPinnedProject(project.value);
            chartRenderKey.value++;
        } catch (error) {
            console.error('Failed to load pinned project:', error);
        }
    };

    /**
     * Load project count
     * Cache được quản lý bởi DashboardPage với memory storage
     */
    const loadProjectCount = async (useCache: boolean = true): Promise<void> => {
        try {
            // @ts-expect-error - Pinia store type inference issue
            if (useCache && dashboardStore.countProject) {
                // Sử dụng cache từ store (đã được quản lý bởi DashboardPage)
                return;
            }

            await getTotalProject();
            // @ts-expect-error - Pinia store type inference issue
            dashboardStore.setCountProject(countProject.value);
        } catch (error) {
            console.error('Failed to load project count:', error);
            // Set default value on error
            const defaultCount = { count: 0 };
            // @ts-expect-error - Pinia store type inference issue
            dashboardStore.setCountProject(defaultCount);
        }
    };

    /**
     * Refresh all dashboard data
     */
    const refreshDashboard = async (): Promise<void> => {
        isLoading.value = true;
        try {
            await Promise.all([
                loadPinnedProject(false),
                loadProjectCount(false)
            ]);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Clear all cache
     * Cache được quản lý bởi DashboardPage với memory storage
     */
    const clearCache = (): void => {
        // @ts-expect-error - Pinia store type inference issue
        dashboardStore.clearAll();
    };

    // Computed properties
    const hasValidProject = computed(() => {
        return project.value?.name && project.value.name.trim() !== '';
    });

    const projectCount = computed(() => {
        // @ts-expect-error - Pinia store type inference issue
        return dashboardStore.countProject?.count || 0;
    });

    return {
        // State
        isLoading,
        project,
        chartRenderKey,

        // Computed
        hasValidProject,
        projectCount,

        // Methods
        loadPinnedProject,
        loadProjectCount,
        refreshDashboard,
        clearCache,
    };
}

