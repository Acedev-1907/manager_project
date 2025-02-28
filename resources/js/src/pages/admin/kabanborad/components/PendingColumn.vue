<script lang="ts" setup>
import { getChar } from '../../../../helper/utils';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';

defineProps<{
    projectData: SingleProjectResponseType;
}>();
const emit = defineEmits<{
    (e: 'fromPendingToCompleted', taskId: number, projectId: number): Promise<void>
}>()


</script>
<template>
    <div class="col-md-4 pending_task ">
        <div class="card card-header">
            <b>Pending</b>
        </div>
        <div class="card-direct">
            <div v-for="task in projectData?.data?.tasks" :key="task.id"
                v-show="task.status === TaskStatus.PENDING ? true : false" draggable="true"
                @drag="emit('fromPendingToCompleted', task.id, projectData?.data?.id)"
                :class="'card card-body task_card pendingTask_' + task.id">
                <!--  -->
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