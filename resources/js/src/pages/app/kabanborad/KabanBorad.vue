<script setup lang="ts">
import { onMounted, ref, watch, onUnmounted, nextTick, triggerRef, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useGetProjectDetail } from './actions/getProjectDetail';
import AddTaskModal from './components/AddTaskModal.vue';
import ProjectModal from '../project/components/ProjectModal.vue';
import { useGetMembers } from '../member/actions/getMember';
import { useTaskStore } from './store/kabanStore';
import { useDragTask } from './actions/dragTask';
import LoadingPage from '../../../components/LoadingPage.vue';
import { useGetProjectMembers } from '../project/actions/getProjectMembers';
import TaskDetailModal from './components/TaskDetailModal.vue';
import ProjectViewModal from './components/ProjectViewModal.vue';
import CompletedTasksModal from './components/CompletedTasksModal.vue';
import { makeHttpReq } from '../../../helper/makeHttpReq';
import { showSuccess, showError, showConfirm } from '../../../helper/alert';
import { useCacheFetch } from '../../../helper/useCacheFetch';
import eventBus, { emitForceCacheClear } from '../../../helper/eventBus';
import { getCurrentUserId, isCurrentUser } from '../../../helper/getUserData';
import { createDebouncedFunction } from '../../../helper/utils';
import { useGlobalRealtimeSetup } from '../../../helper/useGlobalRealtimeSetup';
import KanbanColumn from './components/KanbanColumn.vue';
import AddColumnModal from './components/AddColumnModal.vue';

const route = useRoute();
const router = useRouter();

const taskStore = useTaskStore();
const { ProjectData, getProjectDetail, loading: projectLoading } = useGetProjectDetail();
const { getMembers } = useGetMembers();
const { getProjectMembers, members: projectMembers } = useGetProjectMembers();

const slug = route.query?.query as string;
const modalVisible = ref(false);
const showTaskDetail = ref(false);
const selectedTask = ref<any>(null);
const showProjectViewModal = ref(false);
const showProjectEditModal = ref(false);
const showCompletedTasksModal = ref(false);
const projectUpdateLoading = ref(false);
const menuState = ref<{ column: string, taskId: number } | null>(null);
const showAddColumnModal = ref(false);
const showEditColumnModal = ref(false);
const editingColumn = ref<any>(null);
const kanbanColumnsRef = ref<HTMLElement | null>(null);

// Columns will be fetched from API
const columns = ref<any[]>([]);

// Track locked tasks (tasks being dragged by other users)
const lockedTasks = ref<Map<number, { userId: number, userName: string, userAvatar: string | null }>>(new Map());

// Track tasks being dragged by other users and their current column
const draggingTasks = ref<Map<number, { 
  userId: number, 
  userName: string, 
  userAvatar: string | null,
  columnId: string,
  columnStatus: string,
  taskName: string
}>>(new Map());

// Default columns configuration
const DEFAULT_COLUMNS = [
    {
        key: 'not-started',
        title: 'Not Started',
        icon: 'fas fa-circle',
        iconBg: 'linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)',
        status: 0,
        color: '#3b82f6',
        colorLight: '#60a5fa',
        id: 1,
        position: 0
    },
    {
        key: 'pending',
        title: 'Pending',
        icon: 'fas fa-clock',
        iconBg: 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)',
        status: 1,
        color: '#f59e0b',
        colorLight: '#fbbf24',
        id: 2,
        position: 1
    }
];

function setMenuState(val: { column: string, taskId: number } | null) {
    menuState.value = val;
}

// Check if current user is project creator
const isProjectCreator = computed(() => {
    const currentUserId = getCurrentUserId();
    const creatorId = ProjectData.value?.data?.creator?.id;

    // Convert both to numbers for comparison
    const currentUserIdNum = Number(currentUserId);
    const creatorIdNum = Number(creatorId);

    return currentUserIdNum === creatorIdNum;
});

// Get completed tasks count
const completedTasksCount = computed(() => {
    const tasks = ProjectData.value?.data?.tasks || [];
    return tasks.filter((task: any) => String(task.status) === 'OK').length;
});

const { refetch } = useCacheFetch(
    // @ts-expect-error - Pinia store type inference issue
    taskStore.projectDetailCache,
    // @ts-expect-error - Pinia store type inference issue
    taskStore.setProjectDetailCache,
    // @ts-expect-error - Pinia store type inference issue
    taskStore.clearProjectDetailCache
);

// Initialize global real-time manager
const globalRealtime = useGlobalRealtimeSetup();

function forceScrollbar() {
    if (kanbanColumnsRef.value) {
        kanbanColumnsRef.value.style.width = kanbanColumnsRef.value.scrollWidth + 'px';
    }
}

function scrollToAddColumnButton() {
    const addColumnBtn = document.querySelector('.add-column-btn');
    const kanbanContainer = document.querySelector('.kanban-grid-container');

    if (addColumnBtn && kanbanContainer) {
        const btnRect = addColumnBtn.getBoundingClientRect();
        const containerRect = kanbanContainer.getBoundingClientRect();

        if (btnRect.right > containerRect.right) {
            const scrollLeft = kanbanContainer.scrollLeft + (btnRect.right - containerRect.right) + 20;
            kanbanContainer.scrollTo({
                left: scrollLeft,
                behavior: 'smooth'
            });
        } else if (btnRect.left < containerRect.left) {
            const scrollLeft = kanbanContainer.scrollLeft + (btnRect.left - containerRect.left) - 20;
            kanbanContainer.scrollTo({
                left: scrollLeft,
                behavior: 'smooth'
            });
        }
    }
}

