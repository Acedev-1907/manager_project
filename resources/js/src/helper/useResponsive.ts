import { ref, onMounted, onUnmounted, computed } from "vue";

export interface Breakpoints {
  xs: number;
  sm: number;
  md: number;
  lg: number;
  xl: number;
}

export interface ResponsiveState {
  width: number;
  height: number;
  isMobile: boolean;
  isTablet: boolean;
  isDesktop: boolean;
  breakpoint: keyof Breakpoints;
}

const DEFAULT_BREAKPOINTS: Breakpoints = {
  xs: 0,
  sm: 576,
  md: 768,
  lg: 992,
  xl: 1200,
};

export function useResponsive(customBreakpoints?: Partial<Breakpoints>) {
  const breakpoints = { ...DEFAULT_BREAKPOINTS, ...customBreakpoints };

  const state = ref<ResponsiveState>({
    width: 0,
    height: 0,
    isMobile: false,
    isTablet: false,
    isDesktop: false,
    breakpoint: "xs",
  });

  const updateDimensions = () => {
    const width = window.innerWidth;
    const height = window.innerHeight;

    state.value.width = width;
    state.value.height = height;

    // Determine breakpoint
    if (width >= breakpoints.xl) {
      state.value.breakpoint = "xl";
    } else if (width >= breakpoints.lg) {
      state.value.breakpoint = "lg";
    } else if (width >= breakpoints.md) {
      state.value.breakpoint = "md";
    } else if (width >= breakpoints.sm) {
      state.value.breakpoint = "sm";
    } else {
      state.value.breakpoint = "xs";
    }

    // Set device type flags
    state.value.isMobile = width < breakpoints.md;
    state.value.isTablet = width >= breakpoints.md && width < breakpoints.lg;
    state.value.isDesktop = width >= breakpoints.lg;
  };

  const debouncedUpdate = debounce(updateDimensions, 100);

  onMounted(() => {
    updateDimensions();
    window.addEventListener("resize", debouncedUpdate);
  });

  onUnmounted(() => {
    window.removeEventListener("resize", debouncedUpdate);
  });

  return {
    state,
    breakpoints,
    updateDimensions,
  };
}

// Utility function for debouncing
function debounce<T extends (...args: any[]) => any>(
  func: T,
  delay: number
): (...args: Parameters<T>) => void {
  let timeoutId: number;

  return function (...args: Parameters<T>) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func(...args), delay);
  };
}

// Predefined responsive helpers
export function useMobile() {
  const { state } = useResponsive();
  return computed(() => state.value.isMobile);
}

export function useTablet() {
  const { state } = useResponsive();
  return computed(() => state.value.isTablet);
}

export function useDesktop() {
  const { state } = useResponsive();
  return computed(() => state.value.isDesktop);
}
