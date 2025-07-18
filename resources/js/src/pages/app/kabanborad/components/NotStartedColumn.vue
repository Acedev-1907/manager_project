<script setup lang="ts">
import { computed } from 'vue';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';
import KabanColumnBase from './KabanColumnBase.vue';

const props = defineProps<{ projectData: SingleProjectResponseType, menuState: any, setMenuState: any }>();
const emit = defineEmits<{
    (e: "openTaskModal"): void;
    (e: "viewTask", taskId: number): void;
    (e: "deleteTask", taskId: number): void;
}>();

const notStartedTasks = computed(() => {
    return props.projectData?.data?.tasks?.filter(task => task.status === TaskStatus.NOT_STARTED) || [];
});

function openTaskModal() {
    emit('openTaskModal');
}
</script>
<template>
    <KabanColumnBase title="Not Started" icon="fas fa-circle"
        :iconBg="'linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)'" :tasks="notStartedTasks"
        :projectId="props.projectData?.data?.id" emptyIcon="fas fa-inbox" emptyText="No tasks yet" :showAddTask="true"
        @addTask="openTaskModal" @viewTask="$emit('viewTask', $event)" @deleteTask="$emit('deleteTask', $event)"
        :menuState="props.menuState" :setMenuState="(val: any) => props.setMenuState(val)" columnKey="notstarted">
        <template #empty>
            <div class="empty-create-task">
                <button class="create-task-btn" @click="openTaskModal">
                    <i class="fas fa-plus"></i>
                </button>
                <div class="create-task-label">Create Task</div>
            </div>
        </template>
    </KabanColumnBase>
</template>
<style scoped>
.empty-create-task {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 32px;
}

.create-task-btn {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #e0edfa 0%, #b6d2fa 100%);
    color: #3b82f6;
    border: none;
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 12px #3b82f622;
    cursor: pointer;
    transition: background 0.18s, box-shadow 0.18s;
}

.create-task-btn:hover {
    background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%);
    box-shadow: 0 4px 18px #2563eb22;
}

.create-task-label {
    font-size: 1.1rem;
    color: #3b82f6;
    font-weight: 600;
    letter-spacing: 0.5px;
    opacity: 0.7;
}
</style>
