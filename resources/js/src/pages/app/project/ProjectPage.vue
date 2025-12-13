<script lang="ts" setup>
import { onMounted, ref, watch, onUnmounted, computed } from 'vue';
import { ProjectType, useGetProject } from './actions/GetProject';
import ProjectCard from './components/ProjectCard.vue';
import { useProjectStore } from './store/projectStore';
import { ProjectInputType, useCreateOrUpdateProject } from './actions/createtProject';
import { usepinnendProject } from './actions/pinnendProject';
import LoadingPage from '../../../components/LoadingPage.vue';
import CustomPagination from '../../../components/CustomPagination.vue';
import MainCardLayout from '../../../components/MainCardLayout.vue';
import ProjectModal from './components/ProjectModal.vue';
import FabButton from '../../../components/FabButton.vue';
import { deleteProject } from './actions/deleteProject';
import { showConfirm } from '../../../helper/alert';
import SearchInput from '../../../components/SearchInput.vue';
import { useDashboardStore } from '../dashboard/store/dashboardStore';
import { useGetPinnedProject } from '../dashboard/actions/GetPinnedProject';
import eventBus, { replayRecentEvents, getRecentEvents } from '../../../helper/eventBus';
import ApexDonut from '../dashboard/components/ApexDonut.vue';
import { useStorage } from '../../../composables/useStorage';
import { CachePresets } from '../../../composables/useCacheManager';

// Define component name for keep-alive
defineOptions({
    name: 'ProjectPage'
});

const { getPinnedProject: getPinnedProjectForCache, project: pinnedProjectForCache } = useGetPinnedProject();
const projectStore = useProjectStore();

const { getProjects, projectData } = useGetProject();
const isLoading = ref(true);

// Computed property for template to avoid TypeScript errors
const projectInput = computed(() => {
    // @ts-expect-error - Pinia store type inference issue
    return projectStore.projectInput;
});
const tableLoading = ref(false);
const searchLoading = ref(false);
const { pinnendProject } = usepinnendProject();
const { getPinnedProject, project: pinnedProject } = useGetPinnedProject();
const showProjectModal = ref(false);
const isEdit = ref(false);
const loading = ref(false);
const { createOrUpdate } = useCreateOrUpdateProject();
const query = ref("");
const totalProjects = computed(() => {
    const list = projectData.value?.data?.data;
    return Array.isArray(list) ? list.length : 0;
});

// Cache management - Sử dụng memory storage (tự động clear khi reload)
const projectCache = useStorage<Record<string, any>>('projectCache', {}, {
  ...CachePresets.projectList, // memory, 10 minutes
});

// Get current user ID from user-store (đã được persist)
import { useUserStore } from '../../../state/userStore';
const userStore = useUserStore();
// @ts-expect-error - Pinia store type inference issue
const userId = userStore.user?.id;
const currentUserId: number | null = userId ? Number(userId) : null;

// Function stub: listeners đã được quản lý realtime qua eventBus/Echo
async function setupProjectListeners() {
    return;
}

// Page visibility listener để refresh khi user quay lại tab
let visibilityTimeout: any = null;
const handleVisibilityChange = async () => {
    if (!document.hidden) {
        // Debounce để tránh gọi nhiều lần
        if (visibilityTimeout) {
            clearTimeout(visibilityTimeout);
        }

        visibilityTimeout = setTimeout(async () => {
            // Refresh data ngay lập tức khi user quay lại tab - không hiển thị loading
            projectCache.value.value = {}; // useStorage tự động save
            await fetchProjects(1, query.value, false); // false = không hiển thị loading
        }, 100); // Debounce 100ms
    }
};

