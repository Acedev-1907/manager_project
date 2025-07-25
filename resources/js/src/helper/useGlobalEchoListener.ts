import { onMounted, onUnmounted, ref, watch, Ref } from "vue";
import eventBus from "./eventBus";

let joinedUserChannel: string | number | null = null;

export function useGlobalEchoListener(userId: Ref<string | number | null>) {
  const echoReady = ref(false);

  function setupEchoListener(userIdVal: string | number | null) {
    if (!userIdVal) return;
    if (joinedUserChannel === userIdVal) return;
    joinedUserChannel = userIdVal;
    if (!window.Echo) {
      setTimeout(() => setupEchoListener(userIdVal), 200);
      return;
    }
    try {
      window.Echo.private(`user.${userIdVal}`)
        .listen("NewProjectForMembers", (e: any) => {
          eventBus.emit("new-project-for-members", e);
        })
        .listen("UserRemovedFromProject", (e: any) => {
          eventBus.emit("user-removed-from-project", e);
        });
      echoReady.value = true;
    } catch (error) {
      console.error("Error setting up Echo listener:", error);
    }
  }

  function leaveChannel(userIdVal: string | number | null) {
    if (window.Echo && userIdVal) {
      window.Echo.leave(`user.${userIdVal}`);
      joinedUserChannel = null;
    }
  }

  onMounted(() => {
    setupEchoListener(userId.value);
  });

  // Watch userId thay đổi để leave/join lại channel
  watch(userId, (newId, oldId) => {
    if (oldId) leaveChannel(oldId);
    if (newId) setupEchoListener(newId);
  });

  onUnmounted(() => {
    leaveChannel(userId.value);
  });

  return { echoReady };
}
