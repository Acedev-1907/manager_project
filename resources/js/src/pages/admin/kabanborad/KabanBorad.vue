<script setup lang="ts">
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';
import BreadCrumb from './components/BreadCrumb.vue';
import { useGetProjectDetail } from './actions/getProjectDetail';
import ProjectDetail from './components/ProjectData.vue';
import ProjectProgress from './components/ProjectProgress.vue';
import NotStartedColumn from './components/NotStartedColumn.vue';
import PendingColumn from './components/PendingColumn.vue';
import CompletedColumn from './components/CompletedColumn.vue';
const route = useRoute();

const { ProjectData, getProjectDetail } = useGetProjectDetail();

const query = route.query?.query as string

onMounted(async () => {
    await getProjectDetail(query)
})

</script>
<template>
    <div class="row">
        <BreadCrumb />
        <ProjectDetail :ProjectData="ProjectData" />
        <ProjectProgress :ProjectData="ProjectData" />

    </div>
    <br />

    <div class="card">
        <div class="card-body">
            <div class="row" style="height: 500px;">
                <NotStartedColumn />
                <PendingColumn />
                <CompletedColumn />
            </div>
        </div>
    </div>
</template>
<style>
.assignees button {
    border-radius: 50px;
    width: 40px;
    border: 1px solid grey;
}

.assignees .member_2 {
    position: relative;
    left: -10px;
}

.assignees .member_3 {
    position: relative;
    left: -20px;
}

.task_card {
    padding: 10px;
    margin-top: 7px;
}

.not_started_task {
    background-color: aliceblue;
}

.pending_task {
    background-color: rgba(214, 214, 214, 0.276);
}
</style>