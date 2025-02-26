import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError, successMsg } from "../../../helper/toast-notificaltion";
import { showErrorResponse } from "../../../helper/utils";


export function useLogOutUser() {
    const loading = ref(false)

    async function logout(userId: number | undefined) {
        try {
            loading.value = true

            const data = await makeHttpReq<{ userId: number | undefined }, { message: string }>
                ('logout', 'POST', { userId: userId })
            loading.value = false

        } catch (error) {
            // showErrorResponse(error);
            loading.value = false
            if ((error as Error).message == 'Not authenticated') {
                window.location.href = "/app/login"
            }
        }
    }

    return { logout, loading }
}