// Helper function to transform column from BE to FE format
function transformColumnData(column: any, index?: number): any {
    return {
        key: column.key_name || `column-${column.id}`,
        title: column.name,
        icon: column.icon,
        iconBg: `linear-gradient(135deg, ${column.color} 0%, ${column.color}80 100%)`,
        status: column.position ?? index ?? 0,
        color: column.color,
        colorLight: column.color + '80',
        id: column.id,
        position: column.position ?? index ?? 0
    };
}

// Function to fetch columns from API
async function getProjectColumns() {
    try {
        const projectId = ProjectData.value?.data?.id;
        if (!projectId) {
            columns.value = [...DEFAULT_COLUMNS];
            setTimeout(forceScrollbar, 100);
            return;
        }

        // Get columns from project data (board_columns field)
        const projectData = ProjectData.value?.data as any;
        const projectColumns = projectData?.board_columns;

        if (projectColumns && (Array.isArray(projectColumns) || typeof projectColumns === 'object')) {
            // Convert object to array if needed
            let columnsArray = projectColumns;
            if (!Array.isArray(projectColumns)) {
                columnsArray = Object.values(projectColumns);
            }

            if (columnsArray.length > 0) {
                // Transform data from BE to FE format
                columns.value = columnsArray.map((column: any, index: number) => 
                    transformColumnData(column, index)
                ).sort((a: any, b: any) => a.position - b.position); // Sort by position
            } else {
                // Fallback: create default columns if no data
                columns.value = [...DEFAULT_COLUMNS];
            }
        } else {
            // Fallback: create default columns if no data
            columns.value = [...DEFAULT_COLUMNS];
        }

        setTimeout(forceScrollbar, 100);
    } catch (error) {
        console.error('Error in getProjectColumns:', error);
        // Fallback: create default columns if there's an error
        columns.value = [...DEFAULT_COLUMNS];
        setTimeout(forceScrollbar, 100);
    }
}

