import { ref, computed, type Ref } from 'vue';
import { useStorage, type StorageType } from './useStorage';

/**
 * Cache entry metadata
 */
interface CacheEntry<T> {
  data: T;
  timestamp: number;
  ttl: number;
  key: string;
  storageType: StorageType;
}

/**
 * Cache configuration for different data types
 */
export interface CacheConfig {
  storageType?: StorageType;
  ttl?: number; // Time to live in ms
  maxSize?: number; // Max size in bytes
  version?: string;
}

/**
 * Cache Manager for API responses and other data
 * 
 * This manager provides a centralized way to cache data with different
 * storage strategies based on data type:
 * - localStorage: For persistent data (user preferences, settings)
 * - sessionStorage: For temporary data (form data, current session)
 * - memory: For runtime cache (API responses, computed data)
 */
export function useCacheManager() {
  // In-memory cache registry (for tracking all caches)
  const cacheRegistry = ref<Map<string, CacheEntry<any>>>(new Map());

  /**
   * Get or create a cache entry
   */
  function getCache<T>(
    key: string,
    defaultValue: T,
    config: CacheConfig = {}
  ): {
    value: Ref<T>;
    save: () => void;
    load: () => T;
    remove: () => void;
    isExpired: () => boolean;
  } {
    const {
      storageType = 'memory', // Default to memory for API cache
      ttl = 5 * 60 * 1000, // 5 minutes default for API cache
      maxSize = 1024 * 1024, // 1MB default
      version = '1.0',
    } = config;

    const cache = useStorage(key, defaultValue, {
      storageType,
      ttl,
      maxSize,
      version,
      autoSave: true,
    });

    // Register cache entry
    cacheRegistry.value.set(key, {
      data: defaultValue,
      timestamp: Date.now(),
      ttl,
      key,
      storageType,
    });

    return cache;
  }

  /**
   * Cache API response
   * 
   * @example
   * const cache = useCacheManager();
   * const projectCache = cache.cacheApiResponse('/api/projects', projects, {
   *   ttl: 10 * 60 * 1000, // 10 minutes
   *   storageType: 'memory' // Use memory for API responses
   * });
   */
  function cacheApiResponse<T>(
    endpoint: string,
    data: T,
    config: CacheConfig = {}
  ): void {
    const key = `api:${endpoint}`;
    const {
      storageType = 'memory', // API responses default to memory
      ttl = 5 * 60 * 1000, // 5 minutes default
    } = config;

    const cache = getCache<T>(key, data, { storageType, ttl });
    cache.value.value = data;
    cache.save();
  }

  /**
   * Get cached API response
   */
  function getCachedApiResponse<T>(
    endpoint: string,
    defaultValue: T
  ): T | null {
    const key = `api:${endpoint}`;
    const cache = getCache<T>(key, defaultValue);
    
    if (cache.isExpired()) {
      return null;
    }
    
    return cache.load();
  }

  /**
   * Clear specific cache entry
   */
  function clearCache(key: string): void {
    cacheRegistry.value.delete(key);
    // Also clear from all storage types
    ['localStorage', 'sessionStorage', 'memory'].forEach(storageType => {
      try {
        const storage = storageType === 'localStorage' 
          ? window.localStorage 
          : storageType === 'sessionStorage'
          ? window.sessionStorage
          : null;
        
        if (storage) {
          storage.removeItem(key);
        }
      } catch {
        // Ignore errors
      }
    });
  }

  /**
   * Clear all API caches
   */
  function clearApiCaches(): void {
    const keysToRemove: string[] = [];
    
    cacheRegistry.value.forEach((entry, key) => {
      if (key.startsWith('api:')) {
        keysToRemove.push(key);
      }
    });
    
    keysToRemove.forEach(key => clearCache(key));
  }

  /**
   * Clear all caches of a specific storage type
   */
  function clearByStorageType(storageType: StorageType): void {
    const keysToRemove: string[] = [];
    
    cacheRegistry.value.forEach((entry, key) => {
      if (entry.storageType === storageType) {
        keysToRemove.push(key);
      }
    });
    
    keysToRemove.forEach(key => clearCache(key));
  }

  /**
   * Get cache statistics
   */
  const cacheStats = computed(() => {
    const stats = {
      total: cacheRegistry.value.size,
      byStorageType: {
        localStorage: 0,
        sessionStorage: 0,
        memory: 0,
      },
      expired: 0,
    };

    cacheRegistry.value.forEach((entry) => {
      stats.byStorageType[entry.storageType]++;
      
      const age = Date.now() - entry.timestamp;
      if (age > entry.ttl) {
        stats.expired++;
      }
    });

    return stats;
  });

  /**
   * Clean expired caches
   */
  function cleanExpired(): void {
    const keysToRemove: string[] = [];
    
    cacheRegistry.value.forEach((entry, key) => {
      const age = Date.now() - entry.timestamp;
      if (age > entry.ttl) {
        keysToRemove.push(key);
      }
    });
    
    keysToRemove.forEach(key => clearCache(key));
  }

  return {
    getCache,
    cacheApiResponse,
    getCachedApiResponse,
    clearCache,
    clearApiCaches,
    clearByStorageType,
    cacheStats,
    cleanExpired,
  };
}

/**
 * Predefined cache configurations for common use cases
 */
export const CachePresets = {
  // User preferences (persistent, never expire)
  userPreferences: {
    storageType: 'localStorage' as StorageType,
    ttl: 0, // Never expire
    maxSize: 100 * 1024, // 100KB
  },
  
  // Form data (session only, 1 hour)
  formData: {
    storageType: 'sessionStorage' as StorageType,
    ttl: 60 * 60 * 1000, // 1 hour
    maxSize: 500 * 1024, // 500KB
  },
  
  // API responses (memory, 5 minutes)
  apiResponse: {
    storageType: 'memory' as StorageType,
    ttl: 5 * 60 * 1000, // 5 minutes
    maxSize: 1024 * 1024, // 1MB
  },
  
  // Dashboard data (memory, 2 minutes)
  dashboard: {
    storageType: 'memory' as StorageType,
    ttl: 2 * 60 * 1000, // 2 minutes
    maxSize: 2 * 1024 * 1024, // 2MB
  },
  
  // Project list (memory, 10 minutes)
  projectList: {
    storageType: 'memory' as StorageType,
    ttl: 10 * 60 * 1000, // 10 minutes
    maxSize: 2 * 1024 * 1024, // 2MB
  },
  
  // Member list (memory, 10 minutes)
  memberList: {
    storageType: 'memory' as StorageType,
    ttl: 10 * 60 * 1000, // 10 minutes
    maxSize: 2 * 1024 * 1024, // 2MB
  },
};

