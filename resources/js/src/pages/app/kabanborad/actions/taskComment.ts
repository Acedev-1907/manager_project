import { makeHttpReq } from "../../../../helper/makeHttpReq";

/**
 * Get the list of comments of a task
 * @param {number|string} taskId
 * @returns {Promise<any[]>}
 */
export async function getTaskComments(taskId: number | string) {
  const res = await makeHttpReq<any, { comments: any[] }>(
    `tasks/${taskId}/comments`,
    "GET"
  );
  // Hỗ trợ nhiều dạng trả về: {comments: []} hoặc {data: {comments: []}} hoặc mảng trực tiếp
  if (Array.isArray(res)) return res;
  if (Array.isArray((res as any)?.comments)) return (res as any).comments;
  if (Array.isArray((res as any)?.data?.comments)) return (res as any).data.comments;
  if (Array.isArray((res as any)?.data)) return (res as any).data;
  return [];
}

/**
 * Submit new comment for a task
 * @param {number|string} taskId
 * @param {string} content
 * @returns {Promise<any>}
 */
export async function addTaskComment(taskId: number | string, content: string) {
  const res = await makeHttpReq<{ content: string }, { comment: any }>(
    `tasks/${taskId}/comments`,
    "POST",
    { content }
  );
  // Chuẩn hoá trả về comment
  if ((res as any)?.comment) return (res as any).comment;
  if ((res as any)?.data?.comment) return (res as any).data.comment;
  return res;
}
