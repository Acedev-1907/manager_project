import { onMounted, onUnmounted } from "vue";
import eventBus from "./eventBus";

export function useProjectRealtimeCacheClear() {
  const handleNewProject = (eventData: any) => {
    // Xóa cache project trong localStorage
    localStorage.removeItem("projectCache");

    // Xóa cache trong sessionStorage nếu có
    sessionStorage.removeItem("projectCache");

    // Xóa các cache liên quan đến project
    const keysToRemove = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && key.startsWith("project_page_")) {
        keysToRemove.push(key);
      }
    }
    keysToRemove.forEach((key) => localStorage.removeItem(key));

    // Emit event để các component khác có thể cập nhật
    eventBus.emit("project-cache-cleared", eventData);
  };

  const handleUserRemoved = (eventData: any) => {
    // Xóa cache project trong localStorage
    localStorage.removeItem("projectCache");

    // Xóa cache trong sessionStorage nếu có
    sessionStorage.removeItem("projectCache");

    // Xóa các cache liên quan đến project
    const keysToRemove = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && key.startsWith("project_page_")) {
        keysToRemove.push(key);
      }
    }
    keysToRemove.forEach((key) => localStorage.removeItem(key));

    // Emit event để các component khác có thể cập nhật
    eventBus.emit("project-cache-cleared", eventData);
  };

  onMounted(() => {
    eventBus.on("new-project-for-members", handleNewProject);
    eventBus.on("user-removed-from-project", handleUserRemoved);
  });

  onUnmounted(() => {
    eventBus.off("new-project-for-members", handleNewProject);
    eventBus.off("user-removed-from-project", handleUserRemoved);
  });
}
