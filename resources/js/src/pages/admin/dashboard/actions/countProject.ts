import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";

type countProjectType = { count: number }

export function useGetTotalProject() {
    const countProject = ref<countProjectType>({} as countProjectType)
    async function getTotalProject() {
        try {
            const data = await makeHttpReq<undefined, countProjectType>(`count/projects`, 'GET')
            countProject.value = data
            updateData()
        } catch (error) {
            showErrorResponse(error)
        }
    }

    function updateData() {
        window.Echo.channel('countProject').listen('NewProjectCreated',
            (e: { countProject: number }) => {
                countProject.value = { count: e.countProject }
            }
        );
    }
    return { countProject, getTotalProject };
}