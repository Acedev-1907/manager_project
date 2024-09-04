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

export default router