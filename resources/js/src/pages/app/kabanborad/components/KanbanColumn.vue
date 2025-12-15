<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount } from 'vue';
import MemberAvatar from './MemberAvatar.vue';

interface ColumnConfig {
  key: string;
  title: string;
  icon: string;
  iconBg: string;
  status: number;
  color: string;
  colorLight: string;
  id?: number;
  position?: number;
}

const props = defineProps({
  config: {
    type: Object as () => ColumnConfig,
    required: true
  },
  tasks: {
    type: Array as () => any[],
    default: () => []
  },
  projectId: [String, Number],
  showAddTask: {
    type: Boolean,
    default: false
  },
  menuState: Object,
  setMenuState: Function,
  lockedTasks: {
    type: Object as () => Map<number, { userId: number, userName: string, userAvatar: string | null }>,
    default: () => new Map()
  },
  draggingTasks: {
    type: Object as () => Map<number, { 
      userId: number, 
      userName: string, 
      userAvatar: string | null,
      columnId: string,
      columnStatus: string,
      taskName: string
    }>,
    default: () => new Map()
  }
});

const emit = defineEmits(['viewTask', 'deleteTask', 'addTask', 'updateColumnTitle', 'updateColumnColor', 'editColumn', 'deleteColumn', 'completeTask']);

const filteredTasks = computed(() => {
  const filtered = props.tasks.filter(task => String(task.status) === String(props.config.status));
  return filtered;
});

function taskCardStyle(taskObj: any) {
  const isLocked = props.lockedTasks.has(taskObj.id);
  const isMenuOpen = props.menuState && props.menuState.column === props.config.key && props.menuState.taskId === taskObj.id;
  return {
    zIndex: isMenuOpen ? 3000 : 0,
    position: isLocked ? 'relative' : undefined,
    cursor: isLocked ? 'not-allowed' : undefined,
    opacity: isLocked ? '0.6' : undefined
  } as Record<string, string | number | undefined>;
}

const canEditColumn = computed(() => {
  return props.config.key !== 'not-started';
});

function openMenu(taskId: number, event: MouseEvent) {
  event.stopPropagation();
  if (props.menuState && props.menuState.column === props.config.key && props.menuState.taskId === taskId) {
    props.setMenuState?.(null);
  } else {
    props.setMenuState?.({ column: props.config.key, taskId });
  }
}

