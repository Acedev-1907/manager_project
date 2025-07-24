export const notificationMessages = {
  en: {
    sent: "{name} has sent you a friend invitation.",
    accepted_self: "You have accepted the invitation.",
    declined_self: "You have declined the invitation.",
    accepted: "{name} has accepted your friend invitation.",
    declined: "{name} has declined your friend invitation.",
    project_created_self: "You have created a new project: {project}.",
    project_assigned: "{creator} added you to a new project: {project}.",
  },
  vi: {
    sent: "{name} đã gửi cho bạn lời mời kết bạn.",
    accepted_self: "Bạn đã xác nhận kết bạn.",
    declined_self: "Bạn đã hủy lời mời kết bạn.",
    accepted: "{name} đã xác nhận lời mời kết bạn của bạn.",
    declined: "{name} đã từ chối lời mời kết bạn của bạn.",
    project_created_self: "Bạn đã tạo dự án mới: {project}.",
    project_assigned: "{creator} đã thêm bạn vào dự án mới: {project}.",
  },
};

// Define allowed keys
export type NotificationKey = keyof (typeof notificationMessages)["en"];

// Hàm dịch đơn giản
export function tNotification(
  key: NotificationKey,
  lang: "en" | "vi",
  vars?: Record<string, string>
) {
  let msg = notificationMessages[lang][key] || "";
  if (vars) {
    Object.keys(vars).forEach((k) => {
      msg = msg.replace(`{${k}}`, vars[k]);
    });
  }
  return msg;
}
