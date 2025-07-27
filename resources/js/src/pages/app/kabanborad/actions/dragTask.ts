import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { taskStore } from "../store/kabanStore";
import { getAvatarSrc } from "../../../../helper/avatar";
import { emitForceCacheClear } from "../../../../helper/eventBus";
import { getCurrentUserId } from "../../../../helper/getUserData";

// Constants
const DRAG_CONFIG = {
  threshold: 5,
  directionThreshold: 3,
  scrollThreshold: 50,
  maxScrollSpeed: 8,
  minScrollSpeed: 2,
  speedMultiplier: 0.6,
  transitionFactor: 0.7,
  slowdownFactor: 0.8,
  scrollInterval: 16, // ~60fps
  apiDebounceTime: 500,
} as const;

// Optimistic UI update
export function updateTaskOptimistically(
  taskId: number,
  newStatus: string,
  projectData: any
) {
  if (!projectData?.value?.data?.tasks) return;

  const tasks = projectData.value.data.tasks;
  const taskIndex = tasks.findIndex((t: any) => t.id === taskId);

  if (taskIndex !== -1) {
    const updatedTask = { ...tasks[taskIndex] };
    updatedTask.status = parseInt(newStatus);
    updatedTask.updated_at = new Date().toISOString();
    tasks.splice(taskIndex, 1, updatedTask);
  }
}

