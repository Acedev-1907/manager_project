import mitt from "mitt";

type Events = {
  taskCreated: void;
};

export const eventBus = mitt();
