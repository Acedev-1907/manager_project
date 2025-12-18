export const APP = {
  baseURL: import.meta.env.APP_URL || window.location.origin,
  apiBaseURL: (import.meta.env.APP_URL || window.location.origin) + "/api",
};