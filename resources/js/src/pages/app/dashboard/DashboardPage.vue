<script lang="ts" setup>
import { onMounted, onActivated, ref, onUnmounted } from 'vue';
import PostList from './components/PostList.vue';
import RightSidebar from './components/RightSidebar.vue';
import LeftSidebar from './components/LeftSidebar.vue';
import { useGetPinnedProject } from './actions/GetPinnedProject';
import { useGetTotalProject } from './actions/countProject';
import LoadingPage from '../../../components/LoadingPage.vue';
import { useDashboardStore } from '../dashboard/store/dashboardStore';
import eventBus, { replayRecentEvents, getRecentEvents } from '../../../helper/eventBus';
import { getCurrentUserId, isCurrentUser } from '../../../helper/getUserData';
import { createDebouncedFunction } from '../../../helper/utils';
import { useGlobalRealtimeSetup } from '../../../helper/useGlobalRealtimeSetup';
import { useStorage } from '../../../composables/useStorage';
import { CachePresets } from '../../../composables/useCacheManager';

// Define component name for keep-alive
defineOptions({
    name: 'DashboardPage'
});

const { project, getPinnedProject } = useGetPinnedProject()
const { countProject, getTotalProject } = useGetTotalProject()
const isLoading = ref(true);
const dashboardStore = useDashboardStore();
const globalRealtime = useGlobalRealtimeSetup();

// Key to force chart re-render when data changes
const chartRenderKey = ref(0);

// Flag to prevent duplicate refresh
let isRefreshing = false;
let lastRefreshTime = 0;
const REFRESH_DEBOUNCE = 1000; // 1 second

// Cache management - Sử dụng memory storage (tự động clear khi reload)
const dashboardCache = useStorage<Record<string, any>>('dashboardCache', {}, {
  ...CachePresets.dashboard, // memory, 2 minutes
});

// Timestamp cache - Sử dụng memory storage
const pinnedProjectTimestamp = useStorage<number | null>('pinned_project_timestamp', null, {
  storageType: 'memory',
  ttl: 2 * 60 * 1000, // 2 minutes
});

const countProjectTimestamp = useStorage<number | null>('count_project_timestamp', null, {
  storageType: 'memory',
  ttl: 2 * 60 * 1000, // 2 minutes
});

// Helper function to check cache validity
const isCacheValid = (timestamp: number | null): boolean => {
    if (!timestamp) return false;
    const age = Date.now() - timestamp;
    return age < (2 * 60 * 1000); // 2 minutes
};

// Helper function to setup project listeners
const setupProjectListeners = (projectData: any) => {
    if (!projectData?.id) return;

    globalRealtime.setupProjectListener(projectData.id);

    if (projectData?.tasks && Array.isArray(projectData.tasks)) {
        projectData.tasks.forEach((task: any) => {
            if (task.id) {
                globalRealtime.setupTaskListener(task.id);
            }
        });
    }
};

// Helper function to save to cache
const saveToCache = (key: string, data: any) => {
    dashboardCache.value.value[key] = data;
    
    // Update timestamp in memory storage
    if (key === 'pinned_project') {
        pinnedProjectTimestamp.value.value = Date.now();
        chartRenderKey.value += 1;
    } else if (key === 'count_project') {
        countProjectTimestamp.value.value = Date.now();
    }
};

// Helper function to clear only pinned project cache (preserve count project)
const clearPinnedProjectCache = () => {
    if (dashboardCache.value.value['pinned_project']) {
        delete dashboardCache.value.value['pinned_project'];
        pinnedProjectTimestamp.value.value = null;
    }
};

// Debounced function to refresh pinned project
const debouncedRefreshPinnedProject = createDebouncedFunction(async () => {
    try {
        await getPinnedProject();
        // @ts-expect-error - Pinia store type inference issue
        dashboardStore.setPinnedProject(project.value);
        saveToCache('pinned_project', project.value);
        setupProjectListeners(project.value);
    } catch (error) {
        // Silent error handling
    }
}, 1000);

// Refresh with duplicate prevention
const refreshPinnedProjectOnce = async () => {
    const now = Date.now();
    
    // Prevent duplicate calls within debounce window
    if (isRefreshing || (now - lastRefreshTime) < REFRESH_DEBOUNCE) {
        console.log('Dashboard: Skipping duplicate refresh');
        return;
    }
    
    isRefreshing = true;
    lastRefreshTime = now;
    
    try {
        // Only clear pinned project cache, preserve count project cache
        clearPinnedProjectCache();
        await getPinnedProject();
        // @ts-expect-error - Pinia store type inference issue
        dashboardStore.setPinnedProject(project.value);
        saveToCache('pinned_project', project.value);
        setupProjectListeners(project.value);
        // chartRenderKey already incremented by saveToCache
    } catch (error) {
        console.error('Dashboard refresh error:', error);
    } finally {
        isRefreshing = false;
    }
};

