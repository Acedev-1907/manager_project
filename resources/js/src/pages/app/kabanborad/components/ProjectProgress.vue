<script lang="ts" setup>
import { computed } from 'vue';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';

const props = defineProps<{
    ProjectData: SingleProjectResponseType
}>();

// Compute task statistics from tasks array
const taskStats = computed(() => {
    const tasks = props.ProjectData?.data?.tasks || [];
    const totalTasks = tasks.length;
    const completedTasks = tasks.filter(task => String(task.status) === 'OK').length;
    const pendingTasks = tasks.filter(task => Number(task.status) === 1).length;
    const notStartedTasks = tasks.filter(task => Number(task.status) === 0).length;
    const progress = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;
    return {
        total: totalTasks,
        completed: completedTasks,
        pending: pendingTasks,
        notStarted: notStartedTasks,
        progress
    };
});

const progressPercentage = computed(() => {
    const progress = props.ProjectData?.data?.task_progress?.progress;
    if (progress && !isNaN(Number(progress))) {
        return Number(progress);
    }
    return taskStats.value.progress;
});
</script>

<template>
    <div class="progress-container">
        <div class="progress-header">
            <div class="progress-title">
                <i class="fas fa-chart-line"></i>
                <span>Project Progress</span>
            </div>
            <div class="progress-percentage">
                {{ progressPercentage }}%
            </div>
        </div>

        <div class="progress-wrapper">
            <div class="progress-bar-container">
                <div class="progress-bar" :style="{ width: progressPercentage + '%' }"
                    :class="{ 'animate-progress': progressPercentage > 0 }">
                    <div class="progress-fill"></div>
                    <div class="progress-shimmer"></div>
                </div>

                <!-- Floating Progress Indicator -->
                <div class="progress-indicator" :style="{ left: `calc(${progressPercentage}% - 12px)` }"
                    :class="{ 'show-indicator': progressPercentage > 0 }">
                    <div class="indicator-pulse"></div>
                    <div class="indicator-value">{{ progressPercentage }}%</div>
                </div>
            </div>

            <!-- Progress Milestones -->
            <div class="progress-milestones">
                <div class="milestone" :class="{ 'active': progressPercentage >= 25 }">
                    <div class="milestone-dot"></div>
                    <span class="milestone-label">25%</span>
                </div>
                <div class="milestone" :class="{ 'active': progressPercentage >= 50 }">
                    <div class="milestone-dot"></div>
                    <span class="milestone-label">50%</span>
                </div>
                <div class="milestone" :class="{ 'active': progressPercentage >= 75 }">
                    <div class="milestone-dot"></div>
                    <span class="milestone-label">75%</span>
                </div>
                <div class="milestone" :class="{ 'active': progressPercentage >= 100 }">
                    <div class="milestone-dot"></div>
                    <span class="milestone-label">100%</span>
                </div>
            </div>
        </div>

        <!-- Task Statistics -->
        <div class="task-stats">
            <div class="stat-item">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ taskStats.completed }}</div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ taskStats.pending }}</div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon not-started">
                    <i class="fas fa-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ taskStats.notStarted }}</div>
                    <div class="stat-label">Not Started</div>
                </div>
            </div>
            <div class="stat-item total">
                <div class="stat-icon total">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ taskStats.total }}</div>
                    <div class="stat-label">Total Tasks</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.progress-container {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.progress-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.progress-title i {
    color: #3b82f6;
    font-size: 16px;
}

.progress-percentage {
    font-size: 24px;
    font-weight: 800;
    color: #3b82f6;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.progress-wrapper {
    position: relative;
    margin-bottom: 24px;
}

.progress-bar-container {
    position: relative;
    height: 12px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 6px;
    overflow: visible;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-top: 8px;
    margin-bottom: 8px;
}

.progress-bar {
    position: relative;
    height: 100%;
    background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 50%, #3b82f6 100%);
    border-radius: 6px;
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: visible;
}

.progress-fill {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, #10b981 0%, #059669 50%, #10b981 100%);
    border-radius: 6px;
}

.progress-shimmer {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.4) 50%, transparent 100%);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% {
        left: -100%;
    }

    100% {
        left: 100%;
    }
}

.animate-progress .progress-fill {
    animation: progressGlow 2s ease-in-out infinite alternate;
}

@keyframes progressGlow {
    0% {
        box-shadow: 0 0 5px rgba(16, 185, 129, 0.3);
    }

    100% {
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.6);
    }
}

/* Floating Progress Indicator */
.progress-indicator {
    position: absolute;
    top: -8px;
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    transition: all 0.3s ease;
    opacity: 1;
    transform: scale(1);
    z-index: 10;
}

.progress-indicator.show-indicator {
    opacity: 1;
    transform: scale(1);
}

.indicator-pulse {
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    border-radius: 50%;
    background: rgba(16, 185, 129, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }

    100% {
        transform: scale(2);
        opacity: 0;
    }
}

.indicator-value {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
    opacity: 1;
    transition: opacity 0.3s ease;
    z-index: 15;
}

.progress-indicator:hover .indicator-value {
    opacity: 1;
}

/* Progress Milestones */
.progress-milestones {
    display: flex;
    justify-content: space-between;
    margin-top: 16px;
    padding: 0 6px;
}

.milestone {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    transition: all 0.3s ease;
}

.milestone-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    border: 2px solid #f1f5f9;
    transition: all 0.3s ease;
}

.milestone.active .milestone-dot {
    background: #10b981;
    border-color: #d1fae5;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.4);
}

.milestone-label {
    font-size: 10px;
    color: #64748b;
    font-weight: 500;
    transition: color 0.3s ease;
}

.milestone.active .milestone-label {
    color: #10b981;
    font-weight: 600;
}

/* Task Statistics */
.task-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: white;
}

.stat-icon.completed {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.pending {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.not-started {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
}

.stat-icon.total {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}

.stat-label {
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
    margin-top: 2px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .progress-container {
        padding: 16px;
        border-radius: 12px;
    }

    .progress-header {
        margin-bottom: 16px;
    }

    .progress-title {
        font-size: 16px;
    }

    .progress-percentage {
        font-size: 20px;
    }

    .task-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .stat-item {
        padding: 10px;
    }

    .stat-icon {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }

    .stat-value {
        font-size: 16px;
    }

    .stat-label {
        font-size: 10px;
    }
}

@media (max-width: 480px) {
    .progress-container {
        padding: 12px;
    }

    .progress-title {
        font-size: 14px;
    }

    .progress-percentage {
        font-size: 18px;
    }

    .task-stats {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .stat-item {
        padding: 8px;
    }

    .stat-icon {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }

    .stat-value {
        font-size: 14px;
    }

    .stat-label {
        font-size: 9px;
    }
}
</style>