<template>
    <div class="project-card">
        <div class="project-header">
            <div class="project-icon">
                <i class="fas fa-project-diagram"></i>
            </div>
            <h3 class="project-name">{{ project.name }}</h3>
        </div>
        
        <div class="project-charts">
            <div class="chart-wrapper">
                <h4 class="chart-title">Task Distribution</h4>
                <ApexDonut 
                    :key="`donut-${renderKey}`"
                    :task="project.tasks || [0, 0]"
                    :columnNames="project.columnNames || ['Pending', 'Completed']"
                    :columnColors="project.columnColors || ['#f59e0b', '#10b981']" 
                />
            </div>
            
            <div class="chart-wrapper">
                <h4 class="chart-title">Progress</h4>
                <ApexRadialBar 
                    :key="`radial-${renderKey}`"
                    :percent="project.progress || 0" 
                />
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import ApexDonut from './ApexDonut.vue';
import ApexRadialBar from './ApexRadialBar.vue';

interface Props {
    project: {
        id?: number;
        name: string;
        tasks?: number[];
        columnNames?: string[];
        columnColors?: string[];
        progress?: number;
    };
    renderKey?: number;
}

withDefaults(defineProps<Props>(), {
    renderKey: 0,
});
</script>

<style scoped>
.project-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    color: white;
}

.project-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.project-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    backdrop-filter: blur(10px);
}

.project-name {
    font-size: 22px;
    font-weight: 700;
    margin: 0;
    flex: 1;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.project-charts {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.chart-wrapper {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 20px;
    backdrop-filter: blur(10px);
}

.chart-title {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 12px 0;
    text-align: center;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .project-charts {
        grid-template-columns: 1fr;
    }

    .project-card {
        padding: 20px;
    }

    .project-name {
        font-size: 18px;
    }
}
</style>

