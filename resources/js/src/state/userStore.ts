import { defineStore } from "pinia";

export interface User {
  id: number | string;
  name: string;
  email?: string;
  avatar?: string;
  phone?: string;
  cover_photo?: string;
  friend_code?: string | null;
}

export interface UserInfoCache {
  id: number | string;
  name: string;
  email?: string;
  phone?: string;
  avatar?: string;
  cover_photo?: string;
  friend_code?: string | null;
}

interface UserState {
  user: User | null;
  avatar: string;
  userInfoCache: UserInfoCache | null;
  lastFetched: number | null;
}

export const useUserStore = defineStore("user", {
  state: (): UserState => ({
    user: null,
    avatar: "",
    userInfoCache: null, // cache user info (runtime only, not persisted)
    lastFetched: null, // thời gian fetch gần nhất (runtime only)
  }),
  
  getters: {
    isLoggedIn(): boolean {
      return this.user !== null;
    },
    userId(): number | string | undefined {
      return this.user?.id;
    },
    userName(): string {
      return this.user?.name || '';
    },
    userAvatar(): string {
      return this.user?.avatar || this.avatar || '';
    },
  },
  
  actions: {
    setUser(user: User | null): void {
      this.user = user;
      if (user?.avatar) {
        this.avatar = user.avatar;
      }
    },
    setAvatar(avatar: string): void {
      this.avatar = avatar;
      if (this.user) {
        this.user.avatar = avatar;
      }
    },
    setUserInfoCache(data: UserInfoCache | null): void {
      this.userInfoCache = data;
      this.lastFetched = data ? Date.now() : null;
    },
    clearUserInfoCache(): void {
      this.userInfoCache = null;
      this.lastFetched = null;
    },
    clearUser(): void {
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
