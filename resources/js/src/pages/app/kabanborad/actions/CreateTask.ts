import { ref } from "vue";
import { taskStore } from "../store/kabanStore";
import { showSuccess } from "../../../../helper/alert";
import { showErrorResponse } from "../../../../helper/utils";
import eventBus from "../../../../helper/eventBus";
import { createTaskApi } from "../../../../services/taskService";

export type CreateTaskInput = {
  name: string;
  memberIds: Array<number>;
  projectId: number;
  content?: string;
};

export function useCreateTask() {
  const loading = ref(false);

  async function createTask() {
    try {
      loading.value = true;

      // @ts-expect-error - Pinia store type inference issue
      const data = await createTaskApi(taskStore.taskInput);
      loading.value = false;
      showSuccess(data.message);
      eventBus.emit("taskCreated");
    } catch (error) {
      loading.value = false;
      showErrorResponse(error);
    }
  }
  return { createTask, loading };
}
