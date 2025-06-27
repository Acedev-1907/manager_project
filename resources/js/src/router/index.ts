import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
  history: createWebHistory("/app"),
  routes: [
    {
      path: "/auth",
      name: "auth",
      component: () => import("../pages/auth/AuthPage.vue"),

      children: [
        {
          path: "/register",
          name: "register",
          component: () => import("../pages/auth/RegisterPage.vue"),
        },
        {
          path: "/login",
          name: "login",
          component: () => import("../pages/auth/LoginPage.vue"),
        },
      ],
    },
    {
      path: "/admin",
      name: "admin",
      component: () => import("../pages/admin/AdminPage.vue"),
      meta: { requiresAuth: true },
      children: [
        {
          path: "",
          name: "dashboard",
          component: () => import("../pages/admin/dashboard/DashboardPage.vue"),
        },
        {
          path: "/members",
          name: "members",
          component: () => import("../pages/admin/member/MemberPage.vue"),
        },
        {
          path: "/create-members",
          name: "create-members",
          component: () => import("../pages/admin/member/CreateMember.vue"),
        },
        {
          path: "/projects",
          name: "projects",
          component: () => import("../pages/admin/project/ProjectPage.vue"),
        },
        {
          path: "/create-project",
          name: "create-project",
          component: () =>
            import("../pages/admin/project/components/CreateProject.vue"),
        },
        {
          path: "/kaban",
          name: "kaban",
          component: () => import("../pages/admin/kabanborad/KabanBorad.vue"),
        },
      ],
    },
  ],
});

// Navigation Guard Check login
router.beforeEach((to, from, next) => {
  const userData = localStorage.getItem("userData"); // Lấy toàn bộ dữ liệu từ localStorage
  const token = userData ? JSON.parse(userData).token : null; // Lấy token từ dữ liệu
  const isAuthenticated = !!token; // Chuyển token thành boolean

  // console.log(token);
  // Nếu đã đăng nhập mà vào trang login hoặc register thì chuyển sang /admin
  if (isAuthenticated && (to.path === "/login" || to.path === "/register")) {
    next({ path: "/admin" });
    return;
  }

  if (to.meta.requiresAuth && !isAuthenticated) {
    // console.log("Redirecting to login because user is not authenticated");
    next({ path: "/login" });
  } else {
    next();
  }
});

export default router;
