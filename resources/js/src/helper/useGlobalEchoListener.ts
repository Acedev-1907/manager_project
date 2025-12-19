import { onMounted, onUnmounted, ref, watch, Ref } from "vue";
import eventBus from "./eventBus";
import { getCurrentUserId } from "./getUserData";
import { subscribeTaskComments, unsubscribeTaskComments } from "./taskCommentsRealtime";

let joinedUserChannel: string | number | null = null;
const joinedProjectChannels: Set<number> = new Set();
const joinedTaskChannels: Set<number> = new Set();

export function useGlobalEchoListener(userId: Ref<string | number | null>) {
  const echoReady = ref(false);

  function setupEchoListener(userIdVal: string | number | null) {
    if (!userIdVal) return;
    if (joinedUserChannel === userIdVal) return;
    
    if (!window.Echo) {
      setTimeout(() => setupEchoListener(userIdVal), 200);
      return;
    }
    
    try {
      const channel = window.Echo.private(`user.${userIdVal}`);
      
      channel
        .listen("NewProjectForMembers", (e: any) => {
          eventBus.emit("new-project-for-members", e);
        })
        .listen("UserRemovedFromProject", (e: any) => {
          eventBus.emit("user-removed-from-project", e);
        })
        .listen(".PrivateMessageCreated", (e: any) => {
          // Realtime message 1-1 (dùng prefix . vì event có broadcastAs())
          eventBus.emit("chat-new-message", e);
        })
        .error((error: any) => {
          if (import.meta.env.DEV) {
            console.error('[Chat Realtime] Channel error:', error);
          }
        });
      
      joinedUserChannel = userIdVal;
      echoReady.value = true;
    } catch (error) {
      if (import.meta.env.DEV) {
        console.error('[Chat Realtime] Setup error:', error);
      }
    }
  }

  function leaveChannel(userIdVal: string | number | null) {
    if (window.Echo && userIdVal) {
      window.Echo.leave(`user.${userIdVal}`);
      joinedUserChannel = null;
    }
  }

  // Global project listeners
  function setupProjectListener(projectId: number) {
    if (!projectId || joinedProjectChannels.has(projectId)) return;
    if (!window.Echo) {
      setTimeout(() => setupProjectListener(projectId), 200);
      return;
    }

    try {
      window.Echo.private(`project.${projectId}`)
        .listen("TrackCompletedAndPending", (e: any) => {
          eventBus.emit("force-cache-clear", {
            projectId: projectId,
            reason: "task-status-changed",
            timestamp: Date.now(),
            userId: e.userId || getCurrentUserId(),
          });
        })
        .listen("TrackProjectProgress", (e: any) => {
          eventBus.emit("force-cache-clear", {
            projectId: projectId,
            reason: "project-progress-updated",
            timestamp: Date.now(),
            userId: e.userId || getCurrentUserId(),
          });
        });
      joinedProjectChannels.add(projectId);
    } catch (error) {
      // Silent error handling
    }
  }

  function leaveProjectChannel(projectId: number) {
    if (window.Echo && joinedProjectChannels.has(projectId)) {
      window.Echo.leave(`project.${projectId}`);
      joinedProjectChannels.delete(projectId);
    }
  }

  // Global task listeners
  function setupTaskListener(taskId: number) {
    if (!taskId || joinedTaskChannels.has(taskId)) return;

    // Dùng helper realtime dùng chung thay vì tự listen Echo
    subscribeTaskComments(taskId);
    joinedTaskChannels.add(taskId);
  }

  function leaveTaskChannel(taskId: number) {
    if (!joinedTaskChannels.has(taskId)) return;
    unsubscribeTaskComments(taskId);
    joinedTaskChannels.delete(taskId);
  }

  // Cleanup all channels
  function cleanupAllChannels() {
    if (window.Echo) {
      // Leave user channel
      if (joinedUserChannel) {
        window.Echo.leave(`user.${joinedUserChannel}`);
        joinedUserChannel = null;
      }

      // Leave all project channels
      joinedProjectChannels.forEach((projectId) => {
        window.Echo.leave(`project.${projectId}`);
      });
      joinedProjectChannels.clear();

      // Leave all task channels
      joinedTaskChannels.forEach((taskId) => {
        unsubscribeTaskComments(taskId);
      });
      joinedTaskChannels.clear();
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

  return {
    echoReady,
    setupProjectListener,
    leaveProjectChannel,
    setupTaskListener,
    leaveTaskChannel,
    cleanupAllChannels,
    getActiveProjects: () => Array.from(joinedProjectChannels),
    getActiveTasks: () => Array.from(joinedTaskChannels),
  };
}
