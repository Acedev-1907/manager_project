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

    if (typeof window === "undefined" || !window.Echo) {
      // Echo chưa sẵn sàng -> không đăng ký realtime
      return;
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
        })
        .listen("ColumnAdded", (e: any) => {
          // Emit event for column added updates
          eventBus.emit("column-added", {
            projectId,
            column: e.column,
            timestamp: Date.now(),
            userId: e.userId,
          });
        })
        .listen("ColumnDeleted", (e: any) => {
          // Emit event for column deleted updates
          eventBus.emit("column-deleted", {
            projectId,
            columnId: e.columnId,
            timestamp: Date.now(),
            userId: e.userId,
          });
        })
        .listen("TaskDragStarted", (e: any) => {
          // Emit event for task drag started
          eventBus.emit("task-drag-started", {
            projectId,
            taskId: e.taskId,
            userId: e.userId,
            userName: e.userName,
            userAvatar: e.userAvatar,
            timestamp: Date.now(),
          });
        })
        .listen("TaskDragEnded", (e: any) => {
          // Emit event for task drag ended
          eventBus.emit("task-drag-ended", {
            projectId,
            taskId: e.taskId,
            userId: e.userId,
            timestamp: Date.now(),
          });
        })
        .listen("TaskDragOverColumn", (e: any) => {
          // Emit event for task drag over column
          eventBus.emit("task-drag-over-column", {
            projectId,
            taskId: e.taskId,
            columnId: e.columnId,
            columnStatus: e.columnStatus,
            userId: e.userId,
            userName: e.userName,
            userAvatar: e.userAvatar,
            timestamp: Date.now(),
          });
        })
        .listen("TaskStatusChanged", (e: any) => {
          // Emit event for task status changed
          eventBus.emit("task-status-changed-realtime", {
            projectId,
            task: e.task,
            taskId: e.taskId,
            status: e.status,
            userId: e.userId,
            timestamp: e.updatedAt || Date.now(),
          });
        })
        // Whisper listener để hiển thị gần như tức thời khi drag
        .listenForWhisper("drag-started", (e: any) => {
          eventBus.emit("task-drag-started", {
            projectId,
            taskId: e.task_id,
            userId: e.user_id,
            userName: e.user_name,
            userAvatar: e.user_avatar,
            timestamp: Date.now(),
          });
        })
        .listenForWhisper("drag-ended", (e: any) => {
          eventBus.emit("task-drag-ended", {
            projectId,
            taskId: e.task_id,
            userId: e.user_id,
            timestamp: Date.now(),
          });
        })
        .listenForWhisper("drag-over-column", (e: any) => {
          eventBus.emit("task-drag-over-column", {
            projectId,
            taskId: e.task_id,
            columnId: e.column_id,
            columnStatus: e.column_status,
            userId: e.user_id,
            userName: e.user_name,
            userAvatar: e.user_avatar,
            timestamp: Date.now(),
          });
        })
        .listenForWhisper("task-status-optimistic", (e: any) => {
          eventBus.emit("task-status-optimistic", {
            projectId,
            taskId: e.task_id,
            status: e.status,
            userId: e.user_id,
            timestamp: Date.now(),
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

    if (typeof window === "undefined" || !window.Echo) {
      return;
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
