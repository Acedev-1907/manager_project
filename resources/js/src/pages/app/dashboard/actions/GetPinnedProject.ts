import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";

type pinnedProject = {
  id: number;
  name: string;
  progress: number;
  tasks: Array<number>;
};
export type pinnedProjectType = {
  data: pinnedProject;
};

export function useGetPinnedProject() {
  const project = ref<pinnedProject>({} as pinnedProject);
  async function getPinnedProject() {
    try {
      const response = await makeHttpReq<
        undefined,
        { code: number; data: pinnedProject; message: string }
      >(`pinned/projects`, "GET");
      project.value = response.data;
    } catch (error) {
      showErrorResponse(error);
    }
  }
  return { getPinnedProject, project };
}
