import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";

type countProjectType = { count: number }

export function useGetCountProject() {
    const countProject = ref<countProjectType>({} as countProjectType)
    async function getCountProject() {
        try {
            const data = await makeHttpReq<undefined, countProjectType>(`count/projects`, 'GET')
            countProject.value = data
        } catch (error) {
            showErrorResponse(error)
        }
    }
    return { getCountProject, countProject }
}