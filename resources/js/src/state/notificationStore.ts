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
    try {
      window.Echo.leave(currentChannel);
    } catch (e) {
      // Ignore leave errors
    }
  }
  currentChannel = channelName;
  
  try {
    const channel = window.Echo.private(channelName);
    
    // Listen for notification events
    channel.notification(async (notification: any) => {
      console.log('📬 Notification received:', notification);
      
      // Cập nhật unread count ngay lập tức từ notification data
      if (typeof notification.unread_count === 'number') {
        notificationCount.value = notification.unread_count;
      }
      
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
      
      // Thêm notification vào đầu danh sách ngay lập tức (optimistic update)
      if (notification.id || notification.invitation_id) {
        const mappedNoti = mapNotificationDataFromBroadcast(notification);
        // Kiểm tra xem notification đã tồn tại chưa
        const existingIndex = notifications.value.findIndex(
          (n: any) => n.id === mappedNoti.id || 
          (n.invitation_id && mappedNoti.invitation_id && n.invitation_id === mappedNoti.invitation_id)
        );
        if (existingIndex === -1) {
          // Thêm vào đầu danh sách
          notifications.value.unshift(mappedNoti);
        }
      }
      
      // Fetch lại từ API sau một chút delay để đảm bảo DB đã được cập nhật
      // Nhưng merge với notifications hiện có thay vì ghi đè
      setTimeout(async () => {
        await fetchNotifications(true); // true = merge mode
      }, 1000);
      
      // Phát âm thanh nếu user đã từng tương tác
      if (userInteracted) {
        try {
          new Audio("/sounds/new-notification.mp3").play();
        } catch (e) {
          // ignore audio play errors
        }
      }
    });
    
    // Listen for connection events để debug
    channel.subscribed(() => {
      console.log('✅ Subscribed to notification channel:', channelName);
    });
    
    channel.error((error: any) => {
      console.error('❌ Notification channel error:', error);
    });
    
  } catch (error) {
    console.error('❌ Error setting up notification listener:', error);
  }
}

