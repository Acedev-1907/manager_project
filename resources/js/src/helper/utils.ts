import { showError } from "./toast-notificaltion";
import { isAuthError, handleAuthError } from "./authInterceptor";

// Error handling utilities
export function showErrorResponse(err: unknown) {
  // Kiểm tra lỗi authentication trước
  if (isAuthError(err)) {
    handleAuthError();
    return;
  }

  if (Array.isArray(err)) {
    for (const message of err as string[]) {
      showError(message);
    }
  } else {
    showError((err as Error).message);
  }
}

// Debounce utility
export function myDebounce<T extends (...args: any[]) => any>(
  func: T,
  delay: number
): (...args: Parameters<T>) => void {
  let timeoutId: number;

  return function (...args: Parameters<T>) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func(...args), delay);
  };
}

// Modal utilities
export function openModal(element: string): Promise<HTMLElement | null> {
  return new Promise((resolve) => {
    const modal = document.getElementById(element) as HTMLElement;

    if (modal) {
      setTimeout(() => {
        modal.classList.add("fade", "show");
        modal.style.display = "block";
        modal.classList.add("in");
      }, 500);

      // Add class to the body for the modal backdrop
      document.body.classList.add("modal-open");

      const modalBackdrop = document.createElement("div");
      modalBackdrop.className = "modal-backdrop fade show";
      document.body.appendChild(modalBackdrop);
    }
    resolve(modal);
  });
}

export function closeModal(element: string): void {
  const modal = document.getElementById(element) as HTMLElement;
  const modalBackdrop = document.querySelector(".modal-backdrop");

  if (modal) {
    // Remove added classes
    modal.classList.remove("in", "show", "fade");
    modal.style.display = "";

    document.body.classList.remove("modal-open");

    // Remove the modal backdrop element
    if (modalBackdrop) {
      document.body.removeChild(modalBackdrop);
    }
  }
}

// String utilities
export function getChar(str: string, index: number = 1): string {
  if (typeof str !== "string" || !str) {
    return "";
  }

  if (index >= 0 && index < str.length) {
    return str.charAt(index).toLocaleUpperCase();
  }

  return "";
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

// Validation utilities
export function isValidEmail(email: string): boolean {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Array utilities
export function chunk<T>(array: T[], size: number): T[][] {
  const chunks: T[][] = [];
  for (let i = 0; i < array.length; i += size) {
    chunks.push(array.slice(i, i + size));
  }
  return chunks;
}

// Object utilities
export function deepClone<T>(obj: T): T {
  if (obj === null || typeof obj !== "object") {
    return obj;
  }

  if (obj instanceof Date) {
    return new Date(obj.getTime()) as unknown as T;
  }

  if (Array.isArray(obj)) {
    return obj.map((item) => deepClone(item)) as unknown as T;
  }

  const cloned = {} as T;
  for (const key in obj) {
    if (obj.hasOwnProperty(key)) {
      cloned[key] = deepClone(obj[key]);
    }
  }

  return cloned;
}
