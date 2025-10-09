import { PiniaPluginContext } from 'pinia';
import { watch } from 'vue';

export interface PersistOptions {
  key?: string;
  paths?: string[]; // Specific state paths to persist
  beforeRestore?: (context: PiniaPluginContext) => void;
  afterRestore?: (context: PiniaPluginContext) => void;
  serializer?: {
    serialize: (value: any) => string;
    deserialize: (value: string) => any;
  };
  storage?: Storage;
}

/**
 * Pinia plugin to persist store state to localStorage
 * Usage in store:
 * 
 * export const useUserStore = defineStore('user', {
 *   state: () => ({ ... }),
 *   persist: {
 *     key: 'user-store',
 *     paths: ['user', 'avatar'], // Only persist these
 *   }
 * })
 */
export function createPersistedState() {
  return (context: PiniaPluginContext) => {
    const { store, options } = context;
    
    // @ts-ignore - persist is custom option
    const persistOptions: PersistOptions = options.persist || null;
    
    if (!persistOptions) return;

    const {
      key = store.$id,
      paths = null,
      beforeRestore,
      afterRestore,
      serializer = {
        serialize: JSON.stringify,
        deserialize: JSON.parse,
      },
      storage = localStorage,
    } = persistOptions;

    /**
     * Restore state from storage
     */
    const restoreState = () => {
      try {
        beforeRestore?.(context);
        
        const savedState = storage.getItem(key);
        if (!savedState) return;

        const parsed = serializer.deserialize(savedState);
        
        // Check if it's our cached format
        const data = parsed.data ? parsed.data : parsed;
        
        // Check TTL if present
        if (parsed.timestamp) {
          const age = Date.now() - parsed.timestamp;
          const ttl = 30 * 60 * 1000; // 30 minutes
          if (age > ttl) {
            storage.removeItem(key);
            return;
          }
        }

        // Restore specific paths or entire state
        if (paths && Array.isArray(paths)) {
          paths.forEach((path) => {
            if (data[path] !== undefined) {
              store.$state[path] = data[path];
            }
          });
        } else {
          store.$state = data;
        }

        afterRestore?.(context);
      } catch (error) {
        console.error(`Error restoring state for store "${key}":`, error);
      }
    };

    /**
     * Save state to storage
     */
    const saveState = () => {
      try {
        let stateToPersist: any;

        // Persist specific paths or entire state
        if (paths && Array.isArray(paths)) {
          stateToPersist = {};
          paths.forEach((path) => {
            stateToPersist[path] = store.$state[path];
          });
        } else {
          stateToPersist = store.$state;
        }

        // Wrap with timestamp and version
        const cached = {
          data: stateToPersist,
          timestamp: Date.now(),
          version: '1.0',
        };

        const serialized = serializer.serialize(cached);
        storage.setItem(key, serialized);
      } catch (error) {
        if (error instanceof DOMException && error.name === 'QuotaExceededError') {
          console.error(`localStorage quota exceeded for store "${key}"`);
        } else {
          console.error(`Error saving state for store "${key}":`, error);
        }
      }
    };

    // Restore state on initialization
    restoreState();

    // Watch for state changes and persist
    watch(
      () => store.$state,
      () => {
        saveState();
      },
      { deep: true }
    );
  };
}

/**
 * Clear all persisted stores
 */
export function clearPersistedStores(): void {
  const storeKeys = ['user-store', 'dashboard-store', 'project-store', 'member-store'];
  storeKeys.forEach(key => {
    localStorage.removeItem(key);
  });
}