// Setup count project listener
const setupCountProjectListener = () => {
    try {
        const currentUserId = getCurrentUserId();
        if (!currentUserId) return;
        if (typeof window === 'undefined' || !window.Echo) {
            // Echo chưa sẵn sàng -> bỏ qua realtime, chỉ dùng dữ liệu API/cache
            return;
        }

        window.Echo.private(`user.${currentUserId}`).listen(
            "UserProjectCountUpdated",
            (e: { countProject: number; userId: number }) => {
                const newCount = { count: e.countProject };
                // @ts-expect-error - Pinia store type inference issue
                dashboardStore.setCountProject(newCount);
                saveToCache('count_project', newCount);
            }
        );
    } catch (error) {
        // Silent error handling
    }
};

// Handle pinned project data
const handlePinnedProjectData = async (hasValidCache: boolean) => {
    if (hasValidCache) {
        const cachedData = dashboardCache.value.value['pinned_project'];
        // @ts-expect-error - Pinia store type inference issue
        dashboardStore.setPinnedProject(cachedData);
        project.value = cachedData;
        setupProjectListeners(project.value);
        // Increment chart key when loading cached data
        chartRenderKey.value += 1;
        
        // Debug log
        console.log('Dashboard - Loaded from cache:', {
            name: project.value?.name,
            tasks: project.value?.tasks,
            progress: project.value?.progress,
            columnNames: project.value?.columnNames,
            columnColors: project.value?.columnColors
        });

        // Replay recent events if any
        if (project.value?.id) {
            const recentEvents = getRecentEvents('force-cache-clear', project.value.id);
            if (recentEvents.length > 0) {
                replayRecentEvents('force-cache-clear', project.value.id);
            }
        }
    } else {
        await getPinnedProject();
        // @ts-expect-error - Pinia store type inference issue
        dashboardStore.setPinnedProject(project.value);
        saveToCache('pinned_project', project.value);
        setupProjectListeners(project.value);

        // Debug log
        console.log('Dashboard - Loaded from API:', {
            name: project.value?.name,
            tasks: project.value?.tasks,
            progress: project.value?.progress,
            columnNames: project.value?.columnNames,
            columnColors: project.value?.columnColors
        });

        // Replay recent events if any
        if (project.value?.id) {
            const recentEvents = getRecentEvents('force-cache-clear', project.value.id);
            if (recentEvents.length > 0) {
                replayRecentEvents('force-cache-clear', project.value.id);
            }
        }
    }
};

// Handle count project data
const handleCountProjectData = async (hasValidCache: boolean) => {
    if (hasValidCache) {
        const cachedData = dashboardCache.value.value['count_project'];
        // @ts-expect-error - Pinia store type inference issue
        dashboardStore.setCountProject(cachedData);
    } else {
        try {
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Count project API timeout')), 10000);
            });

            await Promise.race([getTotalProject(), timeoutPromise]);
            // @ts-expect-error - Pinia store type inference issue
            dashboardStore.setCountProject(countProject.value);
            saveToCache('count_project', countProject.value);
        } catch (error) {
            const defaultCount = { count: 0 };
            countProject.value = defaultCount;
            // @ts-expect-error - Pinia store type inference issue
            dashboardStore.setCountProject(defaultCount);
            saveToCache('count_project', defaultCount);
        }
    }
};

