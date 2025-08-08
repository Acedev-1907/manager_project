import { showError, showSuccess } from "./alert";
import { isAuthError, handleAuthError } from "./authInterceptor";

// Error handling utilities
export function showErrorResponse(err: unknown) {
  if (isAuthError(err)) {
    handleAuthError();
    return;
  }

  if (Array.isArray(err)) {
    for (const message of err as string[]) {
      showError(message);
    }
  } else if (typeof err === "string") {
    showError(err);
  } else if (typeof err === "object" && err && "message" in err) {
    showError((err as any).message);
  } else {
    showError(err as string);
  }
}

// Debounce utility
export function createDebouncedFunction<T extends (...args: any[]) => any>(
  func: T,
  delay: number
): (...args: Parameters<T>) => void {
  let timeoutId: ReturnType<typeof setTimeout>;

  return (...args: Parameters<T>) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func(...args), delay);
  };
}

// Cache utilities
export const CACHE_TIMEOUT = 1800000; // 30 minutes

export function isCacheValid(timestamp: string | null): boolean {
  if (!timestamp) return false;
  const age = Date.now() - parseInt(timestamp);
  return age < CACHE_TIMEOUT;
}

export function saveToCache(key: string, data: any) {
  localStorage.setItem(key, JSON.stringify(data));
  localStorage.setItem(`${key}_timestamp`, Date.now().toString());
}

export function getFromCache<T>(key: string): T | null {
  try {
    const data = localStorage.getItem(key);
    const timestamp = localStorage.getItem(`${key}_timestamp`);

    if (!data || !timestamp || !isCacheValid(timestamp)) {
      return null;
    }

    return JSON.parse(data);
  } catch (error) {
    return null;
  }
}

export function clearCache(key: string) {
  localStorage.removeItem(key);
  localStorage.removeItem(`${key}_timestamp`);
}

// Validation utilities
export function isValidEmail(email: string): boolean {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

export function isValidPhone(phone: string): boolean {
  const phoneRegex = /^\+?[\d\s\-\(\)]{10,}$/;
  return phoneRegex.test(phone);
}

// Date utilities
export function formatDate(date: string | Date): string {
  const d = new Date(date);
  return d.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

export function formatDateTime(date: string | Date): string {
  const d = new Date(date);
  return d.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// String utilities
export function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + "...";
}

export function capitalizeFirst(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

// Array utilities
export function uniqueArray<T>(array: T[]): T[] {
  return [...new Set(array)];
}

export function groupBy<T>(array: T[], key: keyof T): Record<string, T[]> {
  return array.reduce((groups, item) => {
    const group = String(item[key]);
    groups[group] = groups[group] || [];
    groups[group].push(item);
    return groups;
  }, {} as Record<string, T[]>);
}
