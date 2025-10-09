<script lang="ts" setup>
import { onMounted, computed } from 'vue';
import LoadingPage from '../../../components/LoadingPage.vue';
import DashboardStats from './components/DashboardStats.vue';
import ProjectCard from './components/ProjectCard.vue';
import { useDashboardData } from '../../../composables/useDashboardData';
import { useDashboardRealtime } from '../../../composables/useDashboardRealtime';
import { useGlobalRealtimeSetup } from '../../../helper/useGlobalRealtimeSetup';

// Define component name for keep-alive
defineOptions({
    name: 'DashboardPageOptimized'
});

// Composables
const {
    isLoading,
    project,
    chartRenderKey,
    hasValidProject,
    projectCount,
    loadPinnedProject,
    loadProjectCount,
    refreshDashboard,
} = useDashboardData();

const globalRealtime = useGlobalRealtimeSetup();

// Setup realtime với callback
useDashboardRealtime(
    refreshDashboard,
    () => project.value?.id
);

// Computed
const statsData = computed(() => [
    {
        title: 'Total Projects',
        value: projectCount.value,
        subtitle: 'Active projects',
        icon: 'fas fa-folder',
        iconColor: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
    {
        title: 'Active Tasks',
        value: project.value?.tasks?.reduce((a, b) => a + b, 0) || 0,
        subtitle: 'In current project',
        icon: 'fas fa-tasks',
        iconColor: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    },
    {
        title: 'Completion Rate',
        value: `${project.value?.progress || 0}%`,
        subtitle: 'Overall progress',
        icon: 'fas fa-chart-line',
        iconColor: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    },
]);

/**
 * Setup project listeners
 */
const setupProjectListeners = (projectData: any) => {
    if (!projectData?.id) return;

    globalRealtime.setupProjectListener(projectData.id);

    if (projectData?.tasks && Array.isArray(projectData.tasks)) {
        projectData.tasks.forEach((task: any) => {
            if (task.id) {
                globalRealtime.setupTaskListener(task.id);
            }
        });
    }
};

/**
 * Initialize dashboard
 */
const initializeDashboard = async () => {
    isLoading.value = true;
    
    try {
        // Load data in parallel
        await Promise.all([
            loadPinnedProject(),
            loadProjectCount(),
        ]);

        // Setup listeners if we have a project
        if (project.value?.id) {
            setupProjectListeners(project.value);
        }
    } catch (error) {
        console.error('Failed to initialize dashboard:', error);
    } finally {
        isLoading.value = false;
    }
};

// Lifecycle
onMounted(() => {
    initializeDashboard();
});
</script>

<template>
    <div class="dashboard-container">
        <LoadingPage :visible="isLoading" />
        
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <h1 class="dashboard-title">
                    <i class="fas fa-chart-pie"></i>
                    Dashboard Overview
                </h1>
                <p class="dashboard-subtitle">Welcome back! Here's what's happening with your projects.</p>
            </div>
        </header>

        <!-- Stats Grid -->
        <section class="stats-section">
            <div class="stats-grid">
                <DashboardStats
                    v-for="(stat, index) in statsData"
                    :key="index"
                    :title="stat.title"
                    :value="stat.value"
                    :subtitle="stat.subtitle"
                    :icon="stat.icon"
                    :iconColor="stat.iconColor"
                />
            </div>
        </section>

        <!-- Pinned Project -->
        <section v-if="hasValidProject" class="project-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-star"></i>
                    Priority Project
                </h2>
            </div>
            <ProjectCard 
                :project="project" 
                :renderKey="chartRenderKey"
            />
        </section>

        <!-- Empty State -->
        <section v-else class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-folder-open"></i>
            </div>
            <h3 class="empty-title">No Pinned Project</h3>
            <p class="empty-text">Pin a project to see its details and progress here.</p>
        </section>
    </div>
</template>

<style scoped>
.dashboard-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 32px;
}

.dashboard-header {
    margin-bottom: 32px;
}

.header-content {
    background: white;
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.dashboard-title {
    font-size: 32px;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.dashboard-title i {
    color: #667eea;
}

.dashboard-subtitle {
    font-size: 16px;
    color: #64748b;
    margin: 0;
}

.stats-section {
    margin-bottom: 32px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}

.project-section {
    margin-bottom: 32px;
}

.section-header {
    margin-bottom: 20px;
}

.section-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
}

.section-title i {
    color: #fbbf24;
}

.empty-state {
    background: white;
    border-radius: 20px;
    padding: 64px 32px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: white;
}

.empty-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 12px 0;
}

.empty-text {
    font-size: 16px;
    color: #64748b;
    margin: 0;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 16px;
    }

    .header-content {
        padding: 20px;
    }

    .dashboard-title {
        font-size: 24px;
    }

    .dashboard-subtitle {
        font-size: 14px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .section-title {
        font-size: 20px;
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        font-size: 32px;
    }

    .empty-title {
        font-size: 20px;
    }
}
</style>

