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

// Function to handle MemberEvent actions, can be reused in multiple places
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
    // If current user is receiver then update receivedInvitations
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
    // If current user is sender then update sentInvitations
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
    // Remove invitation from sentInvitations local based on invitation_id
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
    // Remove invitation from receivedInvitations local based on invitation_id (for receiver)
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
    // If invitation_id exists then remove from receivedInvitations local (prioritize correct invitation_id)
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
    // If invitation_id exists then remove from sentInvitations local (prioritize correct invitation_id)
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

    // Always remove invitation from Received Invitations based on id === invitation_id
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

    // If current user is receiver (the person receiving)
    if (
      e.payload &&
      e.payload.member &&
      String(userId) === String(e.payload.member.id)
    ) {
      // 2. Add new member (sender) to friendsList if not already present
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
      // This user is the sender (the person sending)
      // 1. Remove all invitations with receiver_id === member.id or id === invitation_id from sentInvitations
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
      // 2. Add new member to friendsList if not already present
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
      // 3. Add new member to memberCache if available
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
  // Handle other actions if needed
}
