import { ref } from "vue";

const user = ref<{ name: string; avatar: string } | null>(null);

export function useUserStore() {
  function setUser(newUser: { name: string; avatar: string }) {
    user.value = newUser;
  }
  function setAvatar(avatar: string) {
    if (user.value) user.value.avatar = avatar;
  }
  function initUserFromLocalStorage() {
    const data = localStorage.getItem("userData");
    if (data) {
      const parsed = JSON.parse(data);
      if (parsed.user) {
        setUser({ name: parsed.user.name, avatar: parsed.user.avatar });
      }
    }
  }
  return { user, setUser, setAvatar, initUserFromLocalStorage };
}
