import { defineStore } from "pinia";
import { CreateTaskInput } from "../actions/CreateTask";

const useTaskStore = defineStore("task", {
  state: () => ({
    taskInput: {
      name: "",
      memberIds: [],
      projectId: 0,
      content: "",
    } as CreateTaskInput,
    edit: false,
    currentTaskId: 0 as number,
    projectDetailCache: {} as Record<string, any>, // cache project detail by slug
    lastFetched: {} as Record<string, number>, // fetch time by slug
  }),
  actions: {
    setProjectDetailCache(slug: string, data: any) {
      this.projectDetailCache[slug] = data;
      this.lastFetched[slug] = Date.now();
    },
    clearProjectDetailCache(slug: string) {
      delete this.projectDetailCache[slug];
      delete this.lastFetched[slug];
    },
  },
});

export const taskStore = useTaskStore();
