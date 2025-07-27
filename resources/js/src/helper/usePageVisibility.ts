import { onMounted, onUnmounted, ref } from "vue";
import eventBus from "./eventBus";

export function usePageVisibility() {
  const isPageVisible = ref(!document.hidden);
  const lastFocusTime = ref(Date.now());

  const handleVisibilityChange = () => {
    const wasHidden = !isPageVisible.value;
    isPageVisible.value = !document.hidden;

    // Khi user focus lại vào tab sau khi đã ẩn
    if (wasHidden && isPageVisible.value) {
      const timeSinceLastFocus = Date.now() - lastFocusTime.value;

      // Nếu đã hơn 2 phút kể từ lần focus cuối, emit event để refresh cache
      if (timeSinceLastFocus > 2 * 60 * 1000) {
        eventBus.emit("page-refocus", {
          timeSinceLastFocus,
          currentTime: Date.now(),
        });
      }

      lastFocusTime.value = Date.now();
    }
  };

  const handleFocus = () => {
    const timeSinceLastFocus = Date.now() - lastFocusTime.value;

    // Nếu đã hơn 2 phút kể từ lần focus cuối, emit event để refresh cache
    if (timeSinceLastFocus > 2 * 60 * 1000) {
      eventBus.emit("page-refocus", {
        timeSinceLastFocus,
        currentTime: Date.now(),
      });
    }

    lastFocusTime.value = Date.now();
  };

  onMounted(() => {
    // Lắng nghe sự kiện visibility change
    document.addEventListener("visibilitychange", handleVisibilityChange);

    // Lắng nghe sự kiện focus khi user quay lại tab
    window.addEventListener("focus", handleFocus);

    // Lưu thời gian focus hiện tại
    lastFocusTime.value = Date.now();
  });

  onUnmounted(() => {
    document.removeEventListener("visibilitychange", handleVisibilityChange);
    window.removeEventListener("focus", handleFocus);
  });

  return {
    isPageVisible,
    lastFocusTime,
  };
}
