<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BreadCrumb from './components/BreadCrumb.vue';
import { useGetProjectDetail } from './actions/getProjectDetail';
import ProjectDetail from './components/ProjectData.vue';
import ProjectProgress from './components/ProjectProgress.vue';
import PendingColumn from './components/PendingColumn.vue';
import CompletedColumn from './components/CompletedColumn.vue';
import AddTaskModal from './components/AddTaskModal.vue';
import { useGetMembers } from '../member/actions/getMember';
import { taskStore } from './store/kabanStore';
import NotStartedColumn from './components/NotStartedColumn.vue';
import { useDragTask } from './actions/dragTask';
import LoadingPage from '../../../components/LoadingPage.vue';

const route = useRoute();
const router = useRouter();

const { ProjectData, getProjectDetail, loading: projectLoading } = useGetProjectDetail();
const { getMembers, loading, memberData } = useGetMembers();

const slug = route.query?.query as string;
const modalVisible = ref(false);

onMounted(async () => {
    await getProjectDetail(slug);
    getMembers(1, '');
    setTimeout(() => {
        setupAllDropListeners();
    }, 100);
});

async function openTaskModal() {
    const projectId = ProjectData.value?.data?.id;
    if (!projectId) return;
    taskStore.taskInput.projectId = projectId;
    taskStore.taskInput.memberIds = [];
    modalVisible.value = true;
}

function closeTaskModal() {
    modalVisible.value = false;
}

function goBackToProjects() {
    router.push('/projects');
}