function closeMenu() {
  // This function is called but showMenuTaskId is not used anymore
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

function handleCardMouseEnter() {
  // This function is called but showMenuTaskId is not used anymore
}

function handleCardMouseLeave(taskId: number) {
  if (props.menuState && props.menuState.column === props.config.key && props.menuState.taskId === taskId) {
    props.setMenuState?.(null);
  }
}

function onAddTask() {
  emit('addTask');
}

function onEditColumn() {
  emit('editColumn', props.config);
}

function onRemoveColumn() {
  emit('deleteColumn', props.config.id);
}

function onCompleteTask(taskId: number) {
  emit('completeTask', taskId);
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div class="kanban-column" :class="[
    `${config.key}-column`,
    { 'empty-column': filteredTasks.length === 0 }
  ]" :data-column-id="config.id" :data-column-status="config.status" :data-column-key="config.key"
    :data-column-color="config.color" :style="{
      '--column-color': config.color,
      '--column-color-light': config.colorLight
    }">
    <div class="column-header">
      <div class="column-title">
        <div class="title-icon" :style="{ background: config.iconBg }">
          <i :class="config.icon"></i>
        </div>
        <div class="title-content">
          <div class="title-display">
            <h3>{{ config.title }}</h3>
            <div class="column-actions">
              <span v-if="canEditColumn" class="edit-hint" @click.stop="onEditColumn">
                <i class="fas fa-edit"></i>
              </span>
              <span v-if="canEditColumn" class="remove-hint" @click.stop="onRemoveColumn">
                <i class="fas fa-trash"></i>
              </span>
            </div>
          </div>
          <span class="task-count">{{ filteredTasks.length }} tasks</span>
        </div>
      </div>
      <button v-if="showAddTask" @click="onAddTask" class="add-task-btn">
        <i class="fas fa-plus"></i>
      </button>
    </div>
    <div class="column-content">
      <div class="tasks-container">
        <div v-if="filteredTasks.length === 0" class="empty-state">
          <!-- Not Started column only shows Create Task button -->
          <template v-if="config.status === 0">
            <div v-if="showAddTask" class="empty-create-task">
              <button class="create-task-btn" @click="onAddTask">
                <i class="fas fa-plus"></i>
              </button>
              <div class="create-task-label">Create Task</div>
            </div>
          </template>
          <!-- Other columns show "No tasks yet" -->
          <template v-else>
            <div class="empty-icon">
              <i class="fas fa-inbox"></i>
            </div>
            <p>No tasks yet</p>
            <div v-if="showAddTask" class="empty-create-task">
              <button class="create-task-btn" @click="onAddTask">
                <i class="fas fa-plus"></i>
              </button>
              <div class="create-task-label">Create Task</div>
            </div>
          </template>
        </div>
        <div v-else class="task-list">
          <div v-for="taskObj in filteredTasks" :key="taskObj.id" class="task-card" 
            :draggable="!props.lockedTasks.has(taskObj.id)"
            :data-task-id="taskObj.id" :data-project-id="projectId" @mouseenter="handleCardMouseEnter"
            @mouseleave="handleCardMouseLeave(taskObj.id)" @dragstart="closeMenu"
            :style="taskCardStyle(taskObj)">
            <div class="task-header">
              <h4 class="task-title">{{ taskObj.name }}</h4>
              <div class="task-actions">
                <button class="task-action-btn" @click="openMenu(taskObj.id, $event)">
                  <i class="fas fa-ellipsis-h"></i>
                </button>
                <div
                  v-if="props.menuState && props.menuState.column === config.key && props.menuState.taskId === taskObj.id"
                  class="task-menu">
                  <div class="task-menu-item" @click="onView(taskObj.id)">
                    <i class="fas fa-eye"></i> View
                  </div>
                  <div class="task-menu-item" @click="onDelete(taskObj.id)">
                    <i class="fas fa-trash-alt"></i> Delete
                  </div>
                </div>
              </div>
            </div>
            <div class="task-members">
              <div v-if="taskObj.task_members && taskObj.task_members.length > 0" class="assignees">
                <div v-for="(member, index) in taskObj.task_members.slice(0, 3)" :key="member.id" class="member-avatar"
                  :class="`member-${index + 1}`">
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
              <div class="task-actions-footer">
                <button class="complete-task-btn" @click="onCompleteTask(taskObj.id)" title="Mark as completed">
                  <i class="fas fa-check"></i>
                </button>
              </div>
            </div>
            <!-- Lock overlay for tasks being dragged by other users -->
            <div v-if="props.lockedTasks.has(taskObj.id)" class="task-lock-overlay">
              <div class="lock-indicator">
                <div class="lock-avatar">
                  <img
                    v-if="props.lockedTasks.get(taskObj.id)?.userAvatar"
                    :src="props.lockedTasks.get(taskObj.id)?.userAvatar || undefined"
                    alt="avatar"
                  />
                  <div v-else class="lock-avatar-fallback">
                    {{ (props.lockedTasks.get(taskObj.id)?.userName || 'U').slice(0,1) }}
                  </div>
                </div>
                <div class="lock-text">
                  <div class="lock-user">{{ props.lockedTasks.get(taskObj.id)?.userName || 'Someone' }}</div>
                  <div class="lock-action">is moving this task</div>
                </div>
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
  border: none;
}

.kanban-column.empty-column {
  min-height: 200px;
  max-height: 250px;
  border: none;
}

.kanban-column.empty-column .column-content {
  overflow-y: hidden;
}

.kanban-column.not-started-column.empty-column .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.kanban-column.not-started-column.empty-column .empty-create-task {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  text-align: center;
}

.kanban-column.not-started-column.empty-column .create-task-btn {
  width: 40px;
  height: 40px;
  font-size: 14px;
}

.kanban-column.not-started-column.empty-column .create-task-label {
  font-size: 12px;
  color: #6b7280;
}

.kanban-column::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--column-color), var(--column-color-light));
}

.not-started-column {
  --column-color: #3b82f6;
  --column-color-light: #60a5fa;
}

.pending-column {
  --column-color: #f59e0b;
  --column-color-light: #fbbf24;
}

.completed-column {
  --column-color: #10b981;
  --column-color-light: #34d399;
}

.custom-column {
  --column-color: #8b5cf6;
  --column-color-light: #a78bfa;
}

