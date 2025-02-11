import { defineStore } from 'pinia';
import { CreateTaskInput } from '../actions/CreateTask';

const useTaskStore = defineStore('task', {
    state: () => ({
        taskInput: {} as CreateTaskInput,
        edit: false
    })
})

export const taskStore = useTaskStore();