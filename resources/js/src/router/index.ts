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
    // Catch all route - redirect to login
    {
      path: "/:pathMatch(.*)*",
      redirect: "/login",
    },
  ],
});

// Navigation Guard Check login
router.beforeEach((to, from, next) => {
  try {
    const userData = localStorage.getItem("userData");
    let token = null;
    let isAuthenticated = false;

    // Safely parse userData
    if (userData) {
      try {
        const parsedData = JSON.parse(userData);
        token = parsedData?.token;
        isAuthenticated = !!token;
      } catch (parseError) {
        console.warn("Error parsing userData:", parseError);
        // Clear invalid data
        localStorage.removeItem("userData");
        isAuthenticated = false;
      }
    }

    // Nếu đã đăng nhập mà vào trang login hoặc register thì chuyển sang /admin
    if (
      isAuthenticated &&
      (to.path === "/login" || to.path === "/register" || to.path === "/auth")
    ) {
      next({ path: "/admin" });
      return;
    }

    // Nếu cần auth mà chưa đăng nhập
    if (to.meta.requiresAuth && !isAuthenticated) {
      next({ path: "/login" });
      return;
    }

    // Các trường hợp khác - cho phép
    next();
  } catch (error) {
    console.error("Router guard error:", error);
    // Nếu có lỗi, clear localStorage và chuyển về login
    localStorage.removeItem("userData");
    next({ path: "/login" });
  }
});

export default router;
