<template>
    <apexchart height="155" type="pie" :options="options" :series="series"></apexchart>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
    name: 'ApexDonut',
    props: {
        task: { type: Array, required: true },
        columnNames: { type: Array, default: () => ['pending', 'completed'] },
        columnColors: { type: Array, default: () => ['#f59e0b', '#10b981'] },
    },
    data() {
        return {
            options: {
                title: {
                    text: '',
                    align: 'left',
                },
                chart: {
                    id: 'apex-donut',
                },
                labels: this.$props.columnNames,
                colors: this.$props.columnColors,
                markers: {
                    size: 5,
                    hover: {
                        sizeOffset: 6,
                    },
                },
            },
            series: this.$props.task,
        };
    },
    computed: {
        // Computed property để reactive với prop changes
        chartSeries() {
            return this.task;
        }
    },
    watch: {
        // Watch prop changes để update chart
        task: {
            handler(newTasks) {
                this.series = newTasks;
            },
            deep: true,
            immediate: true
        },
        columnNames: {
            handler(newNames) {
                this.options.labels = newNames;
            },
            deep: true,
            immediate: true
        },
        columnColors: {
            handler(newColors) {
                this.options.colors = newColors;
            },
            deep: true,
            immediate: true
        }
    },
    mounted() {
        // Initialize series
        this.series = this.task;
    }
});
</script>