import { defineStore } from "pinia";

const userProjectStore = defineStore("project", {
  state: () => ({
    projectInput: {
      id: 0,
      name: "",
      startDate: "",
      endDate: "",
      members: [] as number[],
    },
    edit: false,
    projectList: null as any, // cache project list
    lastFetched: null as number | null, // thời gian fetch gần nhất
  }),
  actions: {
    setProjects(data: any) {
      this.projectList = data;
      this.lastFetched = Date.now();
    },
    clearProjects() {
      this.projectList = null;
      this.lastFetched = null;
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

export const projectStore = userProjectStore();
