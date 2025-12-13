// Type definitions for Pinia stores to fix TypeScript inference issues
import type { useUserStore } from '../state/userStore';
import type { useProjectStore } from '../pages/app/project/store/projectStore';
import type { useDashboardStore } from '../pages/app/dashboard/store/dashboardStore';
import type { useMemberStore } from '../pages/app/member/store/MemberStore';
import type { useTaskStore } from '../pages/app/kabanborad/store/kabanStore';

// Extend Pinia Store type to include store properties
declare module 'pinia' {
  export interface StoreGeneric {
    // User Store
    user?: ReturnType<typeof useUserStore>['user'];
    setUser?: ReturnType<typeof useUserStore>['setUser'];
    clearUser?: ReturnType<typeof useUserStore>['clearUser'];
    setAvatar?: ReturnType<typeof useUserStore>['setAvatar'];
    userInfoCache?: ReturnType<typeof useUserStore>['userInfoCache'];
    setUserInfoCache?: ReturnType<typeof useUserStore>['setUserInfoCache'];
    clearUserInfoCache?: ReturnType<typeof useUserStore>['clearUserInfoCache'];
    userAvatar?: ReturnType<typeof useUserStore>['userAvatar'];
    
    // Project Store
    projectInput?: ReturnType<typeof useProjectStore>['projectInput'];
    edit?: ReturnType<typeof useProjectStore>['edit'];
    clearProjects?: ReturnType<typeof useProjectStore>['clearProjects'];
    
    // Dashboard Store
    pinnedProject?: ReturnType<typeof useDashboardStore>['pinnedProject'];
    setPinnedProject?: ReturnType<typeof useDashboardStore>['setPinnedProject'];
    countProject?: ReturnType<typeof useDashboardStore>['countProject'];
    setCountProject?: ReturnType<typeof useDashboardStore>['setCountProject'];
    clearAll?: ReturnType<typeof useDashboardStore>['clearAll'];
    
    // Member Store
    friendsList?: ReturnType<typeof useMemberStore>['friendsList'];
    setFriendsList?: ReturnType<typeof useMemberStore>['setFriendsList'];
    sentInvitations?: ReturnType<typeof useMemberStore>['sentInvitations'];
    setSentInvitations?: ReturnType<typeof useMemberStore>['setSentInvitations'];
    receivedInvitations?: ReturnType<typeof useMemberStore>['receivedInvitations'];
    setReceivedInvitations?: ReturnType<typeof useMemberStore>['setReceivedInvitations'];
    memberCache?: ReturnType<typeof useMemberStore>['memberCache'];
    setMemberCache?: ReturnType<typeof useMemberStore>['setMemberCache'];
    searchQuery?: ReturnType<typeof useMemberStore>['searchQuery'];
    setSearchQuery?: ReturnType<typeof useMemberStore>['setSearchQuery'];
    currentPage?: ReturnType<typeof useMemberStore>['currentPage'];
    setCurrentPage?: ReturnType<typeof useMemberStore>['setCurrentPage'];
    hasFriendsList?: ReturnType<typeof useMemberStore>['hasFriendsList'];
    isCacheFresh?: ReturnType<typeof useMemberStore>['isCacheFresh'];
    
    // Task Store
    taskInput?: ReturnType<typeof useTaskStore>['taskInput'];
    projectDetailCache?: ReturnType<typeof useTaskStore>['projectDetailCache'];
    setProjectDetailCache?: ReturnType<typeof useTaskStore>['setProjectDetailCache'];
    clearProjectDetailCache?: ReturnType<typeof useTaskStore>['clearProjectDetailCache'];
  }
}

