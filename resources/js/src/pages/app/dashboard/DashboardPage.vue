<script lang="ts" setup>
import { onMounted, onActivated, ref, onUnmounted, watch } from 'vue';
import { useGetPinnedProject } from './actions/GetPinnedProject';
import ApexDonut from './components/ApexDonut.vue';
import ApexRadialBar from './components/ApexRadialBar.vue';
import { useGetTotalProject } from './actions/countProject';
import LoadingPage from '../../../components/LoadingPage.vue';
import { useDashboardStore } from '../dashboard/store/dashboardStore';
import eventBus, { replayRecentEvents, getRecentEvents } from '../../../helper/eventBus';
import { getCurrentUserId, isCurrentUser } from '../../../helper/getUserData';
import { createDebouncedFunction } from '../../../helper/utils';
import { useGlobalRealtimeSetup } from '../../../helper/useGlobalRealtimeSetup';

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

// Cache management
const dashboardCache = ref<Record<string, any>>({});
const CACHE_TIMEOUT = 1800000; // 30 minutes

// Initialize cache from localStorage
const initializeCache = () => {
    try {
        const cachedData = localStorage.getItem('dashboardCache');
        if (cachedData && cachedData !== '0' && cachedData !== 'null') {
            const parsed = JSON.parse(cachedData);
            if (parsed && typeof parsed === 'object') {
                dashboardCache.value = parsed;
            }
        }
    } catch (error) {
        dashboardCache.value = {};
    }
};

// Auto-save cache to localStorage
watch(dashboardCache, (val) => {
    localStorage.setItem('dashboardCache', JSON.stringify(val));
}, { deep: true });

// Helper function to check cache validity
const isCacheValid = (timestamp: string | null): boolean => {
    if (!timestamp) return false;
    const age = Date.now() - parseInt(timestamp);
    return age < CACHE_TIMEOUT;
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
    dashboardCache.value[key] = data;
    localStorage.setItem(`${key}_timestamp`, Date.now().toString());
    
    // Increment chart key to force re-render when pinned project changes
    if (key === 'pinned_project') {
        chartRenderKey.value += 1;
    }
};

// Helper function to clear dashboard cache
const clearDashboardCache = () => {
    dashboardCache.value = {};
    localStorage.setItem('dashboardCache', '{}');
    localStorage.removeItem('pinned_project_timestamp');
    localStorage.removeItem('count_project_timestamp');
};

// Helper function to clear only pinned project cache (preserve count project)
const clearPinnedProjectCache = () => {
    if (dashboardCache.value['pinned_project']) {
        delete dashboardCache.value['pinned_project'];
        localStorage.setItem('dashboardCache', JSON.stringify(dashboardCache.value));
        localStorage.removeItem('pinned_project_timestamp');
    }
};

// Debounced function to refresh pinned project
const debouncedRefreshPinnedProject = createDebouncedFunction(async () => {
    try {
        await getPinnedProject();
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
        if (currentUserId) {
            window.Echo.private(`user.${currentUserId}`).listen(
                "UserProjectCountUpdated",
                (e: { countProject: number; userId: number }) => {
                    const newCount = { count: e.countProject };
                    dashboardStore.setCountProject(newCount);
                    saveToCache('count_project', newCount);
                }
            );
        }
    } catch (error) {
        // Silent error handling
    }
};

// Handle pinned project data
const handlePinnedProjectData = async (hasValidCache: boolean) => {
    if (hasValidCache) {
        const cachedData = dashboardCache.value['pinned_project'];
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
        const cachedData = dashboardCache.value['count_project'];
        dashboardStore.setCountProject(cachedData);
    } else {
        try {
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Count project API timeout')), 10000);
            });

            await Promise.race([getTotalProject(), timeoutPromise]);
            dashboardStore.setCountProject(countProject.value);
            saveToCache('count_project', countProject.value);
        } catch (error) {
            const defaultCount = { count: 0 };
            countProject.value = defaultCount;
            dashboardStore.setCountProject(defaultCount);
            saveToCache('count_project', defaultCount);
        }
    }
};

