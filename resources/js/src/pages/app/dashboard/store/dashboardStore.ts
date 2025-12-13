import { defineStore } from "pinia";

export const useDashboardStore = defineStore("dashboard", {
  state: () => ({
    pinnedProject: null as any, // Runtime cache only - use memory cache
    countProject: null as any, // Runtime cache only - use memory cache
    chartData: null as any, // Runtime cache only - use memory cache
  }),
  
  getters: {
    hasPinnedProject: (state: { pinnedProject: any }) => state.pinnedProject !== null,
    hasChartData: (state: { chartData: any }) => state.chartData !== null,
    projectCount: (state: { countProject: any }) => state.countProject?.count || 0,
  },
  
  actions: {
    setPinnedProject(data: any) {
      this.pinnedProject = data;
    },
    clearPinnedProject() {
      this.pinnedProject = null;
    },
    setCountProject(data: any) {
      this.countProject = data;
    },
    clearCountProject() {
      this.countProject = null;
    },
    setChartData(data: any) {
      this.chartData = data;
    },
    clearChartData() {
      this.chartData = null;
    },
    clearAll() {
      this.pinnedProject = null;
      this.countProject = null;
      this.chartData = null;
    },
  },
  
  // Không persist dashboard data - nên dùng memory cache với TTL ngắn
  // Dashboard data thay đổi thường xuyên và không cần persist
  // persist: false, // Explicitly disable persistence
});