// Khởi tạo lắng nghe realtime khi user đăng nhập
export function listenRealtime() {
  const userStore = useUserStore();
  // @ts-expect-error - Pinia store type inference issue
  let userId: string | number | null = userStore.user?.id ?? null;
  if (!userId) {
    const userDataRaw = localStorage.getItem("userData");
    const userData = userDataRaw ? JSON.parse(userDataRaw) : {};
    // Ưu tiên lấy từ user-store (đã được persist)
    // @ts-expect-error - Pinia store type inference issue
    userId = userStore.user?.id || null;
    // Fallback: lấy từ userData (backward compatibility)
    if (!userId) {
      userId = userData.userId || (userData.user && userData.user.id) || null;
    }
    if (userId) setupNotificationListener(userId);
  }
  watch(
    // @ts-expect-error - Pinia store type inference issue
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
export async function fetchNotifications(mergeMode: boolean = false) {
  const res = await makeHttpReq<unknown, any>(
    "notifications",
    "GET"
  );
  
  // Backend trả về: { code, data: { notifications, unread_count }, message }
  const responseData = (res as any)?.data || res;
  
  if (
    responseData &&
    typeof responseData === "object" &&
    Array.isArray(responseData.notifications) &&
    typeof responseData.unread_count === "number"
  ) {
    const data = responseData as NotificationApiResponse;
    const mappedNotifications = data.notifications.map(mapNotificationData);
    
    if (mergeMode) {
      // Merge mode: Giữ lại notifications hiện có và thêm/cập nhật từ API
      // Match theo cả id và invitation_id để xử lý trường hợp temp id
      mappedNotifications.forEach((apiNoti: any) => {
        // Tìm notification đã có theo id hoặc invitation_id
        const existingIndex = notifications.value.findIndex(
          (n: any) => 
            n.id === apiNoti.id || 
            (apiNoti.invitation_id && n.invitation_id && n.invitation_id === apiNoti.invitation_id)
        );
        
        if (existingIndex !== -1) {
          // Cập nhật notification đã có (thay thế temp id bằng id thật từ DB)
          notifications.value[existingIndex] = apiNoti;
        } else {
          // Thêm notification mới vào đầu danh sách
          notifications.value.unshift(apiNoti);
        }
      });
      
      // Loại bỏ duplicate (theo invitation_id)
      const seen = new Set();
      notifications.value = notifications.value.filter((n: any) => {
        const key = n.invitation_id ? `inv_${n.invitation_id}` : n.id;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
      });
      
      // Giới hạn số lượng notifications (giữ 20 mới nhất)
      if (notifications.value.length > 20) {
        notifications.value = notifications.value.slice(0, 20);
      }
    } else {
      // Normal mode: Ghi đè toàn bộ
      notifications.value = mappedNotifications;
    }
    
    notificationCount.value = data.unread_count;
  } else {
    if (!mergeMode) {
      notifications.value = Array.isArray(res) ? (res as any[]) : [];
      notificationCount.value = notifications.value.filter(
        (n) => !n.read_at
      ).length;
    }
    // Nếu mergeMode, không thay đổi notifications.value nếu API lỗi
  }
}

// Lấy toàn bộ thông báo
export async function fetchAllNotifications() {
  const res = await makeHttpReq<unknown, any>(
    "notifications/all",
    "GET"
  );
  
  // Backend trả về: { code, data: { notifications, unread_count }, message }
  const responseData = (res as any)?.data || res;
  
  if (
    responseData &&
    typeof responseData === "object" &&
    Array.isArray(responseData.notifications) &&
    typeof responseData.unread_count === "number"
  ) {
    const data = responseData as NotificationApiResponse;
    notifications.value = data.notifications.map(mapNotificationData);
    notificationCount.value = data.unread_count;
  } else {
    notifications.value = Array.isArray(responseData) ? (responseData as any[]) : [];
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
  try {
    // Lưu lại notifications hiện có trước khi mark as read
    const currentNotifications = [...notifications.value];
    
    // Cập nhật read_at cho tất cả notifications hiện có (optimistic update)
    currentNotifications.forEach((noti: any) => {
      if (!noti.read_at) {
        noti.read_at = new Date().toISOString();
      }
    });
    
    // Cập nhật notifications ngay lập tức
    notifications.value = currentNotifications;
    
    // Cập nhật unread count về 0
    notificationCount.value = 0;
    
    // Gọi API để đánh dấu đã đọc (không đợi kết quả)
    makeHttpReq<any, any>(`notifications/read-all`, "POST").catch((error) => {
      console.error('Error marking all as read:', error);
      // Nếu lỗi, rollback unread count
      notificationCount.value = currentNotifications.filter((n: any) => !n.read_at).length;
    });
    
    // Không fetch lại để tránh mất notifications
    // Notifications đã được cập nhật ở trên rồi
  } catch (error) {
    console.error('Error marking all as read:', error);
  }
}

// Xóa 1 thông báo
export async function removeNotification(id: string) {
  await makeHttpReq<any, any>(`notifications/${id}`, "DELETE");
  notifications.value = notifications.value.filter((n) => n.id !== id);
  await fetchNotifications();
}

// Helper: Map notification từ broadcast event (realtime)
function mapNotificationDataFromBroadcast(notification: any) {
  const lang = localStorage.getItem("lang") || "en";
  let message = notification.message || "";
  
  // Xử lý message dựa trên type
  if (notification.type === "sent") {
    message = tNotification("sent", lang as "en" | "vi", {
      name: notification.sender_name || "",
    });
  } else if (notification.type === "accepted") {
    message = tNotification("accepted", lang as "en" | "vi", {
      name: notification.sender_name || "",
    });
  } else if (notification.type === "declined") {
    message = tNotification("declined", lang as "en" | "vi", {
      name: notification.sender_name || "",
    });
  }
  
  return {
    id: notification.id || `temp-${notification.invitation_id || Date.now()}`,
    message,
    avatar: notification.sender_avatar || notification.avatar || "",
    created_at: notification.created_at || new Date().toISOString(),
    slug: notification.slug || "",
    project_id: notification.project_id || "",
    read_at: null, // Mới nhận nên chưa đọc
    invitation_id: notification.invitation_id || null,
    sender_name: notification.sender_name || null,
    sender_avatar: notification.sender_avatar || null,
    type: notification.type || null,
  };
}

// Helper: Chuẩn hóa dữ liệu notification cho FE (từ database)
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
