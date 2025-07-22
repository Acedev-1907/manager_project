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
import { useCacheFetch } from '../../../helper/useCacheFetch';

const { getProjects, projectData } = useGetProject();
const isLoading = ref(true);
const tableLoading = ref(false);
const router = useRouter();
const { pinnendProject } = usepinnendProject();
const showProjectModal = ref(false);
const isEdit = ref(false);
const loading = ref(false);
const { createOrUpdate } = useCreateOrUpdateProject();
const query = ref("");
const projectCache = ref<Record<string, any>>(JSON.parse(localStorage.getItem('projectCache') || '{}'));
const userId = ref(null);
let joinedUserChannel: string | number | null = null;

watch(projectCache, (val) => {
    localStorage.setItem('projectCache', JSON.stringify(val));
}, { deep: true });

// Get current user ID from localStorage
const userDataRaw = localStorage.getItem('userData');
const userData = userDataRaw ? JSON.parse(userDataRaw) : {};
const currentUserId = userData.id || (userData.user && userData.user.id) || null;

// Add a function to setup Echo listener
function setupEchoListener(userIdVal: string | number | null) {
    if (!userIdVal) return;
    if (joinedUserChannel === userIdVal) return; // Đã join rồi, không join lại
    joinedUserChannel = userIdVal;
    if (!window.Echo) {
        setTimeout(() => setupEchoListener(userIdVal), 200);
        return;
    }
    try {
        window.Echo.private(`user.${userIdVal}`)
            .listen('NewProjectForMembers', async (e: any) => {
                projectCache.value = {};
                await refetch('project_page_1_' + query.value, async () => {
                    await getProjects(1, query.value);
                    return projectData.value;
                }, (data) => {
                    projectData.value = data;
                });
            })
            .listen('UserRemovedFromProject', async (e: any) => {
                projectCache.value = {}; // Xóa cache khi nhận event (giữ reference)
                await refetch('project_page_1_' + query.value, async () => {
                    await getProjects(1, query.value);
                    return projectData.value;
                }, (data) => {
                    projectData.value = data;
                });
            });
    } catch (error) {
        console.error('Error setting up Echo listener:', error);
    }
}

watch(userId, (newId, oldId) => {
    if (window.Echo && oldId) {
        window.Echo.leave(`user.${oldId}`);
        joinedUserChannel = null; // Reset flag khi userId đổi
    }
    if (newId) {
        setupEchoListener(newId);
    }
});

const { getOrFetch, refetch } = useCacheFetch(
    projectCache.value,
    (key, data) => { projectCache.value[key] = data; },
    (key) => { if (key) delete projectCache.value[key]; else projectCache.value = {}; }
);

async function fetchProjects(page = 1, queryStr = "", showLoadingPage = true) {
    const cacheKey = `project_page_${page}_${queryStr}`;
    if (projectCache.value[cacheKey]) {
        projectData.value = projectCache.value[cacheKey];
        isLoading.value = false;
        tableLoading.value = false;
        return;
    }
    if (showLoadingPage) isLoading.value = true;
    else tableLoading.value = true;
    try {
        await getOrFetch(cacheKey, async () => {
            await getProjects(page, queryStr);
            return projectData.value;
        }, (data) => {
            projectData.value = data;
        });
    } catch (e) {
        isLoading.value = false;
        tableLoading.value = false;
        throw e;
    }
    isLoading.value = false;
    tableLoading.value = false;
}

async function handlePinProject(projectId: number) {
    isLoading.value = true;
    await pinnendProject(projectId);
    isLoading.value = false;
    router.push('/dashboard');
}

async function handleDeleteProject(projectId: number) {
    const confirmed = await showConfirm('Are you sure you want to delete this project?');
    if (!confirmed) return;
    isLoading.value = true;
    try {
        await deleteProject(projectId);
        projectCache.value = {}; // Xóa toàn bộ cache project
        await refetch(`project_page_1_${query.value}`, async () => {
            await getProjects(1, query.value);
            return projectData.value;
        }, (data) => {
            projectData.value = data;
        });
    } catch (e: any) {
        alert(e?.message || 'Delete project failed!');
    }
    isLoading.value = false;
}

function openCreateProject() {
    projectStore.projectInput = { id: 0, name: '', startDate: '', endDate: '', members: [] };
    isEdit.value = false;
    showProjectModal.value = true;
}

function openEditProject(project: ProjectType) {
    projectStore.projectInput = {
        ...project,
        startDate: project.startDate || '',
        endDate: project.endDate || '',
        members: (project.users || []).map(u => u.id)
    };
    isEdit.value = true;
    showProjectModal.value = true;
}

async function handleSubmitProject(data: ProjectInputType) {
    loading.value = true;
    projectStore.projectInput = {
        ...data,
        startDate: data.startDate || '',
        endDate: data.endDate || '',
        members: data.members ?? [],
    };
    await createOrUpdate();
    loading.value = false;
    showProjectModal.value = false;
    projectCache.value = {}; // Xóa toàn bộ cache project
    await refetch(`project_page_1_${query.value}`, async () => {
        await getProjects(1, query.value);
        return projectData.value;
    }, (data) => {
        projectData.value = data;
    });
    setupEchoListener(userId.value);
}

const handleSearch = async (searchQuery: string) => {
    query.value = searchQuery;
    await fetchProjects(1, searchQuery, false);
};

onMounted(async () => {
    // Lấy userId từ localStorage hoặc API
    const userDataRaw = localStorage.getItem('userData');
    const userData = userDataRaw ? JSON.parse(userDataRaw) : {};
    userId.value = userData.id || (userData.user && userData.user.id) || null;
    await fetchProjects();
    projectStore.edit = false;
    projectStore.projectInput = { id: 0, name: '', startDate: '', endDate: '', members: [] };
    watch(() => projectData.value, (val) => {
        if (val && val.current_page) {
            localStorage.setItem(
                `project_page_${val.current_page}`,
                JSON.stringify(val)
            );
        }
    }, { immediate: true, deep: true });
});

// Thêm leave kênh khi component bị unmount để tránh nhận event trùng
onUnmounted(() => {
    if (window.Echo && userId.value) {
        window.Echo.leave(`user.${userId.value}`);
        joinedUserChannel = null; // Reset flag khi unmount
    }
});
</script>

<template>
    <MainCardLayout title="Project Management" iconClass="bi bi-kanban-fill"
        containerStyle="padding:1rem 0 1rem 0; position:relative;">
        <template #action>
            <div class="main-action-bar">
                <SearchInput v-model="query" placeholder="Search project..." :loading="tableLoading"
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
                    :currentUserId="currentUserId" @editProject="openEditProject" @deleteProject="handleDeleteProject"
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