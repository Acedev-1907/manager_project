import { LoginResponseType } from "../pages/auth/action/login";

/**
 * Simplified userData structure - chỉ lưu token và userId
 * User info được lưu trong user-store để tránh trùng lặp
 */
export interface UserDataStorage {
  token: string;
  userId?: number | string; // Optional để backward compatibility
}

export function getUserData(): LoginResponseType | null {
  try {
    const userData = localStorage.getItem("userData");

    if (!userData) {
      return null;
    }

    const parsedData = JSON.parse(userData);

    // Validate that the parsed data has the expected structure
    if (parsedData && typeof parsedData === "object" && parsedData.token) {
      // Nếu là format mới (chỉ có token), cần lấy user từ user-store
      if (!parsedData.user && parsedData.userId) {
        // Fallback: tạo user object từ userId (sẽ được cập nhật từ user-store)
        return {
          token: parsedData.token,
          user: {
            id: parsedData.userId,
            name: '',
            email: '',
            avatar: '',
          },
          message: '',
          isLoggedIn: true,
        } as LoginResponseType;
      }
      // Format cũ (backward compatibility)
      return parsedData as LoginResponseType;
    }

    return null;
  } catch (error) {
    return null;
  }
}

/**
 * Lưu userData - chỉ lưu token và userId, không lưu user info (để tránh trùng với user-store)
 */
export function setUserData(userData: LoginResponseType): void {
  try {
    // Chỉ lưu token và userId, không lưu toàn bộ user object
    const storageData: UserDataStorage = {
      token: userData.token,
      userId: userData.user?.id,
    };
    localStorage.setItem("userData", JSON.stringify(storageData));
  } catch (error) {
    // ignore storage errors
  }
}

// Helper function để lấy current user ID
export function getCurrentUserId(): string | number | null {
  try {
    // Fallback: lấy từ userData (user-store sẽ được gọi từ nơi khác)
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
