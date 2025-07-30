import { onMounted, onUnmounted } from "vue";
import eventBus from "./eventBus";

// Global real-time manager for app-wide event handling
export function useAppGlobalRealtime() {
  const activeProjectListeners = new Set<number>();
  const activeTaskListeners = new Set<number>();

  function setupProjectListener(projectId: number) {
    if (activeProjectListeners.has(projectId)) {
      return; // Already listening
    }

    try {
      window.Echo.private(`project.${projectId}`)
        .listen("TrackCompletedAndPending", (e: any) => {
          // Emit event for other components to handle
          eventBus.emit("force-cache-clear", {
            projectId,
            reason: "task-status-changed",
            timestamp: Date.now(),
            userId: e.userId,
          });
        })
        .listen("TrackProjectProgress", (e: any) => {
          // Emit event for project progress updates
          eventBus.emit("project-progress-updated", {
            projectId,
            progress: e.projectProgress,
            timestamp: Date.now(),
            userId: e.userId,
          });
        });

      activeProjectListeners.add(projectId);
    } catch (error) {
      // Silent error handling
    }
  }

  function setupTaskListener(taskId: number) {
    if (activeTaskListeners.has(taskId)) {
      return; // Already listening
    }

    try {
      window.Echo.private(`task.${taskId}`).listen(
        "TaskCommentCreated",
        (e: any) => {
          // Emit event for task comment updates
          eventBus.emit("task-comment-created", {
            taskId,
            comment: e.comment,
            timestamp: Date.now(),
          });
        }
      );

      activeTaskListeners.add(taskId);
    } catch (error) {
      // Silent error handling
    }
  }

  function setupMultipleTaskListeners(taskIds: number[]) {
    taskIds.forEach((taskId) => {
      if (!activeTaskListeners.has(taskId)) {
        setupTaskListener(taskId);
      }
    });
  }

  function removeProjectListener(projectId: number) {
    try {
      window.Echo.leave(`project.${projectId}`);
      activeProjectListeners.delete(projectId);
    } catch (error) {
      // Silent error handling
    }
  }

  function removeTaskListener(taskId: number) {
    try {
      window.Echo.leave(`task.${taskId}`);
      activeTaskListeners.delete(taskId);
    } catch (error) {
      // Silent error handling
    }
  }

  function cleanupAllListeners() {
    // Clean up all project listeners
    activeProjectListeners.forEach((projectId) => {
      try {
        window.Echo.leave(`project.${projectId}`);
      } catch (error) {
        // Silent error handling
      }
    });
    activeProjectListeners.clear();

    // Clean up all task listeners
    activeTaskListeners.forEach((taskId) => {
      try {
        window.Echo.leave(`task.${taskId}`);
      } catch (error) {
        // Silent error handling
      }
    });
    activeTaskListeners.clear();
  }

  function getActiveProjectListeners() {
    return Array.from(activeProjectListeners);
  }

  function getActiveTaskListeners() {
    return Array.from(activeTaskListeners);
  }

  // Initialize global real-time manager
  onMounted(() => {
    // Listen for setup events from other components
    eventBus.on("setup-project-listener", (event: any) => {
      setupProjectListener(event.projectId);
    });

    eventBus.on("setup-task-listener", (event: any) => {
      setupTaskListener(event.taskId);
    });

    eventBus.on("remove-project-listener", (event: any) => {
      removeProjectListener(event.projectId);
    });

    eventBus.on("remove-task-listener", (event: any) => {
      removeTaskListener(event.taskId);
    });

    eventBus.on("cleanup-all-listeners", () => {
      cleanupAllListeners();
    });
  });

  onUnmounted(() => {
    cleanupAllListeners();
  });

  return {
    setupProjectListener,
    setupTaskListener,
    setupMultipleTaskListeners,
    removeProjectListener,
    removeTaskListener,
    cleanupAllListeners,
    getActiveProjectListeners,
    getActiveTaskListeners,
  };
}
