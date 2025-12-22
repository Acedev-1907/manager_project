import { createRouter, createWebHistory } from "vue-router";
import { getUserData } from "../helper/getUserData";

/**
 * Check if user is authenticated
 */
function checkAuth(): boolean {
  try {
    const userData = getUserData();
    // Chỉ cần token là đủ (user info đã được lưu trong user-store)
    return !!(userData?.token);
  } catch (error) {
    return false;
  }
}

/**
 * Check if route is auth route (login, register, etc.)
 */
function isAuthRoute(path: string): boolean {
  return (
    path.startsWith("/auth") ||
    path.includes("/login") ||
    path.includes("/register") ||
    path.includes("/reset-password") ||
    path.includes("/change-password")
  );
}

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
          path: "/newsfeed",
          name: "newsfeed",
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
        {
          path: "/chat",
          name: "chat",
          component: () => import("../pages/app/chat/ChatPage.vue"),
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

// Navigation Guard - Simplified and optimized
router.beforeEach((to, from, next) => {
  const isAuthenticated = checkAuth();
  const isAuthPage = isAuthRoute(to.path);

  // If authenticated and trying to access auth pages, redirect to newsfeed
  if (isAuthenticated && isAuthPage) {
    next({ path: "/newsfeed" });
    return;
  }

  // If route requires auth but user is not authenticated, redirect to login
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ path: "/login" });
    return;
  }

  // Allow navigation
  next();
});

export default router;
