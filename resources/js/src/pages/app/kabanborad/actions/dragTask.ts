import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { taskStore } from "../store/kabanStore";
import { getAvatarSrc } from "../../../../helper/avatar";
import { emitForceCacheClear } from "../../../../helper/eventBus";
import { getCurrentUserId } from "../../../../helper/getUserData";

// Optimistic UI update - cập nhật UI ngay lập tức
export function updateTaskOptimistically(
  taskId: number,
  newStatus: string,
  projectData: any
) {
  if (!projectData?.value?.data?.tasks) {
    return;
  }

  // Tìm task và cập nhật status ngay lập tức
  const tasks = projectData.value.data.tasks;
  const taskIndex = tasks.findIndex((t: any) => t.id === taskId);

  if (taskIndex !== -1) {
    // Cập nhật task với reactive trigger
    const updatedTask = { ...tasks[taskIndex] };
    updatedTask.status = parseInt(newStatus);
    updatedTask.updated_at = new Date().toISOString();

    // Replace task trong array để trigger reactive update
    tasks.splice(taskIndex, 1, updatedTask);
  }
}

// Debounced API call để tránh gọi quá nhiều
let apiCallTimeout: any = null;
export function debouncedChangeTaskStatus(
  taskId: number,
  projectId: number,
  endPoint: string,
  newStatus: string,
  projectData: any
) {
  // Clear timeout cũ nếu có
  if (apiCallTimeout) {
    clearTimeout(apiCallTimeout);
  }

  // Cập nhật UI ngay lập tức (optimistic)
  updateTaskOptimistically(taskId, newStatus, projectData);

  // Emit event ngay lập tức để các page khác cập nhật
  emitForceCacheClear(
    projectId,
    "task-status-changed-by-drag",
    getCurrentUserId()
  );

  // Debounce API call (500ms)
  apiCallTimeout = setTimeout(async () => {
    try {
      await changeTaskStatus(taskId, projectId, endPoint);
    } catch (error) {
      // Silent error handling
    }
  }, 500);
}

export async function changeTaskStatus(
  taskId: number,
  projectId: number,
  endPoint: string
) {
  try {
    await makeHttpReq<changeTaskInput, { message: string }>(endPoint, "POST", {
      taskId: taskId,
      projectId: projectId,
    });

    // Emit event để dashboard biết task đã được cập nhật
    emitForceCacheClear(
      projectId,
      "task-status-changed-by-drag",
      getCurrentUserId()
    );
  } catch (error) {
    // Silent error handling
  }
}

export function cleanupDrag() {
  if (apiCallTimeout) {
    clearTimeout(apiCallTimeout);
    apiCallTimeout = null;
  }
}

interface changeTaskInput {
  taskId: number;
  projectId: number;
}

