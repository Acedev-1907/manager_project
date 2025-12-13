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
    projectList: null as any, // cache project list (runtime only, use memory cache instead)
    lastFetched: null as number | null, // thời gian fetch gần nhất (runtime only)
  }),
  
  getters: {
    hasProjects: (state: { projectList: any }) => state.projectList !== null,
    isCacheFresh: (state: { lastFetched: number | null }) => {
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
  
  // Chỉ persist projectInput (form data) vào sessionStorage (tạm thời)
  // Không persist projectList - nên dùng memory cache thay thế
  persist: {
    key: 'project-store',
    paths: ['projectInput', 'edit'], // Chỉ lưu form input vào sessionStorage
    storageType: 'sessionStorage', // Session only - cleared on tab close
    ttl: 60 * 60 * 1000, // 1 hour
  },
});

// Export as singleton for backward compatibility
export const projectStore = useProjectStore();
