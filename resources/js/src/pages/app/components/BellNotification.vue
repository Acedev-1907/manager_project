<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRouter } from 'vue-router';
import { notifications, notificationCount, markAllAsRead, fetchAllNotifications, removeNotification } from '../../../state/notificationStore';
import eventBus from '../../../helper/eventBus';
import { getAvatarSrc } from '../../../helper/avatar';
import { makeHttpReq } from '../../../helper/makeHttpReq';
import { showSuccess, showError } from '../../../helper/alert';

const bellOpen = ref(false);
const bellDropdownRef = ref<HTMLElement | null>(null);
const router = useRouter();
const loadingAll = ref(false);
const allLoaded = ref(false);

// Xử lý cuộn ngoài khi mở dropdown trên mobile
watch(bellOpen, (open) => {
    if (window.innerWidth <= 767) {
        document.body.style.overflow = open ? 'hidden' : '';
    }
});

// Đóng dropdown khi click ra ngoài
function handleBellClickOutside(event: MouseEvent) {
    if (!bellOpen.value) return;
    const dropdown = bellDropdownRef.value;
    const bellBtn = document.querySelector('.custom-bell-btn');
    if (dropdown && bellBtn && !dropdown.contains(event.target as Node) && !bellBtn.contains(event.target as Node)) {
        closeBell();
    }
}

onMounted(() => {
    document.addEventListener('click', handleBellClickOutside, false);
    eventBus.on('close-bell-notification', closeBell);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', handleBellClickOutside, false);
    eventBus.off('close-bell-notification', closeBell);
    if (window.innerWidth <= 767) document.body.style.overflow = '';
});

async function toggleBell(event?: MouseEvent) {
    if (event) event.stopPropagation();
    eventBus.emit('close-avatar-dropdown');
    if (bellOpen.value) {
        closeBell();
    } else {
        bellOpen.value = true;
        // Đảm bảo notifications được load nếu chưa có
        if (notifications.value.length === 0) {
            await fetchAllNotifications();
        }
        // Đánh dấu tất cả thông báo là đã đọc (badge biến mất ngay)
        await markAllAsRead();
    }
}

function closeBell() {
    bellOpen.value = false;
    moreMenuOpen.value = null;
}

// Xem chi tiết notification
function handleNotificationClick(item: any) {
    let slug = item.slug;
    if (!slug && item.project_id) slug = getSlugByProjectId(item.project_id);
    if (slug) {
        router.push(`/kaban?query=${slug}`);
        closeBell();
    }
}

function getSlugByProjectId(projectId: number | string) {
    for (const key in localStorage) {
        if (key.startsWith('project_page_')) {
            try {
                const pageData = JSON.parse(localStorage.getItem(key) || '{}');
                const projects = pageData.data?.data || [];
                const project = projects.find((p: any) => p.id == projectId);
                if (project && project.slug) return project.slug;
            } catch (e) { /* ignore */ }
        }
    }
    return undefined;
}

// Xem thêm thông báo cũ
async function handleSeePrevious() {
    loadingAll.value = true;
    const beforeCount = notifications.value.length;
    await fetchAllNotifications();
    loadingAll.value = false;
    // Nếu không có thêm thông báo mới, coi như đã load hết
    if (notifications.value.length === beforeCount) {
        allLoaded.value = true;
    }
    setTimeout(() => {
        const list = bellDropdownRef.value?.querySelector('.bell-dropdown-list');
        if (list) list.scrollTop = list.scrollHeight;
    }, 100);
}

// Xử lý menu 3 chấm
const moreMenuOpen = ref<string | number | null>(null);
const hoverMenu = ref<{ [key: string]: boolean }>({});
// Thêm biến loadingInvitation để disable Accept/Decline theo từng invitation
const loadingInvitation = ref<{ [key: string]: boolean }>({});
function toggleMoreMenu(id: string | number, event: MouseEvent) {
    event.stopPropagation();
    moreMenuOpen.value = moreMenuOpen.value === id ? null : id;
}
function handleView(item: any) {
    handleNotificationClick(item);
    moreMenuOpen.value = null;
}
function handleRemove(item: any) {
    removeNotification(item.id);
    moreMenuOpen.value = null;
}

async function handleAcceptInvitation(item: any) {
    if (!item.invitation_id) return;
    if (loadingInvitation.value[item.invitation_id]) return;
    loadingInvitation.value[item.invitation_id] = true;
    try {
        await makeHttpReq<any, any>(`member-invitations/${item.invitation_id}/accept`, 'POST');
        showSuccess('Invitation accepted!');
        await fetchAllNotifications();
    } catch (e) {
        showError('Failed to accept invitation');
    } finally {
        loadingInvitation.value[item.invitation_id] = false;
    }
}

