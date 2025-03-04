<script lang="ts" setup>
import { getChar } from '../../../../helper/utils';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';

defineProps<{
    projectData: SingleProjectResponseType;
}>();

const emit = defineEmits<{
    (e: "fromCompletedToPending", taskId: number, projectId: number): Promise<void>;
    (e: "fromCompletedToNotStarted", taskId: number, projectId: number): Promise<void>;
}>();
</script>
<template>
    <div class="col-md-4 completed_task">
        <div class="card card-header">
            <b>Completed</b>
        </div>
        <div class="card-direct">
            <div v-for="task in projectData?.data?.tasks" :key="task.id"
                v-show="task.status === TaskStatus.COMPLETED ? true : false" draggable="true"
                @drag="emit('fromCompletedToPending', task.id, projectData?.data?.id), emit('fromCompletedToNotStarted', task.id, projectData?.data?.id)"
                :class="'card card-body task_card completedTask_' + task.id">
                <p>{{ task.name }}</p>
                <div class="assignees">
                    <button v-for="(member, index) in task.task_members" :key="member.id"
                        :class="'btn btn-primary member_' + index">
                        {{ getChar(member?.members?.name) }}
                    </button>
                    {{ task?.task_members.length }} assignees
                </div>
            </div>
        </div>
    </div>
</template>