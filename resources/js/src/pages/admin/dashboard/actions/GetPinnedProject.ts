import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";

type pinnedProject = { id: number, name: string }
export type pinnedProjectType = {
    data: pinnedProject
}

export function useGetPinnedProject() {
    const project = ref<pinnedProject>({} as pinnedProject)
    async function getPinnedProject() {
        try {
            const { data } = await makeHttpReq<undefined, pinnedProjectType>(`pinned/projects`, 'GET')
            project.value = data
        } catch (error) {
            showErrorResponse(error)
        }
    }
    return { getPinnedProject, project }
}