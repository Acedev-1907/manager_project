import { ref } from "vue";
import { showErrorResponse } from "./utils";

export function useErrorHandler() {
  const hasError = ref(false);
  const errorMessage = ref("");

  /**
   * @param operation Hàm async cần thực thi
   * @param customErrorMessage Thông báo lỗi custom
   * @param options { showPopup?: boolean } - Nếu false sẽ không hiện popup lỗi
   */
  const withErrorHandling = async <T>(
    operation: () => Promise<T>,
    customErrorMessage?: string,
    options?: { showPopup?: boolean }
  ): Promise<T | null> => {
    try {
      hasError.value = false;
      errorMessage.value = "";
      return await operation();
    } catch (error: any) {
      hasError.value = true;
      errorMessage.value =
        customErrorMessage || error?.message || "An error occurred";
      if (options?.showPopup !== false) {
        showErrorResponse(error);
      }
      return null;
    }
  };

  const clearError = () => {
    hasError.value = false;
    errorMessage.value = "";
  };

  return {
    hasError,
    errorMessage,
    withErrorHandling,
    clearError,
  };
}
