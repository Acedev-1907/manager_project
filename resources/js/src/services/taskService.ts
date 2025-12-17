import { makeHttpReq } from "../helper/makeHttpReq";
import type { CreateTaskInput } from "../pages/app/kabanborad/actions/CreateTask";

// ===== Task comments =====
export async function getTaskComments(taskId: number | string) {
  const res = await makeHttpReq<any, { comments: any[] }>(
    `tasks/${taskId}/comments`,
    "GET"
  );
  if (Array.isArray(res)) return res;
  if (Array.isArray((res as any)?.comments)) return (res as any).comments;
  if (Array.isArray((res as any)?.data?.comments))
    return (res as any).data.comments;
  if (Array.isArray((res as any)?.data)) return (res as any).data;
  return [];
}

export async function addTaskComment(taskId: number | string, content: string) {
  const res = await makeHttpReq<{ content: string }, { comment: any }>(
    `tasks/${taskId}/comments`,
    "POST",
    { content }
  );
  if ((res as any)?.comment) return (res as any).comment;
  if ((res as any)?.data?.comment) return (res as any).data.comment;
  return res;
}

// ===== Completed tasks for project =====
export async function getCompletedTasks(projectId: number) {
  const response = await makeHttpReq<any, any>(
    `projects/${projectId}/completed-tasks`,
    "GET"
  );

  if (response.code === 1000 || response.code === 1002) {
    return response.data || [];
  }

  return [];
}

// ===== Task CRUD / status =====
export async function createTaskApi(payload: CreateTaskInput) {
  return makeHttpReq<CreateTaskInput, { message: string }>(
    "tasks",
    "POST",
    payload
  );
}

export interface ChangeTaskStatusPayload {
  taskId: number;
  projectId: number;
}

export async function changeTaskStatusApi(
  endPoint: string,
  payload: ChangeTaskStatusPayload
) {
  return makeHttpReq<ChangeTaskStatusPayload, { message: string }>(
    endPoint,
    "POST",
    payload
  );
}

export async function notifyTaskDragEnded(taskId: number, projectId: number) {
  return makeHttpReq("tasks/drag-ended", "POST", {
    task_id: taskId,
    project_id: projectId,
  });
}



