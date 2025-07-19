<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import { useGetPinnedProject } from './actions/GetPinnedProject';
import ApexDonut from './components/ApexDonut.vue';
import ApexRadialBar from './components/ApexRadialBar.vue';
import { useGetTotalProject } from './actions/countProject';
import { useGetChartData } from './actions/getChartData';
import LoadingPage from '../../../components/LoadingPage.vue';

const { project, getPinnedProject } = useGetPinnedProject()
const { countProject, getTotalProject } = useGetTotalProject()
const { chartData, getChartData } = useGetChartData();
const isLoading = ref(true);

onMounted(async () => {
    isLoading.value = true;
    await getPinnedProject();
    getTotalProject();
    if (project.value && project.value.id) {
        await getChartData(project.value.id);
    }
    isLoading.value = false;

})
</script>

<style scoped>
.dashboard-container {
    display: flex;
    flex-direction: column;
    padding: 2rem 1rem;
}

.dashboard-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 1rem;
    text-align: center;
}

.dashboard-row {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    justify-content: center;
}

.dashboard-card {
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
    padding: 2rem 1.5rem;
    min-width: 260px;
    flex: 1 1 300px;
    max-width: 370px;
    transition: box-shadow 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.dashboard-card:hover {
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.13);
}

.card-header {
    font-size: 1.2rem;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 1rem;
    text-align: center;
}

.card-body {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.dashboard-project-title {
    color: #6c757d;
    font-size: 1.3rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    text-align: center;
}

.dashboard-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #3182ce;
    text-align: center;
}

.priority-project-container {
    background: #f8fafc;
    border-radius: 1.2rem;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
    padding: 2rem 1.5rem;
    margin-top: 2rem;
    margin-bottom: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.priority-project-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #2563eb;
    margin-bottom: 1.5rem;
    text-align: center;
    letter-spacing: 0.5px;
}

.priority-project-row {
    display: flex;
    gap: 2rem;
    justify-content: center;
    width: 100%;
}

.dashboard-main-row {
    display: flex;
    flex-direction: row;
    gap: 2rem;
    justify-content: center;
    align-items: center;
    margin-bottom: 2rem;
}

.dashboard-main-col {
    flex: 1 1 180px;
    min-width: 180px;
    max-width: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.total-projects-card {
    width: 180px;
    height: 180px;
    border-radius: 1.2rem;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 1.5rem;
}

.dashboard-main-col .dashboard-card {
    width: 220px;
    height: 220px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

@media (max-width: 900px) {
    .dashboard-row {
        flex-direction: column;
        gap: 1.5rem;
    }

    .dashboard-card {
        max-width: 100%;
        min-width: 0;
    }

    .priority-project-row {
        flex-direction: column;
        gap: 1.5rem;
    }

    .dashboard-main-row {
        flex-direction: column;
        gap: 1.5rem;
    }

    .priority-project-container {
        max-width: 100%;
        min-width: 0;
    }

    .dashboard-main-col,
    .total-projects-card {
        min-width: 0;
        max-width: 100%;
        width: 50vw;
        height: 50vw;
        max-width: 320px;
        max-height: 320px;
        min-width: 140px;
        min-height: 140px;
        margin-left: auto;
        margin-right: auto;
    }
}
</style>

<template>
    <div class="dashboard-container">
        <LoadingPage v-if="isLoading" />
        <h2 class="dashboard-title">Dashboard</h2>
        <div class="dashboard-main-row">
            <div class="dashboard-main-col">
                <div class="total-projects-card">
                    <div class="card-header">
                        <b>Total Projects</b>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-number">{{ countProject?.count }}</div>
                    </div>
                </div>
            </div>
            <template v-if="project?.name && project.name.trim() !== ''">
                <div class="priority-project-container">
                    <div class="priority-project-title">
                        Your priority project: {{ project?.name }}
                    </div>
                    <div class="priority-project-row">
                        <div class="dashboard-card">
                            <div class="card-header"><b>Tasks</b></div>
                            <div class="card-body">
                                <div v-if="chartData.tasks">
                                    <ApexDonut :task="chartData.tasks" />
                                </div>
                                <div v-else>
                                    <ApexDonut :task="[0, 0]" />
                                </div>
                            </div>
                        </div>
                        <div class="dashboard-card">
                            <div class="card-header">
                                <b>Task Progress</b>
                            </div>
                            <div class="card-body">
                                <div v-if="chartData.progress > 0">
                                    <ApexRadialBar :percent="chartData.progress" />
                                </div>
                                <div v-else>
                                    <ApexRadialBar :percent="0" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>