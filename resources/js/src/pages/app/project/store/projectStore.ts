import { defineStore } from "pinia";

export interface ProjectInput {
  id: number;
  name: string;
  startDate: string;
  endDate: string;
  content: string;
  members: number[];
}

export const useProjectStore = defineStore("project", {
  state: () => ({
    projectInput: {
      id: 0,
      name: "",
      startDate: "",
      endDate: "",
      content: "",
      members: [] as number[],
    } as ProjectInput,
    edit: false,
    projectList: null as any, // cache project list
    lastFetched: null as number | null, // thời gian fetch gần nhất
  }),
  
  getters: {
    hasProjects: (state) => state.projectList !== null,
    isCacheFresh: (state) => {
      if (!state.lastFetched) return false;
      const age = Date.now() - state.lastFetched;
      return age < 5 * 60 * 1000; // 5 minutes
    },
  },
  
  actions: {
    setProjects(data: any) {
      this.projectList = data;
      this.lastFetched = Date.now();
    },
    clearProjects() {
      this.projectList = null;
      this.lastFetched = null;
    },
    resetProjectInput() {
      this.projectInput = {
        id: 0,
        name: "",
        startDate: "",
        endDate: "",
        content: "",
        members: [],
      };
      this.edit = false;
    },
    async fetchProjectsFromServer(
      getProjects: (page?: number, query?: string) => Promise<void>,
      page = 1,
      query = ""
    ) {
      await getProjects(page, query);
      this.projectList = null; // Để ProjectPage tự refetch lại nếu cần
      this.lastFetched = Date.now();
    },
  },
  
});

// Export as singleton for backward compatibility
export const projectStore = useProjectStore();
