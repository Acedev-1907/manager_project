import type { Ref } from "vue";
import type { Member, MemberListResponse } from "../../../../types/common";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import Swal from "sweetalert2";
import { showSuccess } from "../../../../helper/alert";

type GetMemberType = MemberListResponse;
type MemberType = Member;

export async function fetchMembers(
  friendsList: Ref<GetMemberType>,
  memberCache: Ref<{ [key: string]: GetMemberType }>,
  isLoading: Ref<boolean>,
  query = "",
  page = 1,
  force = false,
  append = false
) {
  const cacheKey = `member_page_${query}_${page}`;
  if (!force && memberCache.value[cacheKey] && !append) {
    friendsList.value = memberCache.value[cacheKey];
    isLoading.value = false;
    return;
  }

  if (append) {
    isLoading.value = false;
  } else {
    isLoading.value = true;
  }

  try {
    const perPage = 52;

    const response = await makeHttpReq<undefined, any>(
      `members?query=${query}&page=${page}&per_page=${perPage}`,
      "GET"
    );
    // If response has nested data, extract correct MemberListResponse type
    let data: GetMemberType;
    if (response && response.data && Array.isArray(response.data.data)) {
      // Laravel resource format { data: { data: [], ...paging... } }
      data = response.data;
    } else {
      data = response;
    }

    if (append && Array.isArray(friendsList.value.data)) {
      // Remove duplicate members by id
      const existingIds = new Set(friendsList.value.data.map((m: any) => m.id));
      const newMembers = (data.data || []).filter(
        (m: any) => !existingIds.has(m.id)
      );
      friendsList.value = {
        ...data,
        data: [...friendsList.value.data, ...newMembers],
      };
    } else {
      friendsList.value = data;
    }

    memberCache.value[cacheKey] = data;
    localStorage.setItem("memberCache", JSON.stringify(memberCache.value));
  } catch (e) {}

  if (append) {
    isLoading.value = false;
  } else {
    isLoading.value = false;
  }
}

export async function fetchSentInvitations(
  sentInvitations: Ref<GetMemberType>,
  isLoading: Ref<boolean>
) {
  isLoading.value = true;
  try {
    const res = await makeHttpReq<undefined, any>(
      "member-invitations/sent",
      "GET"
    );
    let arr = [];
    if (res && Array.isArray(res.data)) {
      arr = res.data;
    } else if (res && res.data && Array.isArray(res.data.data)) {
      arr = res.data.data;
    }
    sentInvitations.value.data = arr.map((inv: any) => ({
      ...inv,
      id: inv.id || inv.invitation_id,
    }));
  } catch (e) {
    sentInvitations.value.data = [];
  }
  isLoading.value = false;
}

export async function fetchReceivedInvitations(
  receivedInvitations: Ref<GetMemberType>,
  isLoading: Ref<boolean>
) {
  isLoading.value = true;
  try {
    const res = await makeHttpReq<undefined, any>(
      "member-invitations/received",
      "GET"
    );
    let arr = [];
    if (res && Array.isArray(res.data)) {
      arr = res.data;
    } else if (res && res.data && Array.isArray(res.data.data)) {
      arr = res.data.data;
    }
    receivedInvitations.value.data = arr.map((inv: any) => ({
      ...inv,
      id: inv.id || inv.invitation_id,
    }));
  } catch (e) {
    receivedInvitations.value.data = [];
  }
  isLoading.value = false;
}

export async function handleRemoveMember(
  member: MemberType,
  memberCache: Ref<{ [key: string]: GetMemberType }>,
  searchQuery: Ref<string>,
  friendsList: Ref<GetMemberType>,
  isLoading: Ref<boolean>
) {
  const result = await Swal.fire({
    title: "Are you sure?",
    text: `Do you really want to remove ${member.name} from your contact list?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, remove",
    cancelButtonText: "Cancel",
  });
  if (result.isConfirmed) {
    await makeHttpReq<undefined, any>(`members/${member.id}`, "DELETE");
    memberCache.value = {};
    localStorage.removeItem("memberCache");
    await fetchMembers(friendsList, memberCache, isLoading, searchQuery.value);
    showSuccess("Removed from contact list");
  }
}
