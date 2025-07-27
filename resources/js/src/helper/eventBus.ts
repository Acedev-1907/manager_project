import mitt from "mitt";
import { getCurrentUserId } from "./getUserData";

const eventBus = mitt();

// Event replay mechanism - store recent events for replay
const recentEvents: Array<{ event: string; data: any; timestamp: number }> = [];
const MAX_RECENT_EVENTS = 10;
const EVENT_REPLAY_WINDOW = 10000; // 10 seconds

// Cross-tab communication
const CROSS_TAB_EVENT_KEY = "taskmgr_cross_tab_events";
const TAB_ID = Math.random().toString(36).substr(2, 9);

// Store recent events for replay
function storeRecentEvent(event: string, data: any) {
  const eventRecord = {
    event,
    data,
    timestamp: Date.now(),
  };

  recentEvents.push(eventRecord);

  // Keep only recent events
  if (recentEvents.length > MAX_RECENT_EVENTS) {
    recentEvents.shift();
  }

  // Clean up old events
  const now = Date.now();
  const validEvents = recentEvents.filter(
    (e) => now - e.timestamp < EVENT_REPLAY_WINDOW
  );
  recentEvents.length = 0;
  recentEvents.push(...validEvents);
}

// Cross-tab event emission
function emitCrossTabEvent(event: string, data: any) {
  const crossTabEvent = {
    event,
    data,
    timestamp: Date.now(),
    tabId: TAB_ID,
    userId: getCurrentUserId(),
  };

  // Store in localStorage to trigger storage event in other tabs
  localStorage.setItem(CROSS_TAB_EVENT_KEY, JSON.stringify(crossTabEvent));

  // Remove after a short delay to allow other tabs to pick it up
  setTimeout(() => {
    localStorage.removeItem(CROSS_TAB_EVENT_KEY);
  }, 100);
}

// Listen for cross-tab events
function setupCrossTabListener() {
  window.addEventListener("storage", (e) => {
    if (e.key === CROSS_TAB_EVENT_KEY && e.newValue) {
      try {
        const crossTabEvent = JSON.parse(e.newValue);

        // Ignore events from current tab
        if (crossTabEvent.tabId === TAB_ID) return;

        // Emit event locally
        eventBus.emit(crossTabEvent.event, crossTabEvent.data);

        // Store for replay
        storeRecentEvent(crossTabEvent.event, crossTabEvent.data);
      } catch (error) {
        // Silent error handling
      }
    }
  });
}

// Initialize cross-tab listener
setupCrossTabListener();

// Get recent events for replay
export function getRecentEvents(eventType?: string, projectId?: number) {
  const now = Date.now();
  const validEvents = recentEvents.filter((e) => {
    const isRecent = now - e.timestamp < EVENT_REPLAY_WINDOW;
    const matchesType = !eventType || e.event === eventType;
    const matchesProject = !projectId || e.data?.projectId === projectId;
    return isRecent && matchesType && matchesProject;
  });

  return validEvents;
}

// Enhanced emit function that stores events and broadcasts to other tabs
export function emitForceCacheClear(
  projectId: number,
  reason: string,
  userId?: string | number | null
) {
  const currentUserId = userId || getCurrentUserId();
  const eventData = {
    projectId,
    reason,
    timestamp: Date.now(),
    userId: currentUserId,
  };

  // Store event for replay
  storeRecentEvent("force-cache-clear", eventData);

  // Emit locally
  eventBus.emit("force-cache-clear", eventData);

  // Broadcast to other tabs
  emitCrossTabEvent("force-cache-clear", eventData);
}

// Enhanced emit for task comments
export function emitTaskCommentCreated(taskId: number, comment: any) {
  const eventData = {
    taskId,
    comment,
    timestamp: Date.now(),
  };

  // Store event for replay
  storeRecentEvent("task-comment-created", eventData);

  // Emit locally
  eventBus.emit("task-comment-created", eventData);

  // Broadcast to other tabs
  emitCrossTabEvent("task-comment-created", eventData);
}

// Replay recent events for a specific page
export function replayRecentEvents(eventType: string, projectId?: number) {
  const recentEvents = getRecentEvents(eventType, projectId);

  if (recentEvents.length > 0) {
    recentEvents.forEach((eventRecord) => {
      eventBus.emit(eventType, eventRecord.data);
    });
  }
}

// Debug function to check cross-tab communication

export default eventBus;
