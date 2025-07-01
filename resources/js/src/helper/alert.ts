import Swal from "sweetalert2";

export function showSuccess(message: string, title = "Thành công") {
  Swal.fire({
    icon: "success",
    title,
    text: message,
  });
}

export function showError(message: string, title = "Lỗi") {
  Swal.fire({
    icon: "error",
    title,
    text: message,
  });
}

export function showWarning(message: string, title = "Cảnh báo") {
  Swal.fire({
    icon: "warning",
    title,
    text: message,
  });
}

export function showInfo(message: string, title = "Thông tin") {
  Swal.fire({
    icon: "info",
    title,
    text: message,
  });
}
