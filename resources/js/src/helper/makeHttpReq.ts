import { APP } from "../App/APP";
import { getUserData } from "./getUserData";
import { eventBus } from "./eventBus";
import {
  handleAuthError,
  isAuthError,
  handleGeneralError,
} from "./authInterceptor";

let requestCount = 0;

function showLoading() {
  if (requestCount === 0) eventBus.emit("show-loading");
  requestCount++;
}

function hideLoading() {
  requestCount--;
  if (requestCount <= 0) {
    requestCount = 0;
    eventBus.emit("hide-loading");
  }
}

type HttpVerbType = "GET" | "POST" | "PUT" | "DELETE" | "PATCH";

interface RequestOptions {
  showGlobalLoading?: boolean;
  timeout?: number;
  headers?: Record<string, string>;
}

export function makeHttpReq<TInput, TResponse>(
  endpoint: string,
  verb: HttpVerbType,
  input?: TInput,
  options: RequestOptions = {}
): Promise<TResponse> {
  const { showGlobalLoading = true, timeout = 30000, headers = {} } = options;

  return new Promise<TResponse>(async (resolve, reject) => {
    if (showGlobalLoading) showLoading();

    try {
      const userData = getUserData();
      const authHeader = userData?.token ? `Bearer ${userData.token}` : "";

      let url = `${APP.apiBaseURL}/${endpoint}`;
      let fetchOptions: RequestInit = {
        method: verb,
        headers: {
          ...(authHeader && { Authorization: authHeader }),
          ...headers,
          "Content-Type": "application/json",
        },
        credentials: "include",
      };

      if (verb !== "GET" && input !== undefined) {
        fetchOptions.body = JSON.stringify(input);
      } else if (verb === "GET" && input) {
        // append params to url
        const params = new URLSearchParams(input as any).toString();
        url += `?${params}`;
      }

      // Timeout logic
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), timeout);
      fetchOptions.signal = controller.signal;

      const response = await fetch(url, fetchOptions);
      clearTimeout(timeoutId);

      const data = await response.json();

      if (!response.ok) {
        if (
          isAuthError({
            status: response.status,
            message: data?.message,
          })
        ) {
          handleAuthError();
          if (showGlobalLoading) hideLoading();
          return reject(new Error("Not authenticated"));
        }
        if (showGlobalLoading) hideLoading();
        return reject(data);
      }

      if (showGlobalLoading) hideLoading();
      resolve(data);
    } catch (error: any) {
      if (showGlobalLoading) hideLoading();
      handleGeneralError(error);
      reject(error);
    }
  });
}
