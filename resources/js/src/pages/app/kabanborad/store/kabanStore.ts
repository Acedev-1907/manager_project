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
  },
});

export const taskStore = useTaskStore();
