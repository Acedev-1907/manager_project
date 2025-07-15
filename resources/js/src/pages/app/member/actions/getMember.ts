import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";

export type MemberType = {
  id: number;
  name: string;
  email: string;
  avatar?: string;
};

export type GetMemberType = {
  data: { data: Array<MemberType> };
} & Record<string, any>;

export function useGetMembers() {
  const loading = ref(false);
  const memberData = ref<GetMemberType>({} as GetMemberType);
  async function getMembers(
    page: number = 1,
    query: string = "",
    showGlobalLoading: boolean = true
  ) {
    try {
      loading.value = true;
      const data = await makeHttpReq<undefined, GetMemberType>(
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
