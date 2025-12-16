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
    if (!currentUserId) return;
    if (typeof window === "undefined" || !window.Echo) {
      // Echo chưa sẵn sàng -> không đăng ký realtime, chỉ dùng dữ liệu API
      return;
    }

    window.Echo.private(`user.${currentUserId}`).listen(
        "UserProjectCountUpdated",
        (e: { countProject: number; userId: number }) => {
          const newCount = { count: e.countProject };
          countProject.value = newCount;

          // Update store
          const dashboardStore = useDashboardStore();
          // @ts-expect-error - Pinia store type inference issue
          dashboardStore.setCountProject(newCount);

          // Cache được quản lý bởi DashboardPage với memory storage
          // Chỉ cần update store và emit event
          try {
            // Emit event để DashboardPage có thể update reactive ref
            eventBus.emit("count-project-updated", newCount);
          } catch (error) {
            // Silent error handling
          }
        }
      );
  }
  return { countProject, getTotalProject };
}
