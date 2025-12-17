import router from "../router";
import { useAuthStore } from "../state/authStore";

/**
 * Check if route is auth route
 */
function isAuthRoute(path: string): boolean {
  return (
    path.startsWith("/auth") ||
    path.includes("/login") ||
    path.includes("/register") ||
    path.includes("/reset-password") ||
    path.includes("/change-password")
  );
}

/**
 * Handle authentication error
 * Only redirects to login if not already on auth route
 * This prevents unnecessary redirects and improves UX
 */
export function handleAuthError() {
  // Clear user data
  localStorage.removeItem("userData");
  
  // Update auth store
  const authStore = useAuthStore();
  // @ts-expect-error - Pinia store type inference issue
  authStore.clearAuth();

  // Get current path
  const currentPath = router.currentRoute.value.path;

  // Don't redirect if already on auth route
  if (isAuthRoute(currentPath)) {
    return;
  }

  // Only redirect if not already going to login
  if (currentPath !== "/login") {
    router.push("/login");
  }
}

/**
 * Check if error is an authentication error
 * 
 * @param error Error object
 * @returns true if error is auth-related
 */
export function isAuthError(error: any): boolean {
  // Check HTTP status codes
  // Chỉ coi 401 là lỗi auth (hết hạn / chưa đăng nhập)
  if (error?.status === 401) return true;
  if (error?.response?.status === 401) return true;

  // Check error messages
  if (error?.message === "Not authenticated") return true;
  if (error?.response?.data?.message === "Not authenticated") return true;

  // Network errors should not be treated as auth errors
  // They will be handled separately
  return false;
}

/**
 * Handle general errors
 * Currently not used but kept for future extensibility
 */
export function handleGeneralError(): void {
  // Future: Add general error handling logic here
  // For now, auth errors are handled by handleAuthError()
}

export const authInterceptor = (httpClient: any) => {
  httpClient.interceptors.response.use(
    (response: any) => response,
    (err: any) => {
      if (isAuthError(err)) {
        handleAuthError();
      }
      return Promise.reject(err);
    }
  );
};
