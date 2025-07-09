import { onMounted, onUnmounted, ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { SingleProjectResponseType } from "./getProjectDetail.type";
import { showErrorResponse } from "../../../../helper/utils";
import { eventBus } from "../../../../helper/eventBus";
import { useRouter } from "vue-router";

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
      loading.value = false;
      ProjectData.value = data;
    } catch (error: any) {
      showErrorResponse(error);
      router.push("/projects");
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
