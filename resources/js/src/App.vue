<template>
    <RouterView />
</template>
<script lang="ts">
import { defineComponent, onMounted } from 'vue';
import { useUserStore } from './state/userStore';
import { makeHttpReq } from './helper/makeHttpReq';

export default defineComponent({
    name: 'App',
    setup() {
        const { setUser } = useUserStore();
        onMounted(async () => {
            const userDataStr = localStorage.getItem("userData");
            let token = null;
            if (userDataStr) {
                try {
                    const userData = JSON.parse(userDataStr);
                    token = userData.token;
                } catch { }
            }
            if (token) {
                try {
                    const res = await makeHttpReq<undefined, any>('user', 'GET');
                    setUser({ name: res.data.name, avatar: res.data.avatar || '', friend_code: res.data.friend_code || null });
                    // Cập nhật lại localStorage
                    const userData = userDataStr ? JSON.parse(userDataStr) : {};
                    userData.user = { ...userData.user, name: res.data.name, avatar: res.data.avatar || '', friend_code: res.data.friend_code || null };
                    localStorage.setItem("userData", JSON.stringify(userData));
                } catch {
                    // Nếu lỗi, không làm gì thêm
                }
            }
        });
    }
});
</script>