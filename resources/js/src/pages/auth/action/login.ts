import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError } from "../../../helper/alert";
import router from "../../../router";
import { useUserStore } from "../../../state/userStore";

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
  const userStore = useUserStore();

  async function login() {
    try {
      loading.value = true;

      const data = await makeHttpReq<LoginUserType, LoginResponseType>(
        "login",
        "POST",
        loginInput.value
      );

      if (data && data.token && data.user) {
        localStorage.setItem("userData", JSON.stringify(data));
        // Khởi tạo lại Echo với token mới
        const { initEcho } = await import("../../../../echo");
        initEcho();
        // Gọi API lấy user mới nhất
        const userRes = await makeHttpReq<undefined, any>("user", "GET");
        userStore.setUser({
          name: userRes.data.name,
          avatar: userRes.data.avatar || "",
        });
        router.push("/dashboard");
      }
    } catch (error: any) {
      loading.value = false;
      const message = error?.message || "Login failed!";
      showError(message);
    }
  }

  return { login, loading };
}
