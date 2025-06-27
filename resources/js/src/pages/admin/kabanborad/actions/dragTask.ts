import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { successMsg } from "../../../../helper/toast-notificaltion";
import { taskStore } from "../store/kabanStore";

export function useDragTask(
  fn: (slug: string) => Promise<void>,
  slug: string,
  ProjectData?: any
) {
  // Hàm cập nhật trạng thái task trên UI ngay lập tức
  function updateTaskStatusInUI(taskId: number, newStatus: number) {
    if (!ProjectData?.value?.data?.tasks) return;
    const tasks = ProjectData.value.data.tasks;
    const idx = tasks.findIndex((t: any) => t.id === taskId);
    if (idx !== -1) {
      const [task] = tasks.splice(idx, 1);
      task.status = newStatus;
      tasks.unshift(task);

      // Tính lại progress (giả sử progress là % task hoàn thành)
      const total = tasks.length;
      const completed = tasks.filter((t: any) => t.status === 2).length; // 2 = COMPLETED
      const progress = total > 0 ? Math.round((completed / total) * 100) : 0;
      if (ProjectData.value.data.task_progress) {
        ProjectData.value.data.task_progress.progress = progress;
      }
    }
  }

  // Đảm bảo chỉ gắn listener một lần cho mỗi cột
  const attachedColumns = new Set<HTMLElement>();

  function addDropListener(
    targetColumn: HTMLElement,
    endpoint: string,
    newStatus: number
  ) {
    if (attachedColumns.has(targetColumn)) return;
    attachedColumns.add(targetColumn);

    targetColumn.addEventListener(
      "dragover",
      function (event) {
        event.preventDefault();
        targetColumn.classList.add("hovered");
      },
      { passive: false }
    );

    targetColumn.addEventListener("dragleave", function () {
      targetColumn.classList.remove("hovered");
    });

    targetColumn.addEventListener("drop", function (event) {
      event.preventDefault();
      targetColumn.classList.remove("hovered");
      const taskId = taskStore.draggedTaskId;
      const projectId = taskStore.draggedProjectId;
      if (taskId && projectId) {
        // Kiểm tra nếu status đã đúng thì không làm gì
        const task = ProjectData?.value?.data?.tasks?.find(
          (t: any) => t.id === taskId
        );
        if (task && task.status !== newStatus) {
          updateTaskStatusInUI(taskId, newStatus);
          changeTaskStatus(taskId, projectId, endpoint);
        }
      }
      taskStore.clearDraggedTask();
    });
  }

  function setupAllDropListeners() {
    const notStartedColumn = document.querySelector(
      ".not_started_task"
    ) as HTMLElement;
    const pendingColumn = document.querySelector(
      ".pending_task"
    ) as HTMLElement;
    const completedColumn = document.querySelector(
      ".completed_task"
    ) as HTMLElement;
    if (notStartedColumn)
      addDropListener(notStartedColumn, "task/pending_to_not_started", 0);
    if (pendingColumn)
      addDropListener(pendingColumn, "task/not_started_to_pending", 1);
    if (completedColumn)
      addDropListener(completedColumn, "task/not_started_to_completed", 2);
  }

  return {
    setupAllDropListeners,
  };
}

export type changeTaskInput = {
  taskId: number;
  projectId: number;
};

export async function changeTaskStatus(
  taskId: number,
  projectId: number,
  endPoint: string
) {
  try {
    await makeHttpReq<changeTaskInput, { message: string }>(endPoint, "POST", {
      taskId: taskId,
      projectId: projectId,
    });
  } catch (error) {
    console.error("Error changing task status:", error);
  }
}
