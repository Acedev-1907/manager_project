import { ref } from "vue";
import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { showError, successMsg } from "../../../../helper/toast-notificaltion";
import { showErrorResponse } from "../../../../helper/utils";

export type MemberInputType = {
    name: string
    email: string
}
export type MemberResponseType = {
    message: string
}

export const MemberInput = ref<MemberInputType>({} as MemberInputType)

export function useCreateOrUpdateMember() {

    const loading = ref(false)

    async function createOrUpdate() {
        try {
            loading.value = true

            const data = await makeHttpReq<MemberInputType, MemberResponseType>('members', 'POST', MemberInput.value)

            loading.value = false
            MemberInput.value = {} as MemberInputType
            successMsg(data.message);
        } catch (error) {
            loading.value = false
            showErrorResponse(error);
        }
    }
    return { createOrUpdate, loading }
}