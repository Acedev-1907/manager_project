export const APP = {
  // TODO: Api config FE
  baseURL:
    import.meta.env.VITE_APP_URL || "https://taskmgrv2-latest.onrender.com",
  apiBaseURL:
    (import.meta.env.VITE_APP_URL || "https://taskmgrv2-latest.onrender.com") +
    "/api",
  // baseURL: "https://project-management-vi.onrender.com",
  // apiBaseURL: "https://project-management-vi.onrender.com/api",
};
