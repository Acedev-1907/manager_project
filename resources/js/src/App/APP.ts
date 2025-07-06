export const APP = {
  // TODO: Api config FE
  baseURL: import.meta.env.VITE_APP_URL,
  apiBaseURL: import.meta.env.VITE_APP_URL + "/api",
  // baseURL: "https://project-management-vi.onrender.com",
  // apiBaseURL: "https://project-management-vi.onrender.com/api",
};

// // Global error handler
// window.addEventListener("error", (event) => {
//   console.error("Global error:", event.error);

//   // Nếu là lỗi network hoặc fetch, có thể cần chuyển về login
//   if (
//     event.error?.name === "TypeError" ||
//     event.error?.message?.includes("fetch")
//   ) {
//     localStorage.removeItem("userData");
//     window.location.href = "/app/login";
//   }
// });

// // Unhandled promise rejection handler
// window.addEventListener("unhandledrejection", (event) => {
//   console.error("Unhandled promise rejection:", event.reason);

//   // Nếu là lỗi authentication hoặc network
//   if (
//     event.reason?.message === "Not authenticated" ||
//     event.reason?.name === "TypeError" ||
//     event.reason?.message?.includes("fetch")
//   ) {
//     localStorage.removeItem("userData");
//     window.location.href = "/app/login";
//   }
// });
