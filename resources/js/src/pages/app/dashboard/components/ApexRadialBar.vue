<template>
    <div :id="chartId" class="apex-chart-container"></div>
</template>

<script lang="ts">
import { defineComponent, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import ApexCharts from 'apexcharts';

export default defineComponent({
    name: 'ApexRadialBar',
    props: {
        percent: {
            type: Number,
            required: true,
        },
    },
    setup(props) {
        const chartId = `chart-radial-${Math.random().toString(36).substr(2, 9)}`;
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
                    series: [props.percent],
                    chart: {
                        height: 230,
                        type: 'radialBar',
                        offsetY: -20,
                        sparkline: {
                            enabled: true,
                        },
                    },
                    colors: ['#3b82f6'],
                    plotOptions: {
                        radialBar: {
                            startAngle: -90,
                            endAngle: 90,
                            track: {
                                background: '#e7e7e7',
                                strokeWidth: props.percent + '%',
                                margin: 5,
                                dropShadow: {
                                    enabled: true,
                                    top: 2,
                                    left: 0,
                                    color: '#999',
                                    opacity: 1,
                                    blur: 2,
                                },
                            },
                            dataLabels: {
                                name: {
                                    show: false,
                                },
                                value: {
                                    offsetY: -2,
                                    fontSize: '30px',
                                },
                            },
                        },
                    },
                    grid: {
                        padding: {},
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shade: 'light',
                            shadeIntensity: 0.4,
                            inverseColors: false,
                            opacityFrom: 1,
                            opacityTo: 1,
                            stops: [0, 50, 53, 91],
                        },
                    },
                    labels: ['Average Results'],
                };

                try {
                    chart = new ApexCharts(element, options);
                    chart.render();
                } catch (error) {
                    console.error('Failed to create chart:', error);
                }
            });
        };

        // Watch for percent changes - recreate chart for reliable updates
        watch(
            () => props.percent,
            () => {
                // Always recreate chart when data changes
                // This ensures proper rendering and avoids updateOptions issues
                createChart();
            }
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
    min-height: 230px;
}
</style>
