import router from "../router";

export function handleAuthError() {
  // Xóa dữ liệu user khỏi localStorage
  localStorage.removeItem("userData");

  // Luôn chuyển về trang login, bất kể đang ở đâu
  const currentPath = router.currentRoute.value.path;

  // Nếu đang ở trang auth (login/register), không cần chuyển hướng
  if (
    currentPath.startsWith("/auth") ||
    currentPath.includes("/login") ||
    currentPath.includes("/register")
  ) {
    return;
  }

  // Chuyển về trang login
  router.push("/login");
}

export function isAuthError(error: any): boolean {
  // Kiểm tra các trường hợp lỗi authentication
  if (error?.status === 401) return true;
  if (error?.status === 405) return true; // Thêm lỗi 405
  if (error?.message === "Not authenticated") return true;
  if (error?.response?.status === 401) return true;
  if (error?.response?.status === 405) return true; // Thêm lỗi 405
  if (error?.response?.data?.message === "Not authenticated") return true;

  // Thêm kiểm tra cho network errors và timeout
  if (error?.name === "AbortError" || error?.name === "TimeoutError")
    return true;
  if (error?.message?.includes("fetch")) return true;

  // Kiểm tra cho các lỗi HTTP 4xx liên quan đến auth
  // if (error?.status >= 400 && error?.status < 500) {
  //   // Nếu là lỗi 403, 401, hoặc các lỗi auth khác
  //   if (error?.status === 403 || error?.status === 401) return true;
  // }

  return false;
}

// Thêm function để xử lý lỗi chung
// TODO: xử lý lỗi chung (nếu lỗi authentication, chuyển về login)
export function handleGeneralError(error: any) {
  // Nếu là lỗi authentication, xử lý như auth error
  // if (isAuthError(error)) {
  //   handleAuthError();
  //   return;
  // }
  // // Nếu là lỗi network hoặc server, có thể cần reload hoặc chuyển về login
  // if (error?.name === "TypeError" || error?.message?.includes("fetch")) {
  //   // Network error - có thể server down hoặc mất kết nối
  //   localStorage.removeItem("userData");
  //   router.push("/login");
  //   return;
  // }
}
