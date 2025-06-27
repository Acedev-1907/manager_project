<script lang="ts" setup>
import { getChar } from '../../../../helper/utils';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';
import { taskStore } from '../store/kabanStore';

defineProps<{
    projectData: SingleProjectResponseType;
}>();

function handleDragStart(taskId: number, projectId: number) {
    taskStore.setDraggedTask(taskId, projectId);
}
</script>
<template>
    <div class="col-md-4 completed_task">
        <div class="card card-header">
            <b>Completed</b>
        </div>
        <div class="card-direct">
            <div v-for="task in projectData?.data?.tasks.filter(t => t.status === TaskStatus.COMPLETED)" :key="task.id"
                draggable="true" @dragstart="handleDragStart(task.id, projectData?.data?.id)"
                :class="'card card-body task_card completedTask_' + task.id">
                <p>{{ task.name }}</p>
                <div class="assignees">
                    <template v-for="(member, index) in (task.task_members ? task.task_members.slice(0, 3) : [])"
                        :key="member.id">
                        <button :class="'btn btn-primary member_' + index">
                            {{ getChar(member?.members?.name) }}
                        </button>
                    </template>
                    <span v-if="task.task_members && task.task_members.length > 3">...</span>
                    {{ task.task_members ? task.task_members.length : 0 }} assignees
                </div>
            </div>
        </div>
    </div>
</template>