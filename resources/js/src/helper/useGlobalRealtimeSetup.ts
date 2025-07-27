import { useAppGlobalRealtime } from "./useAppGlobalRealtime";

export function useGlobalRealtimeSetup() {
  const globalRealtime = useAppGlobalRealtime();

  // Setup global project listeners for multiple projects
  function setupProjectListeners(projectIds: number[]) {
    if (!projectIds || projectIds.length === 0) return;
    projectIds.forEach((projectId) => {
      globalRealtime.setupProjectListener(projectId);
    });
  }

  // Setup global task listeners for multiple tasks
  function setupTaskListeners(taskIds: number[]) {
    if (!taskIds || taskIds.length === 0) return;
    globalRealtime.setupMultipleTaskListeners(taskIds);
  }

  // Setup single project listener
  function setupProjectListener(projectId: number) {
    if (!projectId) return;
    globalRealtime.setupProjectListener(projectId);
  }

  // Setup single task listener
  function setupTaskListener(taskId: number) {
    if (!taskId) return;
    globalRealtime.setupTaskListener(taskId);
  }

  // Remove project listeners
  function removeProjectListeners(projectIds: number[]) {
    if (!projectIds || projectIds.length === 0) return;
    projectIds.forEach((projectId) => {
      globalRealtime.removeProjectListener(projectId);
    });
  }

  // Remove task listeners
  function removeTaskListeners(taskIds: number[]) {
    if (!taskIds || taskIds.length === 0) return;
    taskIds.forEach((taskId) => {
      globalRealtime.removeTaskListener(taskId);
    });
  }

  // Get active listeners
  function getActiveProjectListeners() {
    return globalRealtime.getActiveProjectListeners();
  }

  function getActiveTaskListeners() {
    return globalRealtime.getActiveTaskListeners();
  }

  // Cleanup all listeners
  function cleanupAllListeners() {
    globalRealtime.cleanupAllListeners();
  }

  return {
    setupProjectListeners,
    setupTaskListeners,
    setupProjectListener,
    setupTaskListener,
    removeProjectListeners,
    removeTaskListeners,
    getActiveProjectListeners,
    getActiveTaskListeners,
    cleanupAllListeners,
  };
}
