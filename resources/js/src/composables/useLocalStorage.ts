import { ref, watch, type Ref } from 'vue';

export interface CachedData<T> {
  data: T;
  timestamp: number;
  version?: string;
}

export interface StorageOptions {
  ttl?: number; // Time to live in ms
  autoSave?: boolean; // Auto save on change
  version?: string; // Cache version for migration
  maxSize?: number; // Max cache size in bytes
}

/**
 * Composable for type-safe localStorage operations with TTL support
 * 
 * @param key - Storage key
 * @param defaultValue - Default value if not found or expired
 * @param options - Storage options (ttl, autoSave, version, maxSize)
 */
export function useLocalStorage<T>(
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
} {
  const {
    ttl = 30 * 60 * 1000, // 30 minutes default
    autoSave = true,
    version = '1.0',
    maxSize = 5 * 1024 * 1024 // 5MB default
  } = options;

  const value = ref<T>(defaultValue) as Ref<T>;

  /**
   * Get size of cached data in bytes
   */
  const getSize = (): number => {
    try {
      const rawData = localStorage.getItem(key);
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
    try {
      const rawData = localStorage.getItem(key);
      if (!rawData) return true;

      const cached: CachedData<T> = JSON.parse(rawData);
      
      // Check version mismatch
      if (cached.version !== version) return true;
      
      // Check TTL
      const age = Date.now() - cached.timestamp;
      return age > ttl;
    } catch {
      return true;
    }
  };

  /**
   * Load data from localStorage
   */
  const load = (): T => {
    try {
      const rawData = localStorage.getItem(key);
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
      console.error(`Error loading from localStorage (${key}):`, error);
      return defaultValue;
    }
  };

  /**
   * Save data to localStorage
   */
  const save = (): void => {
    try {
      const cached: CachedData<T> = {
        data: value.value,
        timestamp: Date.now(),
        version,
      };
      
      const serialized = JSON.stringify(cached);
      
      // Check size before saving
      if (maxSize && new Blob([serialized]).size > maxSize) {
        console.warn(`Cache size exceeds maxSize (${maxSize} bytes). Skipping save for key: ${key}`);
        return;
      }
      
      localStorage.setItem(key, serialized);
    } catch (error) {
      // Check if QuotaExceededError
      if (error instanceof DOMException && error.name === 'QuotaExceededError') {
        console.error(`localStorage quota exceeded for key: ${key}. Clearing old cache...`);
        clearOldestCache();
        // Try again after clearing
        try {
          localStorage.setItem(key, JSON.stringify({
            data: value.value,
            timestamp: Date.now(),
            version,
          }));
        } catch {
          console.error(`Still cannot save after clearing cache for key: ${key}`);
        }
      } else {
        console.error(`Error saving to localStorage (${key}):`, error);
      }
    }
  };

  /**
   * Remove data from localStorage
   */
  const remove = (): void => {
    try {
      localStorage.removeItem(key);
      value.value = defaultValue;
    } catch (error) {
      console.error(`Error removing from localStorage (${key}):`, error);
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
  };
}

/**
 * Clear oldest cached items from localStorage
 */
function clearOldestCache(): void {
  try {
    const cacheKeys = Object.keys(localStorage).filter(
      key => key.includes('Cache') || key.includes('_page_') || key.includes('_timestamp')
    );

    if (cacheKeys.length === 0) return;

    // Sort by timestamp (oldest first)
    const sortedKeys = cacheKeys.sort((a, b) => {
      try {
        const aData = JSON.parse(localStorage.getItem(a) || '{}');
        const bData = JSON.parse(localStorage.getItem(b) || '{}');
        return (aData.timestamp || 0) - (bData.timestamp || 0);
      } catch {
        return 0;
      }
    });

    // Remove oldest 30%
    const toRemove = Math.ceil(sortedKeys.length * 0.3);
    for (let i = 0; i < toRemove; i++) {
      localStorage.removeItem(sortedKeys[i]);
    }
  } catch (error) {
    console.error('Error clearing oldest cache:', error);
  }
}

/**
 * Clear all application cache from localStorage
 */
export function clearAllCache(): void {
  const cacheKeys = [
    'dashboardCache',
    'projectCache',
    'memberCache',
  ];

  cacheKeys.forEach(key => localStorage.removeItem(key));

  // Clear paginated cache
  Object.keys(localStorage).forEach((key) => {
    if (
      key.startsWith('project_page_') ||
      key.startsWith('member_page_')
    ) {
      localStorage.removeItem(key);
    }
  });
}

/**
 * Clear cache on logout
 */
export function clearCacheOnLogout(): void {
  // Keep only essential data (remove tokens and user data)
  localStorage.removeItem('userData');
  clearAllCache();
}

/**
 * Get total localStorage usage
 */
export function getLocalStorageUsage(): { used: number; total: number; percentage: number } {
  let totalSize = 0;
  for (const key in localStorage) {
    if (Object.prototype.hasOwnProperty.call(localStorage, key)) {
      totalSize += new Blob([localStorage.getItem(key) || '']).size;
    }
  }
  
  const maxSize = 5 * 1024 * 1024; // 5MB estimate
  return {
    used: totalSize,
    total: maxSize,
    percentage: Math.round((totalSize / maxSize) * 100),
  };
}

/**
 * Limit paginated cache to max pages
 */
export function limitPaginatedCache(prefix: string, maxPages: number = 3): void {
  try {
    const cacheKeys = Object.keys(localStorage).filter(key => key.startsWith(prefix));
    
    if (cacheKeys.length <= maxPages) return;
    
    // Parse and sort by page number
    const sortedKeys = cacheKeys.sort((a, b) => {
      const pageA = parseInt(a.match(/_(\d+)$/)?.[1] || '0');
      const pageB = parseInt(b.match(/_(\d+)$/)?.[1] || '0');
      return pageB - pageA; // Descending - keep recent pages
    });
    
    // Remove oldest pages
    for (let i = maxPages; i < sortedKeys.length; i++) {
      localStorage.removeItem(sortedKeys[i]);
    }
  } catch (error) {
    console.error('Error limiting paginated cache:', error);
  }
}

/**
 * Clean expired cache entries
 */
export function cleanExpiredCache(): void {
  try {
    const now = Date.now();
    const keys = Object.keys(localStorage);
    
    keys.forEach(key => {
      try {
        const data = JSON.parse(localStorage.getItem(key) || '{}');
        if (data.timestamp && data.version) {
          // This looks like our cached data
          const age = now - data.timestamp;
          const defaultTTL = 30 * 60 * 1000; // 30 minutes
          
          if (age > defaultTTL) {
            localStorage.removeItem(key);
          }
        }
      } catch {
        // Not JSON or doesn't match our format, skip
      }
    });
  } catch (error) {
    console.error('Error cleaning expired cache:', error);
  }
}

