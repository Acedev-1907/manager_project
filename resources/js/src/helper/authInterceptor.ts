import router from "../router";

export function handleAuthError() {
  // Xóa dữ liệu user khỏi localStorage
  localStorage.removeItem("userData");

  // Chuyển về trang login nếu không đang ở trang auth
  if (!router.currentRoute.value.path.startsWith("/auth")) {
    router.push("/login");
  }
}

export function isAuthError(error: any): boolean {
  // Kiểm tra các trường hợp lỗi authentication
  if (error?.status === 401) return true;
  if (error?.message === "Not authenticated") return true;
  if (error?.response?.status === 401) return true;
  if (error?.response?.data?.message === "Not authenticated") return true;

  return false;
}
