<script lang="ts" setup>
import { onMounted, ref, watch, onUnmounted } from 'vue';
import { ProjectType, useGetProject } from './actions/GetProject';
import ProjectCard from './components/ProjectCard.vue';
import { useRouter } from 'vue-router';
import { projectStore } from './store/projectStore';
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
import { useProjectRealtime } from '../../../helper/useProjectRealtime';
import { getCurrentUserId, isCurrentUser } from '../../../helper/getUserData';
import { createDebouncedFunction } from '../../../helper/utils';
import { useGlobalRealtimeSetup } from '../../../helper/useGlobalRealtimeSetup';

// Define component name for keep-alive
defineOptions({
    name: 'ProjectPage'
});

const { getPinnedProject: getPinnedProjectForCache, project: pinnedProjectForCache } = useGetPinnedProject();

const { getProjects, projectData } = useGetProject();
const isLoading = ref(true);
const tableLoading = ref(false);
const searchLoading = ref(false);
const router = useRouter();
const { pinnendProject } = usepinnendProject();
const { getPinnedProject, project: pinnedProject } = useGetPinnedProject();
const showProjectModal = ref(false);
const isEdit = ref(false);
const loading = ref(false);
const { createOrUpdate } = useCreateOrUpdateProject();
const query = ref("");

// Cache management
const projectCache = ref<Record<string, any>>({});

// Initialize cache from localStorage with error handling
try {
    const cachedData = localStorage.getItem('projectCache');
    if (cachedData && cachedData !== '0' && cachedData !== 'null') {
        const parsed = JSON.parse(cachedData);
        if (parsed && typeof parsed === 'object') {
            projectCache.value = parsed;
        }
    }
} catch (error) {
    projectCache.value = {};
}

// Initialize global real-time manager
const globalRealtime = useGlobalRealtimeSetup();



// Auto-save cache to localStorage
watch(projectCache, (val) => {
    localStorage.setItem('projectCache', JSON.stringify(val));
}, { deep: true });

// Get current user ID from localStorage
const userDataRaw = localStorage.getItem('userData');
const userData = userDataRaw ? JSON.parse(userDataRaw) : {};
const currentUserId = userData.id || (userData.user && userData.user.id) || null;

