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
  return res?.comments || [];
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
  return res?.comment;
}
