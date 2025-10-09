<template>
    <div :id="chartId" class="apex-chart-container" :style="{ minHeight: `${height}px` }"></div>
</template>

<script lang="ts">
import { defineComponent, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import ApexCharts from 'apexcharts';

/**
 * Base ApexChart Component
 * 
 * Tối ưu chart rendering với lifecycle management
 * Tránh memory leaks và performance issues
 */
export default defineComponent({
    name: 'ApexChartBase',
    props: {
        options: {
            type: Object,
            required: true,
        },
        height: {
            type: Number,
            default: 200,
        },
    },
    setup(props) {
        const chartId = `apex-chart-${Math.random().toString(36).substr(2, 9)}`;
        let chart: ApexCharts | null = null;
        let isDestroyed = false;

        /**
         * Destroy chart safely
         */
        const destroyChart = () => {
            if (chart && !isDestroyed) {
                try {
                    chart.destroy();
                    isDestroyed = true;
                } catch (e) {
                    // Silent catch
                }
                chart = null;
            }
        };

        /**
         * Create chart
         */
        const createChart = () => {
            destroyChart();
            isDestroyed = false;

            nextTick(() => {
                const element = document.getElementById(chartId);
                if (!element) {
                    console.warn('Chart element not found:', chartId);
                    return;
                }

                try {
                    chart = new ApexCharts(element, props.options);
                    chart.render();
                } catch (error) {
                    console.error('Failed to create chart:', error);
                }
            });
        };

        /**
         * Update chart
         */
        const updateChart = () => {
            if (!chart || isDestroyed) {
                createChart();
                return;
            }

            try {
                chart.updateOptions(props.options, true, true);
            } catch (error) {
                // If update fails, recreate chart
                console.warn('Chart update failed, recreating...', error);
                createChart();
            }
        };

        // Watch for options changes
        watch(
            () => props.options,
            () => {
                updateChart();
            },
            { deep: true }
        );

        onMounted(() => {
            createChart();
        });

        onBeforeUnmount(() => {
            destroyChart();
        });

        return {
            chartId,
        };
    },
});
</script>

<style scoped>
.apex-chart-container {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

