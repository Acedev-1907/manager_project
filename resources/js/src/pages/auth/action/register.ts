import { ref } from "vue";
import { useRouter } from "vue-router";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError, showSuccess } from "../../../helper/alert";

export type RegisterUserType = {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
};
export type RegisterResponseType = {
  user: { email: string };
  message: string;
};

export const registerInput = ref<RegisterUserType>({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

export function useRegisterUser() {
  const loading = ref(false);
  const router = useRouter();

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
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
      };
      showSuccess(data.message);
      router.push("/login");
    } catch (error) {
      loading.value = false;
      showError((error as any).message);
    }
  }
  return { register, loading };
}
