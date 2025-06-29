<script setup lang="ts">
import { computed } from 'vue';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';

const props = defineProps<{
    projectData: SingleProjectResponseType
}>();

const emit = defineEmits<{
    (e: "openTaskModal"): void;
}>();

const completedTasks = computed(() => {
    return props.projectData?.data?.tasks?.filter(task => task.status === TaskStatus.COMPLETED) || [];
});

function openTaskModal() {
    emit('openTaskModal');
}
</script>

<template>
    <div class="kanban-column">
        <div class="column-header">
            <div class="column-title">
                <div class="title-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="title-content">
                    <h3>Completed</h3>
                    <span class="task-count">{{ completedTasks.length }} tasks</span>
                </div>
            </div>
        </div>

        <div class="column-content">
            <div class="tasks-container">
                <div v-if="completedTasks.length === 0" class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <p>No completed tasks</p>
                </div>

                <div v-else class="task-list">
                    <div v-for="task in completedTasks" :key="task.id" class="task-card completed" draggable="true"
                        :data-task-id="task.id" :data-project-id="projectData?.data?.id">
                        <div class="task-header">
                            <h4 class="task-title">{{ task.name }}</h4>
                            <div class="task-actions">
                                <div class="completed-badge">
                                    <i class="fas fa-check"></i>
                                </div>
                                <button class="task-action-btn">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </div>
                        </div>

                        <div class="task-members">
                            <div v-if="task.task_members && task.task_members.length > 0" class="assignees">
                                <div v-for="(member, index) in task.task_members.slice(0, 3)" :key="member.id"
                                    class="member-avatar" :class="`member-${index + 1}`">
                                    <span>{{ member.members.name.charAt(0).toUpperCase() }}</span>
                                </div>
                                <div v-if="task.task_members.length > 3" class="more-members">
                                    +{{ task.task_members.length - 3 }}
                                </div>
                            </div>
                            <div v-else class="no-assignees">
                                <i class="fas fa-user-plus"></i>
                                <span>Unassigned</span>
                            </div>
                        </div>

                        <div class="task-footer">
                            <div class="task-date">
                                <i class="fas fa-calendar"></i>
                                <span>{{ new Date(task.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.kanban-column {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    min-height: 450px;
    max-height: 600px;
    display: flex;
    flex-direction: column;
}

.kanban-column::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
}

.column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 20px 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.column-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.title-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.title-content h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    line-height: 1.2;
}

.task-count {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
}

.column-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.column-content::-webkit-scrollbar {
    width: 6px;
}

.column-content::-webkit-scrollbar-track {
    background: transparent;
}

.column-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.column-content::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.tasks-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 200px;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: #94a3b8;
}

.empty-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 12px;
    color: #10b981;
}

.empty-state p {
    font-size: 14px;
    margin: 0 0 16px 0;
    font-weight: 500;
}

.task-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.task-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    cursor: grab;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    /* Touch support for mobile */
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
    -webkit-touch-callout: none;
}

.task-card.completed {
    border-color: #10b981;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
}

.task-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    border-color: #cbd5e1;
}

.task-card:active {
    cursor: grabbing;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.task-title {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    line-height: 1.3;
    flex: 1;
    margin-right: 8px;
}

.task-card.completed .task-title {
    opacity: 0.9;
    color: #374151;
}

.task-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.completed-badge {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 8px;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

.task-action-btn {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: transparent;
    border: none;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 10px;
}

.task-action-btn:hover {
    background: #f1f5f9;
    color: #374151;
}

.task-members {
    margin-bottom: 12px;
    flex: 1;
}

.assignees {
    display: flex;
    align-items: center;
    gap: 4px;
}

.member-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.member-1 {
    z-index: 4;
}

.member-2 {
    z-index: 3;
}

.member-3 {
    z-index: 2;
}

.more-members {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #64748b;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.no-assignees {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #94a3b8;
    font-size: 12px;
}

.no-assignees i {
    font-size: 10px;
}

.task-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.task-date {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #64748b;
    font-size: 11px;
}

.task-date i {
    font-size: 9px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .column-header {
        padding: 16px 16px 12px 16px;
    }

    .title-icon {
        width: 28px;
        height: 28px;
        font-size: 10px;
    }

    .title-content h3 {
        font-size: 14px;
    }

    .task-count {
        font-size: 11px;
    }

    .column-content {
        padding: 12px;
    }

    .task-card {
        padding: 12px;
        min-height: 100px;
    }

    .task-title {
        font-size: 13px;
    }

    .completed-badge {
        width: 18px;
        height: 18px;
        font-size: 7px;
    }

    .member-avatar {
        width: 24px;
        height: 24px;
        font-size: 9px;
    }

    .more-members {
        width: 24px;
        height: 24px;
        font-size: 9px;
    }
}

@media (max-width: 480px) {
    .column-header {
        padding: 12px 12px 8px 12px;
    }

    .title-icon {
        width: 24px;
        height: 24px;
        font-size: 9px;
    }

    .title-content h3 {
        font-size: 13px;
    }

    .column-content {
        padding: 8px;
    }

    .task-card {
        padding: 10px;
        min-height: 90px;
    }

    .task-title {
        font-size: 12px;
    }

    .completed-badge {
        width: 16px;
        height: 16px;
        font-size: 6px;
    }

    .member-avatar {
        width: 20px;
        height: 20px;
        font-size: 8px;
    }

    .more-members {
        width: 20px;
        height: 20px;
        font-size: 8px;
    }
}
</style>