function formatDate(dateString: string | undefined): string {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

const { setupAllDropListeners, setupTaskCardDragListeners } = useDragTask(getProjectDetail, slug, ProjectData);

watch(() => ProjectData.value?.data?.tasks, () => {
    setTimeout(() => {
        setupTaskCardDragListeners();
    }, 100);
}, { deep: true });
</script>

<template>
    <div class="kanban-container">
        <!-- Loading Page -->
        <LoadingPage :visible="projectLoading" />

        <!-- Header Section -->
        <div class="kanban-header">
            <div class="header-content">
                <div class="back-section">
                    <button @click="goBackToProjects" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                </div>
                <div class="title-section">
                    <h1 class="project-title">
                        <i class="fas fa-columns"></i>
                        {{ ProjectData?.data?.name || 'Project Management' }}
                    </h1>
                </div>
                <div class="dates-section project-dates-desktop">
                    <div class="project-dates">
                        <div class="date-card">
                            <div class="date-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="date-content">
                                <div class="date-label">Start Date</div>
                                <div class="date-value">{{ formatDate(ProjectData?.data?.startDate) }}</div>
                            </div>
                        </div>
                        <div class="date-divider"></div>
                        <div class="date-card">
                            <div class="date-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="date-content">
                                <div class="date-label">End Date</div>
                                <div class="date-value">{{ formatDate(ProjectData?.data?.endDate) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="kanban-content">
            <!-- Project Progress Section -->
            <div class="progress-section">
                <div class="project-dates-mobile">
                    <div class="project-dates">
                        <div class="date-card">
                            <div class="date-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="date-content">
                                <div class="date-label">Start Date</div>
                                <div class="date-value">{{ formatDate(ProjectData?.data?.startDate) }}</div>
                            </div>
                        </div>
                        <div class="date-divider"></div>
                        <div class="date-card">
                            <div class="date-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="date-content">
                                <div class="date-label">End Date</div>
                                <div class="date-value">{{ formatDate(ProjectData?.data?.endDate) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <ProjectProgress :ProjectData="ProjectData" />
            </div>

            <!-- Kanban Board Section -->
            <div class="kanban-board">
                <div class="kanban-columns">
                    <NotStartedColumn :projectData="ProjectData" @openTaskModal="openTaskModal"
                        class="kanban-column not-started-column" />
                    <PendingColumn :projectData="ProjectData" @openTaskModal="openTaskModal"
                        class="kanban-column pending-column" />
                    <CompletedColumn :projectData="ProjectData" @openTaskModal="openTaskModal"
                        class="kanban-column completed-column" />
                </div>
            </div>
        </div>

        <!-- Add Task Modal -->
        <AddTaskModal :members="memberData" :visible="modalVisible" @getMembers="getMembers"
            @closeModal="closeTaskModal" />
    </div>
</template>

<style scoped>
/* Main Container */
.kanban-container {
    height: 100vh;
    background: white;
    padding: 24px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    max-width: 100vw;
    box-sizing: border-box;
    position: relative;
}

/* Loading Page Overlay */
:deep(.loading-overlay) {
    position: absolute;
    z-index: 9999;
    inset: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(4px);
    border-radius: 16px;
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

:deep(.loader) {
    min-height: 100%;
}

/* Header Section */
.kanban-header {
    background: white;
    border-radius: 16px;
    padding: 16px 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
    max-width: 100%;
    box-sizing: border-box;
}

.header-content {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 24px;
    max-width: 100%;
    box-sizing: border-box;
}

.back-section {
    display: flex;
    justify-content: flex-start;
}

.back-btn {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 1px solid #e2e8f0;
    color: #64748b;
    padding: 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 44px;
    flex-shrink: 0;
}

.back-btn:hover {
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    border-color: #cbd5e1;
    color: #374151;
    transform: translateX(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.back-btn i {
    font-size: 16px;
}

.title-section {
    display: flex;
    justify-content: center;
    align-items: center;
    min-width: 0;
}

.project-title {
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    line-height: 1.2;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.project-title i {
    color: #3b82f6;
    font-size: 28px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    flex-shrink: 0;
}

.dates-section {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    flex-shrink: 0;
}

.project-dates {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    flex-wrap: nowrap;
}

.date-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    min-width: 120px;
    max-width: 150px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    flex-shrink: 0;
}

.date-card:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}

.date-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 8px;
    color: white;
    font-size: 10px;
    flex-shrink: 0;
}

.date-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.date-label {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.date-value {
    font-size: 12px;
    color: #1e293b;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
}

.date-divider {
    width: 1px;
    height: 32px;
    background: linear-gradient(180deg, transparent 0%, #e2e8f0 50%, transparent 100%);
    margin: 0 4px;
    flex-shrink: 0;
}

/* Content Section */
.kanban-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 8px;
    min-height: 0;
    max-width: 100%;
    box-sizing: border-box;
}

/* Progress Section */
.progress-section {
    padding: 0 0 24px 0;
    max-width: 100%;
    box-sizing: border-box;
}

/* Kanban Board */
.kanban-board {
    padding: 0;
    max-width: 100%;
    box-sizing: border-box;
}

.kanban-columns {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    min-height: 500px;
    max-width: 100%;
    box-sizing: border-box;
}

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

.kanban-column:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
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

/* Drag and Drop Effects */
.kanban-column.hovered {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 2px dashed #3b82f6;
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.task-card.dragging {
    opacity: 0.6;
    transform: scale(1.01);
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    position: relative;
    top: auto;
    left: auto;
}

.ghost-task {
    background: #fbbf24 !important;
    border: 2px solid #f59e0b !important;
    opacity: 1 !important;
    z-index: 99999 !important;
    position: fixed;
    pointer-events: none;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    max-width: 300px;
    transition: none;
    will-change: transform, left, top;
}

.ghost-task .task-title {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 8px 0;
}

.ghost-task .task-members {
    display: flex;
    align-items: center;
    gap: 4px;
}

.ghost-task .member-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    font-weight: 600;
}

/* ----------- PROJECT DATES MOBILE OPTIMIZED ----------- */
@media (max-width: 768px) {
    .kanban-columns {
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-height: auto;
        overflow: hidden;
    }

    .kanban-column {
        min-height: 350px;
        max-height: 400px;
        margin-top: 10px;
        margin-bottom: 10px;
        padding-top: 16px;
        padding-bottom: 16px;
    }

    .dates-section.project-dates-desktop {
        display: none !important;
    }

    .project-dates-mobile {
        display: block !important;
        margin-bottom: 16px;
        font-size: 1.15rem;
    }

    .project-dates-mobile .project-dates {
        gap: 10px;
        padding: 10px 0;
    }

    .project-dates-mobile .date-card {
        padding: 10px 16px;
        gap: 8px;
        border-radius: 12px;
        max-width: 140px;
        min-width: 90px;
    }

    .project-dates-mobile .date-icon {
        width: 24px;
        height: 24px;
        font-size: 14px;
        border-radius: 8px;
    }

    .project-dates-mobile .date-label {
        font-size: 12px;
    }

    .project-dates-mobile .date-value {
        font-size: 15px;
    }
}

@media (min-width: 769px) {
    .project-dates-mobile {
        display: none !important;
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .kanban-columns {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .kanban-container {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .kanban-container {
        padding: 8px;
        overflow-x: hidden;
    }

    .kanban-header {
        padding: 10px 12px;
        margin-bottom: 12px;
        border-radius: 10px;
        overflow: hidden;
    }

    .header-content {
        gap: 8px;
        overflow: hidden;
    }

    .back-btn {
        padding: 8px;
        min-width: 36px;
        height: 36px;
        border-radius: 8px;
    }

    .back-btn i {
        font-size: 12px;
    }

    .project-title {
        font-size: 16px;
        gap: 6px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .project-title i {
        font-size: 14px;
    }

    .project-dates {
        gap: 4px;
        overflow: hidden;
    }

    .date-card {
        padding: 4px 6px;
        gap: 3px;
        border-radius: 6px;
        max-width: 80px;
    }

    .date-icon {
        width: 14px;
        height: 14px;
        font-size: 6px;
        border-radius: 3px;
    }

    .date-label {
        font-size: 6px;
    }

    .date-value {
        font-size: 9px;
    }
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
</style>