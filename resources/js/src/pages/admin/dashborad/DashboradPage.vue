<script lang="ts" setup>
import { onMounted } from 'vue';
import { useGetPinnedProject } from './actions/GetPinnedProject';
import ApexDonut from './components/ApexDonut.vue';
import ApexRadialBar from './components/ApexRadialBar.vue';
import { useGetCountProject } from './actions/GetCountProject';

const { project, getPinnedProject } = useGetPinnedProject()
const { countProject, getCountProject } = useGetCountProject()
onMounted(async () => {
    await getPinnedProject(), getCountProject()
    console.log(getPinnedProject);
})
</script>
<template>
    <div class="row">
        <h2>Dashboard</h2>
        <br />
        <br />
        <br />
        <div class="row">
            <div class="col-md-8">
                <h3 style="color: rgb(118, 119, 120)">
                    Project :{{ project?.name }}
                </h3>
            </div>
        </div>
        <br /><br />
        <div class="row">
            <div class="col-md-4 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <b>Total Projects</b>
                    </div>
                    <div class="card-body">
                        <br />
                        <br />

                        <h2 align="center">{{ countProject?.count }}</h2>
                        <br />
                        <br />
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="card">
                    <div class="card-header"><b>Tasks</b></div>
                    <div class="card-body">
                        <ApexDonut :task="[40, 60]" />
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <b>Task Progress</b>
                    </div>

                    <div class="card-body">
                        <ApexRadialBar :percent=70 />

                        <!-- <div v-if="chartData.progress > 0">
                            <ApexRadialBar :percent="chartData.progress" />
                        </div>
                        <div v-else>
                            <ApexRadialBar :percent="chartData.progress" />
                        </div>
                        <br /> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>