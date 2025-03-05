<script lang="ts" setup>
import { getChar } from '../../../../helper/utils';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';

defineProps<{
    projectData: SingleProjectResponseType
}>()

const emit = defineEmits<{
    (e: 'openTaskModal'): Promise<void>
    (e: 'fromNotStartedToPending', taskId: number, projectId: number): Promise<void>
    (e: 'fromNotStartedToCompleted', taskId: number, projectId: number): Promise<void>
}>()

</script>
<template>
    <div class="col-md-4 not_started_task">
        <div class="card card-header">
            <button @click="emit('openTaskModal')" class="btn btn-warning">Add Task</button>
        </div>
        <div class="card-direct">
            <div v-for="task in projectData?.data?.tasks" :key="task.id"
                v-show="task.status === TaskStatus.NOT_STARTED ? true : false"
                @drag="emit('fromNotStartedToPending', task.id, projectData?.data?.id), emit('fromNotStartedToCompleted', task.id, projectData?.data?.id)"
                draggable="true" :class="'card card-body card-direct  task_card notStartedTask_' + task.id">
                <p>{{ task.name }}</p>
                <div class="assignees">
                    <template v-for="(member, index) in task.task_members.slice(0, 3)" :key="member.id">
                        <button :class="'btn btn-primary member_' + index">
                            {{ getChar(member?.members?.name) }}
                        </button>
                    </template>
                    <span v-if="task.task_members.length > 3">...</span>
                    {{ task.task_members.length }} assignees
                </div>
            </div>
        </div>
    </div>
</template>