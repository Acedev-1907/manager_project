import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showError, successMsg } from "../../../helper/toast-notificaltion";
import { showErrorResponse } from "../../../helper/utils";

export function useLogOutUser() {
  const loading = ref(false);

  async function logout(userId: number | undefined) {
    try {
      loading.value = true;

      await makeHttpReq<{ userId: number | undefined }, { message: string }>(
        "logout",
        "POST",
        { userId: userId },
        { timeout: 5000 }
      );

      loading.value = false;
      return true;
    } catch (error) {
      loading.value = false;

      if ((error as Error).message == "Not authenticated") {
        return true;
      }

      console.warn("Logout error:", error);
      return false;
    }
  }

  return { logout, loading };
}
