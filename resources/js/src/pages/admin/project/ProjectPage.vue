<script lang="ts" setup>
import { onMounted } from 'vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { ProjectType, useGetProject } from './actions/GetProject';
import ProjectTable from './components/ProjectTable.vue';
import { useRouter } from 'vue-router';
import { projectStore } from './store/projectStore';
import { ProjectInputType } from './actions/createtProject';
import { usepinnendProject } from './actions/pinnendProject';


const { getProjects, projectData, loading } = useGetProject()

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

async function pinnedProjectOnDashborad(projectId: number) {
    await pinnendProject(projectId)
    router.push('/admin')
}

onMounted(async () => {
    showListOfMembers()
    projectStore.edit = false
    projectStore.projectInput = {} as ProjectInputType
})

</script>
<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Project
                        <RouterLink style="float:right" to="/create-project" class="btn btn-primary">Create Project
                        </RouterLink>
                    </div>
                    <div class="card-body">
                        <ProjectTable @getProject="getProjects" :loading="loading" @editProject="editProject"
                            :projects="projectData" @pinnedProject="pinnedProjectOnDashborad">
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