// Setup event listeners
const setupEventListeners = () => {
    // Count project updated events
    eventBus.on('count-project-updated', (newCount: any) => {
        dashboardCache.value.value['count_project'] = newCount;
        // @ts-expect-error - Pinia store type inference issue
        dashboardStore.setCountProject(newCount);
    });

    // Force cache clear events
    eventBus.on('force-cache-clear', async (eventData: any) => {
        try {
            if (project.value?.id && eventData?.projectId === project.value.id) {
                console.log('Dashboard: Received force-cache-clear event:', {
                    projectId: eventData.projectId,
                    reason: eventData.reason,
                    userId: eventData.userId,
                    isCurrentUser: isCurrentUser(eventData.userId)
                });
                
                if (isCurrentUser(eventData.userId) && eventData.reason === 'task-status-changed-by-drag') {
                    // Use once to prevent duplicate calls
                    await refreshPinnedProjectOnce();
                } else {
                    debouncedRefreshPinnedProject();
                }
            }
        } catch (error) {
            console.error('Dashboard: Error handling force-cache-clear:', error);
        }
    });

    // Optimistic dashboard updates when dragging task
    eventBus.on('dashboard-optimistic-update', (data: any) => {
        try {
            if (!data) return;
            const currentPinnedId = project.value?.id;
            if (!currentPinnedId || currentPinnedId !== data.projectId) return;

            // Update tasks and progress immediately
            if (Array.isArray(data.tasks)) {
                project.value = {
                    ...project.value,
                    tasks: data.tasks,
                    progress: typeof data.progress === 'number' ? data.progress : project.value?.progress
                };
                // @ts-expect-error - Pinia store type inference issue
                dashboardStore.setPinnedProject(project.value);
                // Save to cache to keep UI consistent on navigation
                saveToCache('pinned_project', project.value);
                // Force chart re-render
                chartRenderKey.value += 1;
            }
        } catch (_) {
            // Silent error handling
        }
    });

    // Task comment events
    eventBus.on('task-comment-created', async (eventData: any) => {
        try {
            if (project.value?.tasks && Array.isArray(project.value.tasks)) {
                const taskExists = project.value.tasks.some((task: any) => task.id === eventData.taskId);
                if (taskExists) {
                    debouncedRefreshPinnedProject();
                }
            }
        } catch (error) {
            // Silent error handling
            console.error(error);
        }
    });

    // Project pinned events
    eventBus.on('project-pinned', async (eventData: any) => {
        try {
            console.log('Dashboard: Received project-pinned event:', eventData);
            
            // Clear only pinned project cache, preserve count project cache
            clearPinnedProjectCache();
            await getPinnedProject();
            // @ts-expect-error - Pinia store type inference issue
            dashboardStore.setPinnedProject(project.value);
            saveToCache('pinned_project', project.value);
            setupProjectListeners(project.value);
            
            console.log('Dashboard: Project pinned successfully:', {
                name: project.value?.name,
                tasks: project.value?.tasks?.length || 0,
                progress: project.value?.progress
            });
        } catch (error) {
            console.error('Dashboard: Error handling project-pinned:', error);
        }
    });
};

// Handle page visibility change
const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible') {
        debouncedRefreshPinnedProject();
    }
};

onMounted(async () => {
    // Cache đã được khởi tạo tự động bởi useStorage

    // Check cache validity
    const hasValidPinnedProjectCache = dashboardCache.value.value['pinned_project'] && isCacheValid(pinnedProjectTimestamp.value.value);
    const hasValidCountProjectCache = dashboardCache.value.value['count_project'] && isCacheValid(countProjectTimestamp.value.value);

    // Set loading based on cache validity
    isLoading.value = !(hasValidPinnedProjectCache && hasValidCountProjectCache);

    // Handle data
    await handlePinnedProjectData(hasValidPinnedProjectCache);
    await handleCountProjectData(hasValidCountProjectCache);

    // Setup listeners
    setupCountProjectListener();
    setupEventListeners();
    document.addEventListener('visibilitychange', handleVisibilityChange);

    isLoading.value = false;

    // Fallback timeout
    setTimeout(() => {
        if (isLoading.value === true) {
            isLoading.value = false;
        }
    }, 8000);
});

