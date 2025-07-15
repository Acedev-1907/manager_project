import Swal from "sweetalert2";

export function showSuccess(message: string, title = "Success") {
  Swal.fire({
    icon: "success",
    title,
    text: message,
  });
}

export function showError(message: string, title = "Error") {
  Swal.fire({
    icon: "error",
    title,
    text: message,
  });
}

export function showWarning(message: string, title = "Warning") {
  Swal.fire({
    icon: "warning",
    title,
    text: message,
  });
}

export function showInfo(message: string, title = "Info") {
  Swal.fire({
    icon: "info",
    title,
    text: message,
  });
}

export function showConfirm(
  message: string,
  title = "Confirmation"
): Promise<boolean> {
  return Swal.fire({
    title,
    text: message,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes",
    cancelButtonText: "No",
    reverseButtons: true,
  }).then((result) => !!result.isConfirmed);
}
