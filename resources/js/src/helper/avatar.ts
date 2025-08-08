// Optimized avatar loading with caching and lazy loading
import { ref } from "vue";

// Cache for loaded avatars
const avatarCache = new Map<string, string>();
const loadingAvatars = new Set<string>();

// Avatar loading states
export const avatarLoadingStates = ref<{ [key: string]: boolean }>({});

// Optimized avatar source getter with caching
export function getAvatarSrc(
  avatar: string | null | undefined,
  userIdOrName?: number | string
): string {
  if (!avatar) {
    // Handle backward compatibility - if userIdOrName is string, treat as name
    if (typeof userIdOrName === "string") {
      return getDefaultAvatarByName(userIdOrName);
    }
    return getDefaultAvatar(userIdOrName as number);
  }

  // If already cached, return immediately
  if (avatarCache.has(avatar)) {
    return avatarCache.get(avatar)!;
  }

  // If it's already a proxy link, return as is
  if (avatar.startsWith("http") || avatar.startsWith("/api/proxy-image")) {
    avatarCache.set(avatar, avatar);
    return avatar;
  }

  // Render through proxy for original links
  const proxyUrl = `/api/proxy-image?url=${encodeURIComponent(avatar)}`;
  avatarCache.set(avatar, proxyUrl);
  return proxyUrl;
}

// Get default avatar based on user ID
export function getDefaultAvatar(userId?: number): string {
  if (!userId) {
    return "/api/proxy-image?url=https://ui-avatars.com/api/?name=U&background=random&color=fff&size=40";
  }

  // Generate consistent avatar based on user ID
  const colors = [
    "#3b82f6",
    "#ef4444",
    "#10b981",
    "#f59e0b",
    "#8b5cf6",
    "#ec4899",
    "#06b6d4",
    "#84cc16",
  ];
  const colorIndex = userId % colors.length;
  const color = colors[colorIndex];

  return `/api/proxy-image?url=https://ui-avatars.com/api/?name=U${userId}&background=${color.replace(
    "#",
    ""
  )}&color=fff&size=40`;
}

// Get default avatar based on name (for backward compatibility)
export function getDefaultAvatarByName(name?: string): string {
  if (!name) {
    return "/api/proxy-image?url=https://ui-avatars.com/api/?name=U&background=random&color=fff&size=40";
  }

  // Generate avatar based on name
  const colors = [
    "#3b82f6",
    "#ef4444",
    "#10b981",
    "#f59e0b",
    "#8b5cf6",
    "#ec4899",
    "#06b6d4",
    "#84cc16",
  ];
  const colorIndex = name.charCodeAt(0) % colors.length;
  const color = colors[colorIndex];

  return `/api/proxy-image?url=https://ui-avatars.com/api/?name=${encodeURIComponent(
    name
  )}&background=${color.replace("#", "")}&color=fff&size=40`;
}

// Lazy load avatar with loading state
export async function lazyLoadAvatar(
  avatar: string | null | undefined,
  userIdOrName?: number | string
): Promise<string> {
  const cacheKey = `${avatar}_${userIdOrName}`;

  // If already loading, wait
  if (loadingAvatars.has(cacheKey)) {
    return new Promise((resolve) => {
      const checkLoaded = () => {
        if (avatarCache.has(avatar || "")) {
          resolve(avatarCache.get(avatar || "")!);
        } else {
          setTimeout(checkLoaded, 50);
        }
      };
      checkLoaded();
    });
  }

  // If already cached, return immediately
  if (avatar && avatarCache.has(avatar)) {
    return avatarCache.get(avatar)!;
  }

  // Set loading state
  loadingAvatars.add(cacheKey);
  avatarLoadingStates.value[cacheKey] = true;

  try {
    const avatarSrc = getAvatarSrc(avatar, userIdOrName);

    // Preload image
    if (
      avatarSrc.startsWith("http") ||
      avatarSrc.startsWith("/api/proxy-image")
    ) {
      await preloadImage(avatarSrc);
    }

    return avatarSrc;
  } catch (error) {
    console.warn("Failed to load avatar:", error);
    if (typeof userIdOrName === "string") {
      return getDefaultAvatarByName(userIdOrName);
    }
    return getDefaultAvatar(userIdOrName as number);
  } finally {
    loadingAvatars.delete(cacheKey);
    avatarLoadingStates.value[cacheKey] = false;
  }
}

// Preload image to ensure it's cached
function preloadImage(src: string): Promise<void> {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve();
    img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
    img.src = src;
  });
}

// Clear avatar cache (useful for memory management)
export function clearAvatarCache(): void {
  avatarCache.clear();
  loadingAvatars.clear();
  avatarLoadingStates.value = {};
}

// Get loading state for specific avatar
export function isAvatarLoading(
  avatar: string | null | undefined,
  userIdOrName?: number | string
): boolean {
  const cacheKey = `${avatar}_${userIdOrName}`;
  return avatarLoadingStates.value[cacheKey] || false;
}
