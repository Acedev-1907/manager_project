// import {useToast} from 'vue-toast-notification';
// import 'vue-toast-notification/dist/theme-sugar.css';

// const $toast = useToast();
// let instance = $toast.success('You did it!');
import { useToast } from "vue-toast-notification";

const toast = useToast();

// Default configuration
const defaultConfig = {
  position: "bottom-right" as const,
  duration: 4000,
  dismissible: true,
};

// Toast types
export function showError(message: string, config = {}) {
  toast.error(message, {
    ...defaultConfig,
    ...config,
  });
}

export function showSuccess(message: string, config = {}) {
  toast.success(message, {
    ...defaultConfig,
    ...config,
  });
}

export function showWarning(message: string, config = {}) {
  toast.warning(message, {
    ...defaultConfig,
    ...config,
  });
}

export function showInfo(message: string, config = {}) {
  toast.info(message, {
    ...defaultConfig,
    ...config,
  });
}

// Legacy function for backward compatibility
export function successMsg(message: string) {
  showSuccess(message);
}
