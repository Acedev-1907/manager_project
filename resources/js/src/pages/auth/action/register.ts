import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError, successMsg } from "../../../helper/toast-notificaltion";

export type RegisterUserType = {
    email: string,
    password: string,
}
export type RegisterResponseType = {
    user: { email: string },
    message: string

}

export const registerInput = ref<RegisterUserType>({} as RegisterUserType)

export function useRegisterUser() {

    const loading = ref(false)

    async function register() {
        try {
            loading.value = true

            const data = await makeHttpReq<RegisterUserType, RegisterResponseType>('register', 'POST', registerInput.value)

            loading.value = false

            successMsg(data.message);
        } catch (error) {
            showError((error as Error).message);
        }
    }
    return { register, loading }
}