// import { defineStore } from "pinia";
// import { MemberInputType } from "../actions/createMember";

// const userMemberStore = defineStore("member", {
//   state: () => ({
//     memberInput: {} as MemberInputType,
//     edit: false,
//     memberListCache: null as any, // cache member list
//     lastFetched: null as number | null, // Last fetch time
//   }),
//   actions: {
//     setMemberListCache(data: any) {
//       this.memberListCache = data;
//       this.lastFetched = Date.now();
//     },
//     clearMemberListCache() {
//       this.memberListCache = null;
//       this.lastFetched = null;
//     },
//   },
// });

// export const memberStore = userMemberStore();
