import { useUserStore } from "../../../../state/userStore";
import { emitForceCacheClear } from "../../../../helper/eventBus";
import eventBus from "../../../../helper/eventBus";
import { getCurrentUserId } from "../../../../helper/getUserData";
import {
  changeTaskStatusApi,
  notifyTaskDragEnded,
} from "../../../../services/taskService";

// Constants
const GHOST_SIZE = 120; // Kích thước cố định cho phi hành gia (Fix lỗi hình quá to)
const DRAG_CONFIG = {
  threshold: 5,
  apiDebounceTime: 0, // Gọi API ngay lập tức khi drop để giảm độ trễ
  // Auto-scroll constants
  horizontalScrollThreshold: 150,
  horizontalScrollSpeed: 15,
  horizontalScrollInterval: 16, // ~60fps
} as const;

// --- SINGLETON GHOST MANAGEMENT (Quản lý phi hành gia duy nhất toàn ứng dụng) ---
let globalGhostElement: HTMLElement | null = null;

function getGhostElement(): HTMLElement | null {
  if (globalGhostElement) return globalGhostElement;
  
  const existing = document.getElementById("fixed-drag-ghost");
  if (existing) {
    globalGhostElement = existing;
    return globalGhostElement;
  }

  const ghost = document.createElement("div");
  ghost.id = "fixed-drag-ghost";
  ghost.className = "mobile-ghost-task";
  // Style mặc định: Luôn ẩn và có kích thước cố định 120px
  ghost.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: ${GHOST_SIZE}px !important;
    height: ${GHOST_SIZE}px !important;
    z-index: 999999;
    pointer-events: none;
    display: none !important;
    opacity: 0;
    background: transparent !important;
    will-change: transform;
    backface-visibility: hidden;
    transform-style: preserve-3d;
  `;

  const img = document.createElement("img");
  img.src = "/images/img_task.png"; 
  img.style.cssText = `
    width: 100%;
    height: 100%;
    object-fit: contain;
    image-rendering: -webkit-optimize-contrast;
  `;

  ghost.appendChild(img);
  document.body.appendChild(ghost);
  globalGhostElement = ghost;
  return globalGhostElement;
}

function showGhost(x: number, y: number, offsetX: number, offsetY: number) {
  const ghost = getGhostElement();
  if (!ghost) return;
  ghost.style.setProperty("display", "flex", "important");
  ghost.style.opacity = "1";
  ghost.style.visibility = "visible";
  ghost.style.transform = `translate3d(${x - offsetX}px, ${y - offsetY}px, 0)`;
}

function hideGhost() {
  const ghost = getGhostElement();
  if (!ghost) return;
  ghost.style.setProperty("display", "none", "important");
  ghost.style.opacity = "0";
  ghost.style.visibility = "hidden";
}
// --- KẾT THÚC QUẢN LÝ SINGLETON ---

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

    // Ép Vue cập nhật reactivity để task nhảy cột ngay lập tức
    if (projectData && projectData.value) {
      projectData.value = { ...projectData.value };
    }

    // Emit optimistic update for dashboard immediately
    try {
      const allTasks = projectData.value.data.tasks || [];
      const pending = allTasks.filter((t: any) => (t.status ?? 0) !== 3).length; // adjust if 3 is done else tune below
      const completed = allTasks.filter((t: any) => (t.status ?? 0) === 3).length;
      const total = pending + completed;
      const progress = total > 0 ? Math.round((completed / total) * 100) : 0;

      eventBus.emit("dashboard-optimistic-update", {
        projectId: projectData.value.data.id,
        tasks: [pending, completed],
        progress,
      });
    } catch (e) {
      // Silent error handling
    }
  }
}

// Debounced API call + optimistic UI cho chính user đang kéo
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

  // Cập nhật UI ngay khi drop (optimistic) để task "ở luôn" cột mới
  updateTaskOptimistically(taskId, newStatus, projectData);

  apiCallTimeout = setTimeout(async () => {
    try {
      await changeTaskStatus(taskId, projectId, endPoint);
    } catch (error) {
      // Nếu lỗi, tạm thời giữ nguyên UI; backend realtime sau đó sẽ đồng bộ lại
    }
  }, DRAG_CONFIG.apiDebounceTime);
}

// Hệ thống thông báo Realtime dùng chung (Unified Whisper System)
const whisperTimeouts = new Map<string, any>();
function whisperDrag(eventName: string, payload: any, throttleMs = 0) {
  if (!window.Echo || !payload?.project_id) return;
  
  const key = `${eventName}-${payload.project_id}`;
  const prev = whisperTimeouts.get(key);
  if (prev) clearTimeout(prev);
  
  // Đẩy vào hàng đợi async (setTimeout 0) để đảm bảo không block luồng UI
  // và giúp mobile gửi tin ổn định hơn trên thiết bị thật
  const action = () => {
    try {
      window.Echo.private(`project.${payload.project_id}`).whisper(eventName, payload);
    } catch (_) { /* ignore */ }
  };

  if (throttleMs === 0) {
    setTimeout(action, 0);
  } else {
    const t = setTimeout(action, throttleMs);
    whisperTimeouts.set(key, t);
  }
}

export async function changeTaskStatus(
  taskId: number,
  projectId: number,
  endPoint: string
) {
  try {
    await changeTaskStatusApi(endPoint, {
      taskId,
      projectId,
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

export function useDragTask(ProjectData?: any) {
  const userStore = useUserStore();

  // Khởi tạo ghost cache ngay khi hook được gọi
  getGhostElement();

  // Unified Notification Helpers
  const notifyDragStarted = (taskId: number, projectId: number, element: HTMLElement) => {
    const user = (userStore as any).user;
    const taskName = element.querySelector(".task-title")?.textContent?.trim() || "Task";
    if (taskId && projectId && user) {
      whisperDrag("drag-started", {
        task_id: taskId,
        project_id: projectId,
        task_name: taskName,
        user_id: user.id,
        user_name: user.name || "Someone",
        user_avatar: user.avatar || null,
      }, 0);
    }
  };

  const notifyDragOver = (taskId: number, projectId: number, columnId: string, columnStatus: string) => {
    const user = (userStore as any).user;
    if (taskId && projectId && user) {
      whisperDrag("drag-over-column", {
        task_id: taskId,
        project_id: projectId,
        column_id: columnId,
        column_status: columnStatus,
        user_id: user.id,
        user_name: user.name || "Someone",
        user_avatar: user.avatar || null,
      }, 50);
    }
  };

  const notifyDragEnded = (taskId: number, projectId: number) => {
    const user = (userStore as any).user;
    if (taskId && projectId && user) {
      whisperDrag("drag-ended", {
        task_id: taskId,
        project_id: projectId,
        user_id: user.id,
      }, 0);
    }
  };

  const notifyStatusOptimistic = (taskId: number, projectId: number, newStatus: number) => {
    const user = (userStore as any).user;
    if (taskId && projectId && user) {
      whisperDrag("task-status-optimistic", {
        task_id: taskId,
        project_id: projectId,
        status: newStatus,
        user_id: user.id,
      }, 0);
    }
  };

  // --- STATE VARIABLES (Quản lý tập trung để tối ưu bộ nhớ) ---
  let isDragging = false;
  let draggedElement: HTMLElement | null = null;
  let hasProcessedDrop = false;

  // Tọa độ và Offset dùng chung
  let startX = 0;
  let startY = 0;
  let currentX = 0;
  let currentY = 0;
  let touchOffsetX = 0;
  let touchOffsetY = 0;

  // Cấu hình ban đầu để hoàn tác
  let originalTransform = "";
  let originalZIndex = "";
  let originalOpacity = "";
  
  let ghostAnimationFrame: number | null = null;
  let touchStartTime = 0;

  // Auto-scroll state for horizontal scrolling
  let horizontalScrollInterval: number | null = null;
  let horizontalScrollSpeed = 0;

  // Cache container và columns để tăng tốc xử lý DOM
  let cachedKanbanContainer: HTMLElement | null = null;
  let cachedContainerRect: DOMRect | null = null;
  let cachedColumns: HTMLElement[] = [];
  let cachedColumnRects: { element: HTMLElement; rect: DOMRect }[] = [];

  // PC Grabbing state
  let isGrabbing = false;
  let grabStartX = 0;
  let grabScrollLeft = 0;
  let isMouseDown = false;
  let mouseDownX = 0;
  let mouseDownY = 0;

  // Set quản lý drop listeners
  const attachedColumns = new Set<ExtendedHTMLElement>();

  function updateCachedColumns() {
    cachedKanbanContainer = document.querySelector(".kanban-grid-container");
    if (cachedKanbanContainer) {
      cachedContainerRect = cachedKanbanContainer.getBoundingClientRect();
    }

    cachedColumns = Array.from(
      document.querySelectorAll(".kanban-column")
    ) as HTMLElement[];
    cachedColumnRects = cachedColumns.map((el) => ({
      element: el,
      rect: el.getBoundingClientRect(),
    }));
  }

  // Auto-scroll functions for horizontal scrolling
  function startHorizontalScroll() {
    if (horizontalScrollInterval) return;

    horizontalScrollInterval = setInterval(() => {
      if (horizontalScrollSpeed !== 0) {
        const kanbanContainer = cachedKanbanContainer || document.querySelector(".kanban-grid-container") as HTMLElement;
        if (kanbanContainer) {
          kanbanContainer.scrollLeft += horizontalScrollSpeed;
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
    if (!cachedKanbanContainer || !cachedContainerRect) {
      updateCachedColumns();
    }
    
    if (!cachedKanbanContainer || !cachedContainerRect) {
      horizontalScrollSpeed = 0;
      return;
    }

    const { left, right } = cachedContainerRect;
    const threshold = DRAG_CONFIG.horizontalScrollThreshold;

    // Tính toán tốc độ cuộn dựa trên khoảng cách tới biên
    if (x < left + threshold) {
      const distance = left + threshold - x;
      horizontalScrollSpeed = -Math.min(DRAG_CONFIG.horizontalScrollSpeed, distance * 0.5);
    } else if (x > right - threshold) {
      const distance = x - (right - threshold);
      horizontalScrollSpeed = Math.min(DRAG_CONFIG.horizontalScrollSpeed, distance * 0.5);
    } else {
      horizontalScrollSpeed = 0;
    }

    // Kích hoạt hoặc dừng interval cuộn
    if (horizontalScrollSpeed !== 0) {
      if (!horizontalScrollInterval) startHorizontalScroll();
    } else {
      stopHorizontalScroll();
    }
  }

  function addDropListener(targetColumn: ExtendedHTMLElement, endpoint: string) {
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

      // Cập nhật vị trí ghost rõ nét cho desktop khi đang drag
      if (isDragging) {
        showGhost(x, y, touchOffsetX, touchOffsetY);
      }

      // Check if mouse is within column bounds
      if (
        x >= rect.left &&
        x <= rect.right &&
        y >= rect.top &&
        y <= rect.bottom
      ) {
        const taskId = parseInt(draggedElement.dataset.taskId || "0");
        const projectId = parseInt(draggedElement.dataset.projectId || "0");
        const columnId = targetColumn.dataset.columnId || "";
        const columnStatus = targetColumn.dataset.columnStatus || "";

        if (taskId && projectId && columnId && columnStatus) {
          notifyDragOver(taskId, projectId, columnId, columnStatus);
        }
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
        // no-op: no highlight styling
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
        const columnStatus = targetColumn.dataset.columnStatus;

        if (taskId && projectId && columnStatus !== undefined) {
          hasProcessedDrop = true;
          debouncedChangeTaskStatus(
            taskId,
            projectId,
            endpoint,
            columnStatus.toString(),
            ProjectData
          );
        }
      }

      // Reset column styling
      // no highlight to reset
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
        addDropListener(columnElement, endpoint);
      }
    });
  }

  function setupTaskCardDragListeners() {
    const taskCards = document.querySelectorAll(".task-card");
    taskCards.forEach((card) => {
      const cardElement = card as HTMLElement;

      // Reset listeners để tránh trùng lặp
      cardElement.removeEventListener("dragstart", handleDragStart);
      cardElement.removeEventListener("dragend", handleDragEnd);

      // Add drag events cho Desktop
      cardElement.addEventListener("dragstart", handleDragStart);
      cardElement.addEventListener("dragend", handleDragEnd);
    });
  }

  function setupTouchListeners() {
    // Touch events hiện đã được xử lý tập trung qua Delegation
    // Hàm này được giữ lại để tương thích với các component cũ nếu cần
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

    // Store the task card for later use
    draggedElement = taskCard as HTMLElement;

    // LƯU LẠI trạng thái hiển thị gốc để khôi phục khi thả (tránh mất task)
    originalTransform = draggedElement.style.transform;
    originalZIndex = draggedElement.style.zIndex;
    originalOpacity = draggedElement.style.opacity;

    // Ép trung tâm của ghost nằm ngay ngón tay chạm
    touchOffsetX = GHOST_SIZE / 2;
    touchOffsetY = GHOST_SIZE / 2;

    // Reset drag state
    isDragging = false;
    hasProcessedDrop = false;

    // Cache columns when starting drag
    updateCachedColumns();

    // TỐI ƯU: Đảm bảo ghost luôn ẩn khi vừa chạm
    hideGhost();
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

        // Rung nhẹ trên điện thoại thật khi bắt đầu kéo (UX giống máy tính)
        if (window.navigator && window.navigator.vibrate) {
          window.navigator.vibrate(20);
        }

        // Ẩn card gốc để không bị mờ đè lên hình ảnh
        try {
          draggedElement?.classList.add("task-card-dragging");
          if (draggedElement) {
            draggedElement.style.opacity = "0"; // Ép ẩn card gốc
          }
          // Chặn các gesture mặc định của trình duyệt mobile
          document.body.style.overflow = "hidden";
          document.body.style.touchAction = "none";
        } catch (_) {
          // ignore
        }

        // Hiện ghost element khi thực sự bắt đầu kéo (Sử dụng !important để đảm bảo hiện)
        showGhost(currentX, currentY, touchOffsetX, touchOffsetY);

        // Gửi thông báo bằng helper dùng chung
        const taskId = Number(draggedElement.dataset.taskId || "0");
        const projectId = Number(draggedElement.dataset.projectId || "0");
        notifyDragStarted(taskId, projectId, draggedElement);

        // Only prevent default when we start dragging
        if (event.cancelable) {
          event.preventDefault();
        }
      }
    }

    if (isDragging) {
      // Chặn cuộn trang tuyệt đối khi đang kéo
      if (event.cancelable) {
        event.preventDefault();
      }

      // Use requestAnimationFrame for smooth ghost movement
      if (ghostAnimationFrame) {
        cancelAnimationFrame(ghostAnimationFrame);
      }

      ghostAnimationFrame = requestAnimationFrame(() => {
        // Cập nhật vị trí và hiện ghost nếu đang kéo
        showGhost(currentX, currentY, touchOffsetX, touchOffsetY);
        updateHorizontalScroll(currentX);
        checkColumnHover(currentX, currentY);
      });
    }
  }

  function handleTouchEndDelegation() {
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
          // Gửi thông báo bằng helper dùng chung
          notifyStatusOptimistic(taskId, projectId, newStatus);
          notifyDragEnded(taskId, projectId);
          
          // Gọi API ngay lập tức (không debounce) để đảm bảo event được broadcast qua Laravel realtime
          notifyTaskDragEnded(taskId, projectId).catch(() => {
            // Silent error handling
          });
        }
      }
      // Nếu chưa drop vào cột, không broadcast drag-ended
      // Để overlay vẫn hiện cho đến khi backend xử lý xong hoặc timeout
    }

    cleanupDragVisuals();
  }

  function handleGlobalDragOver(e: DragEvent) {
    if (!isDragging) return;
    e.preventDefault(); 
    
    // Cập nhật vị trí ghost cho Desktop toàn cục (không bị giới hạn bởi cột)
    showGhost(e.clientX, e.clientY, touchOffsetX, touchOffsetY);
    
    // Kiểm tra cuộn trang tự động
    updateHorizontalScroll(e.clientX);
  }

  function handleDragStart(event: Event) {
    const target = event.target as HTMLElement;
    if (!target.classList.contains("task-card")) return;

    const taskId = Number(target.dataset.taskId || "0");
    const projectId = Number(target.dataset.projectId || "0");

    if (!taskId || !projectId) return;

    isDragging = true;
    draggedElement = target;
    hasProcessedDrop = false;

    // Lắng nghe sự kiện di chuyển chuột toàn cầu để ghost bay khắp màn hình
    document.addEventListener("dragover", handleGlobalDragOver);

    // Cache columns when starting drag
    updateCachedColumns();

    // Store original styles
    originalTransform = target.style.transform;
    originalZIndex = target.style.zIndex;
    originalOpacity = target.style.opacity;

    // Apply drag styles
    target.style.transform = "rotate(5deg) scale(1.05)";
    target.style.zIndex = "9999";
    // Để card rõ nét, không bị mờ khi đang kéo
    target.style.opacity = "1";

    // Thêm class để CSS làm nổi bật card đang được kéo
    try {
      target.classList.add("task-card-dragging");
      document.body.style.overflow = "hidden";
      document.body.style.touchAction = "none";
    } catch (_) {
      // ignore
    }

    // Reset grab mode
    isGrabbing = false;

    // Set drag image: dùng hình trong suốt để ẩn preview mặc định (vốn hay bị mờ trên laptop)
    const dragEvent = event as DragEvent;
    if (dragEvent.dataTransfer) {
      dragEvent.dataTransfer.effectAllowed = "move";
      const rect = target.getBoundingClientRect();
      const offsetX = rect.width / 2;
      const offsetY = rect.height / 2;

      try {
        const img = new Image();
        img.src =
          "data:image/gif;base64,R0lGODlhAQABAAAAACw="; // 1x1 transparent
        dragEvent.dataTransfer.setDragImage(img, offsetX, offsetY);
      } catch {
        // fallback: vẫn dùng target nếu có lỗi
        dragEvent.dataTransfer.setDragImage(target, offsetX, offsetY);
      }

      // Gán offset là trung tâm của ghost 120px để nằm ngay tâm chuột
      touchOffsetX = GHOST_SIZE / 2;
      touchOffsetY = GHOST_SIZE / 2;

      // Tạo ghost rõ nét cho desktop (dùng chung với mobile ghost)
      const targetRect = target.getBoundingClientRect();
      showGhost(targetRect.left + targetRect.width / 2, targetRect.top + targetRect.height / 2, touchOffsetX, touchOffsetY);
      // Ẩn card gốc trong lúc kéo để chỉ còn ghost
      target.style.opacity = "0";
    }

    // Gửi thông báo bằng helper dùng chung
    notifyDragStarted(taskId, projectId, target);
  }

  function handleDragEnd(event: Event) {
    const target = event.target as HTMLElement;
    if (!target.classList.contains("task-card")) return;

    // Gỡ bỏ sự kiện di chuyển toàn cục
    document.removeEventListener("dragover", handleGlobalDragOver);

    const taskId = parseInt(target.dataset.taskId || "0");
    const projectId = parseInt(target.dataset.projectId || "0");

    isDragging = false;
    draggedElement = null;

    // Restore original styles
    target.style.transform = originalTransform;
    target.style.zIndex = originalZIndex;
    target.style.opacity = originalOpacity;

    // Bỏ class highlight khi thả xong
    try {
      target.classList.remove("task-card-dragging");
      document.body.style.overflow = "";
      document.body.style.touchAction = "";
    } catch (_) {
      // ignore
    }

    // Reset all column highlights
    const columns = document.querySelectorAll(".kanban-column");
    columns.forEach((column) => {
      const columnElement = column as HTMLElement;
      columnElement.style.backgroundColor = "";
      columnElement.style.borderColor = "";
    });

    // Stop horizontal scroll
    stopHorizontalScroll();

    // Dọn toàn bộ state/ghost còn lại (desktop + mobile)
    cleanupDragVisuals();

    // Chỉ broadcast drag ended khi đã drop vào cột (hasProcessedDrop === true)
    if (taskId && projectId && hasProcessedDrop) {
      notifyDragEnded(taskId, projectId);
      
      // Gọi API để đảm bảo event được broadcast qua Laravel realtime
      notifyTaskDragEnded(taskId, projectId).catch(() => {
        // Silent error handling
      });
    }

    // Reset flags
    hasProcessedDrop = false;
  }

  function checkColumnHover(x: number, y: number) {
    if (cachedColumnRects.length === 0) {
      updateCachedColumns();
    }

    cachedColumnRects.forEach(({ element: columnElement, rect }) => {
      if (
        x >= rect.left &&
        x <= rect.right &&
        y >= rect.top &&
        y <= rect.bottom
      ) {
        // Chỉ dùng whisper (realtime) để báo nhanh cho user khác, không gọi HTTP API
        if (isDragging && draggedElement) {
          const taskId = parseInt(draggedElement.dataset.taskId || "0");
          const projectId = parseInt(draggedElement.dataset.projectId || "0");
          const columnId = columnElement.dataset.columnId || "";
          const columnStatus = columnElement.dataset.columnStatus || "";

          if (taskId && projectId && columnId && columnStatus) {
            notifyDragOver(taskId, projectId, columnId, columnStatus);
          }
        }
      }
    });
  }

  function cleanupDragVisuals() {
    // Trả lại trạng thái touch mặc định cho element
    if (draggedElement) {
      try {
        draggedElement.style.touchAction = "";
        draggedElement.style.userSelect = "";
        draggedElement.style.webkitUserSelect = "";
        draggedElement.oncontextmenu = null;
      } catch (_) { /* ignore */ }
    }

    // Whisper drag-ended để người khác xóa overlay bằng helper dùng chung
    if (draggedElement) {
      const taskId = parseInt(draggedElement.dataset.taskId || "0");
      const projectId = parseInt(draggedElement.dataset.projectId || "0");
      if (taskId && projectId) {
        notifyDragEnded(taskId, projectId);
      }
    }

    // Trả lại trạng thái scroll cho body
    try {
      document.body.style.overflow = "";
      document.body.style.touchAction = "";
    } catch (_) {
      // ignore
    }

    // Bỏ highlight nếu còn và trả lại trạng thái hiển thị ban đầu
    if (draggedElement) {
      try {
        draggedElement.classList.remove("task-card-dragging");
        // HOÀN TÁC: Trả lại độ hiển thị để task không bị mất sau khi kéo
        draggedElement.style.transform = originalTransform;
        draggedElement.style.zIndex = originalZIndex;
        draggedElement.style.opacity = originalOpacity;
      } catch (_) {
        // ignore
      }
    }

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

    // ẨN TUYỆT ĐỐI GHOST TOÀN CỤC
    hideGhost();

    // Cancel animation frame
    if (ghostAnimationFrame) {
      cancelAnimationFrame(ghostAnimationFrame);
      ghostAnimationFrame = null;
    }

    // Stop horizontal scroll
    stopHorizontalScroll();

    // Reset touch state
    startX = 0;
    startY = 0;
    currentX = 0;
    currentY = 0;
    touchStartTime = 0;
    touchOffsetX = 0;
    touchOffsetY = 0;
    cachedColumnRects = [];
    cachedKanbanContainer = null;
    cachedContainerRect = null;
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

    const kanbanContainer = cachedKanbanContainer || document.querySelector(".kanban-grid-container") as HTMLElement;
    if (kanbanContainer) {
      grabScrollLeft = kanbanContainer.scrollLeft;
    }
  }

  function handleMouseMoveForScroll(event: MouseEvent) {
    if (!isMouseDown) return;

    const deltaX = event.clientX - mouseDownX;
    const deltaY = event.clientY - mouseDownY;
    const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

    if (distance > 5 && !isGrabbing) {
      const target = event.target as HTMLElement;

      if (!target.closest(".task-card")) {
        isGrabbing = true;

        const kanbanContainer = cachedKanbanContainer || document.querySelector(".kanban-grid-container") as HTMLElement;
        if (kanbanContainer) {
          kanbanContainer.style.cursor = "grabbing";
          kanbanContainer.style.userSelect = "none";
        }
      }
    }

    if (isGrabbing) {
      const kanbanContainer = cachedKanbanContainer || document.querySelector(".kanban-grid-container") as HTMLElement;
      if (kanbanContainer) {
        const deltaX = grabStartX - event.clientX;
        kanbanContainer.scrollLeft = grabScrollLeft + deltaX;
      }
    }
  }

  function handleMouseUp() {
    if (isGrabbing) {
      const kanbanContainer = cachedKanbanContainer || document.querySelector(".kanban-grid-container") as HTMLElement;
      if (kanbanContainer) {
        kanbanContainer.style.cursor = "";
        kanbanContainer.style.userSelect = "";
      }
    }

    isMouseDown = false;
    isGrabbing = false;
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
      if (event.cancelable) {
        event.preventDefault();
      }

      const kanbanContainer = cachedKanbanContainer || document.querySelector(".kanban-grid-container") as HTMLElement;
      if (kanbanContainer) {
        const scrollSpeed = 2.0; 
        kanbanContainer.scrollLeft -= deltaX * scrollSpeed;

        startX = touch.clientX;
        startY = touch.clientY;
      }
    }
  }

  function handleScrollTouchEnd() {
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
