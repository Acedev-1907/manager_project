import { makeHttpReq } from "../../../../helper/makeHttpReq";

export async function deleteProject(projectId: number) {
  try {
    const res = await makeHttpReq<undefined, any>(
      `projects/${projectId}`,
      "DELETE"
    );
    return res;
  } catch (error) {
    throw error;
  }
}
