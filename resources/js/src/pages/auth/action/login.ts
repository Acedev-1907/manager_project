import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError, showSuccess } from "../../../helper/alert";
import { showErrorResponse } from "../../../helper/utils";
import router from "../../../router";

export type LoginUserType = {
  email: string;
  password: string;
};
export type LoginResponseType = {
  user: { email: string; id: number; name: string; avatar: string };
  message: string;
  isLoggedIn: boolean;
  token: string;
};

export const loginInput = ref<LoginUserType>({
  email: "",
  password: "",
});

export function useLoginUser() {
  const loading = ref(false);

  async function login() {
    try {
      loading.value = true;

      const data = await makeHttpReq<LoginUserType, LoginResponseType>(
        "login",
        "POST",
        loginInput.value
      );

      loading.value = false;
      loginInput.value = {
        email: "",
        password: "",
      };
      if (data && data.token && data.user) {
        localStorage.setItem("userData", JSON.stringify(data));
        router.push("/dashboard");
      } else {
        showError("Login failed!");
      }
    } catch (error: any) {
      loading.value = false;
      const message =
        error?.response?.data?.message || error?.message || "Login failed!";
      showError(message);
    }
  }

  return { login, loading };
}
