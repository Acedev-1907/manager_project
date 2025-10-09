<template>
    <div :id="chartId" class="apex-chart-container"></div>
</template>

<script lang="ts">
import { defineComponent, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import ApexCharts from 'apexcharts';

export default defineComponent({
    name: 'ApexDonut',
    props: {
        task: { type: Array, required: true },
        columnNames: { type: Array, default: () => ['pending', 'completed'] },
        columnColors: { type: Array, default: () => ['#f59e0b', '#10b981'] },
    },
    setup(props) {
        const chartId = `chart-donut-${Math.random().toString(36).substr(2, 9)}`;
        let chart: ApexCharts | null = null;

        const createChart = () => {
            // Destroy existing chart if any
            if (chart) {
                try {
                    chart.destroy();
                } catch (e) {
                    // Silent catch
                }
                chart = null;
            }

            // Wait for DOM to be ready
            nextTick(() => {
                const element = document.getElementById(chartId);
                if (!element) {
                    console.warn('Chart element not found:', chartId);
                    return;
                }

                const options = {
                    series: props.task as number[],
                    chart: {
                        type: 'pie',
                        height: 155,
                    },
                    labels: props.columnNames as string[],
                    colors: props.columnColors as string[],
                    markers: {
                        size: 5,
                        hover: {
                            sizeOffset: 6,
                        },
                    },
                    legend: {
                        position: 'bottom',
                    },
                };

                try {
                    chart = new ApexCharts(element, options);
                    chart.render();
                } catch (error) {
                    console.error('Failed to create chart:', error);
                }
            });
        };

        // Watch for prop changes - recreate chart for reliable updates
        watch(
            () => [props.task, props.columnNames, props.columnColors],
            () => {
                // Always recreate chart when data changes
                // This ensures proper rendering and avoids updateOptions issues
                createChart();
            },
            { deep: true }
        );

        onMounted(() => {
            createChart();
        });

        onBeforeUnmount(() => {
            if (chart) {
                try {
                    chart.destroy();
                } catch (e) {
                    // Silent catch
                }
                chart = null;
            }
        });

        return {
            chartId,
        };
    },
});
</script>

<style scoped>
.apex-chart-container {
    min-height: 155px;
}
</style>
