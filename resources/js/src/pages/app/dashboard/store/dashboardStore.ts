import { defineStore } from "pinia";

export const useDashboardStore = defineStore("dashboard", {
  state: () => ({
    pinnedProject: null as any,
    countProject: null as any,
    chartData: null as any,
  }),
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
  },
});
