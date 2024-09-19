import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
    history: createWebHistory('/app'),
    routes: [
        {
            path: '/register',
            name: "register",
            component: () => import('../pages/auth/AuthPage.vue'),

            children: [
                {
                    path: '/register',
                    name: "register",
                    component: () => import('../pages/auth/RegisterPage.vue'),
                },
                {
                    path: '/login',
                    name: "login",
                    component: () => import('../pages/auth/LoginPage.vue'),
                },
            ]
        },
        {
            path: '/admin',
            name: "admin",
            component: () => import('../pages/admin/AdminPage.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '/admin',
                    name: "admin",
                    component: () => import('../pages/admin/dashborad/DashboradPage.vue'),
                },
                {
                    path: '/members',
                    name: "members",
                    component: () => import('../pages/admin/member/MemberPage.vue'),
                },
                {
                    path: '/create-members',
                    name: "create-members",
                    component: () => import('../pages/admin/member/CreateMember.vue'),
                }
            ]
        }
    ]
})

// Navigation Guard Check login
router.beforeEach((to, from, next) => {
    const userData = localStorage.getItem('userData'); // Lấy toàn bộ dữ liệu từ localStorage
    const token = userData ? JSON.parse(userData).token : null; // Lấy token từ dữ liệu
    const isAuthenticated = !!token; // Chuyển token thành boolean

    console.log(token);

    // console.log('Navigating to:', to.fullPath); // Kiểm tra đường dẫn
    // console.log('isAuthenticated:', isAuthenticated); // Kiểm tra xem đã đăng nhập chưa

    if (to.meta.requiresAuth && !isAuthenticated) {
        console.log('Redirecting to login because user is not authenticated');
        next({ path: '/login' });
    }
    else {
        next();
    }
});

export default router