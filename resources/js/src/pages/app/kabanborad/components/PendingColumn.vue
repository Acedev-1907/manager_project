<script setup lang="ts">
import { computed } from 'vue';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';
import KabanColumnBase from './KabanColumnBase.vue';

const props = defineProps<{ projectData: SingleProjectResponseType, menuState: any, setMenuState: any }>();

const pendingTasks = computed(() => {
    return props.projectData?.data?.tasks?.filter(task => task.status === TaskStatus.PENDING) || [];
});
</script>
<template>
    <KabanColumnBase title="Pending" icon="fas fa-clock" :iconBg="'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)'"
        :tasks="pendingTasks" :projectId="props.projectData?.data?.id" emptyIcon="fas fa-clock"
        emptyText="No pending tasks" :showAddTask="false" @viewTask="$emit('viewTask', $event)"
        @deleteTask="$emit('deleteTask', $event)" :menuState="props.menuState"
        :setMenuState="(val: any) => props.setMenuState(val)" columnKey="pending" />
</template>
