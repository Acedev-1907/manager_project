// Hàm getAvatarSrc dùng chung cho toàn bộ FE
/**
 * Returns the correct avatar URL for a user/member, supporting proxy and fallback.
 * @param avatar - The avatar URL or path
 * @param name - The user's name (for fallback)
 */
export function getAvatarSrc(avatar?: string, name?: string) {
  if (avatar) {
    // Nếu đã là link proxy (bắt đầu bằng http hoặc /api/proxy-image) thì trả về luôn
    if (avatar.startsWith("http") && avatar.includes("/api/proxy-image?url="))
      return avatar;
    if (avatar.startsWith("/api/proxy-image?url=")) return avatar;
    // Nếu là link gốc thì render qua proxy
    if (avatar.startsWith("http"))
      return `/api/proxy-image?url=${encodeURIComponent(avatar)}`;
    return avatar;
  }
  return "https://ui-avatars.com/api/?name=" + encodeURIComponent(name || "U");
}
