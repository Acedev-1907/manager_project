import { ref, onMounted, onUnmounted } from "vue";

interface ResponsiveState {
  isMobile: boolean;
  isTablet: boolean;
  isDesktop: boolean;
  screenWidth: number;
}

export function useResponsive() {
  const state = ref<ResponsiveState>({
    isMobile: false,
    isTablet: false,
    isDesktop: false,
    screenWidth: 0,
  });

  const updateResponsiveState = () => {
    const width = window.innerWidth;
    state.value.screenWidth = width;
    state.value.isMobile = width < 768;
    state.value.isTablet = width >= 768 && width < 1024;
    state.value.isDesktop = width >= 1024;
  };

  onMounted(() => {
    updateResponsiveState();
    window.addEventListener("resize", updateResponsiveState);
  });

  onUnmounted(() => {
    window.removeEventListener("resize", updateResponsiveState);
  });

  return { state };
}
