import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";
import { showSuccess } from "../../../../helper/alert";

export function usepinnendProject() {
  const loading = ref(false);
  async function pinnendProject(projectId: number) {
    try {
      loading.value = true;
      const data = await makeHttpReq<
        { projectId: number },
        { message: string }
      >(`projects/pinned`, "POST", { projectId: projectId });
      loading.value = false;
      showSuccess(data.message);
    } catch (error) {
      loading.value = false;
      showErrorResponse(error);
    }
  }
  return { pinnendProject };
}
