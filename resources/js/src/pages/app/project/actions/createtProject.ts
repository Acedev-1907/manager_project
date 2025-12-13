import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showSuccess } from "../../../../helper/alert";
import { showErrorResponse } from "../../../../helper/utils";
import { useProjectStore } from "../store/projectStore";

export type ProjectInputType = {
  id: number;
  name: string;
  startDate: string;
  endDate: string;
  content?: string;
  members?: number[];
};
export type ProjectResponseType = {
  message: string;
};

export function useCreateOrUpdateProject() {
  const loading = ref(false);
  const projectStore = useProjectStore();

  async function createOrUpdate() {
    try {
      loading.value = true;
      // @ts-expect-error - Pinia store type inference issue
      const data = projectStore.edit
        ? await updateProject()
        : await createProject();
      loading.value = false;
      // @ts-expect-error - Pinia store type inference issue
      projectStore.projectInput = {
        id: 0,
        name: "",
        startDate: "",
        endDate: "",
        content: "",
        members: [],
      };

      showSuccess(data.message);
      return { success: true, data };
    } catch (error) {
      loading.value = false;
      showErrorResponse(error);
      return { success: false, error };
    }
}

async function createProject() {
  const data = await makeHttpReq<ProjectInputType, ProjectResponseType>(
    "projects",
    "POST",
      // @ts-expect-error - Pinia store type inference issue
    projectStore.projectInput,
    { showGlobalLoading: false }
  );
  return data;
}

async function updateProject() {
  const data = await makeHttpReq<ProjectInputType, ProjectResponseType>(
    "projects",
    "PUT",
      // @ts-expect-error - Pinia store type inference issue
    projectStore.projectInput,
    { showGlobalLoading: false }
  );
    // @ts-expect-error - Pinia store type inference issue
  projectStore.edit = false;

  return data;
  }

  return { createOrUpdate, loading };
}
