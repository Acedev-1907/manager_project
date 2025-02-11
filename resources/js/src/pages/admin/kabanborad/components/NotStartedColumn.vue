<script lang="ts" setup>
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
            <p>
                Created the ui
            </p>
            <div draggable="true" class="assignees">
                <button class="btn btn-primary member_1">I</button>
                <button class="btn btn-light member_2">L</button>
                <button class="btn btn-secondary member_3">F</button>
                3 assigness
            </div>
        </div>

        <div class="card card-body task_card">
            <p>
                Created the ui
            </p>
            <div class="assignees">
                <button class="btn btn-primary member_1">I</button>
                <button class="btn btn-light member_2">L</button>
                <button class="btn btn-secondary member_3">F</button>
                3 assigness
            </div>
        </div>
    </div>
</template>