// Function để setup listeners cho từng project với global real-time manager
async function setupProjectListeners() {
    try {
        const userProjects = projectData.value?.data?.data || [];

        // Lấy danh sách projects đang được listen
        const activeProjects = globalRealtime.getActiveProjectListeners();

        // Chỉ setup cho projects chưa được listen
        const projectsToSetup = userProjects.filter(p => !activeProjects.includes(p.id));

        if (projectsToSetup.length > 0) {
            globalRealtime.setupProjectListeners(projectsToSetup.map(p => p.id));
        }
    } catch (error) {
        // Silent error handling
    }
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
            projectCache.value = {};
            localStorage.setItem('projectCache', '{}');
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
        projectCache.value = {};
        localStorage.setItem('projectCache', '{}');
        // Clear timestamps
        Object.keys(localStorage).forEach(key => {
            if (key.includes('project_page_') && key.includes('_timestamp')) {
                localStorage.removeItem(key);
            }
        });
        // Fetch fresh data
        await fetchProjects(1, query.value, false, true);
    }

    // Listen for force cache clear events
    eventBus.on('force-cache-clear', async (eventData: any) => {
        try {
            // Debug: Log current projects
            const currentProjectIds = projectData.value?.data?.data?.map(p => p.id) || [];

            // Chỉ refresh nếu event liên quan đến project trong danh sách hiện tại
            if (eventData?.projectId && currentProjectIds.includes(eventData.projectId)) {
                // Nếu là optimistic update từ current user, refresh ngay lập tức
                if (isCurrentUser(eventData.userId) && eventData.reason === 'task-status-changed-by-drag') {
                    // Clear cache hoàn toàn và refresh ngay lập tức
                    projectCache.value = {};
                    localStorage.setItem('projectCache', '{}');
                    // Clear tất cả timestamps
                    Object.keys(localStorage).forEach(key => {
                        if (key.includes('project_page_') && key.includes('_timestamp')) {
                            localStorage.removeItem(key);
                        }
                    });
                    await fetchProjects(1, query.value, false, true); // forceRefresh = true
                } else if (!isCurrentUser(eventData.userId)) {
                    // Chỉ clear cache cho project cụ thể, không clear toàn bộ
                    const cacheKey = `project_page_1_${query.value}`;
                    delete projectCache.value[cacheKey];
                    localStorage.removeItem(`${cacheKey}_timestamp`);
                    await fetchProjects(1, query.value, false, false); // Không force refresh
                }
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for project progress updated events
    eventBus.on('project-progress-updated', async (eventData: any) => {
        try {
            const currentProjectIds = projectData.value?.data?.data?.map(p => p.id) || [];

            // Chỉ refresh nếu event liên quan đến project trong danh sách hiện tại
            if (eventData?.projectId && currentProjectIds.includes(eventData.projectId)) {
                // Clear cache hoàn toàn và force refresh để đảm bảo data mới nhất
                projectCache.value = {};
                localStorage.setItem('projectCache', '{}');
                // Clear tất cả timestamps
                Object.keys(localStorage).forEach(key => {
                    if (key.includes('project_page_') && key.includes('_timestamp')) {
                        localStorage.removeItem(key);
                    }
                });
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
    const currentTime = Date.now();
    if (!forceRefresh) {
        // Tăng thời gian cache lên 30 giây để tối ưu cho navigation
        const cacheTimestamp = localStorage.getItem(`${cacheKey}_timestamp`);
        const cacheAge = cacheTimestamp ? currentTime - parseInt(cacheTimestamp) : Infinity;
        const isCacheValid = cacheAge < 30000; // 30 giây thay vì 5 giây

        // Kiểm tra cache
        if (projectCache.value[cacheKey] && isCacheValid) {
            projectData.value = projectCache.value[cacheKey];
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

        // Lưu vào cache
        projectCache.value[cacheKey] = projectData.value;
        localStorage.setItem(`${cacheKey}_timestamp`, currentTime.toString());

        // Update localStorage
        localStorage.setItem('projectCache', JSON.stringify(projectCache.value));

    } catch (e) {
        isLoading.value = false;
        throw e;
    }

    isLoading.value = false;
}

async function handlePinProject(projectId: number) {
    // Kiểm tra xem project đã được ghim chưa
    await getPinnedProject();

    // Nếu project đã được ghim rồi, chỉ chuyển qua dashboard (không clear cache)
    if (pinnedProject.value && pinnedProject.value.id === projectId) {
        router.push('/dashboard');
        return;
    }

    // Nếu project chưa được ghim, gọi API để ghim
    isLoading.value = true;
    await pinnendProject(projectId);
    isLoading.value = false;

    // Clear dashboard cache để đảm bảo dữ liệu mới được load
    const dashboardCache = localStorage.getItem('dashboardCache');
    if (dashboardCache) {
        try {
            const parsedCache = JSON.parse(dashboardCache);
            if (parsedCache && typeof parsedCache === 'object') {
                // Clear pinned project cache
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
    dashboardStore.setPinnedProject(pinnedProjectForCache.value); // Lưu vào cache

    // Set flag để Dashboard biết cần refresh khi activated
    localStorage.setItem('dashboard_needs_refresh', 'true');
    localStorage.setItem('dashboard_refresh_reason', 'project-pinned');
    localStorage.setItem('dashboard_pinned_project_id', projectId.toString());

    // Emit event (cho trường hợp Dashboard đã mounted)
    eventBus.emit('project-pinned', { projectId, project: pinnedProjectForCache.value });

    router.push('/dashboard');
}

async function handleDeleteProject(projectId: number) {
    const confirmed = await showConfirm('Are you sure you want to delete this project?');
    if (!confirmed) return;
    isLoading.value = true;
    try {
        await deleteProject(projectId);
        projectCache.value = {}; // Xóa toàn bộ cache project
        await fetchProjects(1, query.value, true);
    } catch (e: any) {
        alert(e?.message || 'Delete project failed!');
    }
    isLoading.value = false;
}

function openCreateProject() {
    projectStore.projectInput = { id: 0, name: '', startDate: '', endDate: '', content: '', members: [] };
    isEdit.value = false;
    showProjectModal.value = true;
}

function openEditProject(project: ProjectType) {
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
    projectCache.value = {}; // Xóa toàn bộ cache project
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
    const cacheTimestamp = localStorage.getItem(`${cacheKey}_timestamp`);
    const currentTime = Date.now();
    const cacheAge = cacheTimestamp ? currentTime - parseInt(cacheTimestamp) : Infinity;
    const isCacheValid = cacheAge < 30000; // 30 giây thay vì 5 giây

    // Chỉ fetch nếu không có cache hoặc cache đã hết hạn
    if (!projectCache.value[cacheKey] || !isCacheValid) {
        await fetchProjects();
    } else {
        // Sử dụng cache data
        projectData.value = projectCache.value[cacheKey];
        isLoading.value = false;
    }

    // Setup listeners cho từng project sau khi đã load projects
    await setupProjectListeners();

    projectStore.edit = false;
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
        <!-- Project Card Grid -->
        <div v-if="!isLoading" class="project-card-grid">
            <template v-if="projectData?.data?.data && projectData.data.data.length > 0">
                <ProjectCard v-for="project in projectData.data.data" :key="project.id" :project="project"
                    :currentUserId="currentUserId" 
                    :isPinned="pinnedProject?.id === project.id"
                    @editProject="openEditProject" @deleteProject="handleDeleteProject"
                    @pinnedProject="handlePinProject"
                    @viewProjectDetail="(id) => $router.push('/kaban?query=' + project.slug)" />
            </template>
            <div v-else class="no-data-center">No data</div>
        </div>
        <div class="d-flex justify-content-center">
            <CustomPagination v-if="projectData?.data" :data="projectData.data" :loading="tableLoading"
                @pagination-change-page="fetchProjects" />
        </div>
        <ProjectModal v-if="showProjectModal" :isEdit="isEdit" :projectInput="projectStore.projectInput"
            :loading="loading" @close="showProjectModal = false" @submit="handleSubmitProject" />
        <template #fab>
            <FabButton @click="openCreateProject">
                <i class="bi bi-kanban-fill" style="font-size: 1.8rem; color: white;"></i>
            </FabButton>
        </template>
    </MainCardLayout>
</template>

<style scoped>
.project-card-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1.2rem;
    margin: 0 auto 0 auto;
    max-width: 1100px;
    padding: 0 0.5rem;
    min-height: 220px;
    /* Ensure enough height for centering 'No data' */
}

@media (min-width: 600px) {
    .project-card-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 992px) {
    .project-card-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.no-data-center {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 180px;
    width: 100%;
    font-size: 1.15rem;
    color: #a0aec0;
    font-weight: 500;
    grid-column: 1 / -1;
    /* Span all columns */
    text-align: center;
}
</style>