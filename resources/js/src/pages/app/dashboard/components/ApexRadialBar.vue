<template>
    <ApexChartBase :options="chartOptions" :height="230" />
</template>

<script lang="ts">
import { defineComponent, computed } from 'vue';
import ApexChartBase from './ApexChartBase.vue';

/**
 * ApexRadialBar Component - Optimized
 * 
 * Sử dụng Base component để tái sử dụng logic
 * Tối ưu với computed properties
 */
export default defineComponent({
    name: 'ApexRadialBar',
    components: {
        ApexChartBase,
    },
    props: {
        percent: {
            type: Number,
            required: true,
        },
    },
    setup(props) {
        const getGradientColor = (percent: number) => {
            if (percent >= 80) return ['#10b981', '#059669']; // Green
            if (percent >= 50) return ['#3b82f6', '#2563eb']; // Blue
            if (percent >= 25) return ['#f59e0b', '#d97706']; // Orange
            return ['#ef4444', '#dc2626']; // Red
        };

        const chartOptions = computed(() => {
            const colors = getGradientColor(props.percent);
            
            return {
                series: [props.percent],
                chart: {
                    height: 230,
                    type: 'radialBar',
                    fontFamily: 'Inter, sans-serif',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                    },
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -135,
                        endAngle: 135,
                        hollow: {
                            size: '65%',
                        },
                        track: {
                            background: '#f1f5f9',
                            strokeWidth: '100%',
                            margin: 8,
                            dropShadow: {
                                enabled: true,
                                top: 2,
                                left: 0,
                                color: '#94a3b8',
                                opacity: 0.15,
                                blur: 4,
                            },
                        },
                        dataLabels: {
                            show: true,
                            name: {
                                offsetY: -10,
                                show: true,
                                color: '#64748b',
                                fontSize: '14px',
                                fontWeight: 500,
                            },
                            value: {
                                offsetY: 5,
                                color: '#1e293b',
                                fontSize: '32px',
                                fontWeight: 700,
                                show: true,
                                formatter: (val: number) => `${Math.round(val)}%`,
                            },
                        },
                    },
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        type: 'horizontal',
                        shadeIntensity: 0.5,
                        gradientToColors: [colors[1]],
                        inverseColors: false,
                        opacityFrom: 1,
                        opacityTo: 1,
                        stops: [0, 100],
                    },
                },
                colors: [colors[0]],
                labels: ['Progress'],
            };
        });

        return {
            chartOptions,
        };
    },
});
</script>

