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

      const fetchOptions: RequestInit = {
        method: verb,
        headers: {
          "Content-Type": "application/json",
          ...(authHeader && { Authorization: authHeader }),
          ...headers,
        },
        signal: AbortSignal.timeout(timeout),
      };

      // Chỉ thêm body nếu không phải GET và có input
      if (verb !== "GET" && input !== undefined) {
        fetchOptions.body = JSON.stringify(input);
      }

      const response = await fetch(
        `${APP.apiBaseURL}/${endpoint}`,
        fetchOptions
      );

      if (!response.ok) {
        const errorData = await response
          .json()
          .catch(() => ({ message: "Network error", status: response.status }));

        if (showGlobalLoading) hideLoading();

        // Xử lý lỗi authentication
        if (
          isAuthError({ status: response.status, message: errorData?.message })
        ) {
          handleAuthError();
          return reject(new Error("Not authenticated"));
        }

        return reject(errorData);
      }

      const data: TResponse = await response.json();

      if (showGlobalLoading) hideLoading();
      resolve(data);
    } catch (error) {
      if (showGlobalLoading) hideLoading();

      // Xử lý lỗi network hoặc lỗi khác
      if (isAuthError(error)) {
        handleAuthError();
      } else {
        // Xử lý các lỗi chung khác
        handleGeneralError(error);
      }

      reject(error);
    }
  });
}
