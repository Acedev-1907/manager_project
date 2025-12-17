import { onMounted, onUnmounted, ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { SingleProjectResponseType } from "./getProjectDetail.type";
import eventBus from "../../../../helper/eventBus";
import { useRouter } from "vue-router";
import { showError } from "../../../../helper/alert";

export function useGetProjectDetail() {
  const loading = ref(false);
  const ProjectData = ref<SingleProjectResponseType>(
    {} as SingleProjectResponseType
  );
  let currentSlug = "";
  const router = useRouter();

  async function getProjectDetail(slug: string, showLoading = true) {
    try {
      if (showLoading) loading.value = true;
      currentSlug = slug;
      const data = await makeHttpReq<undefined, SingleProjectResponseType>(
        `projects/${slug}`,
        "GET"
      );

      ProjectData.value = data;
    } catch (error: any) {
      // Backend trả về nhiều dạng khác nhau, nên cố gắng đọc đủ các key có thể có
      const status =
        error?.status ||
        error?.response?.status ||
        error?.status_code ||
        error?.error?.status_code;

      // 404: project không tồn tại
      if (status === 404) {
        showError("Project does not exist!");
        router.push("/projects");
        return;
      }

      // 403: không có quyền truy cập project
      if (status === 403) {
        const message =
          error?.message ||
          error?.error?.message ||
          "You do not have permission to access this project";
        showError(message);
        router.push("/projects");
        return;
      }

      // Các lỗi khác: log ra để debug, không tự ý redirect
      console.error("Failed to get project detail:", error);
    } finally {
      if (showLoading) loading.value = false;
    }
  }

  // Hàm xử lý khi task được tạo
  function handleTaskCreated() {
    if (currentSlug) {
      getProjectDetail(currentSlug, false); // Gọi lại API với slug hiện tại, không bật loading page
    }
  }

  // Lắng nghe sự kiện taskCreated
  onMounted(() => {
    eventBus.on("taskCreated", handleTaskCreated);
  });

  // Gỡ bỏ lắng nghe khi component bị hủy
  onUnmounted(() => {
    eventBus.off("taskCreated", handleTaskCreated);
  });

  return { getProjectDetail, ProjectData, loading };
}
