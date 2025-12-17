import eventBus from "./eventBus";

type TaskChannelMap = Record<number, ReturnType<typeof window.Echo.private> | null>;

const taskChannels: TaskChannelMap = {};

export function subscribeTaskComments(taskId: number) {
  if (!taskId) return;
  if (typeof window === "undefined" || !window.Echo) return;
  if (taskChannels[taskId]) return;

  try {
    const channel = window.Echo.private(`task.${taskId}`);

    channel
      .error((error: any) => {
        console.error("[TaskComment] Channel error", { taskId, error });
      })
      .listen(".TaskCommentCreated", (e: any) => {
        const comment = e?.comment || e;
        if (!comment) return;
        eventBus.emit("task-comment-created", {
          taskId,
          comment,
          timestamp: Date.now(),
        });
      });

    taskChannels[taskId] = channel;
  } catch (error) {
    // Silent error handling để không crash UI
  }
}

export function unsubscribeTaskComments(taskId: number) {
  const channel = taskChannels[taskId];
  if (!channel) return;

  try {
    channel.stopListening(".TaskCommentCreated");
    if (window.Echo) {
      window.Echo.leave(`task.${taskId}`);
    }
  } catch (error) {
    // Silent error handling
  }

  taskChannels[taskId] = null;
  delete taskChannels[taskId];
}

export function cleanupAllTaskCommentChannels() {
  Object.keys(taskChannels).forEach((key) => {
    const taskId = Number(key);
    unsubscribeTaskComments(taskId);
  });
}