// Lắng nghe eventBus để reload project khi có event real-time
onMounted(async () => {
    // Fetch projects data (sử dụng cache bình thường)
    await fetchProjects(1, query.value, true);

    // Setup global project listeners
    setupProjectListeners();

    // Replay recent events that might have been missed
    const currentProjectIds = projectData.value?.data?.data?.map(p => p.id) || [];

    // Replay recent force-cache-clear events for current projects
    currentProjectIds.forEach(projectId => {
        replayRecentEvents('force-cache-clear', projectId);
    });

    // Smart cache strategy: Chỉ clear cache nếu có recent events
    const recentEvents = getRecentEvents('force-cache-clear');
    if (recentEvents.length > 0) {
        projectCache.value.value = {}; // useStorage tự động save
        // Timestamps đã được quản lý bởi useStorage với TTL
        // Fetch fresh data
        await fetchProjects(1, query.value, false, true);
    }

    // Listen for force cache clear events (realtime, includes drag/drop)
    eventBus.on('force-cache-clear', async (eventData: any) => {
        try {
            const currentProjectIds = projectData.value?.data?.data?.map(p => p.id) || [];
            if (!eventData?.projectId || !currentProjectIds.includes(eventData.projectId)) return;

            const isTaskStatusChange = String(eventData.reason || '').includes('task-status-changed');
            if (!isTaskStatusChange) return;

            // Refresh pinned project immediately (no localStorage)
            await getPinnedProject();

            // Nếu cần, refresh danh sách project hiện tại (không dùng localStorage cache)
            await fetchProjects(1, query.value, false, true);
        } catch (error) {
            // Silent
        }
    });

    // Listen realtime events (Echo) to update pinned project for all members
    eventBus.on('project-realtime-event', async (payload: any) => {
        try {
            const { type, data } = payload || {};
            if (type !== 'task-status-changed') return;

            const currentProjectIds = projectData.value?.data?.data?.map(p => p.id) || [];
            if (!data?.projectId || !currentProjectIds.includes(data.projectId)) return;

            await getPinnedProject();
        } catch (_) {
            // Silent
        }
    });

    // Listen for project progress updated events
    eventBus.on('project-progress-updated', async (eventData: any) => {
        try {
            const currentProjectIds = projectData.value?.data?.data?.map(p => p.id) || [];

            // Chỉ refresh nếu event liên quan đến project trong danh sách hiện tại
            if (eventData?.projectId && currentProjectIds.includes(eventData.projectId)) {
                // Clear cache hoàn toàn và force refresh để đảm bảo data mới nhất
                projectCache.value.value = {}; // useStorage tự động save
                // Timestamps đã được quản lý bởi useStorage với TTL
                await fetchProjects(1, query.value, false, true); // forceRefresh = true
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for page visibility changes
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

async function fetchProjects(page = 1, queryStr = "", showLoadingPage = true, forceRefresh = false) {
    const cacheKey = `project_page_${page}_${queryStr}`;

    // Nếu forceRefresh = true, bỏ qua cache hoàn toàn
    if (!forceRefresh) {
        // Kiểm tra cache - useStorage tự động quản lý TTL (10 minutes)
        if (projectCache.value.value[cacheKey] && !projectCache.isExpired()) {
            projectData.value = projectCache.value.value[cacheKey];
            isLoading.value = false;
            tableLoading.value = false;
            return;
        }
    }

    if (showLoadingPage) {
        isLoading.value = true;
    }
    // Không set tableLoading khi refresh/clear cache để tránh hiển thị loading trên search input

    try {
        // Fetch data trực tiếp
        await getProjects(page, queryStr);

        // Lưu vào cache (useStorage tự động save)
        projectCache.value.value[cacheKey] = projectData.value;

    } catch (e) {
        isLoading.value = false;
        throw e;
    }

    isLoading.value = false;
}

async function handlePinProject(projectId: number) {
    try {
    // Kiểm tra xem project đã được ghim chưa
    await getPinnedProject();

        // Nếu project đã được ghim rồi, chỉ refresh lại pinned project data
    if (pinnedProject.value && pinnedProject.value.id === projectId) {
            // Refresh pinned project để cập nhật UI
            await getPinnedProject();
        return;
    }

    // Nếu project chưa được ghim, gọi API để ghim
    isLoading.value = true;
    await pinnendProject(projectId);
        
        // Refresh pinned project data
        await getPinnedProject();

    // Clear dashboard cache để đảm bảo dữ liệu mới được load
    const dashboardCache = localStorage.getItem('dashboardCache');
    if (dashboardCache) {
        try {
            const parsedCache = JSON.parse(dashboardCache);
            if (parsedCache && typeof parsedCache === 'object') {
                // Clear pinned project cache only, preserve count project cache
                delete parsedCache['pinned_project'];
                localStorage.setItem('dashboardCache', JSON.stringify(parsedCache));
                localStorage.removeItem('pinned_project_timestamp');
            }
        } catch (error) {
            // Nếu có lỗi parse, clear toàn bộ dashboard cache
            localStorage.setItem('dashboardCache', '{}');
            localStorage.removeItem('pinned_project_timestamp');
        }
    }

    // Lưu dữ liệu project mới vào cache
    const dashboardStore = useDashboardStore();
    await getPinnedProjectForCache(); // Lấy dữ liệu project mới
    // @ts-expect-error - Pinia store type inference issue
    dashboardStore.setPinnedProject(pinnedProjectForCache.value); // Lưu vào cache

    // Set flag để Dashboard biết cần refresh khi activated (sessionStorage - chỉ trong session)
    sessionStorage.setItem('dashboard_needs_refresh', 'true');
    sessionStorage.setItem('dashboard_refresh_reason', 'project-pinned');
    sessionStorage.setItem('dashboard_pinned_project_id', projectId.toString());

    // Emit event (cho trường hợp Dashboard đã mounted)
    eventBus.emit('project-pinned', { projectId, project: pinnedProjectForCache.value });
    } catch (error) {
        console.error('Error pinning project:', error);
    } finally {
        isLoading.value = false;
    }
}

async function handleDeleteProject(projectId: number) {
    const confirmed = await showConfirm('Are you sure you want to delete this project?');
    if (!confirmed) return;
    isLoading.value = true;
    try {
        await deleteProject(projectId);
        projectCache.value.value = {}; // Xóa toàn bộ cache project
        await fetchProjects(1, query.value, true);
    } catch (e: any) {
        alert(e?.message || 'Delete project failed!');
    }
    isLoading.value = false;
}

function openCreateProject() {
    // @ts-expect-error - Pinia store type inference issue
    projectStore.projectInput = { id: 0, name: '', startDate: '', endDate: '', content: '', members: [] };
    isEdit.value = false;
    showProjectModal.value = true;
}

function openEditProject(project: ProjectType) {
    // @ts-expect-error - Pinia store type inference issue
    projectStore.projectInput = {
        ...project,
        startDate: project.startDate || '',
        endDate: project.endDate || '',
        content: project.content || '',
        members: (project.users || []).map(u => u.id)
    };
    isEdit.value = true;
    showProjectModal.value = true;
}

async function handleSubmitProject(data: ProjectInputType) {
    // Prevent spam clicking
    if (loading.value) return;

    loading.value = true;
    // @ts-expect-error - Pinia store type inference issue
    projectStore.projectInput = {
        ...data,
        startDate: data.startDate || '',
        endDate: data.endDate || '',
        content: data.content || '',
        members: data.members ?? [],
    };
    await createOrUpdate();
    loading.value = false;
    showProjectModal.value = false;
    projectCache.value.value = {}; // Xóa toàn bộ cache project
    await fetchProjects(1, query.value, false); // false = không hiển thị loading page
}

const handleSearch = async (searchQuery: string) => {
    query.value = searchQuery;
    searchLoading.value = true;
    try {
        await fetchProjects(1, searchQuery, false); // false = không hiển thị loading
    } finally {
        searchLoading.value = false;
    }
};

onMounted(async () => {
    // Kiểm tra cache trước khi fetch data
    const cacheKey = `project_page_1_`;

    // Chỉ fetch nếu không có cache hoặc cache đã hết hạn (useStorage tự động quản lý TTL)
    if (!projectCache.value.value[cacheKey] || projectCache.isExpired()) {
        await fetchProjects();
    } else {
        // Sử dụng cache data
        projectData.value = projectCache.value.value[cacheKey];
        isLoading.value = false;
    }

    // Load pinned project for summary widgets
    try {
        await getPinnedProject();
    } catch (error) {
        // Silent error handling
    }

// Handle pending refresh flags from drag-and-drop updates (set in dragTask.ts)
const checkPendingRefreshFlags = async () => {
    try {
        const needsRefresh = sessionStorage.getItem('dashboard_needs_refresh');
        const reason = sessionStorage.getItem('dashboard_refresh_reason') || '';

        if (needsRefresh === 'true' && reason.includes('task-status-changed')) {
            // Dashboard cache đã được quản lý bởi DashboardPage với memory storage
            // Chỉ cần refresh pinned project data
            await getPinnedProject();
        }
    } catch (_) {
        // Silent
    } finally {
        // Clear flags
        sessionStorage.removeItem('dashboard_needs_refresh');
        sessionStorage.removeItem('dashboard_refresh_reason');
        sessionStorage.removeItem('dashboard_pinned_project_id');
    }
};

    // Handle pending refresh flags (from drag/drop)
    await checkPendingRefreshFlags();

    // Setup listeners cho từng project sau khi đã load projects
    await setupProjectListeners();

    // @ts-expect-error - Pinia store type inference issue
    projectStore.edit = false;
    // @ts-expect-error - Pinia store type inference issue
    projectStore.projectInput = { id: 0, name: '', startDate: '', endDate: '', content: '', members: [] };

    // Watch for project data changes and re-setup listeners if needed
    watch(() => projectData.value?.data?.data, async (newProjects, oldProjects) => {
        if (newProjects && newProjects.length > 0) {
            // Chỉ re-setup nếu danh sách project thực sự thay đổi
            const newProjectIds = newProjects.map(p => p.id).sort().join(',');
            const oldProjectIds = oldProjects?.map(p => p.id).sort().join(',') || '';

            if (newProjectIds !== oldProjectIds) {
                await setupProjectListeners();
            }
        }
    }, { deep: true });
});

// Cleanup event listeners when component is unmounted
onUnmounted(() => {
    // Remove eventBus listeners
    eventBus.off('force-cache-clear');
    eventBus.off('project-realtime-event');
    eventBus.off('project-progress-updated');

    // Remove page visibility listener
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <MainCardLayout title="Project Management" iconClass="bi bi-kanban-fill"
        containerStyle="padding:1rem 0 1rem 0; position:relative;">
        <template #action>
            <div class="main-action-bar">
                <SearchInput v-model="query" placeholder="Search project..." :loading="searchLoading"
                    @search="handleSearch" />
                <button class="btn btn-primary create-btn d-none d-md-block" @click="openCreateProject">
                    <i class="bi bi-plus-circle me-1"></i> Create Project
                </button>
            </div>
        </template>
        <LoadingPage v-if="isLoading" />
        <div v-if="!isLoading" class="project-page-content">
            <!-- Summary section (moved from Dashboard) -->
            <div class="info-bar">
                <div class="info-chip">
                    <div class="chip-label">
                        <i class="bi bi-folder2-open"></i>
                        <span>Total Projects</span>
                    </div>
                    <div class="chip-value">{{ totalProjects }}</div>
                </div>

                <div class="info-chip" v-if="pinnedProject && pinnedProject.id">
                    <div class="chip-label">
                        <i class="bi bi-pie-chart-fill"></i>
                        <span>Tasks</span>
                        <span class="chip-pill">
                            <i class="bi bi-pin-fill"></i> {{ pinnedProject.name }}
                        </span>
                    </div>
                    <div class="chip-sub">{{ pinnedProject.tasks?.reduce((a,b)=>a+b,0) || 0 }} tasks</div>
                </div>
            </div>

            <div class="chart-row" v-if="pinnedProject && pinnedProject.id">
                <div class="chart-card">
                    <div class="chart-title">Tasks Distribution</div>
                    <div class="chart-body">
                        <ApexDonut
                            :task="pinnedProject.tasks || [0, 0]"
                            :columnNames="pinnedProject.columnNames || ['Pending', 'Completed']"
                            :columnColors="pinnedProject.columnColors || ['#f59e0b', '#10b981']" />
                    </div>
                </div>
            </div>
            <div v-else class="chart-empty">Pin một project để xem biểu đồ.</div>

        <!-- Project Card Grid -->
            <div class="projects-section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="bi bi-kanban"></i>
                        All Projects
                    </h3>
                    <span class="project-count-badge">{{ totalProjects }} projects</span>
                </div>
                
                <div class="project-card-grid">
            <template v-if="projectData?.data?.data && projectData.data.data.length > 0">
                        <ProjectCard 
                            v-for="project in projectData.data.data" 
                            :key="project.id" 
                            :project="project"
                    :currentUserId="currentUserId" 
                    :isPinned="pinnedProject?.id === project.id"
                            @editProject="openEditProject" 
                            @deleteProject="handleDeleteProject"
                    @pinnedProject="handlePinProject"
                    @viewProjectDetail="(id) => $router.push('/kaban?query=' + project.slug)" />
            </template>
                    <div v-else class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-folder-x"></i>
                        </div>
                        <h3 class="empty-state-title">No Projects Found</h3>
                        <p class="empty-state-message">Get started by creating your first project!</p>
                        <button class="btn btn-primary empty-state-button" @click="openCreateProject">
                            <i class="bi bi-plus-circle me-2"></i>
                            Create Project
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center">
            <CustomPagination v-if="projectData?.data" :data="projectData.data" :loading="tableLoading"
                @pagination-change-page="fetchProjects" />
        </div>
        <ProjectModal v-if="showProjectModal" :isEdit="isEdit" :projectInput="projectInput"
            :loading="loading" @close="showProjectModal = false" @submit="handleSubmitProject" />
        <template #fab>
            <FabButton @click="openCreateProject">
                <i class="bi bi-kanban-fill" style="font-size: 1.8rem; color: white;"></i>
            </FabButton>
        </template>
    </MainCardLayout>
</template>

<style scoped>
.project-page-content {
    padding: 0.5rem 0;
}

/* Summary Section */
.info-bar {
    margin: 0.75rem auto 1rem auto;
    padding: 0.65rem 0.75rem;
    max-width: 1220px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 0.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f5f7fb 100%);
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
}

.info-chip {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    padding: 0.6rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    transition: none;
}

.info-chip:hover {
    box-shadow: none;
    transform: none;
}
.chip-label {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 700;
    color: #0b132a;
    font-size: 0.95rem;
}

.chip-label i {
    color: #3b82f6;
    font-size: 1rem;
}

.chip-value {
    font-weight: 800;
    color: #0b132a;
    font-size: 1.8rem;
    line-height: 1.05;
}

.chip-sub {
    font-size: 0.9rem;
    font-weight: 600;
    color: #64748b;
}

.chip-pill {
    padding: 0.12rem 0.45rem;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 700;
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #7dd3fc;
}

.chart-row {
    max-width: 1220px;
    margin: 0.25rem auto 1rem auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 0.6rem;
}

.chart-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.7rem;
    padding: 0.7rem 0.8rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-height: 180px;
}

.chart-title {
    font-weight: 700;
    color: #0f172a;
    font-size: 0.95rem;
}

.chart-body {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 140px;
}

.chart-foot {
    font-size: 0.88rem;
    color: #6b7280;
    font-weight: 600;
}

.chart-empty {
    max-width: 1220px;
    margin: 0.25rem auto 1rem auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px dashed #d1d5db;
    border-radius: 0.7rem;
    color: #6b7280;
    font-weight: 600;
    padding: 1rem;
}

/* Projects Section */
.projects-section {
    margin-top: 1.25rem;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding: 0 0.5rem;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.section-title i {
    color: #6366f1;
    font-size: 1.4rem;
}

.project-count-badge {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 1.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.project-card-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1rem;
    margin: 0 auto;
    max-width: 1200px;
    padding: 0 0.35rem 0.8rem 0.35rem;
    min-height: 240px;
}

@media (min-width: 640px) {
    .project-card-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .project-card-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 960px) {
    .info-bar {
        grid-template-columns: 1fr;
    }
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    min-height: 400px;
}

.empty-state-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    animation: float 3s ease-in-out infinite;
}

.empty-state-icon i {
    font-size: 4rem;
    color: #6366f1;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.empty-state-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.75rem;
}

.empty-state-message {
    font-size: 1rem;
    color: #64748b;
    margin-bottom: 2rem;
    max-width: 400px;
}

.empty-state-button {
    padding: 0.75rem 2rem;
    border-radius: 0.75rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    transition: all 0.3s ease;
}

.empty-state-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .summary-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .summary-card {
        padding: 1.25rem;
    }
    
    .summary-number {
        font-size: 2.5rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .section-title {
        font-size: 1.25rem;
    }
    
    .empty-state {
        padding: 3rem 1rem;
        min-height: 300px;
    }
    
    .empty-state-icon {
        width: 100px;
        height: 100px;
    }
    
    .empty-state-icon i {
        font-size: 3rem;
    }
    
    .empty-state-title {
        font-size: 1.5rem;
    }
}
</style>