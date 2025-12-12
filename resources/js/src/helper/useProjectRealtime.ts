import { ref, onMounted, onUnmounted } from "vue";
import eventBus from "./eventBus";

interface ProjectEvent {
  projectId: number;
  eventType: string;
  data: any;
  updatedAt: string;
  userId?: number;
}

class ProjectRealtimeManager {
  private listeners: Map<number, any> = new Map();
  private isInitialized: boolean = false;
  private errorCount: number = 0;
  private lastErrorTime: number = 0;
  private connectionRetries: Map<number, number> = new Map();
  private maxRetries: number = 3;

  async initialize(): Promise<void> {
    if (this.isInitialized) {
      return;
    }

    try {
      // Initialize Echo if not already done
      if (!window.Echo) {
        return;
      }

      this.isInitialized = true;
    } catch (error) {
      // Silent error handling
    }
  }

  public async listenToProject(
    projectId: number,
    options: {
      onTaskStatusChanged?: (data: any) => void;
      onProjectProgressUpdated?: (data: any) => void;
      onError?: (error: any) => void;
    } = {}
  ) {
    if (!window.Echo || !projectId) {
      return;
    }

    // Kiểm tra xem đã có listener cho project này chưa
    const existingListener = this.listeners.get(projectId);
    if (existingListener) {
      return;
    }

    try {
      const channel = window.Echo.private(`project.${projectId}`);
      const listeners: string[] = [];

      // Listen for task status changes
      if (options.onTaskStatusChanged) {
        channel.listen("TrackCompletedAndPending", (e: ProjectEvent) => {
          try {
            // Validate event data
            if (e && e.projectId && e.eventType === "task_status_changed") {
              options.onTaskStatusChanged?.(e);
              this.emitGlobalEvent("task-status-changed", e);
            } else {
              // Silent error handling
            }
          } catch (_error) {
            // Silent error handling
            options.onError?.(_error);
          }
        });
        listeners.push("TrackCompletedAndPending");
      }

      // Listen for project progress updates
      if (options.onProjectProgressUpdated) {
        channel.listen("TrackProjectProgress", (e: ProjectEvent) => {
          try {
            // Validate event data
            if (
              e &&
              e.projectId &&
              e.eventType === "project_progress_updated"
            ) {
              options.onProjectProgressUpdated?.(e);
              this.emitGlobalEvent("project-progress-updated", e);
            } else {
              // Silent error handling
            }
          } catch (_error) {
            // Silent error handling
            options.onError?.(_error);
          }
        });
        listeners.push("TrackProjectProgress");
      }

      // Store the listener
      this.listeners.set(projectId, {
        projectId,
        channel,
        listeners,
      });

      // Reset retry count for this project
      this.connectionRetries.set(projectId, 0);
    } catch (_error) {
      // Silent error handling
      this.handleConnectionError(projectId, _error, options);
    }
  }

  public async listenToMultipleProjects(
    projects: Array<{ id: number }>,
    options: {
      onTaskStatusChanged?: (data: any) => void;
      onProjectProgressUpdated?: (data: any) => void;
      onError?: (error: any) => void;
    } = {}
  ) {
    for (const project of projects) {
      await this.listenToProject(project.id, options);
    }
  }

  public cleanupProject(projectId: number) {
    const listener = this.listeners.get(projectId);
    if (listener) {
      try {
        listener.channel.stopListening("TrackCompletedAndPending");
        listener.channel.stopListening("TrackProjectProgress");
        window.Echo?.leave(`project.${projectId}`);
        this.listeners.delete(projectId);
        this.connectionRetries.delete(projectId); // Also delete retry count
      } catch (_error) {
        // Silent error handling
      }
    }
  }

  public cleanupAll() {
    for (const [projectId] of this.listeners) {
      this.cleanupProject(projectId);
    }
    this.listeners.clear();
    this.connectionRetries.clear(); // Clear retry counts
  }

