<script lang="ts" setup>
import { getChar } from '../../../../helper/utils';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';

defineProps<{
    projectData: SingleProjectResponseType;
}>()

const emit = defineEmits<{
    (e: 'openTaskModal'): Promise<void>
}>()
</script>

<template>
    <div class="col-md-4 not_started_task">
        <div class="card card-header">
            <button @click="emit('openTaskModal')" class="btn btn-warning">Add Task</button>
        </div>
        <div draggable="true" class="card card-body task_card">
            <div v-for="task in projectData?.data?.tasks" :key="task.id"
                v-show="task.status === TaskStatus.NOT_STARTED ? true : false" draggable="true" class="assignees">
                <p>
                    {{ task.name }}
                </p>
                <button v-for="member in task.task_members" :key="member.id" class="btn btn-primary member_1">{{
                    getChar(member?.members?.name) }}</button>
                <!-- <button class="btn btn-light member_2">L</button>
                <button class="btn btn-secondary member_3">F</button> -->
                {{ task.task_members }}
            </div>
        </div>
    </div>
</template>