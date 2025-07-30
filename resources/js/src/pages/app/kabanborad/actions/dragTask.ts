import { makeHttpReq } from "../../../../helper/makeHttpReq";
import { getAvatarSrc } from "../../../../helper/avatar";
import { emitForceCacheClear } from "../../../../helper/eventBus";
import { getCurrentUserId } from "../../../../helper/getUserData";

// Constants
const DRAG_CONFIG = {
  threshold: 5,
  apiDebounceTime: 500,
  // Auto-scroll constants
  horizontalScrollThreshold: 150,
  horizontalScrollSpeed: 15,
  horizontalScrollInterval: 16, // ~60fps
} as const;

// Extend HTMLElement interface for cleanup function
interface ExtendedHTMLElement extends HTMLElement {
  _dropCleanup?: () => void;
}

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
    // Update task in place to prevent duplicates
    const updatedTask = { ...tasks[taskIndex] };
    const status = parseInt(newStatus);

    // newStatus is the status value directly (0, 1, 2...)
    updatedTask.status = status;
    updatedTask.column_id = status + 1; // Map status to column ID for reference
    updatedTask.updated_at = new Date().toISOString();

    // Replace the task in place instead of splice
    tasks[taskIndex] = updatedTask;
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

  apiCallTimeout = setTimeout(async () => {
    try {
      await changeTaskStatus(taskId, projectId, endPoint);
    } catch (error) {
      // Silent error handling
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

export function useDragTask(ProjectData?: any) {
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
  let touchStartTime = 0;

  // Flag to prevent duplicate API calls
  let hasProcessedDrop = false;

  // Auto-scroll state for horizontal scrolling
  let horizontalScrollInterval: number | null = null;
  let horizontalScrollSpeed = 0;
  let lastX = 0;

  // Mouse drag state for auto-scroll without task
  let isMouseDragging = false;
  let mouseStartX = 0;
  let mouseStartY = 0;
  let isGrabbing = false;
  let grabStartX = 0;
  let grabStartY = 0;
  let grabScrollLeft = 0;
  let isMouseDown = false;
  let mouseDownX = 0;
  let mouseDownY = 0;

  // Drop listeners
  const attachedColumns = new Set<ExtendedHTMLElement>();

  // Auto-scroll functions for horizontal scrolling
  function startHorizontalScroll() {
    if (horizontalScrollInterval) return;

    horizontalScrollInterval = setInterval(() => {
      if (horizontalScrollSpeed !== 0) {
        let kanbanContainer = document.querySelector(".kanban-grid-container");
        if (!kanbanContainer) {
          kanbanContainer = document.querySelector(
            '[class*="kanban-grid-container"]'
          );
        }
        if (!kanbanContainer) {
          kanbanContainer = document.querySelector('[class*="kanban"]');
        }

        if (kanbanContainer) {
          const currentScrollLeft = kanbanContainer.scrollLeft;
          const newScrollLeft = currentScrollLeft + horizontalScrollSpeed;
          kanbanContainer.scrollLeft = newScrollLeft;
        }
      }
    }, DRAG_CONFIG.horizontalScrollInterval);
  }

  function stopHorizontalScroll() {
    if (horizontalScrollInterval) {
      clearInterval(horizontalScrollInterval);
      horizontalScrollInterval = null;
      horizontalScrollSpeed = 0;
    }
  }

  function updateHorizontalScroll(x: number) {
    let kanbanContainer = document.querySelector(".kanban-grid-container");
    if (!kanbanContainer) {
      kanbanContainer = document.querySelector(
        '[class*="kanban-grid-container"]'
      );
    }
    if (!kanbanContainer) {
      kanbanContainer = document.querySelector('[class*="kanban"]');
    }

    if (!kanbanContainer) {
      horizontalScrollSpeed = 0;
      return;
    }

    const containerRect = kanbanContainer.getBoundingClientRect();
    const containerLeft = containerRect.left;
    const containerRight = containerRect.right;
    const containerWidth = containerRect.width;

    // Check if we need to scroll left
    if (x < containerLeft + DRAG_CONFIG.horizontalScrollThreshold) {
      const distanceFromLeft =
        containerLeft + DRAG_CONFIG.horizontalScrollThreshold - x;
      horizontalScrollSpeed = -Math.min(
        DRAG_CONFIG.horizontalScrollSpeed,
        distanceFromLeft * 0.5
      );
    }
    // Check if we need to scroll right
    else if (x > containerRight - DRAG_CONFIG.horizontalScrollThreshold) {
      const distanceFromRight =
        x - (containerRight - DRAG_CONFIG.horizontalScrollThreshold);
      horizontalScrollSpeed = Math.min(
        DRAG_CONFIG.horizontalScrollSpeed,
        distanceFromRight * 0.5
      );
    }
    // No scroll needed
    else {
      horizontalScrollSpeed = 0;
    }

    // Start or stop scroll interval based on speed
    if (horizontalScrollSpeed !== 0 && !horizontalScrollInterval) {
      startHorizontalScroll();
    } else if (horizontalScrollSpeed === 0 && horizontalScrollInterval) {
      stopHorizontalScroll();
    }
  }

  function addDropListener(
    targetColumn: ExtendedHTMLElement,
    endpoint: string,
    newStatus: number
  ) {
    if (attachedColumns.has(targetColumn)) {
      return;
    }

    attachedColumns.add(targetColumn);

    function handleDragOver(e: DragEvent) {
      e.preventDefault();
      e.stopPropagation();

      if (!isDragging || !draggedElement) return;

      const rect = targetColumn.getBoundingClientRect();
      const x = e.clientX;
      const y = e.clientY;

      // Check if mouse is within column bounds
      if (
        x >= rect.left &&
        x <= rect.right &&
        y >= rect.top &&
        y <= rect.bottom
      ) {
        targetColumn.style.backgroundColor = "rgba(59, 130, 246, 0.1)";
        targetColumn.style.borderColor = "#3b82f6";
      } else {
        targetColumn.style.backgroundColor = "";
        targetColumn.style.borderColor = "";
      }
    }

    function handleDragLeave(e: DragEvent) {
      e.preventDefault();
      e.stopPropagation();

      const rect = targetColumn.getBoundingClientRect();
      const x = e.clientX;
      const y = e.clientY;

      // Only remove highlight if mouse actually left the column
      if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
        targetColumn.style.backgroundColor = "";
        targetColumn.style.borderColor = "";
      }
    }

    function handleDrop(e: DragEvent) {
      e.preventDefault();
      e.stopPropagation();

      if (!isDragging || !draggedElement || hasProcessedDrop) return;

      const rect = targetColumn.getBoundingClientRect();
      const x = e.clientX;
      const y = e.clientY;

      // Check if drop is within column bounds
      if (
        x >= rect.left &&
        x <= rect.right &&
        y >= rect.top &&
        y <= rect.bottom
      ) {
        const taskId = parseInt(draggedElement.dataset.taskId || "0");
        const projectId = parseInt(draggedElement.dataset.projectId || "0");

        if (taskId && projectId) {
          hasProcessedDrop = true;
          debouncedChangeTaskStatus(
            taskId,
            projectId,
            endpoint,
            newStatus.toString(),
            ProjectData
          );
        }
      }

      // Reset column styling
      targetColumn.style.backgroundColor = "";
      targetColumn.style.borderColor = "";
    }

    targetColumn.addEventListener("dragover", handleDragOver);
    targetColumn.addEventListener("dragleave", handleDragLeave);
    targetColumn.addEventListener("drop", handleDrop);

    // Store cleanup function
    targetColumn._dropCleanup = () => {
      targetColumn.removeEventListener("dragover", handleDragOver);
      targetColumn.removeEventListener("dragleave", handleDragLeave);
      targetColumn.removeEventListener("drop", handleDrop);
      attachedColumns.delete(targetColumn);
    };
  }

  function setupAllDropListeners() {
    // Clean up existing listeners
    attachedColumns.forEach((column) => {
      if (column._dropCleanup) {
        column._dropCleanup();
      }
    });
    attachedColumns.clear();

    // Find all kanban columns
    const columns = document.querySelectorAll(".kanban-column");
    columns.forEach((column) => {
      const columnElement = column as ExtendedHTMLElement;
      const columnId = columnElement.dataset.columnId;
      const columnStatus = columnElement.dataset.columnStatus;

      if (columnId && columnStatus !== undefined) {
        const endpoint = `task/transition_to_${columnStatus}`;
        const newStatus = parseInt(columnStatus);
        addDropListener(columnElement, endpoint, newStatus);
      }
    });
  }

  function setupTaskCardDragListeners() {
    const taskCards = document.querySelectorAll(".task-card");
    taskCards.forEach((card) => {
      const cardElement = card as HTMLElement;

      // Remove existing listeners first to prevent conflicts
      cardElement.removeEventListener("dragstart", handleDragStart);
      cardElement.removeEventListener("dragend", handleDragEnd);
      cardElement.removeEventListener("touchstart", handleTouchStart);
      cardElement.removeEventListener("touchmove", handleTouchMove);
      cardElement.removeEventListener("touchend", handleTouchEnd);

      // Add drag events for desktop
      cardElement.addEventListener("dragstart", handleDragStart);
      cardElement.addEventListener("dragend", handleDragEnd);

      // Note: Touch events are handled by delegation in setupTouchDelegation
      // to avoid conflicts and improve performance
    });
  }

  function setupTouchListeners() {
    // Use delegation instead of individual listeners to avoid conflicts
    // Individual listeners are removed to prevent double handling
    const taskCards = document.querySelectorAll(".task-card");

    taskCards.forEach((card) => {
      const cardElement = card as HTMLElement;

      // Remove existing listeners to prevent conflicts
      cardElement.removeEventListener("touchstart", handleTouchStart);
      cardElement.removeEventListener("touchmove", handleTouchMove);
      cardElement.removeEventListener("touchend", handleTouchEnd);
    });
  }

  function setupTouchDelegation() {
    // Remove existing listeners
    document.removeEventListener("touchstart", handleTouchStartDelegation);
    document.removeEventListener("touchmove", handleTouchMoveDelegation);
    document.removeEventListener("touchend", handleTouchEndDelegation);

    // Add event delegation listeners
    document.addEventListener("touchstart", handleTouchStartDelegation, {
      passive: true,
    });
    document.addEventListener("touchmove", handleTouchMoveDelegation, {
      passive: false,
    });
    document.addEventListener("touchend", handleTouchEndDelegation, {
      passive: true,
    });
  }

  function handleTouchStartDelegation(event: Event) {
    const target = event.target as HTMLElement;

    // Find the closest task-card element
    const taskCard = target.closest(".task-card");
    if (!taskCard) {
      return;
    }

    const touch = (event as TouchEvent).touches[0];
    startX = touch.clientX;
    startY = touch.clientY;
    currentX = startX;
    currentY = startY;
    touchStartTime = Date.now();

    const taskId = parseInt((taskCard as HTMLElement).dataset.taskId || "0");
    const projectId = parseInt(
      (taskCard as HTMLElement).dataset.projectId || "0"
    );

    if (!taskId || !projectId) {
      return;
    }

    // Create ghost element for mobile
    createMobileGhost(startX, startY);

    // Store the task card for later use
    draggedElement = taskCard as HTMLElement;

    // Reset drag state
    isDragging = false;
    hasProcessedDrop = false;
  }

  function handleTouchMoveDelegation(event: Event) {
    if (!draggedElement) {
      return;
    }

    const touch = (event as TouchEvent).touches[0];
    currentX = touch.clientX;
    currentY = touch.clientY;

    const distance = Math.sqrt(
      Math.pow(currentX - startX, 2) + Math.pow(currentY - startY, 2)
    );

    if (distance > DRAG_CONFIG.threshold && !isDragging && draggedElement) {
      const touchDuration = Date.now() - touchStartTime;

      // Only start drag if touch duration is reasonable (not too short, not too long)
      if (touchDuration > 50 && touchDuration < 2000) {
        isDragging = true;
        hasProcessedDrop = false;
        // Create ghost element immediately when starting drag
        if (!ghostElement) {
          createMobileGhost(currentX, currentY);
        }
        // Only prevent default when we start dragging
        if (event.cancelable) {
          event.preventDefault();
        }
      }
    }

    if (isDragging) {
      // Ensure ghost element exists and is visible
      if (!ghostElement) {
        createMobileGhost(currentX, currentY);
      } else {
        updateMobileGhost(currentX, currentY);
      }
      updateHorizontalScroll(currentX);
      checkColumnHover(currentX, currentY);
    }
  }

  function handleTouchEndDelegation(event: Event) {
    if (!isDragging || !draggedElement) {
      cleanupDragVisuals();
      return;
    }

    const taskId = parseInt(draggedElement.dataset.taskId || "0");
    const projectId = parseInt(draggedElement.dataset.projectId || "0");

    if (taskId && projectId) {
      // Find which column the task was dropped on
      const columns = document.querySelectorAll(".kanban-column");
      let droppedOnColumn = null;

      for (const column of columns) {
        const columnElement = column as HTMLElement;
        const rect = columnElement.getBoundingClientRect();

        if (
          currentX >= rect.left &&
          currentX <= rect.right &&
          currentY >= rect.top &&
          currentY <= rect.bottom
        ) {
          droppedOnColumn = columnElement;
          break;
        }
      }

      if (droppedOnColumn && !hasProcessedDrop) {
        const columnId = droppedOnColumn.dataset.columnId;
        const columnStatus = droppedOnColumn.dataset.columnStatus;

        if (columnId && columnStatus !== undefined) {
          const endpoint = `task/transition_to_${columnStatus}`;
          const newStatus = parseInt(columnStatus);
          hasProcessedDrop = true;
          debouncedChangeTaskStatus(
            taskId,
            projectId,
            endpoint,
            newStatus.toString(),
            ProjectData
          );
        }
      }
    }

    cleanupDragVisuals();
  }

  function handleDragStart(event: Event) {
    const target = event.target as HTMLElement;
    if (!target.classList.contains("task-card")) return;

    const taskId = parseInt(target.dataset.taskId || "0");
    const projectId = parseInt(target.dataset.projectId || "0");

    if (!taskId || !projectId) return;

    isDragging = true;
    draggedElement = target;
    hasProcessedDrop = false;

    // Store original styles
    originalTransform = target.style.transform;
    originalZIndex = target.style.zIndex;
    originalOpacity = target.style.opacity;

    // Apply drag styles
    target.style.transform = "rotate(5deg) scale(1.05)";
    target.style.zIndex = "9999";
    target.style.opacity = "0.8";

    // Reset grab mode
    isGrabbing = false;
    isMouseDragging = false;

    // Set drag image
    const dragEvent = event as DragEvent;
    if (dragEvent.dataTransfer) {
      dragEvent.dataTransfer.effectAllowed = "move";
      dragEvent.dataTransfer.setDragImage(target, 0, 0);
    }
  }

  function handleDragEnd(event: Event) {
    const target = event.target as HTMLElement;
    if (!target.classList.contains("task-card")) return;

    isDragging = false;
    draggedElement = null;

    // Restore original styles
    target.style.transform = originalTransform;
    target.style.zIndex = originalZIndex;
    target.style.opacity = originalOpacity;

    // Reset all column highlights
    const columns = document.querySelectorAll(".kanban-column");
    columns.forEach((column) => {
      const columnElement = column as HTMLElement;
      columnElement.style.backgroundColor = "";
      columnElement.style.borderColor = "";
    });

    // Stop horizontal scroll
    stopHorizontalScroll();

    // Reset flags
    hasProcessedDrop = false;
  }

  function handleMouseMove(event: MouseEvent) {
    if (!isDragging || !draggedElement) return;

    currentX = event.clientX;
    currentY = event.clientY;

    // Update horizontal scroll
    updateHorizontalScroll(currentX);

    // Update ghost element position
    if (ghostElement) {
      updateMobileGhost(currentX, currentY);
    }
  }

  function handleTouchStart(event: Event) {
    const target = event.target as HTMLElement;

    // Find the closest task-card element
    const taskCard = target.closest(".task-card");
    if (!taskCard) {
      return;
    }

    const touch = (event as TouchEvent).touches[0];
    startX = touch.clientX;
    startY = touch.clientY;
    currentX = startX;
    currentY = startY;
    touchStartTime = Date.now();

    const taskId = parseInt((taskCard as HTMLElement).dataset.taskId || "0");
    const projectId = parseInt(
      (taskCard as HTMLElement).dataset.projectId || "0"
    );

    if (!taskId || !projectId) {
      return;
    }

    // Create ghost element for mobile
    createMobileGhost(startX, startY);
  }

  function handleTouchMove(event: Event) {
    if (!ghostElement) {
      return;
    }

    const touch = (event as TouchEvent).touches[0];
    currentX = touch.clientX;
    currentY = touch.clientY;

    const distance = Math.sqrt(
      Math.pow(currentX - startX, 2) + Math.pow(currentY - startY, 2)
    );

    if (distance > DRAG_CONFIG.threshold && !isDragging) {
      isDragging = true;
      const target = event.target as HTMLElement;
      if (target.classList.contains("task-card")) {
        draggedElement = target;
        hasProcessedDrop = false;
      }
      // Only prevent default when we start dragging
      if (event.cancelable) {
        event.preventDefault();
      }
    }

    if (isDragging) {
      updateMobileGhost(currentX, currentY);
      updateHorizontalScroll(currentX);
      checkColumnHover(currentX, currentY);
    }
  }

  function handleTouchEnd(event: Event) {
    if (!isDragging || !draggedElement) {
      cleanupDragVisuals();
      return;
    }

    const taskId = parseInt(draggedElement.dataset.taskId || "0");
    const projectId = parseInt(draggedElement.dataset.projectId || "0");

    if (taskId && projectId) {
      // Find which column the task was dropped on
      const columns = document.querySelectorAll(".kanban-column");
      let droppedOnColumn = null;

      for (const column of columns) {
        const columnElement = column as HTMLElement;
        const rect = columnElement.getBoundingClientRect();

        if (
          currentX >= rect.left &&
          currentX <= rect.right &&
          currentY >= rect.top &&
          currentY <= rect.bottom
        ) {
          droppedOnColumn = columnElement;
          break;
        }
      }

      if (droppedOnColumn && !hasProcessedDrop) {
        const columnId = droppedOnColumn.dataset.columnId;
        const columnStatus = droppedOnColumn.dataset.columnStatus;

        if (columnId && columnStatus !== undefined) {
          const endpoint = `task/transition_to_${columnStatus}`;
          const newStatus = parseInt(columnStatus);
          hasProcessedDrop = true;
          debouncedChangeTaskStatus(
            taskId,
            projectId,
            endpoint,
            newStatus.toString(),
            ProjectData
          );
        }
      }
    }

    cleanupDragVisuals();
  }

  function checkColumnHover(x: number, y: number) {
    const columns = document.querySelectorAll(".kanban-column");
    columns.forEach((column) => {
      const columnElement = column as HTMLElement;
      const rect = columnElement.getBoundingClientRect();

      if (
        x >= rect.left &&
        x <= rect.right &&
        y >= rect.top &&
        y <= rect.bottom
      ) {
        columnElement.style.backgroundColor = "rgba(59, 130, 246, 0.1)";
        columnElement.style.borderColor = "#3b82f6";
      } else {
        columnElement.style.backgroundColor = "";
        columnElement.style.borderColor = "";
      }
    });
  }

  function cleanupDragVisuals() {
    isDragging = false;
    draggedElement = null;
    hasProcessedDrop = false;

    // Reset all column highlights
    const columns = document.querySelectorAll(".kanban-column");
    columns.forEach((column) => {
      const columnElement = column as HTMLElement;
      columnElement.style.backgroundColor = "";
      columnElement.style.borderColor = "";
    });

    // Remove ghost element
    if (ghostElement) {
      ghostElement.remove();
      ghostElement = null;
    }

    // Stop horizontal scroll
    stopHorizontalScroll();
  }

  function getMemberName(member: any) {
    return member?.name || member?.user?.name || "Unknown";
  }

  function getMemberAvatar(member: any) {
    return getAvatarSrc(member?.avatar || member?.user?.avatar);
  }

  function createMobileGhost(x: number, y: number) {
    if (!draggedElement) {
      return;
    }

    // Remove existing ghost
    if (ghostElement) {
      ghostElement.remove();
    }

    // Create ghost element
    ghostElement = document.createElement("div");
    ghostElement.className = "mobile-ghost-task";
    ghostElement.style.cssText = `
      position: fixed;
      top: ${y - 40}px;
      left: ${x - 80}px;
      width: 160px;
      height: 80px;
      background: #ffffff;
      border: 2px solid #3b82f6;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(59, 130, 246, 0.25);
      z-index: 99999;
      pointer-events: none;
      display: flex;
      flex-direction: column;
      padding: 12px;
      transform: rotate(3deg) scale(0.95);
      transition: all 0.1s ease;
      opacity: 1;
      visibility: visible;
    `;

    // Get task info
    const taskId = parseInt(draggedElement.dataset.taskId || "0");
    const taskName =
      draggedElement.querySelector(".task-title")?.textContent || "Task";
    const taskMembers = draggedElement.querySelectorAll(".member-avatar");

    // Create ghost content
    const ghostContent = document.createElement("div");
    ghostContent.style.cssText = `
      display: flex;
      flex-direction: column;
      height: 100%;
      justify-content: space-between;
    `;

    // Task title
    const taskTitleElement = document.createElement("div");
    taskTitleElement.textContent = taskName;
    taskTitleElement.style.cssText = `
      font-size: 14px;
      font-weight: 600;
      color: #1f2937;
      line-height: 1.3;
      margin-bottom: 8px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    `;

    // Task footer with date and members
    const taskFooter = document.createElement("div");
    taskFooter.style.cssText = `
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 12px;
      color: #6b7280;
    `;

    // Date
    const dateElement = document.createElement("div");
    dateElement.style.cssText = `
        display: flex;
      align-items: center;
      gap: 4px;
    `;
    dateElement.innerHTML = `
      <i class="fas fa-calendar" style="font-size: 10px;"></i>
      <span>${new Date().toLocaleDateString()}</span>
    `;

    // Members
    const membersElement = document.createElement("div");
    membersElement.style.cssText = `
      display: flex;
      align-items: center;
        gap: 2px;
      `;

    if (taskMembers.length > 0) {
      const maxMembers = Math.min(taskMembers.length, 3);
      for (let i = 0; i < maxMembers; i++) {
        const memberDot = document.createElement("div");
        memberDot.style.cssText = `
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background: #3b82f6;
        `;
        membersElement.appendChild(memberDot);
      }

      if (taskMembers.length > 3) {
        const moreDot = document.createElement("div");
        moreDot.style.cssText = `
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background: #e5e7eb;
          font-size: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #6b7280;
        `;
        moreDot.textContent = "+";
        membersElement.appendChild(moreDot);
      }
    } else {
      const noMembers = document.createElement("div");
      noMembers.style.cssText = `
        display: flex;
        align-items: center;
        gap: 4px;
        color: #9ca3af;
      `;
      noMembers.innerHTML = `
        <i class="fas fa-user-plus" style="font-size: 10px;"></i>
        <span>Unassigned</span>
      `;
      membersElement.appendChild(noMembers);
    }

    taskFooter.appendChild(dateElement);
    taskFooter.appendChild(membersElement);

    ghostContent.appendChild(taskTitleElement);
    ghostContent.appendChild(taskFooter);

    ghostElement.appendChild(ghostContent);
    document.body.appendChild(ghostElement);

    // Force ghost element to be visible
    setTimeout(() => {
      if (ghostElement) {
        ghostElement.style.opacity = "1";
        ghostElement.style.visibility = "visible";
        ghostElement.style.display = "flex";
        ghostElement.style.zIndex = "99999";
      }
    }, 10);
  }

  function updateGhostTaskColor(x: number, y: number) {
    if (!ghostElement) return;

    const columns = document.querySelectorAll(".kanban-column");
    let isOverColumn = false;

    columns.forEach((column) => {
      const columnElement = column as HTMLElement;
      const rect = columnElement.getBoundingClientRect();

      if (
        x >= rect.left &&
        x <= rect.right &&
        y >= rect.top &&
        y <= rect.bottom
      ) {
        isOverColumn = true;

        // Get column color from data attribute or CSS custom property
        const columnColor =
          columnElement.dataset.columnColor ||
          getComputedStyle(columnElement).getPropertyValue("--column-color") ||
          columnElement.style.getPropertyValue("--column-color");

        // Use the actual column color if available
        if (columnColor && columnColor !== "") {
          ghostElement!.style.borderColor = columnColor;
          ghostElement!.style.boxShadow = `0 8px 32px ${columnColor}40`;
        } else {
          // Fallback to default colors based on column key
          const columnKey =
            columnElement.dataset.columnKey || columnElement.className;

          if (columnKey.includes("not-started") || columnKey.includes("0")) {
            ghostElement!.style.borderColor = "#3b82f6";
            ghostElement!.style.boxShadow =
              "0 8px 32px rgba(59, 130, 246, 0.25)";
          } else if (columnKey.includes("pending") || columnKey.includes("1")) {
            ghostElement!.style.borderColor = "#f59e0b";
            ghostElement!.style.boxShadow =
              "0 8px 32px rgba(245, 158, 11, 0.25)";
          } else if (
            columnKey.includes("completed") ||
            columnKey.includes("OK")
          ) {
            ghostElement!.style.borderColor = "#10b981";
            ghostElement!.style.boxShadow =
              "0 8px 32px rgba(16, 185, 129, 0.25)";
          } else {
            // Use a neutral color for custom columns
            ghostElement!.style.borderColor = "#6b7280";
            ghostElement!.style.boxShadow =
              "0 8px 32px rgba(107, 114, 128, 0.25)";
          }
        }
      }
    });

    if (!isOverColumn && ghostElement) {
      ghostElement.style.borderColor = "#3b82f6";
      ghostElement.style.boxShadow = "0 8px 32px rgba(59, 130, 246, 0.25)";
    }
  }

  function updateMobileGhost(x: number, y: number) {
    if (!ghostElement) {
      createMobileGhost(x, y);
      return;
    }
    ghostElement.style.top = `${y - 40}px`;
    ghostElement.style.left = `${x - 80}px`;
    ghostElement.style.opacity = "1";
    ghostElement.style.visibility = "visible";
    updateGhostTaskColor(x, y);
  }

  function setupMouseDragListeners() {
    const kanbanContainer = document.querySelector(".kanban-grid-container");
    if (!kanbanContainer) return;

    kanbanContainer.addEventListener("contextmenu", handleContextMenu);
    kanbanContainer.addEventListener(
      "mousedown",
      handleMouseDown as EventListener
    );
    kanbanContainer.addEventListener(
      "mousemove",
      handleMouseMoveForScroll as EventListener
    );
    kanbanContainer.addEventListener("mouseup", handleMouseUp as EventListener);
    kanbanContainer.addEventListener(
      "mouseleave",
      handleMouseUp as EventListener
    );
  }

  function handleContextMenu(event: Event) {
    event.preventDefault();
  }

  function handleMouseDown(event: MouseEvent) {
    const target = event.target as HTMLElement;

    // Skip if clicking on task card or interactive elements
    if (
      target.closest(".task-card") ||
      target.closest("button") ||
      target.closest("input") ||
      target.closest(".task-menu") ||
      target.closest(".title-edit-inline") ||
      target.closest(".color-edit-inline")
    ) {
      return;
    }

    isMouseDown = true;
    mouseDownX = event.clientX;
    mouseDownY = event.clientY;
    grabStartX = event.clientX;
    grabStartY = event.clientY;

    const kanbanContainer = document.querySelector(
      ".kanban-grid-container"
    ) as HTMLElement;
    if (kanbanContainer) {
      grabScrollLeft = kanbanContainer.scrollLeft;
    }
  }

  function handleMouseMoveForScroll(event: MouseEvent) {
    if (!isMouseDown) return;

    const deltaX = event.clientX - mouseDownX;
    const deltaY = event.clientY - mouseDownY;
    const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

    // Only start grabbing if mouse moved enough and not over task cards
    if (distance > 5 && !isGrabbing) {
      const target = event.target as HTMLElement;

      if (!target.closest(".task-card")) {
        isGrabbing = true;
        isMouseDragging = true;

        const kanbanContainer = document.querySelector(
          ".kanban-grid-container"
        ) as HTMLElement;
        if (kanbanContainer) {
          kanbanContainer.style.cursor = "grabbing";
          kanbanContainer.style.userSelect = "none";
        }
      }
    }

    if (isGrabbing) {
      const kanbanContainer = document.querySelector(
        ".kanban-grid-container"
      ) as HTMLElement;
      if (kanbanContainer) {
        const deltaX = grabStartX - event.clientX;
        const newScrollLeft = grabScrollLeft + deltaX;
        kanbanContainer.scrollLeft = newScrollLeft;
      }
    }
  }

  function handleMouseUp(event: MouseEvent) {
    if (isGrabbing) {
      const kanbanContainer = document.querySelector(
        ".kanban-grid-container"
      ) as HTMLElement;
      if (kanbanContainer) {
        kanbanContainer.style.cursor = "";
        kanbanContainer.style.userSelect = "";
      }
    }

    isMouseDown = false;
    isGrabbing = false;
    isMouseDragging = false;
  }

  function setupHorizontalScrollTouch() {
    const kanbanContainer = document.querySelector(".kanban-grid-container");
    if (!kanbanContainer) return;

    // Tối ưu: Sử dụng passive listeners để tăng performance
    kanbanContainer.addEventListener("touchstart", handleScrollTouchStart, {
      passive: true,
    });
    kanbanContainer.addEventListener("touchmove", handleScrollTouchMove, {
      passive: false, // Cần false để có thể preventDefault
    });
    kanbanContainer.addEventListener("touchend", handleScrollTouchEnd, {
      passive: true,
    });
  }

  function handleScrollTouchStart(event: Event) {
    // Only handle if not touching a task card
    const target = event.target as HTMLElement;
    if (target.closest(".task-card")) {
      return;
    }

    const touch = (event as TouchEvent).touches[0];
    startX = touch.clientX;
    startY = touch.clientY;
  }

  let lastScrollTime = 0;
  const SCROLL_THROTTLE = 16; // ~60fps

  function handleScrollTouchMove(event: Event) {
    // Only handle if not touching a task card and not dragging
    const target = event.target as HTMLElement;
    if (target.closest(".task-card") || isDragging) {
      return;
    }

    const now = Date.now();
    if (now - lastScrollTime < SCROLL_THROTTLE) {
      return; // Throttle để tối ưu performance
    }
    lastScrollTime = now;

    const touch = (event as TouchEvent).touches[0];
    const deltaX = touch.clientX - startX;
    const deltaY = touch.clientY - startY;

    // If horizontal movement is greater than vertical, allow scroll
    if (Math.abs(deltaX) > Math.abs(deltaY)) {
      // Only prevent default if the event is cancelable
      if (event.cancelable) {
        event.preventDefault();
      }

      const kanbanContainer = document.querySelector(
        ".kanban-grid-container"
      ) as HTMLElement;
      if (kanbanContainer) {
        // Đơn giản hóa: Scroll trực tiếp với tốc độ cao
        const scrollSpeed = 2.0; // Giảm từ 4.0 xuống 2.0 để vừa phải
        kanbanContainer.scrollLeft -= deltaX * scrollSpeed;

        startX = touch.clientX;
        startY = touch.clientY;
      }
    }
  }

  function handleScrollTouchEnd(event: Event) {
    // Reset touch state when touch ends
    startX = 0;
    startY = 0;
    lastScrollTime = 0;
  }

  return {
    setupAllDropListeners,
    setupTaskCardDragListeners,
    setupTouchListeners,
    setupTouchDelegation,
    setupHorizontalScrollTouch,
    setupMouseDragListeners,
  };
}
