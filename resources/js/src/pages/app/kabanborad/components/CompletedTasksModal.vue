<template>
    <Teleport to="body">
        <div v-if="visible" class="modal-backdrop" @click="closeModal">
            <div class="modal-dialog" @click.stop>
                <div class="modal-content">
                    <!-- Enhanced Header -->
                    <div class="modal-header">
                        <div class="header-content">
                            <div class="title-section">
                                <div class="title-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="title-text">
                                    <h3 class="modal-title">Completed Tasks</h3>
                                    <p class="modal-subtitle">
                                        {{ completedTasks.length }} task{{ completedTasks.length !== 1 ? 's' : '' }}
                                        completed
                                    </p>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" @click="closeModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div v-if="loading" class="loading-container">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                            <p>Loading completed tasks...</p>
                        </div>

                        <div v-else-if="completedTasks.length === 0" class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5>No completed tasks yet</h5>
                            <p>Tasks will appear here once they are marked as completed.</p>
                        </div>

                        <div v-else class="completed-tasks-grid">
                            <div v-for="task in completedTasks" :key="task.id" class="task-card">
                                <!-- Task Header -->
                                <div class="task-header">
                                    <div class="task-title-section">
                                        <div class="task-status-icon">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <h4 class="task-title">{{ task.name }}</h4>
                                    </div>
                                    <button class="back-task-btn" @click="onBackTask(task.id)"
                                        title="Move back to Not Started">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>

                                <!-- Task Content -->
                                <div v-if="task.content" class="task-content">
                                    <p>{{ task.content }}</p>
                                </div>

                                <!-- Task Meta Info -->
                                <div class="task-meta">
                                    <div class="completion-info">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>{{ formatDate(task.updated_at) }}</span>
                                    </div>

                                    <div v-if="task.comments && task.comments.length > 0" class="comments-info">
                                        <i class="fas fa-comments"></i>
                                        <span>{{ task.comments.length }}</span>
                                    </div>
                                </div>

                                <!-- Task Members -->
                                <div v-if="task.task_members && task.task_members.length > 0" class="task-members">
                                    <div class="members-avatars">
                                        <div v-for="(member, index) in task.task_members.slice(0, 3)" :key="member.id"
                                            class="member-avatar" :style="{ zIndex: task.task_members.length - index }">
                                            <img :src="getAvatarSrc(member.user?.avatar, member.user?.name)"
                                                :alt="member.user?.name" />
                                            <span class="member-tooltip">{{ member.user?.name }}</span>
                                        </div>
                                        <div v-if="task.task_members.length > 3" class="more-members">
                                            +{{ task.task_members.length - 3 }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
import { getAvatarSrc } from '../../../../helper/avatar';

const props = defineProps<{
    visible: boolean;
    projectId: number | null;
}>();

const emit = defineEmits(['close', 'backTask']);

const completedTasks = ref<any[]>([]);
const loading = ref(false);

watch(() => props.visible, async (newVisible) => {
    if (newVisible && props.projectId) {
        await fetchCompletedTasks();
        // Add class to body when modal is opened
        document.body.classList.add('modal-open');
    } else {
        // Remove class from body when modal is closed
        document.body.classList.remove('modal-open');
    }
});

async function fetchCompletedTasks() {
    if (!props.projectId) return;

    loading.value = true;
    try {
        const response = await makeHttpReq<any, any>(`projects/${props.projectId}/completed-tasks`, 'GET');

        if (response.code === 1000 || response.code === 1002) {
            completedTasks.value = response.data || [];
        } else {
            completedTasks.value = [];
        }
    } catch (error: any) {
        completedTasks.value = [];
    } finally {
        loading.value = false;
    }
}

function closeModal() {
    emit('close');
}

function formatDate(dateString: string) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function onBackTask(taskId: number) {
    emit('backTask', taskId);
}

// Listen for refresh event
function handleRefreshEvent() {
    if (props.visible && props.projectId) {
        fetchCompletedTasks();
    }
}

onMounted(() => {
    window.addEventListener('refreshCompletedTasks', handleRefreshEvent);
});

onUnmounted(() => {
    window.removeEventListener('refreshCompletedTasks', handleRefreshEvent);
    // Remove class from body when component is unmounted
    document.body.classList.remove('modal-open');
});
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 900 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    /* Ensure modal is above all elements including Kanban page */
    isolation: isolate;
    pointer-events: auto;
}

