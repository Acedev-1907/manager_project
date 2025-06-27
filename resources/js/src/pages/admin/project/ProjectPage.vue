<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { ProjectType, useGetProject } from './actions/GetProject';
import ProjectTable from './components/ProjectTable.vue';
import { useRouter } from 'vue-router';
import { projectStore } from './store/projectStore';
import { ProjectInputType } from './actions/createtProject';
import { usepinnendProject } from './actions/pinnendProject';
import LoadingPage from '../../../components/LoadingPage.vue';


const { getProjects, projectData, loading } = useGetProject()

const isLoading = ref(true);

async function showListOfMembers() {
    await getProjects()
}

const router = useRouter()
function editProject(project: ProjectType) {
    projectStore.projectInput = {
        id: project.id,
        name: project.name,
        startDate: project.startDate,
        endDate: project.endDate
    }
    projectStore.edit = true
    router.push('/create-project')
}

const { pinnendProject } = usepinnendProject()

async function pinnedProjectOnDashboard(projectId: number) {
    await pinnendProject(projectId)
    router.push('/admin')
}

onMounted(async () => {
    isLoading.value = true;
    await showListOfMembers();
    projectStore.edit = false;
    projectStore.projectInput = {} as ProjectInputType;
    isLoading.value = false;
})

</script>
<template>
    <div class="container">
        <LoadingPage v-if="isLoading" />
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Project
                        <RouterLink style="float:right" to="/create-project" class="btn btn-primary">Create Project
                        </RouterLink>
                    </div>
                    <div class="card-body">
                        <ProjectTable @getProject="getProjects" :loading="loading" @editProject="editProject"
                            :projects="projectData" @pinnedProject="pinnedProjectOnDashboard">
                            <template #pagination>
                                <Bootstrap5Pagination v-if="projectData?.data" :data="projectData.data"
                                    @pagination-change-page="getProjects" />
                            </template>
                        </ProjectTable>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>