export function useDragTask(
  fn: (slug: string) => Promise<void>,
  slug: string,
  ProjectData?: any
) {
  // Touch drag state
  let isDragging = false;
  let draggedElement: HTMLElement | null = null;
  let startX = 0;
  let startY = 0;
  let currentX = 0;
  let currentY = 0;
  let dragThreshold = 10; // Minimum distance to start dragging
  let originalTransform = "";
  let originalZIndex = "";
  let originalOpacity = "";
  let ghostElement: HTMLElement | null = null;
  let ghostAnimationFrame: number | null = null;

  // Đảm bảo chỉ gắn listener một lần cho mỗi cột
  const attachedColumns = new Set<HTMLElement>();

  function addDropListener(
    targetColumn: HTMLElement,
    endpoint: string,
    newStatus: number
  ) {
    if (attachedColumns.has(targetColumn)) return;
    attachedColumns.add(targetColumn);

    targetColumn.addEventListener(
      "dragover",
      function (event) {
        event.preventDefault();
        targetColumn.classList.add("hovered");
      },
      { passive: false }
    );

    targetColumn.addEventListener("dragleave", function () {
      targetColumn.classList.remove("hovered");
    });

    targetColumn.addEventListener("drop", function (event) {
      event.preventDefault();
      targetColumn.classList.remove("hovered");

      const taskId = taskStore.draggedTaskId;
      const projectId = taskStore.draggedProjectId;

      if (taskId && projectId) {
        // Kiểm tra nếu status đã đúng thì không làm gì
        const task = ProjectData?.value?.data?.tasks?.find(
          (t: any) => t.id === taskId
        );

        if (task && task.status !== newStatus) {
          debouncedChangeTaskStatus(
            taskId,
            projectId,
            endpoint,
            newStatus.toString(),
            ProjectData
          );
        }
      }
      taskStore.clearDraggedTask();
    });

    // Touch drop listeners for mobile - simplified
    targetColumn.addEventListener(
      "touchend",
      function (event) {
        if (isDragging) {
          event.preventDefault();
          const touch = event.changedTouches[0];
          const rect = targetColumn.getBoundingClientRect();

          if (
            touch.clientX >= rect.left &&
            touch.clientX <= rect.right &&
            touch.clientY >= rect.top &&
            touch.clientY <= rect.bottom
          ) {
            // Drop on this column
            targetColumn.classList.remove("hovered");
            const taskId = taskStore.draggedTaskId;
            const projectId = taskStore.draggedProjectId;
            if (taskId && projectId) {
              const task = ProjectData?.value?.data?.tasks?.find(
                (t: any) => t.id === taskId
              );
              if (task && task.status !== newStatus) {
                debouncedChangeTaskStatus(
                  taskId,
                  projectId,
                  endpoint,
                  newStatus.toString(),
                  ProjectData
                );
              }
            }
            taskStore.clearDraggedTask();
          }

          // Clean up
          cleanupDrag();
        }
      },
      { passive: false }
    );
  }

  function setupAllDropListeners() {
    const notStartedColumn = document.querySelector(
      ".not-started-column"
    ) as HTMLElement;
    const pendingColumn = document.querySelector(
      ".pending-column"
    ) as HTMLElement;
    const completedColumn = document.querySelector(
      ".completed-column"
    ) as HTMLElement;
    if (notStartedColumn)
      addDropListener(notStartedColumn, "task/pending_to_not_started", 0);
    if (pendingColumn)
      addDropListener(pendingColumn, "task/not_started_to_pending", 1);
    if (completedColumn)
      addDropListener(completedColumn, "task/not_started_to_completed", 2);

    // Setup drag listeners for task cards
    setupTaskCardDragListeners();
  }

  function setupTaskCardDragListeners() {
    // Remove existing listeners first
    const existingCards = document.querySelectorAll(".task-card");
    existingCards.forEach((card) => {
      card.removeEventListener("dragstart", handleDragStart as EventListener);
      card.removeEventListener("dragend", handleDragEnd as EventListener);
      card.removeEventListener("touchstart", handleTouchStart as EventListener);
      card.removeEventListener("touchmove", handleTouchMove as EventListener);
      card.removeEventListener("touchend", handleTouchEnd as EventListener);
    });

    // Add new listeners
    const taskCards = document.querySelectorAll(".task-card");
    taskCards.forEach((card) => {
      card.addEventListener("dragstart", handleDragStart as EventListener);
      card.addEventListener("dragend", handleDragEnd as EventListener);
      card.addEventListener("touchstart", handleTouchStart as EventListener);
      card.addEventListener("touchmove", handleTouchMove as EventListener);
      card.addEventListener("touchend", handleTouchEnd as EventListener);
    });
  }

  function handleDragStart(event: Event) {
    const dragEvent = event as DragEvent;
    const target = dragEvent.target as HTMLElement;
    const taskCard = target.closest(".task-card") as HTMLElement;

    if (taskCard) {
      const taskId = parseInt(taskCard.dataset.taskId || "0");
      const projectId = parseInt(taskCard.dataset.projectId || "0");

      if (taskId && projectId) {
        taskStore.setDraggedTask(taskId, projectId);
        taskCard.classList.add("dragging");

        // Create ghost element
        const ghost = taskCard.cloneNode(true) as HTMLElement;
        ghost.classList.add("ghost-task");
        ghost.style.opacity = "0.5";
        ghost.style.transform = "rotate(5deg)";
        document.body.appendChild(ghost);

        if (dragEvent.dataTransfer) {
          dragEvent.dataTransfer.effectAllowed = "move";
          // Đặt ghost-task ở giữa chuột
          const rect = ghost.getBoundingClientRect();
          const offsetX = rect.width / 2;
          const offsetY = rect.height / 2;
          dragEvent.dataTransfer.setDragImage(ghost, offsetX, offsetY);
        }
      }
    }
  }

  function handleDragEnd(event: Event) {
    const target = event.target as HTMLElement;
    const taskCard = target.closest(".task-card") as HTMLElement;
    if (taskCard) {
      taskCard.classList.remove("dragging");

      // Remove ghost element
      const ghost = document.querySelector(".ghost-task");
      if (ghost) {
        ghost.remove();
      }
    }
    taskStore.clearDraggedTask();
  }

  // New simplified touch handlers for mobile
  function handleTouchStart(event: Event) {
    const touchEvent = event as TouchEvent;
    const target = touchEvent.target as HTMLElement;
    const taskCard = target.closest(".task-card") as HTMLElement;

    if (taskCard && touchEvent.touches.length === 1) {
      const touch = touchEvent.touches[0];
      startX = touch.clientX;
      startY = touch.clientY;

      const taskId = parseInt(taskCard.dataset.taskId || "0");
      const projectId = parseInt(taskCard.dataset.projectId || "0");

      if (taskId && projectId) {
        draggedElement = taskCard;
        taskStore.setDraggedTask(taskId, projectId);

        // Store original styles
        originalTransform = taskCard.style.transform;
        originalZIndex = taskCard.style.zIndex;
        originalOpacity = taskCard.style.opacity;
      }
    }
  }

  function handleTouchMove(event: Event) {
    if (draggedElement && !isDragging) {
      const touchEvent = event as TouchEvent;
      const touch = touchEvent.touches[0];
      const deltaX = Math.abs(touch.clientX - startX);
      const deltaY = Math.abs(touch.clientY - startY);
      const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

      // Start dragging only if moved enough distance
      if (distance > dragThreshold) {
        isDragging = true;

        // Simple visual feedback - just add dragging class
        draggedElement.classList.add("dragging");

        // Add additional visual feedback for mobile
        draggedElement.style.backgroundColor = "#eff6ff";
        draggedElement.style.border = "2px solid #3b82f6";
        draggedElement.style.boxShadow = "0 4px 16px rgba(59, 130, 246, 0.3)";
        draggedElement.style.transform = "scale(1.02)";
        draggedElement.style.opacity = "0.8";
        draggedElement.style.zIndex = "1000";

        // Create ghost element for mobile
        createMobileGhost(touch.clientX, touch.clientY);

        // Check which column we're hovering over
        checkColumnHover(touch.clientX, touch.clientY);
      }
    }

    if (isDragging) {
      event.preventDefault();
      const touchEvent = event as TouchEvent;
      const touch = touchEvent.touches[0];
      currentX = touch.clientX;
      currentY = touch.clientY;

      // Update ghost position
      updateMobileGhost(currentX, currentY);

      // Check which column we're hovering over
      checkColumnHover(currentX, currentY);
    }
  }

  function handleTouchEnd(event: Event) {
    if (isDragging) {
      event.preventDefault();

      // Find which column we dropped on
      const columns = document.querySelectorAll(".kanban-column");
      let droppedColumn: HTMLElement | null = null;
      let columnStatus = -1;
      let columnEndpoint = "";

      columns.forEach((column) => {
        const rect = column.getBoundingClientRect();
        if (
          currentX >= rect.left &&
          currentX <= rect.right &&
          currentY >= rect.top &&
          currentY <= rect.bottom
        ) {
          droppedColumn = column as HTMLElement;

          // Determine status based on column class
          if (column.classList.contains("not-started-column")) {
            columnStatus = 0;
            columnEndpoint = "task/pending_to_not_started";
          } else if (column.classList.contains("pending-column")) {
            columnStatus = 1;
            columnEndpoint = "task/not_started_to_pending";
          } else if (column.classList.contains("completed-column")) {
            columnStatus = 2;
            columnEndpoint = "task/not_started_to_completed";
          }
        }
      });

      // Process the drop
      if (droppedColumn && columnStatus !== -1) {
        const taskId = taskStore.draggedTaskId;
        const projectId = taskStore.draggedProjectId;
        if (taskId && projectId) {
          const task = ProjectData?.value?.data?.tasks?.find(
            (t: any) => t.id === taskId
          );
          if (task && task.status !== columnStatus) {
            debouncedChangeTaskStatus(
              taskId,
              projectId,
              columnEndpoint,
              columnStatus.toString(),
              ProjectData
            );
          }
        }
      }

      // Clean up
      cleanupDrag();
    }
  }

  function checkColumnHover(x: number, y: number) {
    const columns = document.querySelectorAll(".kanban-column");
    columns.forEach((column) => {
      const rect = column.getBoundingClientRect();
      if (
        x >= rect.left &&
        x <= rect.right &&
        y >= rect.top &&
        y <= rect.bottom
      ) {
        column.classList.add("hovered");
      } else {
        column.classList.remove("hovered");
      }
    });
  }

  function cleanupDrag() {
    isDragging = false;
    if (draggedElement) {
      draggedElement.classList.remove("dragging");
      // Restore original styles
      draggedElement.style.transform = originalTransform;
      draggedElement.style.zIndex = originalZIndex;
      draggedElement.style.opacity = originalOpacity;
      draggedElement.style.backgroundColor = "";
      draggedElement.style.border = "";
      draggedElement.style.boxShadow = "";
      draggedElement = null;
    }
    if (ghostElement) {
      ghostElement.remove();
      ghostElement = null;
    }
    if (ghostAnimationFrame) {
      cancelAnimationFrame(ghostAnimationFrame);
      ghostAnimationFrame = null;
    }

    // Remove hover states from all columns
    document.querySelectorAll(".kanban-column").forEach((column) => {
      column.classList.remove("hovered");
    });

    taskStore.clearDraggedTask();

    // Clear API call timeout if exists
    if (apiCallTimeout) {
      clearTimeout(apiCallTimeout);
      apiCallTimeout = null;
    }
  }

  function getMemberName(member: any) {
    return (
      member?.user?.name ||
      member?.members?.name ||
      member?.member?.name ||
      member?.name ||
      ""
    );
  }
  function getMemberAvatar(member: any) {
    return (
      member?.avatar ||
      member?.user?.avatar ||
      member?.members?.avatar ||
      member?.member?.avatar ||
      ""
    );
  }

  function createMobileGhost(x: number, y: number) {
    if (ghostElement) {
      ghostElement.remove();
    }
    ghostElement = document.createElement("div");
    ghostElement.classList.add("ghost-task");
    ghostElement.style.position = "fixed";
    ghostElement.style.zIndex = "99999";
    ghostElement.style.pointerEvents = "none";
    ghostElement.style.opacity = "1";
    ghostElement.style.transition =
      "left 0.15s cubic-bezier(0.4,0,0.2,1), top 0.15s cubic-bezier(0.4,0,0.2,1)";
    ghostElement.style.willChange = "left, top";
    ghostElement.style.background = "#e0f2fe";
    ghostElement.style.border = "3px solid #3b82f6";
    ghostElement.style.color = "#1e293b";
    ghostElement.style.fontWeight = "bold";
    ghostElement.style.fontSize = "18px";
    ghostElement.style.width = "220px";
    ghostElement.style.minHeight = "80px";
    ghostElement.style.display = "flex";
    ghostElement.style.flexDirection = "column";
    ghostElement.style.alignItems = "flex-start";
    ghostElement.style.justifyContent = "center";
    ghostElement.style.borderRadius = "16px";
    ghostElement.style.padding = "16px";
    ghostElement.style.boxSizing = "border-box";

    // Lấy thông tin task đang kéo
    let taskName = "";
    let taskDate = "";
    let taskMembers: any[] = [];
    if (draggedElement) {
      const nameEl = draggedElement.querySelector(".task-title");
      if (nameEl) taskName = nameEl.textContent || "";
      const dateEl = draggedElement.querySelector(".task-date span");
      if (dateEl) taskDate = dateEl.textContent || "";
      // Lấy data-task-id để tìm task trong ProjectData
      const taskId = draggedElement.dataset.taskId;
      let taskObj = null;
      if (taskId && ProjectData?.value?.data?.tasks) {
        taskObj = ProjectData.value.data.tasks.find((t: any) => t.id == taskId);
      }
      if (taskObj && Array.isArray(taskObj.task_members)) {
        taskMembers = taskObj.task_members;
      }
    }

    // Tạo nội dung HTML cho ghost-task (hiển thị avatar đúng)
    ghostElement.innerHTML = `
      <div style="font-size:20px;font-weight:bold;margin-bottom:8px;">${taskName}</div>
      <div style="font-size:14px;margin-bottom:8px;">${
        taskDate ? "📅 " + taskDate : ""
      }</div>
      <div style="display:flex;gap:4px;">
        ${taskMembers
          .map((m: any) => {
            const avatar = getAvatarSrc(getMemberAvatar(m), getMemberName(m));
            const name = getMemberName(m);
            if (avatar && !avatar.includes("ui-avatars.com")) {
              return `<img src='${avatar}' style='width:28px;height:28px;border-radius:50%;border:2px solid #f59e0b;object-fit:cover;background:#fff;' alt='${name}' title='${name}' />`;
            } else {
              return `<span style='background:#fff;color:#f59e0b;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:bold;border:2px solid #f59e0b;'>${
                name ? name.charAt(0).toUpperCase() : "?"
              }</span>`;
            }
          })
          .join("")}
      </div>
    `;

    // Đặt vị trí center theo ngón tay
    const rect = ghostElement.getBoundingClientRect();
    const offsetX = rect.width / 2;
    const offsetY = rect.height / 2;
    ghostElement.style.left = x - offsetX + "px";
    ghostElement.style.top = y - offsetY + "px";
    ghostElement.style.transform = "";

    document.body.appendChild(ghostElement);
  }

  function updateGhostTaskColor(x: number, y: number) {
    if (!ghostElement) return;
    const columns = document.querySelectorAll(".kanban-column");
    let found = false;
    columns.forEach((column) => {
      const rect = column.getBoundingClientRect();
      if (
        x >= rect.left &&
        x <= rect.right &&
        y >= rect.top &&
        y <= rect.bottom
      ) {
        found = true;
        if (column.classList.contains("not-started-column") && ghostElement) {
          ghostElement.style.background = "#e0f2fe";
          ghostElement.style.border = "3px solid #3b82f6";
        } else if (
          column.classList.contains("pending-column") &&
          ghostElement
        ) {
          ghostElement.style.background = "#fef9c3";
          ghostElement.style.border = "3px solid #f59e0b";
        } else if (
          column.classList.contains("completed-column") &&
          ghostElement
        ) {
          ghostElement.style.background = "#dcfce7";
          ghostElement.style.border = "3px solid #10b981";
        }
      }
    });
    if (!found && ghostElement) {
      ghostElement.style.background = "#e0f2fe";
      ghostElement.style.border = "3px solid #3b82f6";
    }
  }

  function updateMobileGhost(x: number, y: number) {
    if (!ghostElement) return;
    if (ghostAnimationFrame) cancelAnimationFrame(ghostAnimationFrame);
    ghostAnimationFrame = requestAnimationFrame(() => {
      if (!ghostElement) return;
      const rect = ghostElement.getBoundingClientRect();
      const offsetX = rect.width / 2;
      const offsetY = rect.height / 2;
      ghostElement.style.left = x - offsetX + "px";
      ghostElement.style.top = y - offsetY + "px";
      updateGhostTaskColor(x, y);
    });
  }

  return {
    setupAllDropListeners,
    setupTaskCardDragListeners,
  };
}
