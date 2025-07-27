import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";
import { useDashboardStore } from "../store/dashboardStore";
import eventBus from "../../../../helper/eventBus";
import { getCurrentUserId } from "../../../../helper/getUserData";

type countProjectType = { count: number };

export function useGetTotalProject() {
  const countProject = ref<countProjectType>({} as countProjectType);
  async function getTotalProject() {
    try {
      const response = await makeHttpReq<
        undefined,
        { code: number; data: countProjectType; message: string }
      >(`count/projects`, "GET");

      countProject.value = response.data;
      updateData();
    } catch (error) {
      showErrorResponse(error);
      // Set default value if API fails
      countProject.value = { count: 0 };
    }
  }

  function updateData() {
    // Listen to user-specific project count updates
    const currentUserId = getCurrentUserId();
    if (currentUserId) {
      window.Echo.private(`user.${currentUserId}`).listen(
        "UserProjectCountUpdated",
        (e: { countProject: number; userId: number }) => {
          const newCount = { count: e.countProject };
          countProject.value = newCount;

          // Update store
          const dashboardStore = useDashboardStore();
          dashboardStore.setCountProject(newCount);

          // Update cache
          try {
            const dashboardCache = JSON.parse(
              localStorage.getItem("dashboardCache") || "{}"
            );
            dashboardCache["count_project"] = newCount;
            localStorage.setItem(
              "dashboardCache",
              JSON.stringify(dashboardCache)
            );
            localStorage.setItem(
              "count_project_timestamp",
              Date.now().toString()
            );

            // Emit event để DashboardPage có thể update reactive ref
            eventBus.emit("count-project-updated", newCount);
          } catch (error) {
            // Silent error handling
          }
        }
      );
    }
  }
  return { countProject, getTotalProject };
}
