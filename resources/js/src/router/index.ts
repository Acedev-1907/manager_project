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
        {
          path: "/change-password",
          name: "change-password",
          component: () => import("../pages/auth/ChangePassword.vue"),
        },
        {
          path: "/reset-password",
          name: "reset-password",
          component: () => import("../pages/auth/ResetPassword.vue"),
        },
      ],
    },
    {
      path: "/app",
      name: "app",
      component: () => import("../pages/app/AppPage.vue"),
      meta: { requiresAuth: true },
      children: [
        {
          path: "/dashboard",
          name: "dashboard",
          component: () => import("../pages/app/dashboard/DashboardPage.vue"),
        },
        {
          path: "/members",
          name: "members",
          component: () => import("../pages/app/member/MemberPage.vue"),
        },
        {
          path: "/create-members",
          name: "create-members",
          component: () => import("../pages/app/member/CreateMember.vue"),
        },
        {
          path: "/projects",
          name: "projects",
          component: () => import("../pages/app/project/ProjectPage.vue"),
        },
        {
          path: "/create-project",
          name: "create-project",
          component: () =>
            import("../pages/app/project/components/CreateProject.vue"),
        },
        {
          path: "/kaban",
          name: "kaban",
          component: () => import("../pages/app/kabanborad/KabanBorad.vue"),
        },
        {
          path: "/profile",
          name: "profile",
          component: () => import("../pages/app/UserProfile.vue"),
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

    if (
      isAuthenticated &&
      (to.path === "/login" || to.path === "/register" || to.path === "/auth")
    ) {
      next({ path: "/dashboard" });
      return;
    }

    if (to.meta.requiresAuth && !isAuthenticated) {
      next({ path: "/login" });
      return;
    }

    // Các trường hợp khác - cho phép
    next();
  } catch (error) {
    console.error("Router guard error:", error);
    localStorage.removeItem("userData");
    next({ path: "/login" });
  }
});

export default router;
