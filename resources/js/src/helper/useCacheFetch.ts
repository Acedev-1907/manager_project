// Generic composable for cache/fetch logic
export function useCacheFetch<T>(
  cacheMap: Record<string, T>,
  setCache: (key: string, data: T) => void,
  clearCache: (key: string) => void
) {
  // Lấy dữ liệu từ cache hoặc fetch mới
  async function getOrFetch(
    key: string,
    fetchFn: () => Promise<T>,
    setData: (data: T) => void
  ) {
    if (cacheMap[key]) {
      setData(cacheMap[key]);
      return;
    }
    const data = await fetchFn();
    setCache(key, data);
    setData(data);
  }

  // Xóa cache và fetch lại
  async function refetch(
    key: string,
    fetchFn: () => Promise<T>,
    setData: (data: T) => void
  ) {
    clearCache(key);
    const data = await fetchFn();
    setCache(key, data);
    setData(data);
  }

  return { getOrFetch, refetch };
}
