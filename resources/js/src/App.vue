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


        const { setUser } = useUserStore();
        // Lấy userId từ localStorage và dùng ref để reactive
        const userId = ref<string | number | null>(null);
        const userDataStr = localStorage.getItem("userData");
        if (userDataStr) {
            try {
                const userData = JSON.parse(userDataStr);
                userId.value = userData.id || (userData.user && userData.user.id) || null;
            } catch (err) {
                // ignore parse error
            }
        }
        // Khởi tạo lắng nghe Echo toàn cục với userId là ref
        useGlobalEchoListener(userId);
        // Gọi composable clear cache project khi có event realtime
        useProjectRealtimeCacheClear();

        onMounted(async () => {
            const userDataStr = localStorage.getItem("userData");
            let token = null;
            if (userDataStr) {
                try {
                    const userData = JSON.parse(userDataStr);
                    token = userData.token;
                } catch (err) {
                    // ignore parse error
                }
            }
            if (token) {
                try {
                    const res = await makeHttpReq<undefined, any>('user', 'GET');
                    setUser({ 
                        id: res.data.id,
                        name: res.data.name, 
                        avatar: res.data.avatar || '', 
                        friend_code: res.data.friend_code || null 
                    });
                    // Cập nhật lại localStorage
                    const userData = userDataStr ? JSON.parse(userDataStr) : {};
                    userData.user = { 
                        id: res.data.id,
                        name: res.data.name, 
                        avatar: res.data.avatar || '', 
                        friend_code: res.data.friend_code || null 
                    };
                    localStorage.setItem("userData", JSON.stringify(userData));
                } catch (err) {
                    // ignore fetch error
                }
            }
        });
    }
});
</script>