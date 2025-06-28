import { APP } from "../App/APP";
import { getUserData } from "./getUserData";
import { eventBus } from "./eventBus";
import { handleAuthError, isAuthError } from "./authInterceptor";

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

type HttpVerbType = "GET" | "POST" | "PUT" | "DELETE";

export function makeHttpReq<TInput, TResponse>(
  endpoint: string,
  verb: HttpVerbType,
  input?: TInput,
  showGlobalLoading: boolean = true
) {
  return new Promise<TResponse>(async (resolve, reject) => {
    if (showGlobalLoading) showLoading();
    try {
      const userData = getUserData();
      const authHeader = "Bearer " + userData?.token;

      const fetchOptions: RequestInit = {
        method: verb,
        headers: {
          "content-type": "application/json",
          Authorization: authHeader,
        },
      };

      // Chỉ thêm body nếu không phải GET
      if (verb !== "GET" && input !== undefined) {
        fetchOptions.body = JSON.stringify(input);
      }

      const res = await fetch(`${APP.apiBaseURL}/${endpoint}`, fetchOptions);
      const data: TResponse = await res.json();

      if (!res.ok) {
        if (showGlobalLoading) hideLoading();

        // Xử lý lỗi authentication
        if (
          isAuthError({ status: res.status, message: (data as any)?.message })
        ) {
          handleAuthError();
          return reject(new Error("Not authenticated"));
        }

        return reject(data);
      }
      if (showGlobalLoading) hideLoading();
      resolve(data);
    } catch (error) {
      if (showGlobalLoading) hideLoading();

      // Xử lý lỗi network hoặc lỗi khác
      if (isAuthError(error)) {
        handleAuthError();
      }

      reject(error);
    }
  });
}
