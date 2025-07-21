<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRouter } from 'vue-router';
import { markAsRead, notifications, removeNotification } from '../../../state/notificationStore';
import { eventBus } from '../../../helper/eventBus';
import { getAvatarSrc } from '../../../helper/avatar';
const props = defineProps<{
    notificationCount: number,
    notifications: Array<{
        id: number | string,
        avatar?: string,
        creator_name?: string,
        message: string,
        created_at: string,
        project_id?: string | number
    }>
}>()
const emit = defineEmits(['toggle'])

const bellOpen = ref(false)
const bellDropdownRef = ref<HTMLElement | null>(null)
const router = useRouter();
const localBadgeCount = ref(props.notificationCount)
let badgeFrozen = false;

// Luôn đồng bộ localBadgeCount với props.notificationCount nếu không bị freeze
watch(() => props.notificationCount, (val) => {
    if (!badgeFrozen || !bellOpen.value) localBadgeCount.value = val;
});

watch(bellOpen, (open) => {
    if (!open) {
        badgeFrozen = false;
        localBadgeCount.value = props.notificationCount;
    }
    // Ẩn thanh cuộn ngoài khi mở dropdown ở mobile
    if (window.innerWidth <= 767 && open) {
        document.body.style.overflow = 'hidden';
    } else if (window.innerWidth <= 767 && !open) {
        document.body.style.overflow = '';
    }
});

function toggleBell(event?: MouseEvent) {
    if (event) event.stopPropagation();
    // Đóng dropdown avatar nếu đang mở
    eventBus.emit('close-avatar-dropdown');
    // Toggle luôn trạng thái bellOpen
    if (bellOpen.value) {
        closeBell();
    } else {
        bellOpen.value = true;
        emit('toggle', true);
        if (localBadgeCount.value > 0) {
            badgeFrozen = true;
            localBadgeCount.value = 0;
            const unread = notifications.value.filter(n => !n.read_at);
            unread.forEach(n => markAsRead(n.id));
        }
    }
}

function closeBell() {
    bellOpen.value = false;
    moreMenuOpen.value = null; // Reset menu 3 chấm khi đóng dropdown
    emit('toggle', false);
}
function handleBellClickOutside(event: MouseEvent) {
    if (!bellOpen.value) return;
    const dropdown = bellDropdownRef.value;
    const bellBtn = document.querySelector('.custom-bell-btn');
    if (
        dropdown &&
        bellBtn &&
        !dropdown.contains(event.target as Node) &&
        !bellBtn.contains(event.target as Node)
    ) {
        closeBell();
    }
    // Đóng menu 3 chấm nếu click ra ngoài
    if (moreMenuOpen.value) {
        const moreMenu = document.querySelector('.bell-dropdown [style*="z-index:999"]');
        if (moreMenu && !moreMenu.contains(event.target as Node)) {
            moreMenuOpen.value = null;
        }
    }
}
onMounted(() => {
    document.addEventListener('click', handleBellClickOutside, false); // bubbling phase
    eventBus.on('close-bell-notification', closeBell);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', handleBellClickOutside, false);
    eventBus.off('close-bell-notification', closeBell);
    // Đảm bảo khôi phục cuộn khi component bị hủy
    if (window.innerWidth <= 767) document.body.style.overflow = '';
});

function getSlugByProjectId(projectId: number | string) {
    // Duyệt qua tất cả các key project_page_* trong localStorage
    for (const key in localStorage) {
        if (key.startsWith('project_page_')) {
            try {
                const pageData = JSON.parse(localStorage.getItem(key) || '{}');
                const projects = pageData.data?.data || [];
                const project = projects.find((p: any) => p.id == projectId);
                if (project && project.slug) return project.slug;
            } catch (e) { /* ignore parse error */ }
        }
    }
    return undefined;
}

// Hàm xử lý khi click vào notification
function handleNotificationClick(item: any) {

    let slug = item.slug;
    if (!slug && item.project_id) {
        slug = getSlugByProjectId(item.project_id);
    }
    if (slug) {
        router.push(`/kaban?query=${slug}`);
        closeBell();
    }
}

const formatTime = (dateStr: string) => {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = (now.getTime() - date.getTime()) / 1000; // seconds
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
    return date.toLocaleDateString();
}

