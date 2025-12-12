import { LoginResponseType } from "../pages/auth/action/login";

export function getUserData(): LoginResponseType | null {
  try {
    const userData = localStorage.getItem("userData");

    if (!userData) {
      return null;
    }

    const parsedData = JSON.parse(userData);

    // Validate that the parsed data has the expected structure
    if (parsedData && typeof parsedData === "object" && parsedData.token) {
      return parsedData as LoginResponseType;
    }

    return null;
  } catch (error) {
    return null;
  }
}

export function setUserData(userData: LoginResponseType): void {
  try {
    localStorage.setItem("userData", JSON.stringify(userData));
  } catch (error) {
    // ignore storage errors
  }
}

// Helper function để lấy current user ID
export function getCurrentUserId(): string | number | null {
  try {
    const userData = getUserData();
    if (!userData) return null;

    return userData.user?.id || null;
  } catch (error) {
    return null;
  }
}

// Helper function để lấy current user data với fallback
export function getCurrentUserData(): any {
  try {
    const userData = getUserData();
    if (!userData) return {};

    return userData;
  } catch (error) {
    return {};
  }
}

// Helper function để kiểm tra user có phải là current user không
export function isCurrentUser(userId: string | number | null): boolean {
  const currentUserId = getCurrentUserId();
  return currentUserId !== null && currentUserId === userId;
}
