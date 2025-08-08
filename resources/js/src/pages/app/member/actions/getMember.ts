import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";
import type { Member, MemberListResponse } from "../../../../types/common";

export type MemberType = Member;
export type GetMemberType = MemberListResponse;

export function useGetMembers() {
  const loading = ref(false);
  const memberData = ref<MemberListResponse>({
    data: [],
    total: 0,
    current_page: 1,
    last_page: 1,
    per_page: 10,
  });
  async function getMembers(
    page: number = 1,
    query: string = "",
    showGlobalLoading: boolean = true
  ) {
    try {
      loading.value = true;
      const data = await makeHttpReq<undefined, MemberListResponse>(
        `members?query=${query}&page=${page}`,
        "GET",
        undefined,
        { showGlobalLoading }
      );
      loading.value = false;
      memberData.value = data;
    } catch (error) {
      loading.value = false;
      showErrorResponse(error);
    }
  }

  return { getMembers, memberData, loading };
}

export async function removeMember(id: number) {
  try {
    await makeHttpReq<undefined, any>(`members/${id}`, "DELETE");
  } catch (error) {
    showErrorResponse(error);
  }
}
