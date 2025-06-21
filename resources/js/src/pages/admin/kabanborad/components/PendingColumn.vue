<script lang="ts" setup>
import { getChar } from '../../../../helper/utils';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';

defineProps<{
    projectData: SingleProjectResponseType;
}>();
const emit = defineEmits<{
    (e: 'fromPendingToCompleted', taskId: number, projectId: number): Promise<void>
    (e: 'fromPendingToNotStarted', taskId: number, projectId: number): Promise<void>
}>()


</script>
<template>
    <div class="col-md-4 pending_task">
        <div class="card card-header">
            <b>Pending</b>
        </div>
        <div class="card-direct">
            <div v-for="task in projectData?.data?.tasks" :key="task.id"
                v-show="task.status === TaskStatus.PENDING ? true : false" draggable="true"
                @drag="emit('fromPendingToCompleted', task.id, projectData?.data?.id), emit('fromPendingToNotStarted', task.id, projectData?.data?.id)"
                :class="'card card-body task_card pendingTask_' + task.id">
                <p>{{ task.name }}</p>
                <div class="assignees">
                    <template v-for="(member, index) in (task.task_members ? task.task_members.slice(0, 3) : [])" :key="member.id">
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