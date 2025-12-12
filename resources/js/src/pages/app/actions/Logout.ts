import { ref } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";

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
    } catch (_error) {
      loading.value = false;

      if ((_error as Error).message == "Not authenticated") {
        return true;
      }

      return false;
    }
  }

  return { logout, loading };
}