  private emitGlobalEvent(eventType: string, data: any) {
    // Emit events immediately without debouncing for faster response
    eventBus.emit("project-realtime-event", {
      type: eventType,
      data,
      timestamp: Date.now(),
    });

    // Emit specific cache clear events for immediate UI updates
    if (eventType === "task-status-changed") {
      eventBus.emit("force-cache-clear", {
        projectId: data.projectId,
        userId: data.userId, // Include userId from event data
        reason: "task-status-changed-global",
        timestamp: Date.now(),
      });
    } else if (eventType === "project-progress-updated") {
      eventBus.emit("force-cache-clear", {
        projectId: data.projectId,
        userId: data.userId, // Include userId from event data
        reason: "project-progress-updated-global",
        timestamp: Date.now(),
      });
    }
  }

  public getActiveProjects(): number[] {
    return Array.from(this.listeners.keys());
  }

  public getErrorCount() {
    return this.errorCount;
  }

  public resetErrorCount() {
    this.errorCount = 0;
  }

  private handleConnectionError(projectId: number, error: any, options: any) {
    const currentRetries = this.connectionRetries.get(projectId) || 0;
    if (currentRetries < this.maxRetries) {
      setTimeout(() => {
        this.listenToProject(projectId, options);
      }, 1000 * (currentRetries + 1)); // Exponential backoff
      this.connectionRetries.set(projectId, currentRetries + 1);
    } else {
      this.errorCount++; // Increment error count for this project
      options.onError?.(error);
    }
  }
}

// Singleton instance
const projectRealtimeManager = new ProjectRealtimeManager();

// Vue composable
export function useProjectRealtime() {
  const isInitialized = ref(false);
  const activeListeners = new Map<number, any>();

  function initialize() {
    if (isInitialized.value) return;

    try {
      if (!window.Echo) {
        // Silent error handling
        return;
      }
      isInitialized.value = true;
    } catch (error) {
      // Silent error handling
    }
  }

  function listenToProject(projectId: number) {
    if (!projectId || activeListeners.has(projectId)) {
      return;
    }

    if (!window.Echo) {
      // Silent error handling
      return;
    }

    try {
      const listener = window.Echo.private(`project.${projectId}`)
        .listen("TrackCompletedAndPending", (e: any) => {
          if (!e || typeof e !== "object") {
            // Silent error handling
            return;
          }

          try {
            eventBus.emit("force-cache-clear", {
              projectId,
              reason: "task-status-changed",
              timestamp: Date.now(),
              userId: e.userId,
            });
          } catch (error) {
            // Silent error handling
          }
        })
        .listen("TrackProjectProgress", (e: any) => {
          if (!e || typeof e !== "object") {
            // Silent error handling
            return;
          }

          try {
            eventBus.emit("project-progress-updated", {
              projectId,
              progress: e.progress,
              timestamp: Date.now(),
            });
          } catch (error) {
            // Silent error handling
          }
        });

      activeListeners.set(projectId, listener);
    } catch (error) {
      // Silent error handling
    }
  }

  function stopListeningToProject(projectId: number) {
    try {
      const listener = activeListeners.get(projectId);
      if (listener && window.Echo) {
        window.Echo.leave(`project.${projectId}`);
        activeListeners.delete(projectId);
      }
    } catch (error) {
      // Silent error handling
    }
  }

  function cleanupAllListeners() {
    try {
      activeListeners.forEach((listener, projectId) => {
        if (window.Echo) {
          window.Echo.leave(`project.${projectId}`);
        }
      });
      activeListeners.clear();
    } catch (error) {
      // Silent error handling
    }
  }

  onMounted(() => {
    initialize();
  });

  onUnmounted(() => {
    cleanupAllListeners();
  });

  return {
    listenToProject,
    stopListeningToProject,
    cleanupAllListeners,
    isInitialized,
  };
}

export default projectRealtimeManager;
