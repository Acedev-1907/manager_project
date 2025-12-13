import { ref, watch, type Ref } from 'vue';

/**
 * Storage types available
 */
export type StorageType = 'localStorage' | 'sessionStorage' | 'memory';

/**
 * Storage interface abstraction
 */
interface StorageAdapter {
  getItem(key: string): string | null;
  setItem(key: string, value: string): void;
  removeItem(key: string): void;
  clear(): void;
  key(index: number): string | null;
  get length(): number;
}

/**
 * Memory storage implementation (in-memory only, cleared on page reload)
 */
class MemoryStorage implements StorageAdapter {
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
function getStorageAdapter(type: StorageType): StorageAdapter {
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

/**
 * Cached data structure with metadata
 */
export interface CachedData<T> {
  data: T;
  timestamp: number;
  version?: string;
  ttl?: number; // Time to live in ms
}

/**
 * Storage options
 */
export interface StorageOptions {
  storageType?: StorageType; // Which storage to use
  ttl?: number; // Time to live in ms (0 = no expiration)
  autoSave?: boolean; // Auto save on change
  version?: string; // Cache version for migration
  maxSize?: number; // Max cache size in bytes
  compress?: boolean; // Compress data (future feature)
}

/**
 * Composable for type-safe storage operations with multiple storage backends
 * 
 * @param key - Storage key
 * @param defaultValue - Default value if not found or expired
 * @param options - Storage options
 * 
 * @example
 * // Use localStorage (persistent across sessions)
 * const userPrefs = useStorage('user-preferences', { theme: 'light' }, {
 *   storageType: 'localStorage',
 *   ttl: 0 // Never expire
 * });
 * 
 * @example
 * // Use sessionStorage (cleared on tab close)
 * const tempData = useStorage('temp-form-data', {}, {
 *   storageType: 'sessionStorage',
 *   ttl: 60 * 60 * 1000 // 1 hour
 * });
 * 
 * @example
 * // Use memory (cleared on page reload)
 * const runtimeCache = useStorage('api-cache', null, {
 *   storageType: 'memory',
 *   ttl: 5 * 60 * 1000 // 5 minutes
 * });
 */
export function useStorage<T>(
  key: string,
  defaultValue: T,
  options: StorageOptions = {}
): {
  value: Ref<T>;
  save: () => void;
  load: () => T;
  remove: () => void;
  isExpired: () => boolean;
  getSize: () => number;
  storageType: StorageType;
} {
  const {
    storageType = 'localStorage',
    ttl = 30 * 60 * 1000, // 30 minutes default
    autoSave = true,
    version = '1.0',
    maxSize = 5 * 1024 * 1024 // 5MB default
  } = options;

  const storage = getStorageAdapter(storageType);
  const value = ref<T>(defaultValue) as Ref<T>;

  /**
   * Get size of cached data in bytes
   */
  const getSize = (): number => {
    try {
      const rawData = storage.getItem(key);
      if (!rawData) return 0;
      return new Blob([rawData]).size;
    } catch {
      return 0;
    }
  };

  /**
   * Check if cached data is expired
   */
  const isExpired = (): boolean => {
    if (ttl === 0) return false; // Never expire
    
    try {
      const rawData = storage.getItem(key);
      if (!rawData) return true;

      const cached: CachedData<T> = JSON.parse(rawData);
      
      // Check version mismatch
      if (cached.version && cached.version !== version) return true;
      
      // Check TTL
      const age = Date.now() - cached.timestamp;
      const dataTTL = cached.ttl || ttl;
      return age > dataTTL;
    } catch {
      return true;
    }
  };

  /**
   * Load data from storage
   */
  const load = (): T => {
    try {
      const rawData = storage.getItem(key);
      if (!rawData) return defaultValue;

      const cached: CachedData<T> = JSON.parse(rawData);
      
      // Check if expired
      if (isExpired()) {
        remove();
        return defaultValue;
      }

      value.value = cached.data;
      return cached.data;
    } catch (error) {
      console.error(`Error loading from ${storageType} (${key}):`, error);
      return defaultValue;
    }
  };

  /**
   * Save data to storage
   */
  const save = (): void => {
    try {
      const cached: CachedData<T> = {
        data: value.value,
        timestamp: Date.now(),
        version,
        ttl: ttl > 0 ? ttl : undefined,
      };
      
      const serialized = JSON.stringify(cached);
      
      // Check size before saving
      if (maxSize && new Blob([serialized]).size > maxSize) {
        console.warn(`Cache size exceeds maxSize (${maxSize} bytes). Skipping save for key: ${key}`);
        return;
      }
      
      storage.setItem(key, serialized);
    } catch (error) {
      // Check if QuotaExceededError (only for localStorage/sessionStorage)
      if (error instanceof DOMException && error.name === 'QuotaExceededError') {
        console.error(`${storageType} quota exceeded for key: ${key}. Clearing old cache...`);
        clearOldestCache(storageType);
        // Try again after clearing
        try {
          storage.setItem(key, JSON.stringify({
            data: value.value,
            timestamp: Date.now(),
            version,
            ttl: ttl > 0 ? ttl : undefined,
          }));
        } catch {
          console.error(`Still cannot save after clearing cache for key: ${key}`);
        }
      } else {
        console.error(`Error saving to ${storageType} (${key}):`, error);
      }
    }
  };

  /**
   * Remove data from storage
   */
  const remove = (): void => {
    try {
      storage.removeItem(key);
      value.value = defaultValue;
    } catch (error) {
      console.error(`Error removing from ${storageType} (${key}):`, error);
    }
  };

  // Auto-load on initialization
  load();

  // Auto-save on value change (optional, can be disabled)
  if (autoSave) {
    watch(value, save, { deep: true });
  }

  return {
    value,
    save,
    load,
    remove,
    isExpired,
    getSize,
    storageType,
  };
}

/**
 * Clear oldest cached items from storage
 */
function clearOldestCache(storageType: StorageType): void {
  try {
    const storage = getStorageAdapter(storageType);
    const cacheKeys: string[] = [];
    
    // Collect all cache keys
    for (let i = 0; i < storage.length; i++) {
      const key = storage.key(i);
      if (key && (key.includes('Cache') || key.includes('_page_') || key.includes('_timestamp'))) {
        cacheKeys.push(key);
      }
    }

    if (cacheKeys.length === 0) return;

    // Sort by timestamp (oldest first)
    const sortedKeys = cacheKeys.sort((a, b) => {
      try {
        const aData = JSON.parse(storage.getItem(a) || '{}');
        const bData = JSON.parse(storage.getItem(b) || '{}');
        return (aData.timestamp || 0) - (bData.timestamp || 0);
      } catch {
        return 0;
      }
    });

    // Remove oldest 30%
    const toRemove = Math.ceil(sortedKeys.length * 0.3);
    for (let i = 0; i < toRemove; i++) {
      storage.removeItem(sortedKeys[i]);
    }
  } catch (error) {
    console.error(`Error clearing oldest cache from ${storageType}:`, error);
  }
}

/**
 * Clear all application cache from specified storage type
 */
export function clearAllCache(storageType: StorageType = 'localStorage'): void {
  const storage = getStorageAdapter(storageType);
  const cacheKeys = [
    'dashboardCache',
    'projectCache',
    'memberCache',
  ];

  cacheKeys.forEach(key => storage.removeItem(key));

  // Clear paginated cache
  for (let i = 0; i < storage.length; i++) {
    const key = storage.key(i);
    if (key && (
      key.startsWith('project_page_') ||
      key.startsWith('member_page_') ||
      key.startsWith('/') // API response cache keys
    )) {
      storage.removeItem(key);
    }
  }
}

/**
 * Clean expired cache entries from specified storage
 */
export function cleanExpiredCache(storageType: StorageType = 'localStorage'): void {
  try {
    const storage = getStorageAdapter(storageType);
    const now = Date.now();
    
    for (let i = 0; i < storage.length; i++) {
      const key = storage.key(i);
      if (!key) continue;
      
      try {
        const rawData = storage.getItem(key);
        if (!rawData) continue;
        
        const data = JSON.parse(rawData);
        if (data.timestamp && (data.version || data.ttl)) {
          // This looks like our cached data
          const age = now - data.timestamp;
          const dataTTL = data.ttl || 30 * 60 * 1000; // Default 30 minutes
          
          if (age > dataTTL) {
            storage.removeItem(key);
          }
        }
      } catch {
        // Not JSON or doesn't match our format, skip
      }
    }
  } catch (error) {
    console.error(`Error cleaning expired cache from ${storageType}:`, error);
  }
}

/**
 * Get storage usage statistics
 */
export function getStorageUsage(storageType: StorageType = 'localStorage'): {
  used: number;
  total: number;
  percentage: number;
  keys: number;
} {
  const storage = getStorageAdapter(storageType);
  let totalSize = 0;
  
  for (let i = 0; i < storage.length; i++) {
    const key = storage.key(i);
    if (key) {
      const value = storage.getItem(key);
      if (value) {
        totalSize += new Blob([value]).size;
      }
    }
  }
  
  const maxSize = storageType === 'memory' 
    ? 50 * 1024 * 1024 // 50MB for memory (no hard limit)
    : 5 * 1024 * 1024; // 5MB estimate for localStorage/sessionStorage
  
  return {
    used: totalSize,
    total: maxSize,
    percentage: Math.round((totalSize / maxSize) * 100),
    keys: storage.length,
  };
}

