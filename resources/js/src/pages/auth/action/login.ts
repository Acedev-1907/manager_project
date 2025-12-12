import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError } from "../../../helper/alert";
import { useAuth } from "../../../composables/useAuth";

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

/**
 * Login composable
 * Uses centralized auth handling
 */
export function useLoginUser() {
  const loading = ref(false);
  const { handleLoginSuccess } = useAuth();

  async function login() {
    try {
      loading.value = true;

      const data = await makeHttpReq<LoginUserType, LoginResponseType>(
        "login",
        "POST",
        loginInput.value
      );

      if (data && data.token && data.user) {
        // Use centralized login success handler
        await handleLoginSuccess(data);
      } else {
        throw new Error("Invalid login response");
      }
    } catch (error: any) {
      loading.value = false;
      const message = error?.message || "Login failed!";
      showError(message);
      throw error; // Re-throw for component handling
    } finally {
      loading.value = false;
    }
  }

  return { login, loading };
}
