import { ref } from "vue";
import { showErrorResponse } from "./utils";

export function useErrorHandler() {
  const hasError = ref(false);
  const errorMessage = ref("");

  const withErrorHandling = async <T>(
    operation: () => Promise<T>,
    customErrorMessage?: string
  ): Promise<T | null> => {
    try {
      hasError.value = false;
      errorMessage.value = "";
      return await operation();
    } catch (error: any) {
      hasError.value = true;
      errorMessage.value =
        customErrorMessage || error?.message || "An error occurred";
      showErrorResponse(error);
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
