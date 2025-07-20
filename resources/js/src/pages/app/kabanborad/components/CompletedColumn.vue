<script setup lang="ts">
import { computed } from 'vue';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';
import KabanColumnBase from './KabanColumnBase.vue';

const props = defineProps<{ projectData: SingleProjectResponseType, menuState: any, setMenuState: any }>();

const completedTasks = computed(() => {
    return props.projectData?.data?.tasks?.filter(task => task.status === TaskStatus.COMPLETED) || [];
});
</script>
<template>
    <KabanColumnBase title="Completed" icon="fas fa-check-circle"
        :iconBg="'linear-gradient(135deg, #10b981 0%, #34d399 100%)'" :tasks="completedTasks"
        :projectId="props.projectData?.data?.id" emptyIcon="fas fa-trophy" emptyText="No completed tasks"
        :showAddTask="false" @viewTask="$emit('viewTask', $event)" @deleteTask="$emit('deleteTask', $event)"
        :menuState="props.menuState" :setMenuState="(val: any) => props.setMenuState(val)" columnKey="completed" />
</template>