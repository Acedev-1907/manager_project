import type { Ref } from "vue";
import type { GetMemberType, MemberType } from "./getMember";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import Swal from "sweetalert2";
import { showSuccess } from "../../../../helper/alert";

export async function fetchMembers(
  friendsList: Ref<GetMemberType>,
  memberCache: Ref<{ [key: string]: GetMemberType }>,
  isLoading: Ref<boolean>,
  query = "",
  force = false
) {
  const cacheKey = `member_page_${query}`;
  if (!force && memberCache.value[cacheKey]) {
    friendsList.value = memberCache.value[cacheKey];
    isLoading.value = false;
    return;
  }
  isLoading.value = true;
  try {
    const data = await makeHttpReq<undefined, GetMemberType>(
      `members?query=${query}`,
      "GET"
    );
    friendsList.value = data;
    memberCache.value[cacheKey] = data;
    localStorage.setItem("memberCache", JSON.stringify(memberCache.value));
  } catch (e) {}
  isLoading.value = false;
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
    // Đảm bảo sentInvitations.value.data.data luôn là mảng invitation và có trường id là id của invitation
    let arr = [];
    if (res && Array.isArray(res.data)) {
      arr = res.data;
    } else if (res && res.data && Array.isArray(res.data.data)) {
      arr = res.data.data;
    }
    sentInvitations.value.data.data = arr.map((inv: any) => ({
      ...inv,
      id: inv.id || inv.invitation_id, // fallback nếu BE trả về invitation_id
    }));
    // console.log("Fetched sentInvitations:", sentInvitations.value.data.data);
  } catch (e) {
    sentInvitations.value.data.data = [];
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
    // Đảm bảo receivedInvitations.value.data.data luôn là mảng invitation và có trường id là id của invitation
    let arr = [];
    if (res && Array.isArray(res.data)) {
      arr = res.data;
    } else if (res && res.data && Array.isArray(res.data.data)) {
      arr = res.data.data;
    }
    receivedInvitations.value.data.data = arr.map((inv: any) => ({
      ...inv,
      id: inv.id || inv.invitation_id, // fallback nếu BE trả về invitation_id
    }));
    // console.log(
    //   "Fetched receivedInvitations:",
    //   receivedInvitations.value.data.data
    // );
  } catch (e) {
    receivedInvitations.value.data.data = [];
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
    await fetchMembers(
      friendsList,
      memberCache,
      isLoading,
      searchQuery.value,
      true
    );
    showSuccess("Removed from contact list");
  }
}

// Có thể tách tiếp các handleAddMember, handleAcceptInvitation, handleDeclineInvitation, handleCancelInvitation tương tự nếu muốn.
