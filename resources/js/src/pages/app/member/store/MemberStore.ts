import { defineStore } from "pinia";

export interface MemberListResponse {
  data: any[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

export const useMemberStore = defineStore("member", {
  state: () => ({
    friendsList: null as MemberListResponse | null,
    sentInvitations: null as MemberListResponse | null,
    receivedInvitations: null as MemberListResponse | null,
    memberCache: {} as { [key: string]: MemberListResponse },
    lastFetched: null as number | null,
    searchQuery: '',
    currentPage: 1,
  }),
  
  getters: {
    hasFriendsList: (state: { friendsList: MemberListResponse | null }) => state.friendsList !== null && state.friendsList.data.length > 0,
    isCacheFresh: (state: { lastFetched: number | null }) => {
      if (!state.lastFetched) return false;
      const age = Date.now() - state.lastFetched;
      return age < 5 * 60 * 1000; // 5 minutes
    },
    hasInvitations: (state: { sentInvitations: MemberListResponse | null; receivedInvitations: MemberListResponse | null }) => {
      return (state.sentInvitations !== null || state.receivedInvitations !== null);
    },
  },
  
  actions: {
    setFriendsList(data: MemberListResponse) {
      this.friendsList = data;
      this.lastFetched = Date.now();
    },
    
    setSentInvitations(data: MemberListResponse) {
      this.sentInvitations = data;
    },
    
    setReceivedInvitations(data: MemberListResponse) {
      this.receivedInvitations = data;
    },
    
    setMemberCache(cache: { [key: string]: MemberListResponse }) {
      this.memberCache = cache;
    },
    
    updateCacheItem(key: string, data: MemberListResponse) {
      this.memberCache[key] = data;
    },
    
    setSearchQuery(query: string) {
      this.searchQuery = query;
    },
    
    setCurrentPage(page: number) {
      this.currentPage = page;
    },
    
    clearAll() {
      this.friendsList = null;
      this.sentInvitations = null;
      this.receivedInvitations = null;
      this.memberCache = {};
      this.lastFetched = null;
      this.searchQuery = '';
      this.currentPage = 1;
    },
  },
  
});
