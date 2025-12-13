import { PiniaPluginContext } from 'pinia';
import { watch } from 'vue';

export type PersistStorageType = 'localStorage' | 'sessionStorage' | 'memory';

/**
 * Memory storage implementation for Pinia
 */
class MemoryStorage implements Storage {
  private data: Map<string, string> = new Map();

  getItem(key: string): string | null {
    return this.data.get(key) || null;
  }

  setItem(key: string, value: string): void {
    this.data.set(key, value);
  }

  removeItem(key: string): void {
    this.data.delete(key);
  }

  clear(): void {
    this.data.clear();
  }

  key(index: number): string | null {
    const keys = Array.from(this.data.keys());
    return keys[index] || null;
  }

  get length(): number {
    return this.data.size;
  }
}

/**
 * Get storage adapter based on type
 */
function getStorageAdapter(type: PersistStorageType): Storage {
  switch (type) {
    case 'localStorage':
      return typeof window !== 'undefined' ? window.localStorage : new MemoryStorage();
    case 'sessionStorage':
      return typeof window !== 'undefined' ? window.sessionStorage : new MemoryStorage();
    case 'memory':
      return new MemoryStorage();
    default:
      return new MemoryStorage();
  }
}

export interface PersistOptions {
  key?: string;
  paths?: string[]; // Specific state paths to persist
  storageType?: PersistStorageType; // Which storage to use (default: localStorage)
  ttl?: number; // Time to live in ms (0 = no expiration, default: 0)
  beforeRestore?: (context: PiniaPluginContext) => void;
  afterRestore?: (context: PiniaPluginContext) => void;
  serializer?: {
    serialize: (value: any) => string;
    deserialize: (value: string) => any;
  };
  storage?: Storage; // Legacy: direct storage object (deprecated, use storageType instead)
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
    
    const persistOptions: PersistOptions | boolean = (options as any).persist || null;
    
    if (!persistOptions) return;

    // Handle boolean (simple enable/disable)
    const opts: PersistOptions = persistOptions === true 
      ? { storageType: 'localStorage' }
      : persistOptions;

    const {
      key = store.$id,
      paths = null,
      storageType = 'localStorage',
      ttl = 0, // No expiration by default
      beforeRestore,
      afterRestore,
      serializer = {
        serialize: JSON.stringify,
        deserialize: JSON.parse,
      },
      storage, // Legacy support
    } = opts;

    // Use storageType if provided, otherwise fall back to legacy storage param
    const storageAdapter = storage || getStorageAdapter(storageType);

    /**
     * Restore state from storage
     */
    const restoreState = () => {
      try {
        beforeRestore?.(context);
        
        const savedState = storageAdapter.getItem(key);
        if (!savedState) return;

        const parsed = serializer.deserialize(savedState);
        
        // Check if it's our cached format
        const data = parsed.data ? parsed.data : parsed;
        
        // Check TTL if present and configured
        if (ttl > 0 && parsed.timestamp) {
          const age = Date.now() - parsed.timestamp;
          if (age > ttl) {
            storageAdapter.removeItem(key);
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

        // Wrap with timestamp and version (only if TTL is set)
        const cached = ttl > 0 ? {
          data: stateToPersist,
          timestamp: Date.now(),
          version: '1.0',
        } : stateToPersist;

        const serialized = serializer.serialize(cached);
        storageAdapter.setItem(key, serialized);
      } catch (error) {
        if (error instanceof DOMException && error.name === 'QuotaExceededError') {
          console.error(`${storageType} quota exceeded for store "${key}"`);
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
 * Clear all persisted stores from specified storage type
 */
export function clearPersistedStores(storageType: PersistStorageType = 'localStorage'): void {
  const storage = getStorageAdapter(storageType);
  const storeKeys = ['user-store', 'dashboard-store', 'project-store', 'member-store'];
  storeKeys.forEach(key => {
    storage.removeItem(key);
  });
}

/**
 * Clear all persisted stores from all storage types
 */
export function clearAllPersistedStores(): void {
  ['localStorage', 'sessionStorage', 'memory'].forEach(type => {
    clearPersistedStores(type as PersistStorageType);
  });
}