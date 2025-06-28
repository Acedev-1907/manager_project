import { showError } from "./toast-notificaltion";
import { isAuthError, handleAuthError } from "./authInterceptor";

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

export function myDebounce<T>(func: () => Promise<T>, delay: number) {
  let time: any;

  return function () {
    clearTimeout(time);

    setTimeout(() => func(), delay);
  };
}

export function openModal(element: string) {
  return new Promise((resolve) => {
    // Open the Bootstrap modal using its API
    var modal = document.getElementById(element) as HTMLElement;

    if (modal) {
      setTimeout(function () {
        modal.classList.add("fade", "show");
        modal.style.display = "block";
        modal.classList.add("in");
      }, 500);

      // Add class to the body for the modal backdrop
      document.body.classList.add("modal-open");

      var modalBackdrop = document.createElement("div");
      modalBackdrop.className = "modal-backdrop fade show";
      document.body.appendChild(modalBackdrop);
    }
    resolve(modal);
  });
}

export function closeModal(element: string) {
  // Close the Bootstrap modal
  var modal = document.getElementById(element) as HTMLElement;
  var modalBackdrop = document.querySelector(".modal-backdrop");

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

export function getChar(str: string) {
  if (typeof str !== "undefined") {
    const index = 1;
    if (index >= 0 && index < str.length) {
      return str.charAt(index).toLocaleUpperCase();
    } else {
      return "";
    }
  }
}
