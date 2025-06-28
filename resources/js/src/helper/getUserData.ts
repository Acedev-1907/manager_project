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
    console.warn("Error parsing user data from localStorage:", error);
    return null;
  }
}

export function setUserData(userData: LoginResponseType): void {
  try {
    localStorage.setItem("userData", JSON.stringify(userData));
  } catch (error) {
    console.error("Error saving user data to localStorage:", error);
  }
}

export function clearUserData(): void {
  try {
    localStorage.removeItem("userData");
  } catch (error) {
    console.error("Error clearing user data from localStorage:", error);
  }
}

export function isUserLoggedIn(): boolean {
  const userData = getUserData();
  return userData !== null && !!userData.token;
}
