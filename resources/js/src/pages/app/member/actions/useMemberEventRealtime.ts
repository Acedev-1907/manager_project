import { onMounted, onUnmounted, Ref } from "vue";

export interface MemberEventPayload {
  byUser: number;
  action: string;
  payload: any;
}

type MemberEventHandler = (e: MemberEventPayload) => void;

export function useMemberEventRealtime(handler: MemberEventHandler) {
  let channel: any = null;

  onMounted(() => {
    const data = JSON.parse(localStorage.getItem("userData") || "{}");
    const userId = data.user?.id;
    if (window.Echo && userId) {
      channel = window.Echo.private(`user.${userId}`).listen(
        "MemberEvent",
        handler
      );
    }
  });

  onUnmounted(() => {
    if (channel) {
      channel.stopListening("MemberEvent");
    }
  });
}

// Hàm xử lý các action MemberEvent, có thể tái sử dụng ở nhiều nơi
export function handleMemberEvent(
  e: MemberEventPayload,
  friendsList: Ref<any>,
  receivedInvitations: Ref<any>,
  sentInvitations: Ref<any>,
  memberCacheRef?: Ref<{ [key: string]: any }>
) {
  if (e.action === "removed") {
    if (friendsList.value && friendsList.value.data) {
      if (!Array.isArray(friendsList.value.data.data)) {
        friendsList.value.data.data = [];
      }
      friendsList.value.data.data = friendsList.value.data.data.filter(
        (member: { id: number }) => member.id !== e.byUser
      );
    }
  }
  if (e.action === "invitation_sent") {
    const data = JSON.parse(localStorage.getItem("userData") || "{}");
    const userId = data.user?.id;
    // Nếu user hiện tại là receiver thì cập nhật receivedInvitations
    if (
      e.payload &&
      e.payload.invitation &&
      e.payload.invitation.receiver_id === userId &&
      receivedInvitations.value &&
      receivedInvitations.value.data
    ) {
      if (!Array.isArray(receivedInvitations.value.data.data)) {
        receivedInvitations.value.data.data = [];
      }
      const exists = receivedInvitations.value.data.data.some(
        (m: any) => m.id === e.payload.invitation.id
      );
      if (!exists) {
        receivedInvitations.value.data.data = [
          e.payload.invitation,
          ...receivedInvitations.value.data.data,
        ];
      }
    }
    // Nếu user hiện tại là sender thì cập nhật sentInvitations
    if (
      e.payload &&
      e.payload.invitation &&
      e.payload.invitation.sender_id === userId &&
      sentInvitations.value &&
      sentInvitations.value.data
    ) {
      if (!Array.isArray(sentInvitations.value.data.data)) {
        sentInvitations.value.data.data = [];
      }
      const exists = sentInvitations.value.data.data.some(
        (m: any) => m.id === e.payload.invitation.id
      );
      if (!exists) {
        sentInvitations.value.data.data.unshift(e.payload.invitation);
      }
    }
  }
  if (e.action === "invitation_declined") {
    // Xóa invitation khỏi sentInvitations local dựa vào invitation_id
    if (
      e.payload &&
      e.payload.invitation_id &&
      sentInvitations.value &&
      sentInvitations.value.data
    ) {
      if (!Array.isArray(sentInvitations.value.data.data)) {
        sentInvitations.value.data.data = [];
      }
      sentInvitations.value.data.data = sentInvitations.value.data.data.filter(
        (m: any) => String(m.id) !== String(e.payload.invitation_id)
      );
    }
    // Xóa invitation khỏi receivedInvitations local dựa vào invitation_id (cho receiver)
    if (
      e.payload &&
      e.payload.invitation_id &&
      receivedInvitations.value &&
      receivedInvitations.value.data
    ) {
      if (!Array.isArray(receivedInvitations.value.data.data)) {
        receivedInvitations.value.data.data = [];
      }
      receivedInvitations.value.data.data =
        receivedInvitations.value.data.data.filter(
          (m: any) => String(m.id) !== String(e.payload.invitation_id)
        );
    }
    // showSuccess("Your invitation has been declined.");
  }
  if (e.action === "invitation_cancelled") {
    // Nếu có invitation_id thì xóa khỏi receivedInvitations local (ưu tiên đúng invitation_id)
    if (
      e.payload &&
      e.payload.invitation_id &&
      receivedInvitations.value &&
      receivedInvitations.value.data
    ) {
      if (!Array.isArray(receivedInvitations.value.data.data)) {
        receivedInvitations.value.data.data = [];
      }
      receivedInvitations.value.data.data =
        receivedInvitations.value.data.data.filter(
          (m: any) => String(m.id) !== String(e.payload.invitation_id)
        );
    }
    // Nếu có invitation_id thì xóa khỏi sentInvitations local (ưu tiên đúng invitation_id)
    if (
      e.payload &&
      e.payload.invitation_id &&
      sentInvitations.value &&
      sentInvitations.value.data
    ) {
      if (!Array.isArray(sentInvitations.value.data.data)) {
        sentInvitations.value.data.data = [];
      }
      sentInvitations.value.data.data = sentInvitations.value.data.data.filter(
        (m: any) => String(m.id) !== String(e.payload.invitation_id)
      );
    }
  }
  if (e.action === "invitation_accepted") {
    const data = JSON.parse(localStorage.getItem("userData") || "{}");
    const userId = data.user?.id;

    // Luôn xóa invitation khỏi Received Invitations theo id === invitation_id
    if (
      receivedInvitations.value &&
      receivedInvitations.value.data &&
      e.payload.invitation_id
    ) {
      if (!Array.isArray(receivedInvitations.value.data.data)) {
        receivedInvitations.value.data.data = [];
      }
      receivedInvitations.value.data.data =
        receivedInvitations.value.data.data.filter(
          (m: any) => String(m.id) !== String(e.payload.invitation_id)
        );
    }

    // Nếu user hiện tại là receiver (người nhận)
    if (
      e.payload &&
      e.payload.member &&
      String(userId) === String(e.payload.member.id)
    ) {
      // 2. Thêm member mới (người gửi) vào friendsList nếu chưa có
      if (friendsList.value && friendsList.value.data && e.byUser) {
        if (!Array.isArray(friendsList.value.data.data)) {
          friendsList.value.data.data = [];
        }
        const exists = friendsList.value.data.data.some(
          (m: any) => m.id === e.byUser
        );
        if (!exists) {
          friendsList.value.data.data.unshift({
            id: e.byUser,
            name: e.payload.member.name,
            email: e.payload.member.email,
            avatar: e.payload.member.avatar,
          });
        }
      }
    } else {
      // user này là sender (người gửi)
      // 1. Xóa tất cả invitation có receiver_id === member.id hoặc id === invitation_id khỏi sentInvitations
      if (sentInvitations.value && sentInvitations.value.data) {
        if (!Array.isArray(sentInvitations.value.data.data)) {
          sentInvitations.value.data.data = [];
        }
        const removeIds = new Set([String(e.payload.invitation_id)]);
        const removeReceivers = new Set([String(e.payload.member.id)]);
        sentInvitations.value.data.data =
          sentInvitations.value.data.data.filter(
            (m: any) =>
              !removeIds.has(String(m.id)) &&
              !removeReceivers.has(String(m.receiver_id))
          );
      }
      // 2. Thêm member mới vào friendsList nếu chưa có
      if (
        e.payload &&
        e.payload.member &&
        friendsList.value &&
        friendsList.value.data
      ) {
        if (!Array.isArray(friendsList.value.data.data)) {
          friendsList.value.data.data = [];
        }
        const exists = friendsList.value.data.data.some(
          (m: any) => m.id === e.payload.member.id
        );
        if (!exists) {
          friendsList.value.data.data.unshift(e.payload.member);
        }
      }
      // 3. Bổ sung member mới vào memberCache nếu có
      if (memberCacheRef && memberCacheRef.value) {
        const cacheKey = `member_page_`;
        if (!memberCacheRef.value[cacheKey]) {
          memberCacheRef.value[cacheKey] = { data: { data: [] } };
        }
        const cacheArr = memberCacheRef.value[cacheKey].data.data;
        const exists = cacheArr.some((m: any) => m.id === e.payload.member.id);
        if (!exists) {
          cacheArr.unshift(e.payload.member);
        }
      }
    }
  }
  // Xử lý các action khác nếu cần
}
