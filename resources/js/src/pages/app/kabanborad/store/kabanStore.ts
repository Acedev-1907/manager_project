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
    draggedTaskId: null as number | null,
    draggedProjectId: null as number | null,
    projectDetailCache: {} as Record<string, any>, // cache project detail theo slug
    lastFetched: {} as Record<string, number>, // thời gian fetch theo slug
  }),
  actions: {
    setDraggedTask(taskId: number, projectId: number) {
      this.draggedTaskId = taskId;
      this.draggedProjectId = projectId;
    },
    clearDraggedTask() {
      this.draggedTaskId = null;
      this.draggedProjectId = null;
    },
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
