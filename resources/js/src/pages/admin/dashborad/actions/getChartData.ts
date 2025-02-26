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
            // updateData()
            console.log(chartData.value);

        } catch (error) {
            showErrorResponse(error)
        }
    }

    // function updateData() {
    //     window.Echo.channel('chartData').listen('NewProjectCreated',
    //         (e: { countProject: number }) => {
    //             // console.log(e);
    //             chartData.value = { count: e.countProject }
    //         }
    //     );
    // }
    return { chartData, getChartData };
}