.kanban-column:not(.not-started-column):not(.pending-column):not(.completed-column):not(.custom-column) {
  --column-color: #8b5cf6;
  --column-color-light: #a78bfa;
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
  flex: 1;
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
  flex-shrink: 0;
}

.title-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  min-width: 0;
}

.title-display {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.title-display h3 {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  flex: 1;
}

.column-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.edit-hint {
  color: #6b7280;
  font-size: 12px;
  opacity: 0.7;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 4px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.edit-hint:hover {
  opacity: 1;
  background-color: #f3f4f6;
  color: #374151;
  transform: scale(1.1);
}

.remove-hint {
  color: #ef4444;
  font-size: 12px;
  opacity: 0.7;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 4px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.remove-hint:hover {
  opacity: 1;
  background-color: #fef2f2;
  color: #dc2626;
  transform: scale(1.1);
}

.title-input {
  flex: 1;
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  border: 2px solid #3b82f6;
  border-radius: 6px;
  padding: 6px 10px;
  background: white;
  outline: none;
  min-width: 150px;
}

.save-btn,
.cancel-btn {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s;
  flex-shrink: 0;
}

.save-btn {
  background: #10b981;
  color: white;
}

.save-btn:hover {
  background: #059669;
  transform: scale(1.05);
}

.cancel-btn {
  background: #ef4444;
  color: white;
}

.cancel-btn:hover {
  background: #dc2626;
  transform: scale(1.05);
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
  flex-shrink: 0;
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

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
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

.empty-create-task {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
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

.task-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.task-card {
  background: #fff;
  border-radius: 18px;
  padding: 10px 12px 8px 12px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.07);
  transition: box-shadow 0.18s, border 0.18s, transform 0.12s;
  cursor: pointer;
  min-height: 48px;
  display: flex;
  flex-direction: column;
  position: relative;
  margin-bottom: 8px;
}

.task-card:hover {
  box-shadow: 0 6px 18px #10b98122;
  border-color: #10b981;
  transform: translateY(-2px) scale(1.01);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 6px;
}

.task-title {
  font-size: 1.08rem;
  font-weight: 700;
  color: #22223b;
  margin: 0;
  line-height: 1.2;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.task-actions {
  flex-shrink: 0;
  position: relative;
}

.task-action-btn {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #f1f5f9;
  border: none;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1.1rem;
  box-shadow: 0 1px 4px #0001;
  margin-left: 2px;
  transition: background 0.18s, color 0.18s;
}

.task-action-btn:hover {
  background: #e0f2fe;
  color: #2563eb;
}

.task-members {
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 4px;
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
  font-size: 11px;
}

.task-actions-footer {
  display: flex;
  gap: 8px;
}

.complete-task-btn {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 6px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 12px;
  box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
}

.complete-task-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.complete-task-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
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

.color-edit-inline {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: 12px;
  position: relative;
  z-index: 10;
  min-width: 180px;
  background: white;
  padding: 8px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.color-grid {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.color-option {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: 2px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 12px;
  flex-shrink: 0;
}

.color-option:hover {
  transform: scale(1.1);
}

.color-option.active {
  border-color: #1e293b;
  transform: scale(1.1);
}

.title-edit-inline {
  display: flex;
  align-items: center;
  gap: 8px;
  position: relative;
  z-index: 10;
  min-width: 250px;
  background: white;
  padding: 8px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

  .title-display h3 {
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
    padding: 7px 7px;
    min-height: 54px;
    border-radius: 12px;
  }

  .task-title {
    font-size: 12px;
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

  .task-footer {
    font-size: 10px;
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

  .title-display h3 {
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
    padding: 8px 10px 6px 10px;
    min-height: 40px;
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

/* Task lock overlay styles */
.task-lock-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(2px);
  pointer-events: none; /* Không chặn thao tác khác */
}

.lock-indicator {
  background: rgba(255, 255, 255, 0.95);
  padding: 8px 12px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
  color: #374151;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.lock-indicator i {
  color: #f59e0b;
  font-size: 0.875rem;
}

.lock-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid #f59e0b;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff7ed;
  flex-shrink: 0;
}

.lock-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.lock-avatar-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #f59e0b;
}

.lock-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.lock-user {
  font-weight: 700;
  color: #111827;
}

.lock-action {
  font-size: 0.8rem;
  color: #4b5563;
}

/* Drag over column indicator */
</style>