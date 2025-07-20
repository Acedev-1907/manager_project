<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
const props = defineProps<{
    notificationCount: number,
    notifications: Array<{ id: number | string, text: string }>
}>()
const emit = defineEmits(['toggle'])

const bellOpen = ref(false)
const bellDropdownRef = ref<HTMLElement | null>(null)
function toggleBell() {
    bellOpen.value = !bellOpen.value
    emit('toggle', bellOpen.value)
}
function closeBell() { bellOpen.value = false }
function handleBellClickOutside(event: MouseEvent) {
    const dropdown = bellDropdownRef.value;
    const bellBtn = document.querySelector('.custom-bell-btn');
    if (
        bellOpen.value &&
        dropdown &&
        bellBtn &&
        !dropdown.contains(event.target as Node) &&
        !bellBtn.contains(event.target as Node)
    ) {
        closeBell();
    }
}
onMounted(() => {
    document.addEventListener('mousedown', handleBellClickOutside);
});
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleBellClickOutside);
});
</script>
<template>
    <span class="custom-bell-btn" @click="toggleBell">
        <i class="bi bi-bell-fill"></i>
        <span class="custom-bell-badge" v-if="props.notificationCount > 0">{{ props.notificationCount }}</span>
    </span>
    <transition name="fade">
        <div v-if="bellOpen" class="bell-dropdown bell-dropdown-mobile" ref="bellDropdownRef">
            <div class="bell-dropdown-header">Notifications</div>
            <ul class="bell-dropdown-list">
                <li v-if="!props.notifications.length" class="bell-dropdown-item">No notifications</li>
                <li v-for="item in props.notifications" :key="item.id" class="bell-dropdown-item">{{ item.text }}</li>
            </ul>
        </div>
    </transition>
</template>
<style scoped>
.custom-bell-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #e0e3e8;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
    transition: box-shadow 0.18s, background 0.18s;
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.10);
    border: none;
    margin-right: 0.5rem;
}

.custom-bell-btn i {
    color: #222;
    font-size: 1.6rem;
    display: block;
    margin: 0;
    padding: 0;
}

.custom-bell-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #e53935;
    color: #fff;
    border-radius: 50%;
    font-size: 15px;
    min-width: 20px;
    min-height: 20px;
    height: 16px;
    line-height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 4px rgba(211, 47, 47, 0.15);
    z-index: 2;
    padding: 0 3px;
}

.custom-bell-btn:hover {
    background: #f0f2f5;
    box-shadow: 0 4px 16px rgba(36, 112, 220, 0.18);
}

.bell-dropdown {
    position: absolute;
    top: 48px;
    right: 0;
    min-width: 320px;
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 8px 32px rgba(36, 112, 220, 0.13), 0 1.5px 8px rgba(36, 112, 220, 0.07);
    padding: 1.2rem 0.8rem 1rem 0.8rem;
    z-index: 1300;
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
}

.bell-dropdown-header {
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: #2563eb;
}

.bell-dropdown-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.bell-dropdown-item {
    padding: 0.6rem 0.5rem;
    border-radius: 8px;
    transition: background 0.15s;
    cursor: pointer;
    color: #222;
}

.bell-dropdown-item:hover {
    background: #f0f2f5;
}

@media (max-width: 767.98px) {
    .bell-dropdown-mobile {
        position: absolute;
        top: 48px;
        right: 0;
        left: auto;
        min-width: 220px;
        max-width: 90vw;
        border-radius: 1.2rem;
        box-shadow: 0 8px 32px rgba(36, 112, 220, 0.13), 0 1.5px 8px rgba(36, 112, 220, 0.07);
        z-index: 1300;
        background: #fff;
        margin: 0;
    }

    .custom-bell-btn {
        width: 44px;
        height: 44px;
        margin-right: 0.2rem;
    }

    .custom-bell-btn i {
        font-size: 1.7rem;
    }
}
</style>