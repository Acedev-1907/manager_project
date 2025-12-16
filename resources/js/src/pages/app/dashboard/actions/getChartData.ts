import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showErrorResponse } from "../../../../helper/utils";

type chartDataType = {
  tasks: Array<number>;
  columnNames: Array<string>;
  columnColors: Array<string>;
  progress: number;
};

export function useGetChartData() {
  const chartData = ref<chartDataType>({} as chartDataType);
  let currentProjectId: number | null = null;

  async function getChartData(projectsId: number) {
    try {
      const data = await makeHttpReq<undefined, chartDataType>(
        `chart-data/projects?projectId=${projectsId}`,
        "GET"
      );
      chartData.value = data;
      currentProjectId = projectsId;
      updateData();
    } catch (error) {
      showErrorResponse(error);
    }
  }

  function updateData() {
    if (!currentProjectId) return;
    if (typeof window === "undefined" || !window.Echo) {
      // Nếu Echo chưa sẵn sàng thì bỏ qua phần realtime, chỉ dùng dữ liệu API
      return;
    }

    // Sử dụng private channels với projectId động
    window.Echo.private(`project.${currentProjectId}`).listen(
      "TrackProjectProgress",
      (e: { projectProgress: number }) => {
        chartData.value.progress = 0;
        setTimeout(() => (chartData.value.progress = e.projectProgress), 1000);
      }
    );

    window.Echo.private(`project.${currentProjectId}`).listen(
      "TrackCompletedAndPending",
      () => {
        // Refresh data từ API để có thông tin cột mới nhất
        getChartData(currentProjectId!);
      }
    );
  }
  return { chartData, getChartData };
}
