import { onMounted } from "vue";
import eventBus from "./eventBus";

export function useProjectRealtimeCacheClear() {
  onMounted(() => {
    eventBus.on("new-project-for-members", () => {
      localStorage.removeItem("projectCache");
    });
    eventBus.on("user-removed-from-project", () => {
      localStorage.removeItem("projectCache");
    });
  });
}
