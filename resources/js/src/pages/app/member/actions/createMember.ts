// import { ref } from "vue";
// import { makeHttpReq } from "../../../../helper/makeHttpReq";
// import { showError, showSuccess } from "../../../../helper/alert";
// import { showErrorResponse } from "../../../../helper/utils";
// import { memberStore } from "../store/MemberStore";

// export type MemberInputType = {
//   id?: number;
//   name: string;
//   email: string;
// };
// export type MemberResponseType = {
//   message: string;
// };

// export function useCreateOrUpdateMember() {
//   const loading = ref(false);

//   async function createOrUpdate() {
//     try {
//       loading.value = true;
//       const data = memberStore.edit
//         ? await updateMember()
//         : await createMember();
//       loading.value = false;
//       memberStore.memberInput = {} as MemberInputType;

//       showSuccess(data.message);
//       return { success: true, data };
//     } catch (error) {
//       loading.value = false;
//       showErrorResponse(error);
//       return { success: false, error };
//     }
//   }
//   return { createOrUpdate, loading };
// }

// async function createMember() {
//   const data = await makeHttpReq<MemberInputType, MemberResponseType>(
//     "members",
//     "POST",
//     memberStore.memberInput
//   );
//   return data;
// }
// async function updateMember() {
//   const data = await makeHttpReq<MemberInputType, MemberResponseType>(
//     "members",
//     "PUT",
//     memberStore.memberInput
//   );
//   memberStore.edit = false;
//   return data;
// }

// export async function addMemberByEmail(email: string) {
//   return makeHttpReq<{ email: string }, any>("members/add-by-email", "POST", {
//     email,
//   });
// }

// export async function addMemberByNameOrEmail(input: string) {
//   return makeHttpReq<{ input: string }, any>(
//     "members/add-by-name-or-email",
//     "POST",
//     { input }
//   );
// }
