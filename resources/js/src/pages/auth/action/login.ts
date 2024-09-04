import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError, successMsg } from "../../../helper/toast-notificaltion";
import { showErrorResponse } from "../../../helper/utils";

export type LoginUserType = {
    email: string,
    password: string,
}
export type LoginResponseType = {
    user: { email: string, id: number },
    message: string
    isLoggedIn: boolean
    token: string
}

export const loginInput = ref<LoginUserType>({} as LoginUserType)

export function useLoginUser() {

    const loading = ref(false)

    async function login() {
        try {
            loading.value = true

            const data = await makeHttpReq<LoginUserType, LoginResponseType>('login', 'POST', loginInput.value)

            loading.value = false
            loginInput.value = {} as LoginUserType
            successMsg(data.message);
            if (data.isLoggedIn) {
                localStorage.setItem('userData', JSON.stringify(data))
                window.location.href = '/app/admin'
            }
        } catch (error) {
            showErrorResponse(error);
            loading.value = false
            for (const message of error as string) {
                showError(message);
            }
        }
    }

    return { login, loading }
}