import { ref, watch } from "vue";
import { useUserStore } from "./userStore";
import { makeHttpReq } from "../helper/makeHttpReq";
import { tNotification } from "../constants/i18n";

// State: Danh sách thông báo và số lượng chưa đọc
export const notifications = ref<any[]>([]);
export const notificationCount = ref(0);

let currentChannel = "";
let userInteracted = false;

// Lắng nghe click đầu tiên để bật âm thanh notification
if (typeof window !== "undefined") {
  window.addEventListener(
    "click",
    () => {
      userInteracted = true;
    },
    { once: true }
  );
}

// Lắng nghe realtime notification qua Echo
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
  window.Echo.private(channelName).notification(async (notification: any) => {
    // Bổ sung project_id, slug nếu thiếu
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
    // Luôn fetch lại notification từ API để đồng bộ
    await fetchNotifications();
    // Phát âm thanh nếu user đã từng tương tác
    if (userInteracted) {
      try {
        new Audio("/sounds/new-notification.mp3").play();
      } catch (e) {}
    }
  });
}

// Khởi tạo lắng nghe realtime khi user đăng nhập
export function listenRealtime() {
  const userStore = useUserStore();
  let userId: string | number | null = userStore.user?.id;
  if (!userId) {
    const userDataRaw = localStorage.getItem("userData");
    const userData = userDataRaw ? JSON.parse(userDataRaw) : {};
    userId = userData.id || (userData.user && userData.user.id) || null;
    if (userId) setupNotificationListener(userId);
  }
  watch(
    () => userStore.user?.id,
    (newId) => {
      if (newId) setupNotificationListener(newId);
    },
    { immediate: true }
  );
}

// Kiểu dữ liệu trả về từ API notification
interface NotificationApiResponse {
  notifications: any[];
  unread_count: number;
}

// Lấy 10 thông báo mới nhất
export async function fetchNotifications() {
  const res = await makeHttpReq<unknown, NotificationApiResponse>(
    "notifications",
    "GET"
  );
  if (
    res &&
    typeof res === "object" &&
    Array.isArray((res as NotificationApiResponse).notifications) &&
    typeof (res as NotificationApiResponse).unread_count === "number"
  ) {
    const data = res as NotificationApiResponse;
    notifications.value = data.notifications.map(mapNotificationData);
    notificationCount.value = data.unread_count;
  } else {
    notifications.value = Array.isArray(res) ? (res as any[]) : [];
    notificationCount.value = notifications.value.filter(
      (n) => !n.read_at
    ).length;
  }
}

// Lấy toàn bộ thông báo
export async function fetchAllNotifications() {
  const res = await makeHttpReq<unknown, NotificationApiResponse>(
    "notifications/all",
    "GET"
  );
  if (
    res &&
    typeof res === "object" &&
    Array.isArray((res as NotificationApiResponse).notifications) &&
    typeof (res as NotificationApiResponse).unread_count === "number"
  ) {
    const data = res as NotificationApiResponse;
    notifications.value = data.notifications.map(mapNotificationData);
    notificationCount.value = data.unread_count;
  } else {
    notifications.value = Array.isArray(res) ? (res as any[]) : [];
    notificationCount.value = notifications.value.filter(
      (n) => !n.read_at
    ).length;
  }
}

// Đánh dấu 1 thông báo là đã đọc
export async function markAsRead(id: string) {
  await makeHttpReq<any, any>(`notifications/${id}/read`, "POST");
  const noti = notifications.value.find((n) => n.id === id);
  if (noti) noti.read_at = new Date();
  await fetchNotifications();
}

// Đánh dấu tất cả thông báo là đã đọc
export async function markAllAsRead() {
  await makeHttpReq<any, any>(`notifications/read-all`, "POST");
  await fetchNotifications();
}

// Xóa 1 thông báo
export async function removeNotification(id: string) {
  await makeHttpReq<any, any>(`notifications/${id}`, "DELETE");
  notifications.value = notifications.value.filter((n) => n.id !== id);
  await fetchNotifications();
}

// Helper: Chuẩn hóa dữ liệu notification cho FE
function mapNotificationData(n: any) {
  const userId = JSON.parse(localStorage.getItem("userData") || "{}").id;
  const lang = localStorage.getItem("lang") || "en";
  let message = "";
  if (n.data?.type === "sent") {
    message = tNotification("sent", lang as "en" | "vi", {
      name: n.data?.sender_name || "",
    });
  } else if (n.data?.type === "accepted" && n.notifiable_id === userId) {
    message = tNotification("accepted_self", lang as "en" | "vi");
  } else if (n.data?.type === "declined" && n.notifiable_id === userId) {
    message = tNotification("declined_self", lang as "en" | "vi");
  } else if (n.data?.type === "accepted") {
    message = tNotification("accepted", lang as "en" | "vi", {
      name: n.data?.sender_name || "",
    });
  } else if (n.data?.type === "declined") {
    message = tNotification("declined", lang as "en" | "vi", {
      name: n.data?.sender_name || "",
    });
  } else if (n.data?.type === "project_created_self") {
    message = tNotification("project_created_self", lang as "en" | "vi", {
      project: n.data?.project_name || "",
    });
  } else if (n.data?.type === "project_assigned") {
    message = tNotification("project_assigned", lang as "en" | "vi", {
      creator: n.data?.creator_name || "",
      project: n.data?.project_name || "",
    });
  } else {
    message = n.data?.message || "";
  }
  return {
    id: n.id,
    message,
    avatar: n.data?.avatar || n.data?.sender_avatar || "",
    created_at: n.created_at || "",
    slug: n.data?.slug || "",
    project_id: n.data?.project_id || "",
    read_at: n.read_at || null,
    invitation_id: n.data?.invitation_id || null,
    sender_name: n.data?.sender_name || null,
    sender_avatar: n.data?.sender_avatar || null,
    type: n.data?.type || null,
  };
}
