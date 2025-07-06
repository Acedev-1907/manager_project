import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showError, showSuccess } from "../../../../helper/alert";
import { showErrorResponse } from "../../../../helper/utils";
import { projectStore } from "../store/projectStore";

export type ProjectInputType = {
  id: number;
  name: string;
  startDate: string;
  endDate: string;
  members?: number[];
};
export type ProjectResponseType = {
  message: string;
};

export function useCreateOrUpdateProject() {
  const loading = ref(false);

  async function createOrUpdate() {
    try {
      loading.value = true;
      const data = projectStore.edit
        ? await updateProject()
        : await createProject();
      loading.value = false;
      projectStore.projectInput = {} as ProjectInputType;

      showSuccess(data.message);
      return { success: true, data };
    } catch (error) {
      loading.value = false;
      showErrorResponse(error);
      return { success: false, error };
    }
  }
  return { createOrUpdate, loading };
}

async function createProject() {
  const data = await makeHttpReq<ProjectInputType, ProjectResponseType>(
    "projects",
    "POST",
    projectStore.projectInput
  );
  return data;
}
async function updateProject() {
  const data = await makeHttpReq<ProjectInputType, ProjectResponseType>(
    "projects",
    "PUT",
    projectStore.projectInput
  );
  projectStore.edit = false;

  return data;
}
