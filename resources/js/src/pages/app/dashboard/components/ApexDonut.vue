<template>
    <ApexChartBase :options="chartOptions" :height="200" />
</template>

<script lang="ts">
import { defineComponent, computed } from 'vue';
import ApexChartBase from './ApexChartBase.vue';

/**
 * ApexDonut Component - Optimized
 * 
 * Sử dụng Base component để tái sử dụng logic
 * Tối ưu với computed properties
 */
export default defineComponent({
    name: 'ApexDonut',
    components: {
        ApexChartBase,
    },
    props: {
        task: { 
            type: Array, 
            required: true 
        },
        columnNames: { 
            type: Array, 
            default: () => ['Pending', 'Completed'] 
        },
        columnColors: { 
            type: Array, 
            default: () => ['#f59e0b', '#10b981'] 
        },
    },
    setup(props) {
        const chartOptions = computed(() => ({
            series: props.task as number[],
            chart: {
                type: 'donut',
                height: 200,
                fontFamily: 'Inter, sans-serif',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                },
            },
            labels: props.columnNames as string[],
            colors: props.columnColors as string[],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '14px',
                                fontWeight: 600,
                            },
                        },
                    },
                },
            },
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '12px',
                    fontWeight: 600,
                },
            },
            legend: {
                position: 'bottom',
                fontSize: '13px',
                fontWeight: 500,
                markers: {
                    width: 12,
                    height: 12,
                    radius: 3,
                },
            },
            tooltip: {
                enabled: true,
                y: {
                    formatter: (val: number) => `${val} tasks`,
                },
            },
        }));

        return {
            chartOptions,
        };
    },
});
</script>

