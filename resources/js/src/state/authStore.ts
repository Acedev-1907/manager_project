import { defineStore } from 'pinia';
import { getUserData } from '../helper/getUserData';

/**
 * Auth Store
 * 
 * Centralized store for authentication state
 * Provides reactive authentication status
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    isAuthenticated: false,
    token: null as string | null,
  }),

  getters: {
    /**
     * Check if user is authenticated
     */
    isLoggedIn: () => {
      // Always check from localStorage for accuracy
      const userData = getUserData();
      // Chỉ cần token là đủ (user info đã được lưu trong user-store)
      return !!(userData?.token);
    },

    /**
     * Get current token
     */
    currentToken: () => {
      const userData = getUserData();
      return userData?.token || null;
    },
  },

  actions: {
    /**
     * Update authentication state
     */
    updateAuthState() {
      const userData = getUserData();
      // Chỉ cần token là đủ (user info đã được lưu trong user-store)
      this.isAuthenticated = !!(userData?.token);
      this.token = userData?.token || null;
    },

    /**
     * Clear authentication state
     */
    clearAuth() {
      this.isAuthenticated = false;
      this.token = null;
    },
  },
});