async function handleDeclineInvitation(item: any) {
    if (!item.invitation_id) return;
    if (loadingInvitation.value[item.invitation_id]) return;
    loadingInvitation.value[item.invitation_id] = true;
    try {
        await makeHttpReq<any, any>(`member-invitations/${item.invitation_id}/decline`, 'POST');
        showSuccess('Invitation declined!');
        await fetchAllNotifications();
    } catch (e) {
        showError('Failed to decline invitation');
    } finally {
        loadingInvitation.value[item.invitation_id] = false;
    }
}

function formatTime(dateStr: string) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = (now.getTime() - date.getTime()) / 1000;
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
    return date.toLocaleDateString();
}
</script>
<template>
    <span class="custom-bell-btn" @click="toggleBell($event)">
        <i class="bi bi-bell-fill"></i>
        <span class="custom-bell-badge" v-if="notificationCount > 0">{{ notificationCount }}</span>
    </span>
    <transition name="fade">
        <div v-if="bellOpen" class="bell-dropdown bell-dropdown-mobile" ref="bellDropdownRef">
            <div class="bell-dropdown-header">Notifications</div>
            <ul class="bell-dropdown-list">
                <li v-if="!notifications.length" class="bell-dropdown-item">No notifications</li>
                <li v-for="item in notifications" :key="item.id" class="bell-dropdown-item notification-item"
                    @click="handleNotificationClick(item)">
                    <img :src="getAvatarSrc(item.avatar)" alt="avatar" class="notification-avatar" />
                    <div class="notification-content">
                        <div class="notification-message">{{ item.message }}</div>
                        <div class="notification-time">{{ formatTime(item.created_at) }}</div>
                        <template v-if="item.type === 'sent' && item.invitation_id">
                            <div class="notification-action-btns fb-btns">
                                <button class="btn btn-confirm-fb" @click.stop="handleAcceptInvitation(item)"
                                    :disabled="loadingInvitation[item.invitation_id]">Accept</button>
                                <button class="btn btn-delete-fb" @click.stop="handleDeclineInvitation(item)"
                                    :disabled="loadingInvitation[item.invitation_id]">Decline</button>
                            </div>
                        </template>
                    </div>
                    <span class="notification-more-btn" @click.stop="toggleMoreMenu(item.id, $event)">
                        ⋮
                        <div v-if="moreMenuOpen === item.id" class="more-menu-mobile">
                            <div class="more-menu-view" :class="{ 'hovered': hoverMenu['view-' + item.id] }"
                                @click.stop="handleView(item)" @mouseenter="hoverMenu['view-' + item.id] = true"
                                @mouseleave="hoverMenu['view-' + item.id] = false">View
                            </div>
                            <div class="more-menu-remove" :class="{ 'hovered': hoverMenu['remove-' + item.id] }"
                                @click.stop="handleRemove(item)" @mouseenter="hoverMenu['remove-' + item.id] = true"
                                @mouseleave="hoverMenu['remove-' + item.id] = false">Remove</div>
                        </div>
                    </span>
                </li>
            </ul>
            <button v-if="notifications.length >= 10 && !allLoaded" @mousedown.stop="" @click.stop="handleSeePrevious"
                :disabled="loadingAll" class="see-previous-btn">
                <span v-if="!loadingAll">See previous notifications</span>
                <span v-else>Loading...</span>
            </button>
        </div>
    </transition>
</template>
<style scoped>
/* ===== Nút chuông thông báo ===== */
.custom-bell-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2100;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    border: 2px solid rgba(255, 255, 255, 0.3);
    margin-right: 0.5rem;
}

.custom-bell-btn i {
    color: #fff;
    font-size: 1.2rem;
    display: block;
    margin: 0;
    padding: 0;
    transition: transform 0.2s ease;
}

.custom-bell-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
}

.custom-bell-btn:hover i {
    transform: scale(1.1);
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
        padding: 0.8rem 0.2rem 4.2rem 0.2rem;
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

.notification-item {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}

.notification-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
}

.notification-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    flex: 1;
    min-width: 0;
}

.notification-message {
    font-size: 14px;
    white-space: normal;
    word-break: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
}

.notification-time {
    font-size: 12px;
    color: #888;
    margin-top: 2px;
}

