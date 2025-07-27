<template>
    <apexchart height="155" type="pie" :options="options" :series="series"></apexchart>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
    name: 'ApexDonut',
    props: {
        task: { type: Array, required: true },
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
                labels: ['pending', 'completed'],
                colors: [
                    '#f59e0b', // pending - orange
                    '#10b981', // completed - green
                ],
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
        }
    },
    mounted() {
        // Initialize series
        this.series = this.task;
    }
});
</script>