import { APP } from "../App/APP";
import { getUserData } from "./getUserData";

type HttpVerbType = "GET" | "POST" | "PUT" | "DELETE";

export function makeHttpReq<TInput, TResponse>(
  endpoint: string,
  verb: HttpVerbType,
  input?: TInput
) {
  return new Promise<TResponse>(async (resolve, reject) => {
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
        return reject(data);
      }
      resolve(data);
    } catch (error) {
      reject(error);
    }
  });
}
