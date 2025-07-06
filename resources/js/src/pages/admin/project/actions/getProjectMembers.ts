import { ref, Ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";

export function useGetProjectMembers() {
  const loading = ref(false);
  const members: Ref<any[]> = ref([]);
  async function getProjectMembers(projectId: number) {
    try {
      loading.value = true;
      const res = await makeHttpReq<undefined, { data: any[] }>(
        `projects/${projectId}/members`,
        "GET"
      );
      members.value = res.data;
      loading.value = false;
    } catch (error) {
      loading.value = false;
      showErrorResponse(error);
    }
  }
  return { getProjectMembers, members, loading };
}
