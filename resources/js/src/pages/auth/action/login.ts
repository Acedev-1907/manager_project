import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError, successMsg } from "../../../helper/toast-notificaltion";
import { showErrorResponse } from "../../../helper/utils";

export type LoginUserType = {
  email: string;
  password: string;
};
export type LoginResponseType = {
  user: { email: string; id: number };
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
      console.log("login function called");
      console.log("loginInput.value:", loginInput.value);
      loading.value = true;

      const data = await makeHttpReq<LoginUserType, LoginResponseType>(
        "login",
        "POST",
        loginInput.value
      );

      console.log("login response:", data);
      loading.value = false;
      loginInput.value = {
        email: "",
        password: "",
      };
      if (data && data.token && data.user) {
        localStorage.setItem("userData", JSON.stringify(data));
        window.location.href = "/app/admin";
      } else {
        showError("Đăng nhập thất bại!");
      }
    } catch (error) {
      console.error("login error:", error);
      showErrorResponse(error);
      loading.value = false;
      for (const message of error as string) {
        showError(message);
      }
    }
  }

  return { login, loading };
}
