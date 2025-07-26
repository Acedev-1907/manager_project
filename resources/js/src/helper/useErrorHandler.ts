import { ref } from "vue";
import { showError } from "./alert";
import { isAuthError, handleAuthError } from "./authInterceptor";

export interface ErrorState {
  error: string | null;
  isLoading: boolean;
}

export function useErrorHandler() {
  const errorState = ref<ErrorState>({
    error: null,
    isLoading: false,
  });

  const handleError = (error: unknown, customMessage?: string) => {
    // Reset error state
    errorState.value.error = null;

    // Handle auth errors
    if (isAuthError(error)) {
      handleAuthError();
      return;
    }

    // Handle different error types
    let errorMessage = customMessage || "An error occurred";

    if (Array.isArray(error)) {
      errorMessage = error[0] || errorMessage;
    } else if (typeof error === "string") {
      errorMessage = error;
    } else if (typeof error === "object" && error && "message" in error) {
      errorMessage = (error as any).message;
    }

    // Set error state
    errorState.value.error = errorMessage;

    // Show error notification
    showError(errorMessage);

    // Log error for debugging
    console.error("Error occurred:", error);
  };

  const clearError = () => {
    errorState.value.error = null;
  };

  const setLoading = (loading: boolean) => {
    errorState.value.isLoading = loading;
  };

  const withErrorHandling = async <T>(
    asyncFn: () => Promise<T>,
    customMessage?: string
  ): Promise<T | null> => {
    setLoading(true);
    clearError();

    try {
      const result = await asyncFn();
      setLoading(false);
      return result;
    } catch (error) {
      handleError(error, customMessage);
      setLoading(false);
      return null;
    }
  };

  return {
    errorState,
    handleError,
    clearError,
    setLoading,
    withErrorHandling,
  };
}
