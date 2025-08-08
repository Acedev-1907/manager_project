import { APP } from "../App/APP";
import { getUserData } from "./getUserData";
import eventBus from "./eventBus";
import {
  handleAuthError,
  isAuthError,
  handleGeneralError,
} from "./authInterceptor";

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
export function makeHttpReq<TInput, TResponse>(
  endpoint: string,
  verb: HttpVerbType,
  input?: TInput,
  options: RequestOptions = {}
): Promise<TResponse> {
  const { showGlobalLoading = true, timeout = 30000, headers = {} } = options;

  return new Promise<TResponse>(async (resolve, reject) => {
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
      let fetchOptions: RequestInit = {
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
          data = await response.text();
          // Handle HTML responses as authentication errors
          handleAuthError();
          if (showGlobalLoading) hideLoading();
          return reject(new Error("Not authenticated"));
        }
      } catch (e) {
        // Handle parsing errors as authentication errors
        handleAuthError();
        if (showGlobalLoading) hideLoading();
        return reject(new Error("Not authenticated"));
      }

      // Handle non-successful responses
      if (!response.ok) {
        if (
          isAuthError({
            status: response.status,
            message: data?.message,
            response: { status: response.status, data },
          })
        ) {
          handleAuthError();
          if (showGlobalLoading) hideLoading();
          return reject(data);
        }
        if (showGlobalLoading) hideLoading();
        return reject(data);
      }

      // Success response
      if (showGlobalLoading) hideLoading();
      resolve(data);
    } catch (error: any) {
      // Handle network and other errors
      if (showGlobalLoading) hideLoading();
      // handleGeneralError(error);
      reject(error);
    }
  });
}
