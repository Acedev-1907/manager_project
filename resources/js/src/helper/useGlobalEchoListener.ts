import { onMounted, onUnmounted, ref, watch, Ref } from "vue";
import eventBus from "./eventBus";
import { getCurrentUserId } from "./getUserData";

let joinedUserChannel: string | number | null = null;
const joinedProjectChannels: Set<number> = new Set();
const joinedTaskChannels: Set<number> = new Set();

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
      // Silent error handling
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
    if (!window.Echo) {
      setTimeout(() => setupTaskListener(taskId), 200);
      return;
    }

    try {
      window.Echo.private(`task.${taskId}`).listen(
        "TaskCommentCreated",
        (e: any) => {
          eventBus.emit("task-comment-created", {
            taskId: taskId,
            comment: e.comment,
            timestamp: Date.now(),
          });
        }
      );
      joinedTaskChannels.add(taskId);
    } catch (error) {
      // Silent error handling
    }
  }

  function leaveTaskChannel(taskId: number) {
    if (window.Echo && joinedTaskChannels.has(taskId)) {
      window.Echo.leave(`task.${taskId}`);
      joinedTaskChannels.delete(taskId);
    }
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
        window.Echo.leave(`task.${taskId}`);
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
