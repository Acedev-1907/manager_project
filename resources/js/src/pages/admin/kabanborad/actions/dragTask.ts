import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { successMsg } from "../../../../helper/toast-notificaltion";
import { taskStore } from "../store/kabanStore";

export function useDragTask(fn: (slug: string) => Promise<void>, slug: string) {

    function addDragAndDropListeners(targetColumn: HTMLElement, taskId: number, projectId: number, endpoint: string) {
        let isDragged = false;

        targetColumn.addEventListener("dragover", function (event) {
            if (!isDragged) {
                event.preventDefault();
                targetColumn.classList.add("hovered");
                isDragged = true;
            }
        });

        targetColumn.addEventListener("dragleave", function () {
            isDragged = false;
            targetColumn.classList.remove("hovered");
        });

        targetColumn.addEventListener("drop", async function (event) {
            event.preventDefault();

            targetColumn.classList.remove("hovered");
            isDragged = false;

            taskStore.currentTaskId = taskId;
            if (!targetColumn.getAttribute("data-listeners-added")) {
                targetColumn.setAttribute("data-listeners-added", "true");

                setTimeout(async () => {
                    await Promise.all([
                        changeTaskStatus(taskStore.currentTaskId, projectId, endpoint),
                        fn(slug)
                    ]);
                    targetColumn.removeAttribute("data-listeners-added");
                }, 200);
            }
        });
    }

    async function fromNotStartedToPending(taskId: number, projectId: number) {
        // const notStartedTask = document.querySelector(`.notStartedTask_${taskId}`) as HTMLElement;
        const pendingColumn = document.querySelector(".pending_task") as HTMLElement;

        addDragAndDropListeners(pendingColumn, taskId, projectId, 'task/not_started_to_pending');
    }

    function fromNotStartedToCompleted(taskId: number, projectId: number) {
        // const completedTask = document.querySelector(`.completedTask_${taskId}`) as HTMLElement;
        const completedColumn = document.querySelector(".completed_task") as HTMLElement;

        addDragAndDropListeners(completedColumn, taskId, projectId, 'task/not_started_to_completed');
    }

    async function fromPendingToNotStarted(taskId: number, projectId: number) {
        // const pendingTask = document.querySelector(`.pendingTask_${taskId}`) as HTMLElement;
        const notStartedColumn = document.querySelector(".not_started_task") as HTMLElement;

        addDragAndDropListeners(notStartedColumn, taskId, projectId, 'task/pending_to_completed');
    }

    async function fromPendingToCompleted(taskId: number, projectId: number) {
        // const pendingTask = document.querySelector(`.pendingTask_${taskId}`) as HTMLElement;
        const completedColumn = document.querySelector(".completed_task") as HTMLElement;

        addDragAndDropListeners(completedColumn, taskId, projectId, 'task/pending_to_completed');
    }

    function fromCompletedToPending(taskId: number, projectId: number) {
        // const completedTask = document.querySelector(`.completedTask_${taskId}`) as HTMLElement;
        const pendingColumn = document.querySelector(".pending_task") as HTMLElement;

        addDragAndDropListeners(pendingColumn, taskId, projectId, 'task/completed_to_pending');
    }

    function fromCompletedToNotStarted(taskId: number, projectId: number) {
        // const completedTask = document.querySelector(`.completedTask_${taskId}`) as HTMLElement;
        const notStartedColumn = document.querySelector(".not_started_task") as HTMLElement;

        addDragAndDropListeners(notStartedColumn, taskId, projectId, 'task/completed_to_not_started');
    }


    return {
        fromNotStartedToPending,
        fromNotStartedToCompleted,
        fromPendingToCompleted,
        fromPendingToNotStarted,
        fromCompletedToPending,
        fromCompletedToNotStarted,
    };
}

export type changeTaskInput = {
    taskId: number;
    projectId: number;
}

export async function changeTaskStatus(
    taskId: number,
    projectId: number,
    endPoint: string,
) {
    try {
        const data = await makeHttpReq<changeTaskInput, { message: string }>(endPoint, "POST", {
            taskId: taskId,
            projectId: projectId
        });

        successMsg(data.message);
    } catch (error) {
        console.error("Error changing task status:", error);
    }
}