import { defineStore } from "pinia";
import { ProjectInputType } from "../actions/createtProject";

const userProjectStore = defineStore("project", {
  state: () => ({
    projectInput: { id: 0, name: "", startDate: "", endDate: "", members: [] },
    edit: false,
  }),
});

export const projectStore = userProjectStore();
