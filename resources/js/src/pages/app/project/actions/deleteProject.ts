import { makeHttpReq } from "../../../../helper/makeHttpReq";

export async function deleteProject(projectId: number) {
  return makeHttpReq<undefined, any>(`projects/${projectId}`, "DELETE");
}