.notification-more-btn {
    display: flex;
    align-items: center;
    height: 100%;
    margin-left: 8px;
    font-size: 20px;
    color: #888;
    cursor: pointer;
    position: relative;
}

.more-menu-mobile {
    position: absolute;
    right: 0;
    top: 36px;
    background: #fff;
    box-shadow: 0 4px 16px rgba(36, 112, 220, 0.13), 0 1.5px 8px rgba(36, 112, 220, 0.07);
    border-radius: 10px;
    z-index: 999;
    min-width: 120px;
    overflow: hidden;
    border: 1px solid #e0e3e8;
}

.more-menu-view {
    padding: 7px 18px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid #f0f0f0;
}

.more-menu-view.hovered {
    background: #f4f8ff;
}

.more-menu-remove {
    padding: 7px 18px;
    font-size: 13px;
    cursor: pointer;
    color: #e53935;
    transition: background 0.15s;
}

.more-menu-remove.hovered {
    background: #fbe9e7;
}

.see-previous-wrapper {
    text-align: center;
    margin-top: 8px;
}

.see-previous-btn {
    padding: 6px 18px;
    border-radius: 8px;
    background: #e3edfa;
    color: #2563eb;
    border: none;
    font-weight: 600;
    cursor: pointer;
}

.see-previous-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.notification-action-btns {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.3rem;
}

.btn-accept {
    background: #fff;
    color: #16a34a;
    border: 1px solid #16a34a;
    border-radius: 6px;
    padding: 2px 12px;
    font-size: 0.95rem;
    font-weight: 600;
    transition: background 0.15s, color 0.15s, border 0.15s;
    cursor: pointer;
}

.btn-accept:hover {
    background: #dcfce7;
    color: #15803d;
    border-color: #15803d;
}

.btn-decline {
    background: #fff;
    color: #ef4444;
    border: 1px solid #ef4444;
    border-radius: 6px;
    padding: 2px 12px;
    font-size: 0.95rem;
    font-weight: 600;
    transition: background 0.15s, color 0.15s, border 0.15s;
    cursor: pointer;
}

.btn-decline:hover {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #b91c1c;
}

.invitation-noti {
    display: flex;
    align-items: flex-start;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(34, 34, 59, 0.08);
    padding: 1rem 1.2rem 1rem 1.2rem;
    margin-bottom: 0.7rem;
    min-height: 70px;
    position: relative;
    transition: box-shadow 0.2s;
}

.large-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 14px;
    border: 2px solid #e0e7ff;
}

.sender-name {
    font-weight: 700;
    color: #222;
}

.notification-action-btns.inline-btns {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.3rem;
}

.btn-accept-en {
    background: #e3f6e8;
    color: #15803d;
    border: 1px solid #b6e4c7;
    border-radius: 5px;
    padding: 2px 14px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s, color 0.15s, border 0.15s;
}

.btn-accept-en:hover {
    background: #b6e4c7;
    color: #166534;
}

.btn-decline-en {
    background: #f3f4f6;
    color: #555;
    border: 1px solid #e5e7eb;
    border-radius: 5px;
    padding: 2px 14px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s, color 0.15s, border 0.15s;
}

.btn-decline-en:hover {
    background: #e5e7eb;
    color: #222;
}

.notification-action-btns.fb-btns {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.3rem;
}

.btn-confirm-fb {
    background: #1877f2;
    color: #fff;
    border: 1.2px solid #1877f2;
    border-radius: 20px;
    padding: 3px 16px;
    font-size: 0.95rem;
    font-weight: 500;
    transition: background 0.16s, border 0.16s, box-shadow 0.16s;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(24, 119, 242, 0.08);
    outline: none;
    letter-spacing: 0.01em;
}

.btn-confirm-fb:hover,
.btn-confirm-fb:focus {
    background: #1657b7;
    border-color: #1657b7;
    box-shadow: 0 2px 8px rgba(24, 119, 242, 0.10);
    color: #fff;
}

.btn-delete-fb {
    background: #fff;
    color: #222;
    border: 1.2px solid #ccd0d5;
    border-radius: 20px;
    padding: 3px 16px;
    font-size: 0.95rem;
    font-weight: 500;
    transition: background 0.16s, border 0.16s;
    cursor: pointer;
    outline: none;
    letter-spacing: 0.01em;
}

.btn-delete-fb:hover,
.btn-delete-fb:focus {
    background: #ececec;
    border-color: #b0b3b8;
    color: #222;
}
</style>
