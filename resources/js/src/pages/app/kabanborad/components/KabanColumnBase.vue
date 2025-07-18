<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import MemberAvatar from './MemberAvatar.vue';

const props = defineProps({
    title: String,
    icon: String,
    iconBg: String,
    tasks: { type: Array as () => any[], default: () => [] },
    projectId: [String, Number],
    emptyIcon: String,
    emptyText: String,
    showAddTask: Boolean,
    menuState: Object,
    setMenuState: Function,
    columnKey: String,
});
const emit = defineEmits(['viewTask', 'deleteTask', 'addTask']);

const showMenuTaskId = ref<number | null>(null);

function openMenu(taskId: number, event: MouseEvent) {
    event.stopPropagation();
    if (props.menuState && props.menuState.column === props.columnKey && props.menuState.taskId === taskId) {
        props.setMenuState?.(null);
    } else {
        props.setMenuState?.({ column: props.columnKey, taskId });
    }
}
function closeMenu() {
    showMenuTaskId.value = null;
}
function onView(taskId: number) {
    closeMenu();
    emit('viewTask', taskId);
}
function onDelete(taskId: number) {
    closeMenu();
    emit('deleteTask', taskId);
}
function handleClickOutside(e: MouseEvent) {
    const card = (e.target as HTMLElement).closest('.task-card');
    const menu = (e.target as HTMLElement).closest('.task-menu');
    const btn = (e.target as HTMLElement).closest('.task-action-btn');
    if (!card || (!menu && !btn)) {
        closeMenu();
    }
}
function handleCardMouseEnter(taskId: number) {
    if (showMenuTaskId.value && showMenuTaskId.value !== taskId) {
        showMenuTaskId.value = null;
    }
}
function handleCardMouseLeave(taskId: number) {
    if (props.menuState && props.menuState.column === props.columnKey && props.menuState.taskId === taskId) {
        props.setMenuState?.(null);
    }
}
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
function onAddTask() {
    emit('addTask');
}
</script>
<template>
    <div class="kanban-column">
        <div class="column-header">
            <slot name="header">
                <div class="column-title">
                    <div class="title-icon" :style="{ background: iconBg }">
                        <i :class="icon"></i>
                    </div>
                    <div class="title-content">
                        <h3>{{ title }}</h3>
                        <span class="task-count">{{ props.tasks.length }} tasks</span>
                    </div>
                    <button v-if="showAddTask" @click="onAddTask" class="add-task-btn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </slot>
        </div>
        <div class="column-content">
            <div class="tasks-container">
                <div v-if="props.tasks.length === 0" class="empty-state">
                    <slot name="empty">
                        <div class="empty-icon"><i :class="emptyIcon"></i></div>
                        <p>{{ emptyText }}</p>
                    </slot>
                </div>
                <div v-else class="task-list">
                    <div v-for="taskObj in props.tasks" :key="taskObj.id" class="task-card" draggable="true"
                        :data-task-id="taskObj.id" :data-project-id="projectId"
                        @mouseenter="handleCardMouseEnter(taskObj.id)" @mouseleave="handleCardMouseLeave(taskObj.id)"
                        @dragstart="closeMenu"
                        :style="{ zIndex: props.menuState && props.menuState.column === props.columnKey && props.menuState.taskId === taskObj.id ? 3000 : 0 }">
                        <div class="task-header">
                            <h4 class="task-title">{{ taskObj.name }}</h4>
                            <div class="task-actions">
                                <button class="task-action-btn" @click="openMenu(taskObj.id, $event)">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div v-if="props.menuState && props.menuState.column === props.columnKey && props.menuState.taskId === taskObj.id"
                                    class="task-menu">
                                    <div class="task-menu-item" @click="onView(taskObj.id)"><i class="fas fa-eye"></i>
                                        View</div>
                                    <div class="task-menu-item" @click="onDelete(taskObj.id)"><i
                                            class="fas fa-trash-alt"></i> Delete</div>
                                </div>
                            </div>
                        </div>
                        <div class="task-members">
                            <div v-if="taskObj.task_members && taskObj.task_members.length > 0" class="assignees">
                                <div v-for="(member, index) in taskObj.task_members.slice(0, 3)" :key="member.id"
                                    class="member-avatar" :class="`member-${index + 1}`">
                                    <MemberAvatar :member="member" />
                                </div>
                                <div v-if="taskObj.task_members.length > 3" class="more-members">
                                    +{{ taskObj.task_members.length - 3 }}
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
                                <span>{{ new Date(taskObj.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
/* Copy CSS chung từ các column hiện tại, giữ nguyên UI/UX */
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
    background: var(--column-color, #3b82f6);
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

.add-task-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 1px solid #e2e8f0;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 12px;
}

.add-task-btn:hover {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-color: #3b82f6;
    color: white;
    transform: scale(1.05);
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

.tasks-container,
.task-list {
    overflow: visible !important;
}

.task-card {
    position: relative;
    z-index: 0;
}

.task-card:hover {
    z-index: 0;
}

.task-menu {
    z-index: 2000 !important;
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
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 12px;
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
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
    -webkit-touch-callout: none;
    position: relative;
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

.task-actions {
    flex-shrink: 0;
    position: relative;
}

.task-action-btn {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: #f5f8fc;
    border: none;
    color: #22223b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    font-size: 1.35rem;
    box-shadow: 0 2px 8px rgba(34, 34, 59, 0.07);
    margin-left: 4px;
}

.task-action-btn:hover {
    background: #e0f2fe;
    color: #2563eb;
    box-shadow: 0 4px 16px #2563eb22;
}

.task-action-btn:active {
    background: #bae6fd;
    color: #1d4ed8;
}

.task-action-btn i {
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.task-menu {
    position: absolute;
    right: 0;
    top: 36px;
    background: #fff;
    border: none;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    z-index: 100;
    min-width: 140px;
    padding: 8px 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
    animation: fadeInMenu 0.18s;
}

@keyframes fadeInMenu {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.task-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 24px 12px 20px;
    font-size: 1rem;
    font-weight: 500;
    color: #22223b;
    background: none;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.18s, color 0.18s;
}

.task-menu-item i {
    font-size: 1.15rem;
    min-width: 22px;
    text-align: center;
}

.task-menu-item:hover {
    background: #e0f2fe;
    color: #0284c7;
}

.task-menu-item:last-child:hover {
    background: #fee2e2;
    color: #dc2626;
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
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
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

    .add-task-btn {
        width: 28px;
        height: 28px;
        font-size: 10px;
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

    .add-task-btn {
        width: 24px;
        height: 24px;
        font-size: 9px;
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

.column-content,
.kanban-column,
.kanban-board {
    overflow: visible !important;
}
</style>