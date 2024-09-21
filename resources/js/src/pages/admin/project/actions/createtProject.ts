import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showError, successMsg } from "../../../../helper/toast-notificaltion";
import { showErrorResponse } from "../../../../helper/utils";
import { projectStore } from "../store/projectStore";

export type ProjectInputType = {
    name: string
    startDate: string
    endDate: string
}
export type ProjectResponseType = {
    message: string
}

export function useCreateOrUpdateProject() {

    const loading = ref(false)

    async function createOrUpdate() {
        try {
            loading.value = true
            const data =
                projectStore.edit ?
                    await updateProject() : await createProject()
            loading.value = false
            projectStore.projectInput = {} as ProjectInputType

            successMsg(data.message)
        } catch (error) {
            loading.value = false
            showErrorResponse(error);
        }
    }
    return { createOrUpdate, loading }
}

async function createProject() {
    const data = await makeHttpReq<ProjectInputType, ProjectResponseType>
        ('projects', 'POST', projectStore.projectInput)
    return data;
}
async function updateProject() {
    const data = await makeHttpReq<ProjectInputType, ProjectResponseType>
        ('projects', 'PUT', projectStore.projectInput)
    projectStore.edit = false
    return data;
}