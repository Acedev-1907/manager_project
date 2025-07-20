import { defineStore } from "pinia";

export const useUserStore = defineStore("user", {
  state: () => ({
    user: null as any,
    avatar: "",
    userInfoCache: null as any, // cache user info
    lastFetched: null as number | null, // thời gian fetch gần nhất
  }),
  actions: {
    setUser(user: any) {
      this.user = user;
    },
    setAvatar(avatar: string) {
      this.avatar = avatar;
    },
    setUserInfoCache(data: any) {
      this.userInfoCache = data;
      this.lastFetched = Date.now();
    },
    clearUserInfoCache() {
      this.userInfoCache = null;
      this.lastFetched = null;
    },
  },
});
