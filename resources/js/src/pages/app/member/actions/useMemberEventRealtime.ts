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

// Handle MemberEvent actions for real-time updates (UI always uses flat array for invitations)
export function handleMemberEvent(
  e: MemberEventPayload,
  friendsList: Ref<any>,
  receivedInvitations: Ref<any>,
  sentInvitations: Ref<any>,
  memberCacheRef?: { value: Ref<{ [key: string]: any }> }
) {
  // Remove member from friends list if removed
  if (e.action === "removed") {
    if (friendsList.value && friendsList.value.data) {
      if (!Array.isArray(friendsList.value.data.data))
        friendsList.value.data.data = [];
      friendsList.value.data.data = friendsList.value.data.data.filter(
        (member: { id: number }) => member.id !== e.byUser
      );
    }
  }

  // Add invitation to received/sent on invitation_sent
  if (e.action === "invitation_sent") {
    const data = JSON.parse(localStorage.getItem("userData") || "{}");
    const userId = data.user?.id;
    // Receiver: update receivedInvitations
    if (
      e.payload &&
      e.payload.invitation &&
      e.payload.invitation.receiver_id === userId &&
      receivedInvitations.value &&
      receivedInvitations.value.data
    ) {
      let arr = Array.isArray(receivedInvitations.value.data)
        ? receivedInvitations.value.data
        : receivedInvitations.value.data.data || [];
      if (!arr.some((m: any) => m.id === e.payload.invitation.id)) {
        arr = [e.payload.invitation, ...arr];
      }
      receivedInvitations.value.data = arr;
    }
    // Sender: update sentInvitations
    if (
      e.payload &&
      e.payload.invitation &&
      e.payload.invitation.sender_id === userId &&
      sentInvitations.value &&
      sentInvitations.value.data
    ) {
      let arr = Array.isArray(sentInvitations.value.data)
        ? sentInvitations.value.data
        : sentInvitations.value.data.data || [];
      if (!arr.some((m: any) => m.id === e.payload.invitation.id)) {
        arr = [e.payload.invitation, ...arr];
      }
      sentInvitations.value.data = arr;
    }
  }

  // Remove invitation from sent/received on invitation_declined
  if (e.action === "invitation_declined") {
    // Remove from sentInvitations
    if (
      e.payload &&
      e.payload.invitation_id &&
      sentInvitations.value &&
      sentInvitations.value.data
    ) {
      let arr = Array.isArray(sentInvitations.value.data)
        ? sentInvitations.value.data
        : sentInvitations.value.data.data || [];
      arr = arr.filter(
        (m: any) => String(m.id) !== String(e.payload.invitation_id)
      );
      sentInvitations.value.data = arr;
    }
    // Remove from receivedInvitations
    if (
      e.payload &&
      e.payload.invitation_id &&
      receivedInvitations.value &&
      receivedInvitations.value.data
    ) {
      let arr = Array.isArray(receivedInvitations.value.data)
        ? receivedInvitations.value.data
        : receivedInvitations.value.data.data || [];
      arr = arr.filter(
        (m: any) => String(m.id) !== String(e.payload.invitation_id)
      );
      receivedInvitations.value.data = arr;
    }
  }

  // Remove invitation from sent/received on invitation_cancelled
  if (e.action === "invitation_cancelled") {
    // Remove from receivedInvitations
    if (
      e.payload &&
      e.payload.invitation_id &&
      receivedInvitations.value &&
      receivedInvitations.value.data
    ) {
      let arr = Array.isArray(receivedInvitations.value.data)
        ? receivedInvitations.value.data
        : receivedInvitations.value.data.data || [];
      arr = arr.filter(
        (m: any) => String(m.id) !== String(e.payload.invitation_id)
      );
      receivedInvitations.value.data = arr;
    }
    // Remove from sentInvitations
    if (
      e.payload &&
      e.payload.invitation_id &&
      sentInvitations.value &&
      sentInvitations.value.data
    ) {
      let arr = Array.isArray(sentInvitations.value.data)
        ? sentInvitations.value.data
        : sentInvitations.value.data.data || [];
      arr = arr.filter(
        (m: any) => String(m.id) !== String(e.payload.invitation_id)
      );
      sentInvitations.value.data = arr;
    }
  }

  // Handle invitation accepted: update friends list and remove invitations
  if (e.action === "invitation_accepted") {
    const data = JSON.parse(localStorage.getItem("userData") || "{}");
    const userId = data.user?.id;

    // Remove from receivedInvitations
    if (
      receivedInvitations.value &&
      receivedInvitations.value.data &&
      e.payload.invitation_id
    ) {
      let arr = Array.isArray(receivedInvitations.value.data)
        ? receivedInvitations.value.data
        : receivedInvitations.value.data.data || [];
      arr = arr.filter(
        (m: any) => String(m.id) !== String(e.payload.invitation_id)
      );
      receivedInvitations.value.data = arr;
    }

    // If current user is receiver, add sender to friendsList
    if (
      e.payload &&
      e.payload.member &&
      String(userId) === String(e.payload.member.id)
    ) {
      if (friendsList.value && friendsList.value.data && e.byUser) {
        if (!Array.isArray(friendsList.value.data.data))
          friendsList.value.data.data = [];
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
      // Sender: remove invitations and add member to friendsList
      if (sentInvitations.value && sentInvitations.value.data) {
        let arr = Array.isArray(sentInvitations.value.data)
          ? sentInvitations.value.data
          : sentInvitations.value.data.data || [];
        arr = arr.filter(
          (m: any) =>
            String(m.id) !== String(e.payload.invitation_id) &&
            String(m.receiver_id) !== String(e.payload.member.id)
        );
        sentInvitations.value.data = arr;
      }
      if (
        e.payload &&
        e.payload.member &&
        friendsList.value &&
        friendsList.value.data
      ) {
        if (!Array.isArray(friendsList.value.data.data))
          friendsList.value.data.data = [];
        const exists = friendsList.value.data.data.some(
          (m: any) => m.id === e.payload.member.id
        );
        if (!exists) {
          friendsList.value.data.data.unshift(e.payload.member);
        }
      }
      // Optionally update memberCache if needed
      if (memberCacheRef && memberCacheRef.value) {
        const cacheKey = `member_page_`;
        if (!memberCacheRef.value.value[cacheKey]) {
          memberCacheRef.value.value[cacheKey] = { data: { data: [] } };
        }
        const cacheArr = memberCacheRef.value.value[cacheKey].data.data;
        const exists = cacheArr.some((m: any) => m.id === e.payload.member.id);
        if (!exists) {
          cacheArr.unshift(e.payload.member);
        }
      }
    }
  }
  // Add more event handlers if needed
}
