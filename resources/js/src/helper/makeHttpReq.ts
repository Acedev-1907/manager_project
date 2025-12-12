import { APP } from "../App/APP";
import { getUserData } from "./getUserData";
import eventBus from "./eventBus";
import { handleAuthError, isAuthError } from "./authInterceptor";

// Request counter for loading state management
let requestCount = 0;

/**
 * Show global loading indicator
 */
function showLoading(): void {
  if (requestCount === 0) {
    eventBus.emit("show-loading");
  }
  requestCount++;
}

/**
 * Hide global loading indicator
 */
function hideLoading(): void {
  requestCount--;
  if (requestCount <= 0) {
    requestCount = 0;
    eventBus.emit("hide-loading");
  }
}

// HTTP method types
type HttpVerbType = "GET" | "POST" | "PUT" | "DELETE" | "PATCH";

// Request options interface
interface RequestOptions {
  showGlobalLoading?: boolean;
  timeout?: number;
  headers?: Record<string, string>;
}

/**
 * Make HTTP request with standardized error handling and loading states
 *
 * @param endpoint API endpoint path
 * @param verb HTTP method
 * @param input Request payload (optional)
 * @param options Request configuration options
 * @returns Promise with response data
 */
export async function makeHttpReq<TInput, TResponse>(
  endpoint: string,
  verb: HttpVerbType,
  input?: TInput,
  options: RequestOptions = {}
): Promise<TResponse> {
  const { showGlobalLoading = true, timeout = 30000, headers = {} } = options;

    if (showGlobalLoading) {
      showLoading();
    }

    try {
      // Get user authentication data
      const userData = getUserData();
      const authHeader = userData?.token ? `Bearer ${userData.token}` : "";

      // Build request URL
      let url = `${APP.apiBaseURL}/${endpoint}`;

      // Prepare fetch options
    const fetchOptions: RequestInit = {
        method: verb,
        headers: {
          ...(authHeader && { Authorization: authHeader }),
          ...headers,
          "Content-Type": "application/json",
        },
        credentials: "include",
      };

      // Handle request body for non-GET requests
      if (verb !== "GET" && input !== undefined) {
        fetchOptions.body = JSON.stringify(input);
      } else if (verb === "GET" && input) {
        // Append query parameters to URL for GET requests
        const params = new URLSearchParams(input as any).toString();
        url += `?${params}`;
      }

      // Setup timeout handling
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), timeout);
      fetchOptions.signal = controller.signal;

      // Make the request
      const response = await fetch(url, fetchOptions);
      clearTimeout(timeoutId);

      // Parse response
      let data;
      try {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
          data = await response.json();
        } else {
        // Non-JSON response - likely HTML redirect or error page
        if (response.status === 401 || response.status === 403) {
          handleAuthError();
          throw new Error("Not authenticated");
        }
        throw new Error("Invalid response format");
        }
      } catch (e) {
      if (response.status === 401 || response.status === 403) {
        handleAuthError();
        throw new Error("Not authenticated");
      }
      throw new Error("Failed to parse response");
      }

      // Handle non-successful responses
      if (!response.ok) {
      const errorInfo = {
            status: response.status,
        message: data?.message || data?.error?.message,
            response: { status: response.status, data },
      };

      if (isAuthError(errorInfo)) {
          handleAuthError();
        throw data || errorInfo;
        }

      throw data || errorInfo;
    }

    return data;
  } finally {
    if (showGlobalLoading) {
      hideLoading();
    }
  }
}