onMounted(async () => {
    // Always fetch project detail from API, not from cache to avoid deleted project cases
    await getProjectDetail(slug);
    getMembers(1, '');

    // Fetch columns from API
    await getProjectColumns();

    setTimeout(() => {
        setupAllDropListeners();
        setupTaskCardDragListeners();
        setupTouchDelegation();
        setupHorizontalScrollTouch();
        setupMouseDragListeners();
        forceScrollbar();
    }, 100);

    // Additional setup for touch listeners with longer delay
    setTimeout(() => {
        setupTouchDelegation();
        setupHorizontalScrollTouch();
    }, 500);

    // Add window resize listener
    window.addEventListener('resize', forceScrollbar);

    const userId = getCurrentUserId();
    if (userId && window.Echo) {
        window.Echo.private(`user.${userId}`)
            .listen('UserRemovedFromProject', (e: { projectId: number }) => {
                if (e.projectId && ProjectData.value?.data?.id === e.projectId) {
                    showError('You have been removed from the project!');
                    router.push('/projects');
                }
            });
    }

    // Setup global project listener for current project
    if (ProjectData.value?.data?.id) {
        globalRealtime.setupProjectListener(ProjectData.value.data.id);

        // Setup global task listeners for all tasks
        if (ProjectData.value?.data?.tasks && Array.isArray(ProjectData.value.data.tasks)) {
            ProjectData.value.data.tasks.forEach((task: any) => {
                if (task.id) {
                    globalRealtime.setupTaskListener(task.id);
                }
            });
        }
    }

    // Listen for force cache clear events
    eventBus.on('force-cache-clear', async (eventData: any) => {
        try {
            if (ProjectData.value?.data?.id && eventData?.projectId === ProjectData.value.data.id) {
                if (!isCurrentUser(eventData.userId)) {
                    debouncedCacheClear({
                        projectId: ProjectData.value?.data?.id,
                        reason: 'task-status-changed-by-other-user',
                        timestamp: Date.now(),
                        userId: getCurrentUserId()
                    });
                }
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for task comment events
    eventBus.on('task-comment-created', async (eventData: any) => {
        try {
            // Only refresh if the comment is from another user and task exists in current project
            if (ProjectData.value?.data?.tasks && Array.isArray(ProjectData.value.data.tasks)) {
                const taskExists = ProjectData.value.data.tasks.some((task: any) => task.id === eventData.taskId);
                if (taskExists && !isCurrentUser(eventData.userId)) {
                    // For comments, we don't need to refresh the entire project data
                    // The TaskDetailModal will handle real-time updates for comments
                    // Only refresh if there are other changes that might affect the task list
                }
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for project progress updated events
    eventBus.on('project-progress-updated', async (eventData: any) => {
        try {
            if (ProjectData.value?.data?.id && eventData?.projectId === ProjectData.value.data.id) {
                if (!isCurrentUser(eventData.userId)) {
                    // Refresh project data to get updated progress
                    await getProjectDetail(slug, false);
                }
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for column added events - Optimized: Update directly from event data (no API call)
    eventBus.on('column-added', async (eventData: any) => {
        try {
            if (ProjectData.value?.data?.id && eventData?.projectId === ProjectData.value.data.id) {
                if (!isCurrentUser(eventData.userId) && eventData.column) {
                    // Optimistic update: Add column directly from event data (instant UI update)
                    const newColumn = transformColumnData(eventData.column, columns.value.length);
                    
                    // Add to columns array and sort
                    columns.value.push(newColumn);
                    columns.value.sort((a: any, b: any) => a.position - b.position);
                    
                    // Update ProjectData to keep sync
                    if (ProjectData.value?.data) {
                        const boardColumns = (ProjectData.value.data as any).board_columns || [];
                        if (Array.isArray(boardColumns)) {
                            boardColumns.push(eventData.column);
                        } else if (typeof boardColumns === 'object') {
                            (ProjectData.value.data as any).board_columns = [...Object.values(boardColumns), eventData.column];
                        } else {
                            (ProjectData.value.data as any).board_columns = [eventData.column];
                        }
                    }
                    
                    // Setup drop listeners for new column (immediate, no delay)
                    await nextTick();
                    setupAllDropListeners();
                    forceScrollbar();
                }
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for column deleted events - Optimized: Remove directly from array (no API call)
    eventBus.on('column-deleted', async (eventData: any) => {
        try {
            if (ProjectData.value?.data?.id && eventData?.projectId === ProjectData.value.data.id) {
                if (!isCurrentUser(eventData.userId) && eventData.columnId) {
                    // Optimistic update: Remove column directly from array (instant UI update)
                    const columnIndex = columns.value.findIndex((col: any) => col.id === eventData.columnId);
                    if (columnIndex !== -1) {
                        columns.value.splice(columnIndex, 1);
                    }
                    
                    // Update ProjectData to keep sync
                    if ((ProjectData.value?.data as any)?.board_columns) {
                        const boardColumns = (ProjectData.value.data as any).board_columns;
                        if (Array.isArray(boardColumns)) {
                            (ProjectData.value.data as any).board_columns = boardColumns.filter((col: any) => col.id !== eventData.columnId);
                        } else if (typeof boardColumns === 'object') {
                            const columnsArray = Object.values(boardColumns);
                            (ProjectData.value.data as any).board_columns = columnsArray.filter((col: any) => col.id !== eventData.columnId);
                        }
                    }
                    
                    // Setup drop listeners after column deletion (immediate, no delay)
                    await nextTick();
                    setupAllDropListeners();
                    forceScrollbar();
                }
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for task drag started events - Lock task for other users
    eventBus.on('task-drag-started', (eventData: any) => {
        try {
            if (ProjectData.value?.data?.id && eventData?.projectId === ProjectData.value.data.id) {
                if (!isCurrentUser(eventData.userId) && eventData.taskId) {
                    // Lock task: Mark as being dragged by another user (Vue reactive)
                    lockedTasks.value.set(eventData.taskId, {
                        userId: eventData.userId,
                        userName: eventData.userName || 'Someone',
                        userAvatar: eventData.userAvatar || null
                    });
                    // Force reactivity update
                    lockedTasks.value = new Map(lockedTasks.value);

                    // Auto-clear lock after 5s nếu không có drag-ended
                    setTimeout(() => {
                        if (lockedTasks.value.has(eventData.taskId)) {
                            lockedTasks.value.delete(eventData.taskId);
                            lockedTasks.value = new Map(lockedTasks.value);
                        }
                        if (draggingTasks.value.has(eventData.taskId)) {
                            draggingTasks.value.delete(eventData.taskId);
                            draggingTasks.value = new Map(draggingTasks.value);
                        }
                    }, 5000);
                }
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for task drag ended events - Unlock task
    eventBus.on('task-drag-ended', (eventData: any) => {
        try {
            if (ProjectData.value?.data?.id && eventData?.projectId === ProjectData.value.data.id) {
                if (eventData.taskId) {
                    // Unlock task (Vue reactive)
                    lockedTasks.value.delete(eventData.taskId);
                    // Remove from dragging tasks
                    draggingTasks.value.delete(eventData.taskId);
                    // Force reactivity update
                    lockedTasks.value = new Map(lockedTasks.value);
                    draggingTasks.value = new Map(draggingTasks.value);

                    // Clear column highlight (no-op now, kept for safety)
                }
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for task drag over column events - Show visual indicator
    eventBus.on('task-drag-over-column', async (eventData: any) => {
        try {
            if (ProjectData.value?.data?.id && eventData?.projectId === ProjectData.value.data.id) {
                if (!isCurrentUser(eventData.userId) && eventData.taskId) {
                    // Find task info
                    const task = ProjectData.value?.data?.tasks?.find((t: any) => t.id === eventData.taskId);
                    const taskName = task?.name || 'Task';
                    
                    // Update dragging task info
                    draggingTasks.value.set(eventData.taskId, {
                        userId: eventData.userId,
                        userName: eventData.userName || 'Someone',
                        userAvatar: eventData.userAvatar || null,
                        columnId: eventData.columnId,
                        columnStatus: eventData.columnStatus,
                        taskName: taskName
                    });
                    
                    // Force reactivity update
                    draggingTasks.value = new Map(draggingTasks.value);
                    
                    // Highlight target column
                    await nextTick();
                    const targetColumn = document.querySelector(`[data-column-id="${eventData.columnId}"]`) as HTMLElement;
                    if (targetColumn) {
                            // (removed dashed highlight per request)
                        
                        // Remove highlight after a short delay if no new event
                        setTimeout(() => {
                            const currentDragging = draggingTasks.value.get(eventData.taskId);
                            if (!currentDragging || currentDragging.columnId !== eventData.columnId) {
                                    // no-op
                            }
                        }, 500);
                    }
                }
            }
        } catch (error) {
            // Silent error handling
        }
    });

    // Listen for task status changed events - Realtime update tasks
    eventBus.on('task-status-changed-realtime', async (eventData: any) => {
        try {
            if (ProjectData.value?.data?.id && eventData?.projectId === ProjectData.value.data.id) {
                const updatedTask = eventData.task;
                const tasks = ProjectData.value?.data?.tasks || [];
                const idx = tasks.findIndex((t: any) => String(t.id) === String(eventData.taskId));
                if (idx !== -1) {
                    tasks[idx] = {
                        ...tasks[idx],
                        ...updatedTask,
                        status: updatedTask?.status ?? eventData.status
                    };
                }
                // Force reactivity update
                ProjectData.value = { ...ProjectData.value };
                await nextTick();
            }
        } catch (error) {
            // Silent
        }
    });

    // Listen for task status optimistic (whisper) - cực nhanh, không chờ API
    eventBus.on('task-status-optimistic', async (eventData: any) => {
        try {
            if (ProjectData.value?.data?.id && eventData?.projectId === ProjectData.value.data.id) {
                const tasks = ProjectData.value?.data?.tasks || [];
                const idx = tasks.findIndex((t: any) => String(t.id) === String(eventData.taskId));
                if (idx !== -1) {
                    tasks[idx] = {
                        ...tasks[idx],
                        status: eventData.status
                    };
                    ProjectData.value = { ...ProjectData.value };
                }
            }
        } catch (_) {
            // Silent
        }
    });
});

// Debounced function to clear cache
const debouncedCacheClear = createDebouncedFunction((eventData: any) => {
    emitForceCacheClear(eventData.projectId, eventData.reason, eventData.userId);
}, 1000);

async function openTaskModal() {
    const projectId = ProjectData.value?.data?.id;
    if (!projectId) return;
    await getProjectMembers(projectId);
    // @ts-expect-error - Pinia store type inference issue
    taskStore.taskInput.projectId = projectId;
    // @ts-expect-error - Pinia store type inference issue
    taskStore.taskInput.memberIds = [];
    modalVisible.value = true;
}

function closeTaskModal() {
    modalVisible.value = false;
}

function goBackToProjects() {
    router.push('/projects');
}

function openTaskDetail(taskId: number) {
    const allTasks = ProjectData.value?.data?.tasks || [];
    selectedTask.value = allTasks.find((t: any) => t.id === taskId);
    showTaskDetail.value = true;
}

function closeTaskDetail() {
    showTaskDetail.value = false;
    selectedTask.value = null;
}

function openAddColumnModal() {
    showAddColumnModal.value = true;
    setTimeout(() => {
        scrollToAddColumnButton();
    }, 100);
}

function closeAddColumnModal() {
    showAddColumnModal.value = false;
}

function openEditColumnModal(column: any) {
    editingColumn.value = column;
    showEditColumnModal.value = true;
}

function closeEditColumnModal() {
    showEditColumnModal.value = false;
    editingColumn.value = null;
}

async function addNewColumn(newColumn: any) {
    try {
        const projectId = ProjectData.value?.data?.id;
        if (!projectId) {
            showError('Project not found!');
            return;
        }

        // Save new column to database through new endpoint
        const response = await makeHttpReq<any, any>(`projects/${projectId}/add-column`, 'POST', {
            add_column: {
                name: newColumn.title,
                color: newColumn.color,
                icon: newColumn.icon
            }
        });

        if (response.code === 1000) {
            // Success - refresh project data to get updated columns
            await getProjectDetail(slug, false);
            await getProjectColumns();

            showSuccess(response.message || 'New column added successfully!');
            setTimeout(forceScrollbar, 100);

            await nextTick();
            setTimeout(() => {
                scrollToAddColumnButton();
                setTimeout(() => {
                    setupAllDropListeners();
                }, 100);
            }, 300);
        } else {
            // Error
            showError(response.message || 'Failed to add column!');
        }
    } catch (error: any) {
        showError(error?.message || 'Failed to add column!');
    }
}

async function updateColumn(newColumn: any) {
    try {
        const projectId = ProjectData.value?.data?.id;
        if (!projectId) {
            showError('Project not found!');
            return;
        }

        // Update column in database through new endpoint
        const response = await makeHttpReq<any, any>(`projects/${projectId}/update-column`, 'PUT', {
            update_column: {
                column_id: editingColumn.value.id,
                name: newColumn.title,
                color: newColumn.color,
                icon: newColumn.icon
            }
        });

        if (response.code === 1000) {
            // Clear cache first to ensure fresh data
            // @ts-expect-error - Pinia store type inference issue
            taskStore.clearProjectDetailCache(slug);

            // Success - refresh project data to get updated columns
            await getProjectDetail(slug, false);
            await getProjectColumns();

            // Force re-render by updating the columns array
            await nextTick();

            // Force Vue to detect the change by creating a new array reference
            columns.value = [...columns.value];

            // Force trigger reactivity
            triggerRef(columns);

            // Force update ProjectData to ensure reactivity
            if (ProjectData.value?.data) {
                ProjectData.value = { ...ProjectData.value };
            }

            showSuccess(response.message || 'Column updated successfully!');
            closeEditColumnModal();
        } else {
            // Error
            showError(response.message || 'Failed to update column!');
        }
    } catch (error: any) {
        showError(error?.message || 'Failed to update column!');
    }
}

function updateColumnTitle(columnKey: string, newTitle: string) {
    const column = columns.value.find(col => col.key === columnKey);
    if (column) {
        column.title = newTitle;

        // Force reactivity by creating new array reference
        columns.value = [...columns.value];
        triggerRef(columns);

        showSuccess('Column title updated!');
    }
}

function updateColumnColor(columnKey: string, newColor: string, newColorLight: string) {
    const column = columns.value.find(col => col.key === columnKey);
    if (column) {
        column.color = newColor;
        column.colorLight = newColorLight;
        column.iconBg = `linear-gradient(135deg, ${newColor} 0%, ${newColorLight} 100%)`;

        // Force reactivity by creating new array reference
        columns.value = [...columns.value];
        triggerRef(columns);

        showSuccess('Column color updated!');
    }
}

function formatDate(dateString: string) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function openEditProjectModal() {
    // Only allow project creator to edit
    if (!isProjectCreator.value) {
        showError('Only project creator can edit project details!');
        return;
    }
    showProjectEditModal.value = true;
}

function closeProjectEditModal() {
    showProjectEditModal.value = false;
}

async function handleProjectUpdate(updatedProject: any) {
    projectUpdateLoading.value = true;
    try {
        const projectId = ProjectData.value?.data?.id;
        if (!projectId) {
            showError('Project not found!');
            return;
        }

        // Update project in database
        const response = await makeHttpReq<any, any>('projects', 'PUT', updatedProject);

        if (response.code === 1000 || response.code === 1002) {
            // Clear cache first to ensure fresh data
            // @ts-expect-error - Pinia store type inference issue
            taskStore.clearProjectDetailCache(slug);

            // Success - refresh project data
            await getProjectDetail(slug, false);

            showSuccess(response.message || 'Project updated successfully!');
            closeProjectEditModal();
        } else {
            // Error
            showError(response.message || 'Failed to update project!');
        }
    } catch (error: any) {
        showError(error?.message || 'Failed to update project!');
    } finally {
        projectUpdateLoading.value = false;
    }
}

function openCompletedTasksModal() {
    showCompletedTasksModal.value = true;
}

function closeCompletedTasksModal() {
    showCompletedTasksModal.value = false;
}

function openProjectViewModal() {
    showProjectViewModal.value = true;
}

function closeProjectViewModal() {
    showProjectViewModal.value = false;
}

const { setupAllDropListeners, setupTaskCardDragListeners, setupTouchDelegation, setupHorizontalScrollTouch, setupMouseDragListeners } = useDragTask(ProjectData);

watch(() => ProjectData.value?.data?.tasks, () => {
    setTimeout(() => {
        setupTaskCardDragListeners();
        setupTouchDelegation();
        setupHorizontalScrollTouch();
    }, 100);

    // Additional setup with longer delay
    setTimeout(() => {
        setupTouchDelegation();
        setupHorizontalScrollTouch();
    }, 500);
}, { deep: true });

// Watch columns to automatically re-setup drag & drop when columns change
watch(() => columns.value, () => {
    setTimeout(() => {
        setupAllDropListeners();
    }, 100);
}, { deep: true });

async function handleRefreshKabanBoard() {
    await refetch(slug, async () => {
        await getProjectDetail(slug, false);
        return ProjectData.value;
    }, (data) => {
        // @ts-expect-error - Type inference issue with cache fetch
        ProjectData.value = data;
    });
}

async function handleDeleteTask(taskId: number) {
    const confirmed = await showConfirm('Are you sure you want to delete this task?', 'Delete Task');
    if (!confirmed) return;
    try {
        await makeHttpReq<undefined, { message: string }>(`tasks/${taskId}`, 'DELETE');
        showSuccess('Task deleted successfully!');
        await refetch(slug, async () => {
            await getProjectDetail(slug, false);
            return ProjectData.value;
        }, (data: any) => {
            ProjectData.value = data as typeof ProjectData.value;
        });
    } catch (err: any) {
        showError(err?.message || 'Delete task failed!');
    }
}

async function handleCompleteTask(taskId: number) {
    const confirmed = await showConfirm('Are you sure you want to mark this task as completed?', 'Complete Task');
    if (!confirmed) return;
    try {
        await makeHttpReq<any, any>(`task/transition_to_OK`, 'POST', {
            taskId: taskId,
            projectId: ProjectData.value?.data?.id
        });
        showSuccess('Task marked as completed!');
        await refetch(slug, async () => {
            await getProjectDetail(slug, false);
            return ProjectData.value;
        }, (data: any) => {
            ProjectData.value = data as typeof ProjectData.value;
        });
    } catch (err: any) {
        showError(err?.message || 'Complete task failed!');
    }
}

async function handleBackTask(taskId: number) {
    const confirmed = await showConfirm('Are you sure you want to move this task back to "Not Started"?', 'Move Back Task');
    if (!confirmed) return;
    try {
        await makeHttpReq<any, any>(`task/transition_to_0`, 'POST', {
            taskId: taskId,
            projectId: ProjectData.value?.data?.id
        });
        showSuccess('Task moved back to "Not Started"!');

        // Refresh project data
        await refetch(slug, async () => {
            await getProjectDetail(slug, false);
            return ProjectData.value;
        }, (data: any) => {
            ProjectData.value = data as typeof ProjectData.value;
        });

        // Force refresh completed tasks modal if it's open
        if (showCompletedTasksModal.value) {
            // Emit event to refresh completed tasks
            const event = new CustomEvent('refreshCompletedTasks');
            window.dispatchEvent(event);
        }
    } catch (err: any) {
        showError(err?.message || 'Move back task failed!');
    }
}

async function handleDeleteColumn(columnId: number) {
    try {
        // Find the column to get its status
        const targetColumn = columns.value.find(col => col.id === columnId);
        if (!targetColumn) {
            showError('Column not found!');
            return;
        }

        // Check if column has tasks by matching the status
        const columnTasks = ProjectData.value?.data?.tasks?.filter((task: any) => task.status === targetColumn.status) || [];

        if (columnTasks.length > 0) {
            showError(`Cannot delete column "${targetColumn.title}". It contains ${columnTasks.length} task(s). Please move or delete all tasks first.`);
            return;
        }

        // Show confirmation dialog
        const confirmed = await showConfirm(`Are you sure you want to delete column "${targetColumn.title}"?`, 'Delete Column');
        if (!confirmed) return;

        const projectId = ProjectData.value?.data?.id;
        if (!projectId) {
            showError('Project not found!');
            return;
        }

        // Delete column from database
        const response = await makeHttpReq<any, any>(`projects/${projectId}/delete-column`, 'DELETE', {
            column_id: columnId
        });

        if (response.code === 1000) {
            // Clear cache first to ensure fresh data
            // @ts-expect-error - Pinia store type inference issue
            taskStore.clearProjectDetailCache(slug);

            // Success - refresh project data to get updated columns
            await getProjectDetail(slug, false);

            await getProjectColumns();

            // Force re-render by updating the columns array
            await nextTick();

            // Force Vue to detect the change by creating a new array reference
            columns.value = [...columns.value];

            // Force trigger reactivity
            triggerRef(columns);

            // Force update ProjectData to ensure reactivity
            if (ProjectData.value?.data) {
                ProjectData.value = { ...ProjectData.value };
            }

            // Force DOM update with setTimeout
            setTimeout(() => {
                columns.value = [...columns.value];
                triggerRef(columns);
            }, 50);

            // Re-setup drag listeners after column update
            setTimeout(() => {
                setupAllDropListeners();
                setupTaskCardDragListeners();
                setupTouchDelegation();
                setupHorizontalScrollTouch();
                forceScrollbar();
            }, 100);

            showSuccess(response.message || 'Column deleted successfully!');
        } else {
            // Error
            showError(response.message || 'Failed to delete column!');
        }
    } catch (error: any) {
        console.error('Error in handleDeleteColumn:', error);
        showError(error?.message || 'Failed to delete column!');
    }
}

// Cleanup event listeners when component is unmounted
onUnmounted(() => {
    eventBus.off('force-cache-clear');
    eventBus.off('task-comment-created');
    eventBus.off('project-progress-updated');
    window.removeEventListener('resize', forceScrollbar);
});
</script>

<template>
    <div class="kanban-container">
        <!-- Loading Page -->
        <LoadingPage :visible="projectLoading" />

        <!-- Header Section -->
        <div class="kanban-header">
            <!-- Back Button -->
            <button class="back-btn" @click="goBackToProjects"><i class="fas fa-arrow-left"></i></button>

            <!-- Project Info Section -->
            <div class="project-info">
                <div class="project-title">
                    <i class="fas fa-columns"></i>
                    <span>{{ ProjectData?.data?.name }}</span>
                </div>
                <div class="project-dates">
                    {{ formatDate(ProjectData?.data?.startDate) }} - {{ formatDate(ProjectData?.data?.endDate) }}
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="complete-btn" @click="openCompletedTasksModal"
                    :title="`View ${completedTasksCount} completed task${completedTasksCount !== 1 ? 's' : ''}`">
                    <i class="fas fa-check-circle"></i>
                    <span v-if="completedTasksCount > 0" class="task-count">{{ completedTasksCount }}</span>
                </button>
                <button v-if="isProjectCreator" class="edit-btn" @click="openEditProjectModal" title="Edit Project">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="view-btn" @click="openProjectViewModal" title="View Project Details">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <!-- Mobile Layout Wrapper -->
            <div class="mobile-header-wrapper">
                <div class="mobile-top-row">
                    <button class="back-btn-mobile" @click="goBackToProjects"><i class="fas fa-arrow-left"></i></button>
                    <div class="project-title-mobile">
                        <i class="fas fa-columns"></i>
                        <span>{{ ProjectData?.data?.name }}</span>
                    </div>
                </div>
                <div class="mobile-dates">
                    {{ formatDate(ProjectData?.data?.startDate) }} - {{ formatDate(ProjectData?.data?.endDate) }}
                </div>
                <div class="mobile-actions">
                    <button class="complete-btn" @click="openCompletedTasksModal"
                        :title="`View ${completedTasksCount} completed task${completedTasksCount !== 1 ? 's' : ''}`">
                        <i class="fas fa-check-circle"></i>
                        <span v-if="completedTasksCount > 0" class="task-count">{{ completedTasksCount }}</span>
                    </button>
                    <button v-if="isProjectCreator" class="edit-btn" @click="openEditProjectModal" title="Edit Project">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="view-btn" @click="openProjectViewModal" title="View Project Details">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="kanban-content">
            <!-- Kanban Board Section -->
            <div class="kanban-grid-container">
                <div class="kanban-grid" ref="kanbanColumnsRef">
                    <KanbanColumn v-for="column in columns" :key="column.key" :config="column"
                        :tasks="ProjectData?.data?.tasks || []" :projectId="ProjectData?.data?.id"
                        :showAddTask="column.key === 'not-started'" :menuState="menuState as any"
                        :setMenuState="setMenuState" :isEditing="false" :lockedTasks="lockedTasks"
                        :draggingTasks="draggingTasks"
                        @viewTask="openTaskDetail"
                        @deleteTask="handleDeleteTask" @addTask="openTaskModal" @editColumn="openEditColumnModal"
                        @deleteColumn="handleDeleteColumn" @updateColumnTitle="updateColumnTitle"
                        @updateColumnColor="updateColumnColor" @completeTask="handleCompleteTask" />
                    <div class="add-column-slot">
                        <button class="add-column-btn" @click="openAddColumnModal">
                            <i class="fas fa-plus"></i> Add Column
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Task Modal -->
        <AddTaskModal :members="projectMembers" :visible="modalVisible" @getMembers="getMembers"
            @closeModal="closeTaskModal" @refreshKabanBoard="handleRefreshKabanBoard" />

        <!-- Task Detail Modal -->
        <TaskDetailModal :visible="showTaskDetail" :task="selectedTask" @close="closeTaskDetail" />

        <!-- Project View Modal -->
        <ProjectViewModal :visible="showProjectViewModal" :projectData="ProjectData?.data"
            @close="closeProjectViewModal" />

        <!-- Project Edit Modal -->
        <ProjectModal v-if="showProjectEditModal" :isEdit="true" :projectInput="ProjectData?.data"
            :loading="projectUpdateLoading" @close="closeProjectEditModal" @submit="handleProjectUpdate" />

        <!-- Completed Tasks Modal -->
        <CompletedTasksModal :visible="showCompletedTasksModal" :projectId="ProjectData?.data?.id || null"
            @close="closeCompletedTasksModal" @backTask="handleBackTask" />

        <!-- Add Column Modal -->
        <AddColumnModal :visible="showAddColumnModal" :existingColumns="columns" @close="closeAddColumnModal"
            @addColumn="addNewColumn" />

        <!-- Edit Column Modal -->
        <AddColumnModal :visible="showEditColumnModal" :existingColumns="columns" :editingColumn="editingColumn"
            @close="closeEditColumnModal" @addColumn="updateColumn" />
    </div>
</template>

<style scoped>
/* Main Container */
.kanban-container {
    background: #f8fafc;
    padding: 8px 0 16px 0;
    display: flex;
    flex-direction: column;
    max-width: 100vw;
    box-sizing: border-box;
    position: fixed !important;
    top: 20px;
    left: 0;
    right: 0;
    bottom: 0;
    height: calc(105vh - 62px);
    overflow: hidden !important;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    :deep(.loading-overlay) {
        position: fixed !important;
        left: 0;
        top: 62px;
        width: 100vw;
        height: calc(100vh - 62px);
        min-height: unset;
        border-radius: 0 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 20000 !important;
    }
}

/* Header Section */
.kanban-header {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: center;
    padding: 16px 18px;
    background: #fff;
    border-radius: 22px;
    box-shadow: 0 2px 16px #0001;
    min-height: 64px;
    position: relative;
    top: 10px;
    margin: 38px;
}

.kanban-header>* {
    flex-shrink: 0;
}

.back-btn {
    position: absolute;
    left: 18px;
}

.action-buttons {
    position: absolute;
    right: 18px;
    display: flex;
    gap: 8px;
    align-items: center;
}

.project-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    text-align: center;
    flex: 1;
    min-width: 0;
}

.project-title {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    justify-content: center;
}

.project-title i {
    color: #3b82f6;
    font-size: 1.2rem;
}

.project-dates {
    font-size: 13px;
    color: #64748b;
    white-space: nowrap;
    text-align: center;
}

.project-content {
    font-size: 13px;
    color: #94a3b8;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Hide mobile wrapper on desktop */
.mobile-header-wrapper {
    display: none;
}

.back-btn,
.edit-btn,
.view-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    color: #2563eb;
    border: none;
    box-shadow: 0 1px 4px #0001;
    margin: 0 2px;
    transition: background 0.18s, color 0.18s;
}

.back-btn {
    color: #374151;
}

.edit-btn {
    color: #2563eb;
}

.view-btn {
    color: #10b981;
}

.back-btn:active,
.edit-btn:active,
.view-btn:active {
    background: #e0e7ef;
}

/* Mobile responsive for action buttons */
@media (max-width: 768px) {

    .back-btn,
    .edit-btn,
    .view-btn {
        width: 32px;
        height: 32px;
        font-size: 1rem;
        margin: 0 1px;
    }
}

@media (max-width: 480px) {

    .back-btn,
    .edit-btn,
    .view-btn {
        width: 28px;
        height: 28px;
        font-size: 0.9rem;
        margin: 0;
    }
}

@media (max-width: 768px) {
    .kanban-container {
        height: calc(100vh - 62px);
    }

    .kanban-header {
        gap: 12px;
        padding: 12px 16px;
        min-height: auto;
        font-size: 12px;
        flex-direction: column;
        align-items: stretch;
        position: relative;
        top: 70px;
        margin-bottom: 85px;
    }

    /* Hide desktop elements on mobile */
    .back-btn,
    .project-info,
    .action-buttons,
    .project-content {
        display: none !important;
    }

    /* Show mobile layout */
    .mobile-header-wrapper {
        display: flex !important;
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }

    .mobile-top-row {
        display: flex !important;
        align-items: center;
        gap: 12px;
        justify-content: center;
        width: 100%;
        min-height: 40px;
        position: relative;
    }

    .back-btn-mobile {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: #374151;
        border: none;
        box-shadow: 0 1px 4px #0001;
        margin: 0;
        transition: background 0.18s, color 0.18s;
        position: absolute;
        left: 0;
    }

    .back-btn-mobile:active {
        background: #e0e7ef;
    }

    .project-title-mobile {
        font-size: 1.1rem;
        font-weight: 800;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .project-title-mobile i {
        color: #3b82f6;
        font-size: 1.1rem;
    }

    .mobile-dates {
        font-size: 11px;
        color: #64748b;
        text-align: center;
    }

    .mobile-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
    }

    .mobile-actions .edit-btn,
    .mobile-actions .view-btn {
        width: 32px;
        height: 32px;
        font-size: 0.95rem;
        margin: 0;
    }

    /* Mobile complete button in mobile actions */
    .mobile-actions .complete-btn {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        margin-right: 4px;
        border-width: 1.5px;
    }

    .mobile-actions .complete-btn i {
        font-size: 0.9rem;
    }

    .mobile-actions .complete-btn .task-count {
        width: 16px;
        height: 16px;
        font-size: 0.65rem;
        top: -5px;
        right: -5px;
        border-width: 1px;
    }
}

/* Nút Add Column */
.add-column-slot {
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-left: 16px;
}

.add-column-btn {
    width: 170px;
    min-width: 170px;
    font-size: 1.1rem;
    padding: 14px 0;
    border-radius: 24px;
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    color: #fff;
    font-weight: 700;
    border: none;
    box-shadow: 0 2px 8px #10b98122;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
    position: relative;
    z-index: 10;
}

.add-column-btn:hover {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    box-shadow: 0 4px 16px #10b98133;
    transform: translateY(-2px) scale(1.04);
}

.add-column-btn i {
    font-size: 1.3rem;
}

.kanban-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 182px);
    min-height: 0;
    width: 100%;
    min-width: 0;
    overflow: hidden !important;
    max-height: calc(100vh - 182px) !important;
    /* Tối ưu performance cho mobile */
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
    will-change: transform;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

/* Pure CSS Flexbox Approach - Clean and Simple */
.kanban-grid-container {
    width: 100%;
    height: 100%;
    overflow-x: auto;
    overflow-y: hidden !important;
    -ms-overflow-style: auto;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
    /* Tối ưu performance cho scroll */
    -webkit-overflow-scrolling: touch;
    scroll-behavior: auto;
    /* Hardware acceleration */
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    /* Tối ưu thêm */
    contain: layout style paint;
    isolation: isolate;
    /* Cải thiện performance cho mobile */
    will-change: scroll-position;
    overscroll-behavior-x: contain;
}

.kanban-grid {
    display: flex;
    gap: 16px;
    padding: 16px;
    min-width: max-content;
    width: max-content;
    height: 100%;
    align-items: flex-start;
    flex-wrap: nowrap;
    /* Tối ưu performance cho mobile */
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
    will-change: transform;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

/* Webkit scrollbar styling */
.kanban-grid-container::-webkit-scrollbar {
    height: 10px;
    background: #f1f5f9;
}

.kanban-grid-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 5px;
}

.kanban-grid-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 5px;
}

.kanban-grid-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Complete button styles */
.complete-btn {
    background: #f3f4f6;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #6b7280;
    margin-right: 8px;
    position: relative;
}

.complete-btn:hover:not(:disabled) {
    background: #e5e7eb;
    border-color: #9ca3af;
    color: #374151;
}

.complete-btn .task-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
}

.complete-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Mobile responsive for complete button */
@media (max-width: 768px) {
    .complete-btn {
        width: 36px;
        height: 36px;
        border-radius: 6px;
        margin-right: 6px;
        border-width: 1.5px;
    }

    .complete-btn i {
        font-size: 1rem;
    }

    .complete-btn .task-count {
        width: 18px;
        height: 18px;
        font-size: 0.7rem;
        top: -6px;
        right: -6px;
        border-width: 1.5px;
    }
}

@media (max-width: 480px) {
    .complete-btn {
        width: 32px;
        height: 32px;
        border-radius: 5px;
        margin-right: 4px;
        border-width: 1px;
    }

    .complete-btn i {
        font-size: 0.9rem;
    }

    .complete-btn .task-count {
        width: 16px;
        height: 16px;
        font-size: 0.65rem;
        top: -5px;
        right: -5px;
        border-width: 1px;
    }
}

.kanban-column {
    width: 320px;
    min-width: 320px;
    max-width: 320px;
    height: 1px;
    flex-shrink: 0;
}

.add-column-slot {
    width: 320px;
    min-width: 320px;
    max-width: 320px;
    height: fit-content;
    flex-shrink: 0;
    border-radius: 16px;
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
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
</style>