let lockCount = 0;

export function lockBodyScroll() {
  lockCount += 1;
  if (lockCount === 1) {
    document.body.style.overflow = "hidden";
    document.body.classList.add("modal-open");
  }
}

export function unlockBodyScroll() {
  if (lockCount === 0) return;
  lockCount -= 1;
  if (lockCount === 0) {
    document.body.style.overflow = "";
    document.body.classList.remove("modal-open");
  }
}


