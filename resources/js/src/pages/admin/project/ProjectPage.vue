<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import { ProjectType, useGetProject } from './actions/GetProject';
import ProjectTable from './components/ProjectTable.vue';
import { useRouter } from 'vue-router';
import { projectStore } from './store/projectStore';
import { ProjectInputType } from './actions/createtProject';
import { usepinnendProject } from './actions/pinnendProject';
import LoadingPage from '../../../components/LoadingPage.vue';
import CustomPagination from '../../../components/CustomPagination.vue';
import MainCardLayout from '../../../components/MainCardLayout.vue';

const { getProjects, projectData } = useGetProject();
const isLoading = ref(true);
const tableLoading = ref(false);
const router = useRouter();
const { pinnendProject } = usepinnendProject();

async function fetchProjects(page = 1, query = "", showLoadingPage = true) {
    if (showLoadingPage) {
        isLoading.value = true;
        await getProjects(page, query);
        isLoading.value = false;
    } else {
        tableLoading.value = true;
        await getProjects(page, query);
        tableLoading.value = false;
    }
}

function handleEditProject(project: ProjectType) {
    projectStore.projectInput = {
        id: project.id,
        name: project.name,
        startDate: project.startDate,
        endDate: project.endDate
    };
    projectStore.edit = true;
    router.push('/create-project');
}

async function handlePinProject(projectId: number) {
    isLoading.value = true;
    await pinnendProject(projectId);
    isLoading.value = false;
    router.push('/admin');
}

onMounted(async () => {
    await fetchProjects();
    projectStore.edit = false;
    projectStore.projectInput = {} as ProjectInputType;
});
</script>

<template>
    <MainCardLayout title="Project Management" iconClass="bi bi-kanban-fill"
        containerStyle="padding:1rem 0 1rem 0; position:relative;">
        <template #action>
            <RouterLink to="/create-project" class="btn btn-primary create-btn">
                <i class="bi bi-plus-circle me-1"></i> Create Project
            </RouterLink>
        </template>
        <LoadingPage v-if="isLoading" />
        <ProjectTable @getProject="fetchProjects" :loading="tableLoading" @editProject="handleEditProject"
            :projects="projectData" @pinnedProject="handlePinProject">
            <template #pagination>
                <CustomPagination v-if="projectData?.data" :data="projectData.data" :loading="tableLoading"
                    @pagination-change-page="fetchProjects" />
            </template>
        </ProjectTable>
        <template #fab>
            <RouterLink to="/create-project" class="fab-add-project d-md-none">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="13" y="6" width="2" height="16" rx="1" fill="white" />
                    <rect x="6" y="13" width="16" height="2" rx="1" fill="white" />
                </svg>
            </RouterLink>
        </template>
    </MainCardLayout>
</template>

<style scoped>
.fab-add-project {
    position: fixed;
    top: auto;
    left: auto;
    right: 1rem;
    bottom: 4.5rem;
    z-index: 1002;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #fff;
    background: linear-gradient(135deg, #2563eb 60%, #60a5fa 100%);
    width: 3.2rem;
    height: 3.2rem;
    border-radius: 50%;
    box-shadow: 0 4px 18px rgba(34, 34, 59, 0.18);
    transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
    border: none;
    outline: none;
    cursor: pointer;
    padding: 0;
    margin: 0;
    text-align: center;
    text-decoration: none;
}

.fab-add-project:focus {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

.fab-add-project:hover {
    background: linear-gradient(135deg, #1d4ed8 60%, #2563eb 100%);
    box-shadow: 0 8px 24px rgba(34, 34, 59, 0.22);
    transform: scale(1.09);
    color: #fff;
    text-decoration: none;
}

.fab-add-project svg {
    display: block;
    margin: 0 auto;
}

@media (min-width: 769px) {
    .fab-add-project {
        display: none !important;
    }
}
</style>