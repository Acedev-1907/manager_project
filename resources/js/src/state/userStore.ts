import { defineStore } from "pinia";

export interface User {
  id: number | string;
  name: string;
  email?: string;
  avatar?: string;
  friend_code?: string | null;
}

export const useUserStore = defineStore("user", {
  state: () => ({
    user: null as User | null,
    avatar: "",
    userInfoCache: null as any, // cache user info (runtime only, not persisted)
    lastFetched: null as number | null, // thời gian fetch gần nhất (runtime only)
  }),
  
  getters: {
    isLoggedIn: (state: { user: User | null }) => state.user !== null,
    userId: (state: { user: User | null }) => state.user?.id,
    userName: (state: { user: User | null; avatar: string }) => state.user?.name || '',
    userAvatar: (state: { user: User | null; avatar: string }) => state.user?.avatar || state.avatar || '',
  },
  
  actions: {
    setUser(user: User | null) {
      this.user = user;
      if (user?.avatar) {
        this.avatar = user.avatar;
      }
    },
    setAvatar(avatar: string) {
      this.avatar = avatar;
      if (this.user) {
        this.user.avatar = avatar;
      }
    },
    setUserInfoCache(data: any) {
      this.userInfoCache = data;
      this.lastFetched = Date.now();
    },
    clearUserInfoCache() {
      this.userInfoCache = null;
      this.lastFetched = null;
    },
    clearUser() {
      this.user = null;
      this.avatar = '';
      this.userInfoCache = null;
      this.lastFetched = null;
    },
  },
  
  // Chỉ persist user và avatar (dữ liệu quan trọng), không persist cache
  persist: {
    key: 'user-store',
    paths: ['user', 'avatar'], // Chỉ lưu user và avatar vào localStorage
    storageType: 'localStorage', // Persistent across sessions
    ttl: 0, // Never expire
  },
});