const moreMenuOpen = ref<string | number | null>(null);
// Thêm biến trạng thái hover cho từng option
const hoverMenu = ref<{ [key: string]: boolean }>({});
function toggleMoreMenu(id: string | number, event: MouseEvent) {
    event.stopPropagation();
    moreMenuOpen.value = moreMenuOpen.value === id ? null : id;
}
function handleView(item: any) {
    // Có thể emit hoặc xử lý view ở đây
    handleNotificationClick(item);
    moreMenuOpen.value = null;
}
function handleRemove(item: any) {
    removeNotification(item.id);
    moreMenuOpen.value = null;
}
</script>
<template>
    <span class="custom-bell-btn" @click="toggleBell($event)">
        <i class="bi bi-bell-fill"></i>
        <span class="custom-bell-badge" v-if="localBadgeCount > 0">{{ localBadgeCount }}</span>
    </span>
    <transition name="fade">
        <div v-if="bellOpen" class="bell-dropdown bell-dropdown-mobile" ref="bellDropdownRef">
            <div class="bell-dropdown-header">Notifications</div>
            <ul class="bell-dropdown-list">
                <li v-if="!props.notifications.length" class="bell-dropdown-item">No notifications</li>
                <li v-for="item in props.notifications" :key="item.id" class="bell-dropdown-item"
                    @click="handleNotificationClick(item)"
                    style="cursor:pointer; display: flex; align-items: center; gap: 10px; position: relative;">
                    <img :src="getAvatarSrc(item.avatar)" alt="avatar"
                        style="width:36px; height:36px; border-radius:50%; object-fit:cover; margin-right:10px;" />
                    <div style="display: flex; flex-direction: column; align-items: flex-start; flex:1; min-width:0;">
                        <div
                            style="font-size:14px; white-space:normal; word-break:break-word; overflow:hidden; text-overflow:ellipsis; font-weight:500;">
                            {{ item.message }}
                        </div>
                        <div style="font-size:12px; color:#888; margin-top:2px;">
                            {{ formatTime(item.created_at) }}
                        </div>
                    </div>
                    <span
                        style="display:flex; align-items:center; height:100%; margin-left:8px; font-size:20px; color:#888; cursor:pointer; position:relative;"
                        @click.stop="toggleMoreMenu(item.id, $event)">
                        ⋮
                        <div v-if="moreMenuOpen === item.id" class="more-menu-mobile"
                            style="position:absolute; right:0; top:36px; background:#fff; box-shadow:0 4px 16px rgba(36,112,220,0.13),0 1.5px 8px rgba(36,112,220,0.07); border-radius:10px; z-index:999; min-width:120px; overflow:hidden; border:1px solid #e0e3e8;">
                            <div style="padding:7px 18px; font-size:13px; cursor:pointer; transition:background 0.15s; border-bottom:1px solid #f0f0f0;"
                                :style="hoverMenu['view-' + item.id] ? 'background:#f4f8ff' : ''"
                                @click.stop="handleView(item)" @mouseenter="hoverMenu['view-' + item.id] = true"
                                @mouseleave="hoverMenu['view-' + item.id] = false">View</div>
                            <div style="padding:7px 18px; font-size:13px; cursor:pointer; color:#e53935; transition:background 0.15s;"
                                :style="hoverMenu['remove-' + item.id] ? 'background:#fbe9e7' : ''"
                                @click.stop="handleRemove(item)" @mouseenter="hoverMenu['remove-' + item.id] = true"
                                @mouseleave="hoverMenu['remove-' + item.id] = false">Remove</div>
                        </div>
                    </span>
                </li>
            </ul>
        </div>
    </transition>
</template>
<style scoped>
/* ===== Nút chuông thông báo ===== */
.custom-bell-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #e0e3e8;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2100;
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

.custom-bell-btn:hover {
    background: #f0f2f5;
    box-shadow: 0 4px 16px rgba(36, 112, 220, 0.18);
}

/* ===== Badge số lượng thông báo ===== */
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

/* ===== Dropdown thông báo ===== */
.bell-dropdown {
    position: absolute;
    top: 48px;
    right: 0;
    min-width: 428px;
    max-height: 420px;
    overflow-y: overlay;
    background: #f4f8ff;
    border-radius: 1.2rem;
    box-shadow:
        0 8px 32px rgba(36, 112, 220, 0.13),
        0 1.5px 8px rgba(36, 112, 220, 0.07);
    padding: 1.2rem 0.8rem 1rem 0.8rem;
    z-index: 1300;
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
    border-top: 4px solid #2563eb;
}

/* Webkit Scrollbar Styling */
.bell-dropdown::-webkit-scrollbar {
    width: 8px;
}


.bell-dropdown::-webkit-scrollbar-thumb {
    background: #c7d2fe;
    border-radius: 8px;
}

.bell-dropdown::-webkit-scrollbar-track {
    background: #f4f8ff;
    margin-top: 16px;
    margin-bottom: 16px;
    border-radius: 8px;
}

/* ===== Header của dropdown ===== */
.bell-dropdown-header {
    font-weight: 900;
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: #2563eb;
    letter-spacing: 0.5px;
    text-shadow: 0 2px 8px #e0e7ef;
}

/* ===== Danh sách và mục thông báo ===== */
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
    user-select: none;
}

.bell-dropdown-item:hover {
    background: #f0f2f5;
}

/* ===== Giao diện Mobile ===== */
@media (max-width: 767.98px) {
    .bell-dropdown-mobile {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        width: 100vw;
        max-width: 100vw;
        min-width: 0;
        height: calc(100vh - 70px);
        max-height: calc(100vh - 70px);
        border-radius: 0 0 1.2rem 1.2rem;
        box-shadow: none;
        z-index: 2000;
        background: #f4f8ff;
        margin: 0;
        padding: 0.8rem 0.2rem 1.2rem 0.2rem;
        overflow-y: auto;
        border-top: 4px solid #2563eb;
        /* Custom scrollbar for mobile */
        scrollbar-width: thin;
        scrollbar-color: #c7d2fe #f4f8ff;
    }

    .bell-dropdown-mobile::-webkit-scrollbar {
        width: 7px;
    }

    .bell-dropdown-mobile::-webkit-scrollbar-track {
        background: #f4f8ff;
        margin-top: 10px;
        margin-bottom: 10px;
        border-radius: 7px;
    }

    .bell-dropdown-mobile::-webkit-scrollbar-thumb {
        background: #c7d2fe;
        border-radius: 7px;
    }

    .bell-dropdown-mobile::-webkit-scrollbar-thumb:hover {
        background: #a5b4fc;
    }

    .bell-dropdown-mobile::-webkit-scrollbar-button {
        display: none;
        height: 0;
        width: 0;
    }

    .custom-bell-btn {
        width: 44px;
        height: 44px;
        margin-right: 0.2rem;
    }

    .custom-bell-btn i {
        font-size: 1.7rem;
    }

    .bell-dropdown-item {
        padding: 0.6rem 1.5rem;
    }

    .bell-dropdown-header {
        padding-left: 15px;
    }

    .bell-dropdown-item .more-menu-mobile {
        right: 21px !important;
        top: -21px !important;
    }
}
</style>
