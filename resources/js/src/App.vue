<template>
    <RouterView />
</template>
<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue';
import { useUserStore } from './state/userStore';
import { makeHttpReq } from './helper/makeHttpReq';
import { useGlobalEchoListener } from './helper/useGlobalEchoListener';
import { useProjectRealtimeCacheClear } from './helper/useProjectRealtimeCacheClear';
import { useAppGlobalRealtime } from './helper/useAppGlobalRealtime';

export default defineComponent({
    name: 'App',
    setup() {
        // Initialize global real-time manager
        useAppGlobalRealtime();


        const userStore = useUserStore();
        // Lấy userId từ user-store hoặc userData (backward compatibility)
        const userId = ref<string | number | null>(null);
        // Ưu tiên lấy từ user-store (đã được persist)
        // @ts-expect-error - Pinia store type inference issue
        if (userStore.user?.id) {
            // @ts-expect-error - Pinia store type inference issue
            userId.value = userStore.user.id;
        } else {
            // Fallback: lấy từ userData (backward compatibility)
        const userDataStr = localStorage.getItem("userData");
        if (userDataStr) {
            try {
                const userData = JSON.parse(userDataStr);
                    userId.value = userData.userId || (userData.user && userData.user.id) || null;
            } catch (err) {
                // ignore parse error
                }
            }
        }
        // Khởi tạo lắng nghe Echo toàn cục với userId là ref
        useGlobalEchoListener(userId);
        // Gọi composable clear cache project khi có event realtime
        useProjectRealtimeCacheClear();

        onMounted(async () => {
            const userDataStr = localStorage.getItem("userData");
            let token: string | null = null;
            if (userDataStr) {
                try {
                    const userData = JSON.parse(userDataStr);
                    token = userData.token;
                } catch (err) {
                    // ignore parse error
                }
            }

            // Nếu có token (user đã đăng nhập), luôn khởi tạo Echo sau mỗi lần reload
            if (token) {
                try {
                    const { initEcho } = await import('../echo.js');
                    initEcho();
                } catch (err) {
                    console.warn('Failed to initialize Echo on App mount:', err);
                }

                try {
                    const res = await makeHttpReq<undefined, any>('user', 'GET');
                    // Chỉ cập nhật user-store, không cập nhật userData (chỉ lưu token)
                    // @ts-expect-error - Pinia store type inference issue
                    userStore.setUser({ 
                        id: res.data.id,
                        name: res.data.name, 
                        avatar: res.data.avatar || '', 
                        friend_code: res.data.friend_code || null 
                    });
                } catch (err) {
                    // ignore fetch error
                }
            }
        });
    }
});
</script>