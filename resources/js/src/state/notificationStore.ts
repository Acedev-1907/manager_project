import { ref, watch } from "vue";
import { useUserStore } from "./userStore";
import { makeHttpReq } from "../helper/makeHttpReq";

export const notifications = ref<any[]>([]);
export const notificationCount = ref(0);

let currentChannel = "";
let userInteracted = false;
if (typeof window !== "undefined") {
  window.addEventListener(
    "click",
    () => {
      userInteracted = true;
    },
    { once: true }
  );
}

function setupNotificationListener(userId: string | number | null) {
  if (!userId) return;
  if (!window.Echo) {
    setTimeout(() => setupNotificationListener(userId), 200);
    return;
  }
  const channelName = `user-notification.${userId}`;
  if (currentChannel && window.Echo) {
    window.Echo.leave(currentChannel);
  }
  currentChannel = channelName;
  window.Echo.private(channelName).notification((notification: any) => {
    // Nếu thiếu project_id hoặc slug, lấy từ notification.project nếu có
    if (
      (!notification.project_id || !notification.slug) &&
      notification.project
    ) {
      if (!notification.project_id && notification.project.id) {
        notification.project_id = notification.project.id;
      }
      if (!notification.slug && notification.project.slug) {
        notification.slug = notification.project.slug;
      }
    }
    notifications.value.unshift(notification);
    if (typeof notification.unread_count === "number") {
      notificationCount.value = notification.unread_count;
    } else {
      notificationCount.value++;
    }
    // Play sound when receive new notification (chỉ khi user đã tương tác)
    if (userInteracted) {
      try {
        const audio = new Audio("/sounds/new-notification.mp3");
        audio.play();
      } catch (e) {
        /* ignore */
      }
    }
  });
}

export function listenRealtime() {
  const userStore = useUserStore();
  // Lấy userId từ localStorage nếu userStore chưa có
  let userId = userStore.user?.id;
  if (!userId) {
    const userDataRaw = localStorage.getItem("userData");
    const userData = userDataRaw ? JSON.parse(userDataRaw) : {};
    userId = userData.id || (userData.user && userData.user.id) || null;
    if (userId) {
      setupNotificationListener(userId);
    }
  }
  watch(
    () => userStore.user?.id,
    (newId) => {
      if (newId) {
        setupNotificationListener(newId);
      }
    },
    { immediate: true }
  );
}

export async function fetchNotifications() {
  const res = await makeHttpReq<any, any>("notifications", "GET");
  // Nếu response là object có notifications và unread_count
  if (
    res &&
    Array.isArray(res.notifications) &&
    typeof res.unread_count === "number"
  ) {
    // Map lại dữ liệu cho đúng định dạng BellNotification.vue cần
    notifications.value = res.notifications.map((n: any) => ({
      id: n.id,
      message: n.data?.message || "",
      avatar: n.data?.avatar || "",
      created_at: n.created_at || "",
      slug: n.data?.slug || "",
      project_id: n.data?.project_id || "",
    }));
    notificationCount.value = res.unread_count;
  } else {
    // fallback cũ
    notifications.value = res;
    notificationCount.value = notifications.value.filter(
      (n: any) => !n.read_at
    ).length;
  }
}

export async function markAsRead(id: string) {
  await makeHttpReq<any, any>(`notifications/${id}/read`, "POST");
  const noti = notifications.value.find((n: any) => n.id === id);
  if (noti) noti.read_at = new Date();
  notificationCount.value = notifications.value.filter(
    (n: any) => !n.read_at
  ).length;
}

export async function removeNotification(id: string) {
  await makeHttpReq<any, any>(`notifications/${id}`, "DELETE");
  notifications.value = notifications.value.filter((n: any) => n.id !== id);
  notificationCount.value = notifications.value.filter(
    (n: any) => !n.read_at
  ).length;
}
