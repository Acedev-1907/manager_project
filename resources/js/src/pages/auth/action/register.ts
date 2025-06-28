import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError, successMsg } from "../../../helper/toast-notificaltion";

export type RegisterUserType = {
  email: string;
  password: string;
  confirmPassword: string;
};
export type RegisterResponseType = {
  user: { email: string };
  message: string;
};

export const registerInput = ref<RegisterUserType>({
  email: "",
  password: "",
  confirmPassword: "",
});

export function useRegisterUser() {
  const loading = ref(false);

  async function register() {
    try {
      loading.value = true;

      const data = await makeHttpReq<RegisterUserType, RegisterResponseType>(
        "register",
        "POST",
        registerInput.value
      );

      loading.value = false;
      registerInput.value = {
        email: "",
        password: "",
        confirmPassword: "",
      };
      successMsg(data.message);
    } catch (error) {
      loading.value = false;
      for (const message of error as string) {
        showError(message);
      }
    }
  }
  return { register, loading };
}
