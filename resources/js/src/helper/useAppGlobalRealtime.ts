import { ref, computed } from "vue";
import { useGlobalEchoListener } from "./useGlobalEchoListener";
import { useProjectRealtimeCacheClear } from "./useProjectRealtimeCacheClear";
import { storeToRefs } from "pinia";
import { useUserStore } from "../state/userStore";

export function useAppGlobalRealtime() {
  // Lấy userId reactive từ userStore (Pinia)
  const userStore = useUserStore();
  const { user } = storeToRefs(userStore);
  const userId = computed(() => user.value?.id || null);
  useGlobalEchoListener(userId);
  useProjectRealtimeCacheClear();
}
