<script lang="ts" setup>
import { onMounted } from 'vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useGetProject } from './actions/GetProject';
import ProjectTable from './components/ProjectTable.vue';


const { getProjects, projectData, loading } = useGetProject()

async function showListOfMembers() {
    await getProjects()
}

// function editMember(member: MemberType) {
//     memberStore.memberInput = member
//     memberStore.edit = true
//     router.push('/create-members')
// }

onMounted(async () => {
    showListOfMembers()
    // memberStore.edit = false
    // memberStore.memberInput = {} as MemberInputType
})

</script>
<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Project
                        <RouterLink style="float:right" to="/create-projects" class="btn btn-primary">Create Project
                        </RouterLink>
                    </div>
                    <div class="card-body">
                        <ProjectTable @getProject="getProjects" :loading="loading" :projects="projectData">
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