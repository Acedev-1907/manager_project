import { defineStore } from 'pinia';
import { ProjectInputType } from '../actions/createtProject';

const userProjectStore = defineStore('project', {
    state: () => ({
        projectInput: {} as ProjectInputType,
        edit: false
    })
})

export const projectStore = userProjectStore();