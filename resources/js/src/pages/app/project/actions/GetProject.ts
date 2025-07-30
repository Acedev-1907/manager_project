import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";

export type ProjectType = {
  id: number;
  name: string;
  startDate: string;
  endDate: string;
  content?: string;
  slug: string;
  task_progress: {
    id: number;
    projectId: number;
    progress: string;
    created_at: string;
    updated_at: string;
  };
  creator?: {
    id: number;
    name: string;
    email: string;
  } | null;
  users?: Array<{
    id: number;
    name: string;
    email: string;
  }>;
};

export type GetProjectType = {
  data: { data: Array<ProjectType> };
} & Record<string, any>;

export function useGetProject() {
  const loading = ref(false);
  const projectData = ref<GetProjectType>({} as GetProjectType);
  async function getProjects(page: number = 1, query: string = "") {
    try {
      loading.value = true;
      const data = await makeHttpReq<undefined, GetProjectType>(
        `projects?query=${query}&page=${page}`,
        "GET"
      );
      loading.value = false;
      projectData.value = data;
    } catch (error) {
      loading.value = false;
      showErrorResponse(error);
    }
  }
  return { getProjects, projectData, loading };
}
