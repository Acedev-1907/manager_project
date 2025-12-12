import { ref, computed } from 'vue';
import router from '../router';
import { getUserData, setUserData, getCurrentUserId } from '../helper/getUserData';
import { LoginResponseType } from '../pages/auth/action/login';
import { useUserStore } from '../state/userStore';
import { makeHttpReq } from '../helper/makeHttpReq';
import { clearAllCache } from './useLocalStorage';

/**
 * Centralized Authentication Composable
 * 
 * Manages all authentication-related logic including:
 * - Login/logout
 * - Token management
 * - Authentication state
 * - User data synchronization
 */
export function useAuth() {
  const userStore = useUserStore();
  const isInitialized = ref(false);

  /**
   * Check if user is authenticated
   */
  const isAuthenticated = computed(() => {
    const userData = getUserData();
    return !!(userData?.token && userData?.user);
  });

  /**
   * Get current user data from localStorage
   */
  const getCurrentUserData = () => {
    return getUserData();
  };

  /**
   * Get current user ID
   */
  const getUserId = () => {
    return getCurrentUserId();
  };

  /**
   * Initialize auth state from localStorage
   * Should be called on app startup
   */
  const initializeAuth = async () => {
    if (isInitialized.value) return;

    const userData = getUserData();
    if (userData?.user) {
      // Sync user data to store
      userStore.setUser({
        id: userData.user.id,
        name: userData.user.name,
        avatar: userData.user.avatar || '',
        friend_code: null, // Will be fetched from API if needed
      });

      // Initialize Echo if needed
      try {
        const { initEcho } = await import('../../echo.js');
        initEcho();
      } catch (error) {
        console.warn('Failed to initialize Echo:', error);
      }
    }

    isInitialized.value = true;
  };

  /**
   * Handle successful login
   * 
   * @param loginData Login response data
   */
  const handleLoginSuccess = async (loginData: LoginResponseType) => {
    if (!loginData?.token || !loginData?.user) {
      throw new Error('Invalid login response');
    }

    // Save to localStorage
    setUserData(loginData);

    // Initialize Echo with new token
    try {
      const { initEcho } = await import('../../echo.js');
      initEcho();
    } catch (error) {
      console.warn('Failed to initialize Echo:', error);
    }

    // Fetch latest user data from API
    try {
      const userRes = await makeHttpReq<undefined, { data: any }>('user', 'GET');
      userStore.setUser({
        id: userRes.data.id,
        name: userRes.data.name,
        avatar: userRes.data.avatar || '',
        friend_code: userRes.data.friend_code || null,
      });
    } catch (error) {
      // Fallback to login data if API call fails
      userStore.setUser({
        id: loginData.user.id,
        name: loginData.user.name,
        avatar: loginData.user.avatar || '',
        friend_code: null,
      });
    }

    // Clear cache on login
    clearCacheOnLogin();

    // Navigate to dashboard
    router.push('/dashboard');
  };

  /**
   * Clear cache on login
   */
  const clearCacheOnLogin = () => {
    // Use centralized cache clearing function
    clearAllCache();
  };

  /**
   * Handle logout
   * 
   * @param clearStores Callback to clear Pinia stores
   */
  const handleLogout = async (clearStores?: () => void) => {
    // Clear user data
    localStorage.removeItem('userData');
    
    // Clear stores if callback provided
    if (clearStores) {
      clearStores();
    }

    // Clear user store
    userStore.clearUser();

    // Navigate to login
    router.push('/login');
  };

  /**
   * Check if current route is auth route
   */
  const isAuthRoute = (path: string): boolean => {
    return (
      path.startsWith('/auth') ||
      path.includes('/login') ||
      path.includes('/register') ||
      path.includes('/reset-password') ||
      path.includes('/change-password')
    );
  };

  /**
   * Redirect to login if not authenticated
   * Only redirects if not already on auth route
   */
  const redirectToLoginIfNeeded = () => {
    const currentPath = router.currentRoute.value.path;
    
    // Don't redirect if already on auth route
    if (isAuthRoute(currentPath)) {
      return;
    }

    router.push('/login');
  };

  return {
    isAuthenticated,
    isInitialized: computed(() => isInitialized.value),
    getCurrentUserData,
    getUserId,
    initializeAuth,
    handleLoginSuccess,
    handleLogout,
    isAuthRoute,
    redirectToLoginIfNeeded,
  };
}