// Handle component activation from keep-alive
onActivated(async () => {
    console.log('Dashboard activated');
    
    // Check if we need to refresh due to project pinning (sessionStorage - chỉ trong session)
    const needsRefresh = sessionStorage.getItem('dashboard_needs_refresh');
    const refreshReason = sessionStorage.getItem('dashboard_refresh_reason');
    
    if (needsRefresh === 'true') {
        console.log('Dashboard activated: Needs refresh due to:', refreshReason);
        
        // Show loading while refreshing
        isLoading.value = true;
        
        // Clear flags
        sessionStorage.removeItem('dashboard_needs_refresh');
        sessionStorage.removeItem('dashboard_refresh_reason');
        sessionStorage.removeItem('dashboard_pinned_project_id');
        
        try {
            // Force refresh - only clear pinned project cache
            clearPinnedProjectCache();
            await getPinnedProject();
            // @ts-expect-error - Pinia store type inference issue
            dashboardStore.setPinnedProject(project.value);
            saveToCache('pinned_project', project.value);
            setupProjectListeners(project.value);
            
            // Force chart re-render with delay to ensure DOM is ready
            chartRenderKey.value += 1;
            
            // Wait a bit for charts to initialize
            await new Promise(resolve => setTimeout(resolve, 100));
            
            console.log('Dashboard - Refreshed after pin:', {
                name: project.value?.name,
                tasks: project.value?.tasks,
                progress: project.value?.progress,
                columnNames: project.value?.columnNames,
                columnColors: project.value?.columnColors
            });
        } catch (error) {
            console.error('Error refreshing dashboard after pin:', error);
        } finally {
            isLoading.value = false;
        }
        
        return;
    }
    
    // Force charts to reflow after keep-alive activation
    try {
        // Nâng key để buộc ApexCharts remount trong mọi trường hợp
        chartRenderKey.value += 1;
        // Trì hoãn nhẹ rồi phát sự kiện resize để ApexCharts tính lại kích thước
        setTimeout(() => {
            try { window.dispatchEvent(new Event('resize')); } catch (e) { /* silent */ }
        }, 60);
    } catch (e) {
        // Silent error handling
    }

    // Check if dashboard has valid data, if not, refresh
    const hasValidPinnedProject = project.value && project.value.id && project.value.name;
    // @ts-expect-error - Pinia store type inference issue
    const hasValidCountProject = dashboardStore.countProject && dashboardStore.countProject.count !== undefined;
    
    if (!hasValidPinnedProject || !hasValidCountProject) {
        console.log('Dashboard activated: Missing data, refreshing...', {
            hasValidPinnedProject,
            hasValidCountProject,
            pinnedProject: project.value,
            // @ts-expect-error - Pinia store type inference issue
            countProject: dashboardStore.countProject
        });
        
        // Show loading while refreshing
        isLoading.value = true;
        
        try {
            // Refresh both pinned project and count project
            await handlePinnedProjectData(false); // Force refresh
            await handleCountProjectData(false); // Force refresh
        } catch (error) {
            console.error('Error refreshing dashboard data:', error);
        } finally {
            isLoading.value = false;
        }
        
        return;
    }
    
    // Check for missed events while component was inactive
    const allRecentEvents = getRecentEvents();

    // Determine pinned project id even if project not yet loaded
    let pinnedProjectId: number | undefined = project.value?.id;
    if (!pinnedProjectId) {
        try {
            // Try in-memory dashboardCache first
        const cachedPinned = dashboardCache?.value?.value?.['pinned_project'];
            if (cachedPinned?.id) pinnedProjectId = cachedPinned.id;
        } catch (_) {
            // Silent error handling
        }
    }

    const relevantEvents = allRecentEvents.filter((event: any) => {
        const isForce = event.type === 'force-cache-clear';
        const isDrag = event.data?.reason === 'task-status-changed-by-drag';
        const isSameProject = pinnedProjectId
            ? event.data?.projectId === pinnedProjectId
            : true; // if unknown project, accept any
        return isForce && isDrag && isSameProject;
    });

    // If there were task changes while we were away, refresh
    if (relevantEvents.length > 0) {
        console.log('Dashboard activated: Found missed task changes, refreshing...', {
            pinnedProjectId,
            recentCount: relevantEvents.length,
        });
        // Use once to prevent duplicate with event listener
        await refreshPinnedProjectOnce();
    }
});

onUnmounted(() => {
    eventBus.off('force-cache-clear');
    eventBus.off('task-comment-created');
    eventBus.off('count-project-updated');
    eventBus.off('project-pinned');
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <div class="dashboard-container">
        <LoadingPage :visible="isLoading" />
        <div class="friendbook-layout">
            <!-- Left Sidebar -->
            <div class="left-sidebar-wrapper">
                <LeftSidebar />
            </div>

            <!-- Main Content Area -->
            <div class="main-content">
            <PostList />
            </div>

            <!-- Right Sidebar -->
            <div class="right-sidebar-wrapper">
                <RightSidebar />
            </div>
        </div>
    </div>
</template>


<style scoped>
.dashboard-container {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 64px);
    background-color: #f0f2f5;
    padding: 0;
}

.friendbook-layout {
    display: grid;
    grid-template-columns: 320px 1fr 320px;
    gap: 20px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    width: 100%;
    align-items: start;
}

.left-sidebar-wrapper {
    height: fit-content;
    align-self: start;
}


.main-content {
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.right-sidebar-wrapper {
    height: fit-content;
    align-self: start;
}

@media (max-width: 1200px) {
    .friendbook-layout {
        grid-template-columns: 320px 1fr;
    }
    
    .right-sidebar-wrapper {
        display: none;
    }
}

@media (max-width: 768px) {
    .friendbook-layout {
        grid-template-columns: 1fr;
        padding: 12px;
        gap: 12px;
    }
    
    .left-sidebar-wrapper {
        display: none;
    }
    
    .dashboard-container {
        padding: 0;
    }
}
</style>