.modal-dialog {
    max-width: 900px;
    width: 100%;
    max-height: 85vh;
    z-index: 901 !important;
    position: relative;
}

.modal-content {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 902 !important;
    /* Ensure content is above all elements */
    isolation: isolate;
    position: relative;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem 2rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
    z-index: 903 !important;
    position: relative;
}

.header-content {
    flex: 1;
}

.title-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.title-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.title-text {
    flex: 1;
}

.modal-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1.2;
}

.modal-subtitle {
    font-size: 1rem;
    color: #64748b;
    margin: 0.25rem 0 0 0;
    font-weight: 500;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: #64748b;
    cursor: pointer;
    padding: 0.75rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-close:hover {
    background: #f1f5f9;
    color: #1e293b;
    transform: scale(1.05);
}

.modal-body {
    padding: 0;
    overflow-y: auto;
    flex: 1;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.loading-spinner {
    margin-bottom: 1rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: #64748b;
    text-align: center;
}

.empty-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    color: #10b981;
    font-size: 2rem;
}

.completed-tasks-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    max-height: 65vh;
    overflow-y: auto;
    padding: 1.5rem;
}

.completed-tasks-grid::-webkit-scrollbar {
    width: 8px;
}

.completed-tasks-grid::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 4px;
}

.completed-tasks-grid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.completed-tasks-grid::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.task-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.25rem;
    transition: all 0.3s ease;
    min-height: 180px;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

.task-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.task-card:hover {
    background: #fafbfc;
    border-color: #cbd5e1;
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 0.75rem;
}

.task-title-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.task-status-icon {
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.task-title {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
    line-height: 1.4;
    margin: 0;
    flex: 1;
}

.back-task-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border: none;
    border-radius: 8px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
    flex-shrink: 0;
}

.back-task-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
}

.back-task-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
}

.task-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    gap: 0.5rem;
}

.completion-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f0fdf4;
    color: #166534;
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
}

.completion-info i {
    font-size: 0.75rem;
}

.comments-info {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: #fef3c7;
    color: #92400e;
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
}

.comments-info i {
    font-size: 0.75rem;
}

.task-content {
    color: #475569;
    margin-bottom: 1rem;
    line-height: 1.5;
    flex: 1;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.task-content p {
    margin: 0;
    font-size: 0.875rem;
}

.task-members {
    margin-top: auto;
}

.members-avatars {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.member-avatar {
    position: relative;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.member-avatar:hover {
    transform: scale(1.1);
    z-index: 10;
}

.member-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.member-tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    z-index: 20;
}

.member-avatar:hover .member-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-4px);
}

.more-members {
    background: #e2e8f0;
    color: #64748b;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.task-comments {
    border-top: 1px solid #e5e7eb;
    padding-top: 0.75rem;
}

.comments-label {
    font-size: 0.875rem;
    color: #6b7280;
    display: flex;
    align-items: center;
}

@media (max-width: 768px) {
    .modal-dialog {
        max-width: 95vw;
        margin: 10px;
    }

    .modal-header {
        padding: 1.5rem 1rem;
    }

    .title-section {
        gap: 0.75rem;
    }

    .title-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .modal-title {
        font-size: 1.5rem;
    }

    .completed-tasks-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        padding: 1rem;
    }

    .task-card {
        padding: 1rem;
        min-height: 160px;
    }

    .task-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }

    .task-title-section {
        width: 100%;
    }

    .back-task-btn {
        align-self: flex-end;
    }

    .task-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .completion-info,
    .comments-info {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}

@media (max-width: 480px) {
    .modal-header {
        padding: 1rem;
    }

    .title-section {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }

    .completed-tasks-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        padding: 1rem;
    }

    .task-card {
        min-height: 140px;
    }

    .task-title {
        font-size: 0.9rem;
    }

    .task-content p {
        font-size: 0.8rem;
    }

    .member-avatar {
        width: 24px;
        height: 24px;
    }

    .more-members {
        width: 24px;
        height: 24px;
        font-size: 0.7rem;
    }
}

/* Ensure SweetAlert2 notifications appear above modal */
:deep(.swal2-container) {
    z-index: 999 !important;
}

:deep(.swal2-popup) {
    z-index: 999 !important;
}
</style>