// Setup event listeners
const setupEventListeners = () => {
    // Count project updated events
    eventBus.on('count-project-updated', (newCount: any) => {
        dashboardCache.value['count_project'] = newCount;
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
    // Initialize cache
    initializeCache();

    // Check cache validity
    const pinnedProjectTimestamp = localStorage.getItem('pinned_project_timestamp');
    const countProjectTimestamp = localStorage.getItem('count_project_timestamp');

    const hasValidPinnedProjectCache = dashboardCache.value['pinned_project'] && isCacheValid(pinnedProjectTimestamp);
    const hasValidCountProjectCache = dashboardCache.value['count_project'] && isCacheValid(countProjectTimestamp);

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
    
    // Check if we need to refresh due to project pinning
    const needsRefresh = localStorage.getItem('dashboard_needs_refresh');
    const refreshReason = localStorage.getItem('dashboard_refresh_reason');
    
    if (needsRefresh === 'true') {
        console.log('Dashboard activated: Needs refresh due to:', refreshReason);
        
        // Show loading while refreshing
        isLoading.value = true;
        
        // Clear flags
        localStorage.removeItem('dashboard_needs_refresh');
        localStorage.removeItem('dashboard_refresh_reason');
        localStorage.removeItem('dashboard_pinned_project_id');
        
        try {
            // Force refresh - only clear pinned project cache
            clearPinnedProjectCache();
            await getPinnedProject();
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
    const hasValidCountProject = dashboardStore.countProject && dashboardStore.countProject.count !== undefined;
    
    if (!hasValidPinnedProject || !hasValidCountProject) {
        console.log('Dashboard activated: Missing data, refreshing...', {
            hasValidPinnedProject,
            hasValidCountProject,
            pinnedProject: project.value,
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
            // @ts-ignore
            const cachedPinned = dashboardCache?.value?.['pinned_project'];
            if (cachedPinned?.id) pinnedProjectId = cachedPinned.id;
            if (!pinnedProjectId) {
                const dashCacheRaw = localStorage.getItem('dashboardCache');
                if (dashCacheRaw) {
                    const parsed = JSON.parse(dashCacheRaw || '{}');
                    if (parsed?.pinned_project?.id) pinnedProjectId = parsed.pinned_project.id;
                }
            }
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

<style scoped>
.dashboard-container {
    display: flex;
    flex-direction: column;
    padding: 2rem 1rem;
}

.dashboard-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 1rem;
    text-align: center;
}



.dashboard-card {
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
    padding: 2rem 1.5rem;
    min-width: 260px;
    flex: 1 1 300px;
    max-width: 370px;
    transition: box-shadow 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.dashboard-card:hover {
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.13);
}

.card-header {
    font-size: 1.2rem;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 1rem;
    text-align: center;
}

.card-body {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}



.dashboard-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #3182ce;
    text-align: center;
}

.priority-project-container {
    background: #f8fafc;
    border-radius: 1.2rem;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
    padding: 2rem 1.5rem;
    margin-top: 2rem;
    margin-bottom: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.priority-project-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #2563eb;
    margin-bottom: 1.5rem;
    text-align: center;
    letter-spacing: 0.5px;
}

.priority-project-row {
    display: flex;
    gap: 2rem;
    justify-content: center;
    width: 100%;
}

.dashboard-main-row {
    display: flex;
    flex-direction: row;
    gap: 2rem;
    justify-content: center;
    align-items: center;
    margin-bottom: 2rem;
}

.dashboard-main-col {
    flex: 1 1 180px;
    min-width: 180px;
    max-width: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.total-projects-card {
    width: 180px;
    height: 180px;
    border-radius: 1.2rem;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 1.5rem;
}

.dashboard-main-col .dashboard-card {
    width: 220px;
    height: 220px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

@media (max-width: 900px) {
    .dashboard-card {
        max-width: 100%;
        min-width: 0;
    }

    .priority-project-row {
        flex-direction: column;
        gap: 1.5rem;
    }

    .dashboard-main-row {
        flex-direction: column;
        gap: 1.5rem;
    }

    .priority-project-container {
        max-width: 100%;
        min-width: 0;
    }

    .dashboard-main-col,
    .total-projects-card {
        min-width: 0;
        max-width: 100%;
        width: 50vw;
        height: 50vw;
        max-width: 320px;
        max-height: 320px;
        min-width: 140px;
        min-height: 140px;
        margin-left: auto;
        margin-right: auto;
    }
}
</style>

<template>
    <div class="dashboard-container">
        <LoadingPage :visible="isLoading" />
        <h2 class="dashboard-title">Dashboard</h2>
        <div class="dashboard-main-row">
            <div class="dashboard-main-col">
                <div class="total-projects-card">
                    <div class="card-header">
                        <b>Total Projects</b>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-number">{{ dashboardStore.countProject?.count }}</div>
                    </div>
                </div>
            </div>
            <template v-if="project && project.id && project.name && project.name.trim() !== ''">
                <div class="priority-project-container">
                    <div class="priority-project-title">
                        Your priority project: {{ project.name }}
                    </div>
                    <div class="priority-project-row">
                        <div class="dashboard-card">
                            <div class="card-header"><b>Tasks</b></div>
                            <div class="card-body">
                                <div v-if="project.tasks && Array.isArray(project.tasks) && project.tasks.length > 0">
                                    <ApexDonut 
                                        :key="`donut-${chartRenderKey}-${project.id}`"
                                        :task="project.tasks"
                                        :columnNames="project.columnNames || ['pending', 'completed']"
                                        :columnColors="project.columnColors || ['#f59e0b', '#10b981']" />
                                </div>
                                <div v-else>
                                    <ApexDonut 
                                        :key="`donut-empty-${chartRenderKey}`"
                                        :task="[0, 0]" 
                                        :columnNames="['pending', 'completed']"
                                        :columnColors="['#f59e0b', '#10b981']" />
                                </div>
                            </div>
                        </div>
                        <div class="dashboard-card">
                            <div class="card-header">
                                <b>Task Progress</b>
                            </div>
                            <div class="card-body">
                                <div v-if="project.progress !== undefined && project.progress !== null">
                                    <ApexRadialBar 
                                        :key="`radial-${chartRenderKey}-${project.id}`"
                                        :percent="project.progress" />
                                </div>
                                <div v-else>
                                    <ApexRadialBar 
                                        :key="`radial-empty-${chartRenderKey}`"
                                        :percent="0" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>