// Debounced API call
let apiCallTimeout: any = null;
export function debouncedChangeTaskStatus(
  taskId: number,
  projectId: number,
  endPoint: string,
  newStatus: string,
  projectData: any
) {
  if (apiCallTimeout) {
    clearTimeout(apiCallTimeout);
  }

  updateTaskOptimistically(taskId, newStatus, projectData);
  emitForceCacheClear(
    projectId,
    "task-status-changed-by-drag",
    getCurrentUserId()
  );

  apiCallTimeout = setTimeout(async () => {
    try {
      await changeTaskStatus(taskId, projectId, endPoint);
    } catch (error) {
      console.error("Error in debounced API call:", error);
    }
  }, DRAG_CONFIG.apiDebounceTime);
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

    emitForceCacheClear(
      projectId,
      "task-status-changed-by-drag",
      getCurrentUserId()
    );
  } catch (error) {
    console.error("HTTP request failed:", error);
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
  let originalTransform = "";
  let originalZIndex = "";
  let originalOpacity = "";
  let ghostElement: HTMLElement | null = null;
  let ghostAnimationFrame: number | null = null;

  // Auto-scroll state
  let autoScrollInterval: number | null = null;
  let autoScrollSpeed = 0;
  let lastY = 0;
  let dragDirection = 0; // -1: up, 0: none, 1: down

  // Auto-scroll functions
  function startAutoScroll() {
    if (autoScrollInterval) return;

    autoScrollInterval = setInterval(() => {
      if (autoScrollSpeed !== 0) {
        const currentScrollY = window.scrollY;
        const newScrollY = currentScrollY + autoScrollSpeed;
        window.scrollTo(0, newScrollY);
      }
    }, DRAG_CONFIG.scrollInterval);
  }

  function stopAutoScroll() {
    if (autoScrollInterval) {
      clearInterval(autoScrollInterval);
      autoScrollInterval = null;
      autoScrollSpeed = 0;
    }
  }

  function updateAutoScroll(y: number) {
    const windowHeight = window.innerHeight;
    const scrollY = window.scrollY;
    const documentHeight = document.documentElement.scrollHeight;

    // Track drag direction
    if (lastY !== 0) {
      const deltaY = y - lastY;
      if (Math.abs(deltaY) > DRAG_CONFIG.directionThreshold) {
        dragDirection = deltaY > 0 ? 1 : -1;
      }
    }
    lastY = y;

    // Remove existing indicators
    const existingIndicators = document.querySelectorAll(
      ".auto-scroll-indicator"
    );
    existingIndicators.forEach((indicator) => indicator.remove());

    // Calculate distances
    const distanceFromTop = y;
    const distanceFromBottom = windowHeight - y;

    let shouldScroll = false;
    let scrollDirection = 0;
    let speed = 0;

    // Scroll up when near top edge OR dragging up
    if (distanceFromTop < DRAG_CONFIG.scrollThreshold || dragDirection === -1) {
      if (scrollY > 0) {
        shouldScroll = true;
        scrollDirection = -1;
        speed = DRAG_CONFIG.maxScrollSpeed * DRAG_CONFIG.speedMultiplier;
      }
    }
    // Scroll down when near bottom edge OR dragging down
    else if (
      distanceFromBottom < DRAG_CONFIG.scrollThreshold ||
      dragDirection === 1
    ) {
      if (scrollY < documentHeight - windowHeight) {
        shouldScroll = true;
        scrollDirection = 1;
        speed = DRAG_CONFIG.maxScrollSpeed * DRAG_CONFIG.speedMultiplier;
      }
    }

    if (shouldScroll) {
      const targetSpeed = scrollDirection * speed;
      autoScrollSpeed =
        autoScrollSpeed * DRAG_CONFIG.transitionFactor +
        targetSpeed * (1 - DRAG_CONFIG.transitionFactor);

      if (!autoScrollInterval) {
        startAutoScroll();
      }

      showAutoScrollIndicator(scrollDirection === -1 ? "top" : "bottom");
    } else {
      autoScrollSpeed *= DRAG_CONFIG.slowdownFactor;
      if (Math.abs(autoScrollSpeed) < 0.1) {
        stopAutoScroll();
      }
    }
  }

  function showAutoScrollIndicator(position: "top" | "bottom") {
    const indicator = document.createElement("div");
    indicator.className = `auto-scroll-indicator ${position}`;
    indicator.textContent =
      position === "top" ? "↑ Scroll Up" : "↓ Scroll Down";
    document.body.appendChild(indicator);

    setTimeout(() => {
      if (indicator.parentNode) {
        indicator.parentNode.removeChild(indicator);
      }
    }, 2000);
  }

  // Drop listeners
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

    // Touch drop listeners for mobile
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

    setupTaskCardDragListeners();
  }

  function setupTaskCardDragListeners() {
    const taskCards = document.querySelectorAll(".task-card");

    taskCards.forEach((card) => {
      // Remove existing listeners
      card.removeEventListener("dragstart", handleDragStart as EventListener);
      card.removeEventListener("dragend", handleDragEnd as EventListener);
      card.removeEventListener("touchstart", handleTouchStart as EventListener);
      card.removeEventListener("touchmove", handleTouchMove as EventListener);
      card.removeEventListener("touchend", handleTouchEnd as EventListener);

      // Add new listeners
      card.addEventListener("dragstart", handleDragStart as EventListener);
      card.addEventListener("dragend", handleDragEnd as EventListener);
      card.addEventListener("touchstart", handleTouchStart as EventListener, {
        passive: false,
      });
      card.addEventListener("touchmove", handleTouchMove as EventListener, {
        passive: false,
      });
      card.addEventListener("touchend", handleTouchEnd as EventListener, {
        passive: false,
      });
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

        const ghost = taskCard.cloneNode(true) as HTMLElement;
        ghost.classList.add("ghost-task");
        ghost.style.opacity = "0.5";
        ghost.style.transform = "rotate(5deg)";
        document.body.appendChild(ghost);

        if (dragEvent.dataTransfer) {
          dragEvent.dataTransfer.effectAllowed = "move";
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
      const ghost = document.querySelector(".ghost-task");
      if (ghost) ghost.remove();
    }
    taskStore.clearDraggedTask();
  }

  function handleTouchStart(event: Event) {
    const touchEvent = event as TouchEvent;
    const touch = touchEvent.touches[0];

    // Reset drag state
    isDragging = false;
    startX = touch.clientX;
    startY = touch.clientY;
    currentX = touch.clientX;
    currentY = touch.clientY;

    // Initialize auto-scroll state
    dragDirection = 0;
    lastY = touch.clientY;
    stopAutoScroll();

    // Store the dragged element and set taskStore values
    const target = event.target as HTMLElement;
    const taskCard = target.closest(".task-card") as HTMLElement;
    if (taskCard) {
      draggedElement = taskCard;
      const taskId = parseInt(taskCard.dataset.taskId || "0");
      const projectId = parseInt(taskCard.dataset.projectId || "0");

      if (taskId && projectId) {
        taskStore.setDraggedTask(taskId, projectId);
      }
    }
  }

  function handleTouchMove(event: Event) {
    if (!draggedElement) return;

    const touchEvent = event as TouchEvent;
    const touch = touchEvent.touches[0];

    if (!isDragging) {
      const deltaX = Math.abs(touch.clientX - startX);
      const deltaY = Math.abs(touch.clientY - startY);
      const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

      if (distance > DRAG_CONFIG.threshold) {
        isDragging = true;
        event.preventDefault();

        document.body.style.touchAction = "none";
        document.body.style.overflow = "auto";

        draggedElement.classList.add("dragging");
        draggedElement.style.backgroundColor = "#eff6ff";
        draggedElement.style.border = "2px solid #3b82f6";
        draggedElement.style.boxShadow = "0 4px 16px rgba(59, 130, 246, 0.3)";
        draggedElement.style.transform = "scale(1.02)";
        draggedElement.style.opacity = "0.8";
        draggedElement.style.zIndex = "1000";

        createMobileGhost(touch.clientX, touch.clientY);
        checkColumnHover(touch.clientX, touch.clientY);
      }
    } else {
      event.preventDefault();
      currentX = touch.clientX;
      currentY = touch.clientY;

      updateMobileGhost(currentX, currentY);
      checkColumnHover(currentX, currentY);
      updateAutoScroll(currentY);
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
      cleanupDragVisuals();
      taskStore.clearDraggedTask();
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

  function cleanupDragVisuals() {
    isDragging = false;
    if (draggedElement) {
      draggedElement.classList.remove("dragging");
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

    document.querySelectorAll(".kanban-column").forEach((column) => {
      column.classList.remove("hovered");
    });

    document.body.style.touchAction = "";
    document.body.style.overflow = "";
    stopAutoScroll();
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

    // Get task info
    let taskName = "";
    let taskDate = "";
    let taskMembers: any[] = [];
    if (draggedElement) {
      const nameEl = draggedElement.querySelector(".task-title");
      if (nameEl) taskName = nameEl.textContent || "";
      const dateEl = draggedElement.querySelector(".task-date span");
      if (dateEl) taskDate = dateEl.textContent || "";
      const taskId = draggedElement.dataset.taskId;
      let taskObj = null;
      if (taskId && ProjectData?.value?.data?.tasks) {
        taskObj = ProjectData.value.data.tasks.find((t: any) => t.id == taskId);
      }
      if (taskObj && Array.isArray(taskObj.task_members)) {
        taskMembers = taskObj.task_members;
      }
    }

    // Create ghost content
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

    // Position ghost
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
