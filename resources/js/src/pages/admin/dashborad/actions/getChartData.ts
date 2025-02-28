import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";

type chartDataType = {
    tasks: Array<number>
    progress: number
}

export function useGetChartData() {
    const chartData = ref<chartDataType>({} as chartDataType)
    async function getChartData(projectsId: number) {
        try {
            const data = await makeHttpReq<undefined, chartDataType>(`chart-data/projects?projectId=${projectsId}`, 'GET')
            chartData.value = data
            updateData()
            // console.log(chartData.value);

        } catch (error) {
            showErrorResponse(error)
        }
    }

    function updateData() {
        window.Echo.channel('channel-project-progress').listen('TrackProjectProgress',
            (e: { projectProgress: number }) => {
                chartData.value.progress=0
                setTimeout(()=>chartData.value.progress=e.projectProgress,1000)
            }
        );

        window.Echo.channel('channel-tasks-project').listen('TrackCompletedAndPending',
            (e: { tasks:Array<number> }) => {
                console.log(e);
                
                chartData.value.tasks=undefined as any
                setTimeout(()=>chartData.value.tasks=e.tasks,1000)
            }
        );
    }
    return { chartData, getChartData };
}