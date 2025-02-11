import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { taskStore } from "../store/kabanStore";
import { successMsg } from "../../../../helper/toast-notificaltion";
import { showErrorResponse } from "../../../../helper/utils";

export type CreateTaskInput = {
    name: string
    memberIds: Array<number>,

    projectId: number,
}

export function useCreateTask() {

    const loading = ref(false)

    async function createTask() {
        try {
            loading.value = true

            const data = await makeHttpReq<CreateTaskInput, { message: string }>('tasks', 'POST', taskStore.taskInput)
            loading.value = false
            successMsg(data.message)
        } catch (error) {
            loading.value = false
            showErrorResponse(error);
        }
    }
    return { createTask, loading }
}