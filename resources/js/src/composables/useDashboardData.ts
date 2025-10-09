import { ref, computed } from 'vue';
import { useDashboardStore } from '../pages/app/dashboard/store/dashboardStore';
import { useGetPinnedProject } from '../pages/app/dashboard/actions/GetPinnedProject';
import { useGetTotalProject } from '../pages/app/dashboard/actions/countProject';

/**
 * Dashboard Data Composable
 * 
 * Tập trung quản lý data và logic cho Dashboard
 * Giảm duplicated code và tối ưu API calls
 */
export function useDashboardData() {
    const dashboardStore = useDashboardStore();
    const { project, getPinnedProject } = useGetPinnedProject();
    const { countProject, getTotalProject } = useGetTotalProject();
    
    const isLoading = ref(false);
    const chartRenderKey = ref(0);

    // Cache management
    const CACHE_TIMEOUT = 1800000; // 30 minutes

    /**
     * Kiểm tra cache validity
     */
    const isCacheValid = (timestamp: string | null): boolean => {
        if (!timestamp) return false;
        const age = Date.now() - parseInt(timestamp);
        return age < CACHE_TIMEOUT;
    };

    /**
     * Load pinned project data
     */
    const loadPinnedProject = async (useCache: boolean = true): Promise<void> => {
        try {
            if (useCache) {
                const cachedData = dashboardStore.pinnedProject;
                const timestamp = localStorage.getItem('pinned_project_timestamp');
                
                if (cachedData && isCacheValid(timestamp)) {
                    project.value = cachedData;
                    chartRenderKey.value++;
                    return;
                }
            }

            await getPinnedProject();
            dashboardStore.setPinnedProject(project.value);
            localStorage.setItem('pinned_project_timestamp', Date.now().toString());
            chartRenderKey.value++;
        } catch (error) {
            console.error('Failed to load pinned project:', error);
        }
    };

    /**
     * Load project count
     */
    const loadProjectCount = async (useCache: boolean = true): Promise<void> => {
        try {
            if (useCache) {
                const cachedData = dashboardStore.countProject;
                const timestamp = localStorage.getItem('count_project_timestamp');
                
                if (cachedData && isCacheValid(timestamp)) {
                    return;
                }
            }

            await getTotalProject();
            dashboardStore.setCountProject(countProject.value);
            localStorage.setItem('count_project_timestamp', Date.now().toString());
        } catch (error) {
            console.error('Failed to load project count:', error);
            // Set default value on error
            const defaultCount = { count: 0 };
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
     */
    const clearCache = (): void => {
        localStorage.removeItem('pinned_project_timestamp');
        localStorage.removeItem('count_project_timestamp');
        dashboardStore.clearAll();
    };

    // Computed properties
    const hasValidProject = computed(() => {
        return project.value?.name && project.value.name.trim() !== '';
    });

    const projectCount = computed(() => {
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
        isCacheValid,
    };
}

