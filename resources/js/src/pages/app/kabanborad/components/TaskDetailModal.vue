<script setup lang="ts">
import { ref, watch, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';
import { getTaskComments, addTaskComment } from '../actions/taskComment';
import { showError } from '../../../../helper/alert';

const props = defineProps<{ visible: boolean, task: any }>();
const emit = defineEmits(['close']);
const comments = ref<any[]>([]);
const newComment = ref('');
const loadingComments = ref(false);
const commentsListRef = ref<HTMLElement | null>(null);
const mobileCommentsListRef = ref<HTMLElement | null>(null);
const showNewMsgBtn = ref(false);
const sendingComment = ref(false); // Thêm biến chống spam gửi comment
const activeTab = ref<'content' | 'discussion'>('content'); // Tab active cho mobile

// Lấy userId hiện tại
const currentUserId = computed(() => {
    const data = localStorage.getItem("userData");
    if (data) {
        const parsed = JSON.parse(data);
        return parsed.user?.id;
    }
    return null;
});
const currentUserIdStr = computed(() => String(currentUserId.value));

let channel: any = null;

// Theo dõi khi modal mở và taskId thay đổi để mount/unmount channel realtime
watch([
    () => props.visible,
    () => props.task?.id
], ([visible, taskId]) => {
    if (channel) {
        channel.stopListening('TaskCommentCreated');
        channel = null;
    }
    if (visible && taskId && window.Echo) {
        channel = window.Echo.private('task.' + taskId)
            .listen('TaskCommentCreated', (e: any) => {
                const commentUserId = String(e.comment.user?.id || e.comment.user_id);
                if (commentUserId !== String(currentUserId.value)) {
                    handleNewRealtimeComment(e.comment, false);
                }
            });
    }
});

// Khóa cuộn body khi modal mở, khôi phục khi đóng
watch(() => props.visible, (visible) => {
    if (visible) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

onUnmounted(() => {
    document.body.style.overflow = '';
});

function close() { emit('close'); }

// Load danh sách comment khi mở modal hoặc đổi task
async function loadComments() {
    if (!props.task?.id) return;
    loadingComments.value = true;
    try {
        comments.value = await getTaskComments(props.task.id);
        await nextTick();
        scrollToBottom();
    } catch (e: any) {
        showError(e?.message || 'Failed to load comments');
    } finally {
        loadingComments.value = false;
    }
}

watch(() => props.task, () => {
    comments.value = [];
    newComment.value = '';
    loadComments();
});

// Theo dõi scroll để ẩn nút khi user tự cuộn xuống cuối
function onCommentsScroll() {
    if (isUserAtBottom()) {
        showNewMsgBtn.value = false;
    }
}

// Theo dõi thay đổi comments để ẩn nút khi user tự cuộn xuống cuối
watch(comments, () => {
    if (isUserAtBottom()) {
        showNewMsgBtn.value = false;
    }
}, { deep: true });

function scrollToBottom() {
    // Scroll cho desktop
    if (commentsListRef.value) {
        commentsListRef.value.scrollTop = commentsListRef.value.scrollHeight;
    }
    // Scroll cho mobile
    if (mobileCommentsListRef.value) {
        mobileCommentsListRef.value.scrollTop = mobileCommentsListRef.value.scrollHeight;
    }
}

// Gửi comment mới
async function sendComment() {
    if (!newComment.value.trim() || sendingComment.value) return;
    const tempId = 'temp-' + Date.now();
    const userData = JSON.parse(localStorage.getItem("userData") || '{}');
    const tempComment = {
        id: tempId,
        content: newComment.value,
        user: {
            id: currentUserId.value,
            name: userData.user?.name || 'You',
            avatar: userData.user?.avatar || ''
        },
        created_at: new Date().toISOString(),
        pending: true
    };
    comments.value.push(tempComment);
    const sendingText = newComment.value;
    newComment.value = '';
    sendingComment.value = true;
    try {
        const comment = await addTaskComment(props.task.id, sendingText);
        // Tìm và thay thế comment tạm bằng comment thật
        const idx = comments.value.findIndex(c => c.id === tempId);
        if (idx !== -1) {
            comments.value[idx] = comment;
        }
        await nextTick();
        scrollToBottom();
    } catch (e: any) {
        // Nếu lỗi, xóa comment tạm
        const idx = comments.value.findIndex(c => c.id === tempId);
        if (idx !== -1) {
            comments.value.splice(idx, 1);
        }
        showError(e?.message || 'Failed to send comment');
    } finally {
        sendingComment.value = false;
    }
}

// Xác định user đang ở gần cuối (dưới 150px)
function isUserAtBottom(threshold = 150) {
    // Kiểm tra desktop
    if (commentsListRef.value) {
        const el = commentsListRef.value;
        return Math.abs(el.scrollHeight - el.scrollTop - el.clientHeight) < threshold;
    }
    // Kiểm tra mobile
    if (mobileCommentsListRef.value) {
        const el = mobileCommentsListRef.value;
        return Math.abs(el.scrollHeight - el.scrollTop - el.clientHeight) < threshold;
    }
    return true;
}

// Xử lý khi nhận comment mới (realtime hoặc của chính mình)
async function handleNewRealtimeComment(comment: any, isMine = false) {
    if (!comments.value.find(c => c.id === comment.id)) {
        comments.value.push(comment);
        await nextTick();
        if (isMine || isUserAtBottom(150)) {
            scrollToBottom();
            showNewMsgBtn.value = false;
        } else {
            showNewMsgBtn.value = true;
        }
    }
}

// Khi user bấm nút 'New message'
function goToBottom() {
    scrollToBottom();
    showNewMsgBtn.value = false;
}

// Helper cho UI
function getStatusText(status: number) {
    if (status === 0) return 'Not Started';
    if (status === 1) return 'Pending';
    if (status === 2) return 'Completed';
    return '';
}
function getMemberName(member: any) {
    return member?.user?.name || member?.members?.name || member?.member?.name || member?.name || '';
}
function getMemberAvatar(member: any) {
    return member?.avatar || member?.user?.avatar || member?.members?.avatar || member?.member?.avatar || '';
}
function formatTime(dateStr: string) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const isToday =
        d.getFullYear() === now.getFullYear() &&
        d.getMonth() === now.getMonth() &&
        d.getDate() === now.getDate();
    if (isToday) {
        return d.toLocaleTimeString();
    }
    return d.toLocaleString();
}
</script>

<template>
    <div v-if="visible" class="modal-overlay">
        <div class="modal-content split-layout">
            <!-- Mobile Header với Close Button -->
            <div class="mobile-header">
                <h3 class="mobile-title">{{ task?.name }}</h3>
                <button class="close-btn mobile-close" @click="close">×</button>
            </div>

            <!-- Desktop Close Button (ẩn trên mobile) -->
            <button class="close-btn desktop-close" @click="close">×</button>
            <!-- Mobile Tab Navigation -->
            <div class="mobile-tabs">
                <button :class="['tab-btn', { active: activeTab === 'content' }]" @click="activeTab = 'content'">
                    <i class="bi bi-file-text"></i>
                    Content
                </button>
                <button :class="['tab-btn', { active: activeTab === 'discussion' }]" @click="activeTab = 'discussion'">
                    <i class="bi bi-chat-dots"></i>
                    Discussion
                </button>
            </div>

            <!-- Desktop Layout -->
            <div class="desktop-layout">
                <div class="task-info-col">
                    <h2>{{ task?.name }}</h2>
                    <div class="task-status">Status: <b>{{ getStatusText(task?.status) }}</b></div>
                    <div class="task-members">
                        <div v-for="m in task?.task_members" :key="m.id" class="member-avatar-box">
                            <img :src="getAvatarSrc(getMemberAvatar(m), getMemberName(m))" class="member-avatar-img"
                                :alt="getMemberName(m)" :title="getMemberName(m)" />
                        </div>
                    </div>
                    <div class="task-date">Created: {{ new Date(task?.created_at).toLocaleString() }}</div>
                    <div class="task-content">
                        <b>Content:</b>
                        {{ task?.content || task?.description || 'No content' }}
                    </div>
                </div>
                <div class="task-chat-col">
                    <div class="comments-section">
                        <h4>Discussion</h4>
                        <hr class="divider" />
                        <div class="comments-list" ref="commentsListRef" @scroll="onCommentsScroll">
                            <template v-if="comments.length === 0">
                                <div class="comments-placeholder">This is a chat for task discussion.</div>
                            </template>
                            <div v-for="c in comments" :key="c.id"
                                :class="['comment-item', String(c.user?.id || c.user_id) === currentUserIdStr ? 'my-message' : 'other-message']">
                                <div class="comment-bubble-wrap">
                                    <template v-if="String(c.user?.id || c.user_id) === currentUserIdStr">
                                        <div class="comment-bubble">
                                            <div class="comment-text">{{ c.content }}</div>
                                        </div>
                                        <img :src="getAvatarSrc(c.user?.avatar || '', c.user?.name || c.user?.email || '')"
                                            class="comment-avatar" />
                                    </template>
                                    <template v-else>
                                        <img v-if="c.user?.avatar"
                                            :src="getAvatarSrc(c.user.avatar, c.user?.name || c.user?.email || '')"
                                            class="comment-avatar" />
                                        <img v-else :src="getAvatarSrc('', c.user?.name || c.user?.email || '')"
                                            class="comment-avatar" />
                                        <div class="comment-bubble">
                                            <div class="comment-text">{{ c.content }}</div>
                                        </div>
                                    </template>
                                </div>
                                <div class="comment-time">{{ formatTime(c.created_at) }}</div>
                            </div>
                        </div>
                        <!-- Nút thông báo tin nhắn mới -->
                        <div v-if="showNewMsgBtn" class="new-msg-alert">
                            <button @click="goToBottom">New message</button>
                        </div>
                        <div class="comment-input">
                            <input v-model="newComment" @keyup.enter="sendComment" :disabled="sendingComment"
                                placeholder="Type a message..." />
                            <button @click="sendComment" :disabled="sendingComment">Send</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Layout -->
            <div class="mobile-layout">
                <!-- Content Tab -->
                <div v-if="activeTab === 'content'" class="mobile-content-tab">
                    <h2 class="mobile-content-title">{{ task?.name }}</h2>
                    <div class="task-status">Status: <b>{{ getStatusText(task?.status) }}</b></div>
                    <div class="task-members">
                        <div v-for="m in task?.task_members" :key="m.id" class="member-avatar-box">
                            <img :src="getAvatarSrc(getMemberAvatar(m), getMemberName(m))" class="member-avatar-img"
                                :alt="getMemberName(m)" :title="getMemberName(m)" />
                        </div>
                    </div>
                    <div class="task-date">Created: {{ new Date(task?.created_at).toLocaleString() }}</div>
                    <div class="task-content">
                        <b>Content:</b>
                        {{ task?.content || task?.description || 'No content' }}
                    </div>
                </div>

                <!-- Discussion Tab -->
                <div v-if="activeTab === 'discussion'" class="mobile-discussion-tab">
                    <div class="comments-section">
                        <div class="comments-list" ref="mobileCommentsListRef" @scroll="onCommentsScroll">
                            <template v-if="comments.length === 0">
                                <div class="comments-placeholder">This is a chat for task discussion.</div>
                            </template>
                            <div v-for="c in comments" :key="c.id"
                                :class="['comment-item', String(c.user?.id || c.user_id) === currentUserIdStr ? 'my-message' : 'other-message']">
                                <div class="comment-bubble-wrap">
                                    <template v-if="String(c.user?.id || c.user_id) === currentUserIdStr">
                                        <div class="comment-bubble">
                                            <div class="comment-text">{{ c.content }}</div>
                                        </div>
                                        <img :src="getAvatarSrc(c.user?.avatar || '', c.user?.name || c.user?.email || '')"
                                            class="comment-avatar" />
                                    </template>
                                    <template v-else>
                                        <img v-if="c.user?.avatar"
                                            :src="getAvatarSrc(c.user.avatar, c.user?.name || c.user?.email || '')"
                                            class="comment-avatar" />
                                        <img v-else :src="getAvatarSrc('', c.user?.name || c.user?.email || '')"
                                            class="comment-avatar" />
                                        <div class="comment-bubble">
                                            <div class="comment-text">{{ c.content }}</div>
                                        </div>
                                    </template>
                                </div>
                                <div class="comment-time">{{ formatTime(c.created_at) }}</div>
                            </div>
                        </div>
                        <!-- Nút thông báo tin nhắn mới -->
                        <div v-if="showNewMsgBtn" class="new-msg-alert">
                            <button @click="goToBottom">New message</button>
                        </div>
                        <div class="comment-input">
                            <input v-model="newComment" @keyup.enter="sendComment" :disabled="sendingComment"
                                placeholder="Type a message..." />
                            <button @click="sendComment" :disabled="sendingComment">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.25);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
    padding: 32px 18px 24px 18px;
    min-width: 540px;
    max-width: 1050px;
    min-height: 380px;
    position: relative;
    display: flex;
    flex-direction: row;
    gap: 0;
    top: 20px;
    transition: min-width 0.2s, min-height 0.2s, max-width 0.2s;
}

.split-layout {
    display: flex;
    flex-direction: row;
    gap: 0;
}

.task-info-col {
    flex: 1.1;
    padding-right: 28px;
    border-right: 1.5px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    gap: 18px;
    max-height: 550px;
    overflow-y: auto;
}

.task-chat-col {
    flex: 1.1;
    padding-left: 28px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    height: 550px;
}

/* Desktop Close Button */
.desktop-close {
    position: absolute;
    right: 18px;
    top: 12px;
    font-size: 1.6rem;
    background: none;
    border: none;
    color: #888;
    cursor: pointer;
    z-index: 10000;
}

/* Mobile Close Button */
.mobile-close {
    display: none;
    font-size: 1.4rem;
    color: #6b7280;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: background 0.2s ease;
}

.mobile-close:hover {
    background: #f3f4f6;
}

@media (max-width: 900px) {
    .modal-content {
        flex-direction: column;
        min-width: 90vw;
        max-width: 98vw;
        min-height: 500px;
        max-height: 98vh;
        padding: 18px 8px 18px 8px;
        box-sizing: border-box;
        /* Cố định chiều rộng/chiều cao khi chuyển tab */
        transition: none;
    }

    .mobile-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 20px;
        padding: 0 0 10px 0;
    }

    /* Ẩn desktop close button trên mobile */
    .desktop-close {
        display: none;
    }

    /* Hiển thị mobile close button */
    .mobile-close {
        display: block;
    }

    .mobile-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        flex: 1;
        text-align: left;
        padding-right: 0;
    }
}

.task-status {
    font-size: 1.1rem;
    margin-bottom: 4px;
}

.task-members {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 1rem;
    align-items: center;
    margin-bottom: 8px;
}

.member-avatar-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.member-avatar-img {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e0e7ef;
    background: #e0e7ef;
    transition: box-shadow 0.18s;
    cursor: pointer;
}

.member-avatar-img:hover {
    box-shadow: 0 2px 12px #2563eb44;
}

.member-avatar-fallback {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #e0e7ef;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 700;
}

.member-avatar-name {
    font-size: 0.95rem;
    color: #22223b;
    margin-top: 2px;
}

.task-date {
    font-size: 0.95rem;
    color: #888;
}

.task-content {
    font-size: 1.05rem;
    color: #333;
    margin-bottom: 8px;
    white-space: pre-line;
}

.comments-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.comments-list {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 12px;
    width: 100%;
}

.comment-item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
    /* Đảm bảo chiếm hết chiều ngang */
}

.my-message {
    align-items: flex-end;
}

.my-message .comment-bubble-wrap {
    flex-direction: row;
    justify-content: flex-end;
}

.my-message .comment-time {
    justify-content: flex-end;
}

.my-message .comment-bubble {
    background: #2563eb;
    color: #fff;
    border-radius: 18px 18px 6px 18px;
}

.other-message {
    align-items: flex-start;
}

.other-message .comment-bubble-wrap {
    flex-direction: row;
    justify-content: flex-start;
}

.other-message .comment-time {
    justify-content: flex-start;
}

.other-message .comment-bubble {
    background: #e6edf6;
    color: #22223b;
    border-radius: 18px 18px 18px 6px;
}

.divider {
    border: none;
    border-top: 1.5px solid #e5e7eb;
    margin: 6px 0 0px 0;
    box-shadow:
        0 2px 8px 0 #2563eb33,
        0 4px 16px 0 #b0b0b066,
        0 1.5px 0 #2563eb44;
}

.comment-bubble-wrap {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.my-message .comment-bubble-wrap {
    flex-direction: row;
    justify-content: flex-end;
}

.other-message .comment-bubble-wrap {
    flex-direction: row;
    justify-content: flex-start;
}

.comment-bubble {
    background: #f3f6fa;
    color: #22223b;
    border-radius: 18px 18px 6px 18px;
    padding: 12px 18px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    max-width: 340px;
    min-width: 60px;
    word-break: break-word;
    display: flex;
    flex-direction: column;
    position: relative;
}

.my-message .comment-bubble {
    background: #2563eb;
    color: #fff;
    border-radius: 18px 18px 6px 18px;
}

.comment-text {
    font-size: 1.08rem;
}

.comment-time {
    font-size: 0.80rem;
    color: #b0b0b0;
    margin-top: 4px;
    margin-bottom: 2px;
    width: 100%;
    display: flex;
}

.comment-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 1.5px solid #e0e7ef;
    background: #e0e7ef;
    margin-bottom: 2px;
    margin-top: 8px;
}

.comment-input {
    display: flex;
    gap: 10px;
    margin-top: 0;
    margin-bottom: 0;
    background: #f8fafc;
    border-radius: 16px;
    padding: 10px 14px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.comment-input input {
    flex: 1;
    border-radius: 10px;
    border: 1.5px solid #e0e7ef;
    padding: 10px 16px;
    font-size: 1.05rem;
    background: transparent;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
}

.comment-input button {
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 22px;
    font-size: 1.05rem;
    cursor: pointer;
    transition: background 0.18s;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.comment-input button:hover {
    background: #1d4ed8;
}

.new-msg-alert {
    display: flex;
    justify-content: center;
    margin-bottom: 6px;
}

.new-msg-alert button {
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 18px;
    padding: 6px 18px;
    font-size: 1rem;
    box-shadow: 0 2px 8px #2563eb33;
    cursor: pointer;
    transition: background 0.18s;
}

.new-msg-alert button:hover {
    background: #1d4ed8;
}

.comments-placeholder {
    color: #b0b0b0;
    font-size: 1.08rem;
    text-align: center;
    margin-top: 32px;
    font-style: italic;
    opacity: 0.85;
}

/* Mobile Header */
.mobile-header {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 0 0 16px 0;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 20px;
}

.mobile-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    flex: 1;
    text-align: center;
    padding-right: 40px;
    /* Để tránh overlap với close button */
}

/* Mobile Tab Navigation */
.mobile-tabs {
    display: none;
    width: 100%;
    background: #f8fafc;
    border-radius: 12px;
    padding: 6px;
    margin-bottom: 20px;
    gap: 6px;
}

.tab-btn {
    flex: 1;
    padding: 14px 16px;
    border: none;
    background: transparent;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.tab-btn i {
    font-size: 1.1rem;
}

.tab-btn.active {
    background: #2563eb;
    color: #fff;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.tab-btn:hover:not(.active) {
    background: #e5e7eb;
    color: #374151;
}

/* Desktop Layout */
.desktop-layout {
    display: flex;
    flex-direction: row;
    gap: 0;
    width: 100%;
}

/* Mobile Layout */
.mobile-layout {
    display: none;
    width: 100%;
    min-height: 400px;
}

.mobile-content-tab {
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 18px;
    height: 500px;
    overflow-y: auto;
}

.mobile-discussion-tab {
    display: flex;
    flex-direction: column;
    height: 500px;
}

.mobile-discussion-tab .comments-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.mobile-discussion-tab .comments-list {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 12px;
    width: 100%;
}

@media (max-width: 900px) {
    .modal-content {
        flex-direction: column;
        min-width: 90vw;
        max-width: 98vw;
        padding: 18px 8px 18px 8px;
        min-height: 500px;
    }

    .split-layout {
        top: 23px;
        flex-direction: column;
    }

    /* Hide desktop layout on mobile */
    .desktop-layout {
        display: none;
    }

    /* Show mobile layout on mobile */
    .mobile-layout {
        display: block;
    }

    /* Show mobile header and tabs on mobile */
    .mobile-header {
        display: flex;
    }

    .mobile-tabs {
        display: flex;
    }

    .task-info-col {
        border-right: none;
        border-bottom: 1.5px solid #e5e7eb;
        padding-right: 0;
        padding-bottom: 18px;
        margin-bottom: 18px;
    }

    .task-chat-col {
        padding-left: 0;
    }

    /* Mobile specific adjustments */
    .mobile-content-title {
        display: none;
        /* Ẩn title trong content tab vì đã có ở header */
    }

    .mobile-content-tab {
        padding: 12px;
        height: 500px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .mobile-discussion-tab {
        height: 500px;
        display: flex;
        flex-direction: column;
    }

    .mobile-content-tab .task-status {
        font-size: 1rem;
        margin-bottom: 8px;
    }

    .mobile-content-tab .task-members {
        gap: 8px;
        margin-bottom: 12px;
    }

    .mobile-content-tab .member-avatar-img {
        width: 32px;
        height: 32px;
    }

    .mobile-content-tab .task-date {
        font-size: 0.9rem;
        margin-bottom: 12px;
    }

    .mobile-content-tab .task-content {
        font-size: 1rem;
        line-height: 1.5;
    }

    /* Mobile discussion tab adjustments */
    .mobile-discussion-tab .comment-bubble {
        max-width: 280px;
        padding: 10px 14px;
    }

    .mobile-discussion-tab .comment-text {
        font-size: 1rem;
    }

    .mobile-discussion-tab .comment-input {
        margin-top: 12px;
        padding: 8px 12px;
    }

    .mobile-discussion-tab .comment-input input {
        font-size: 1rem;
        padding: 8px 12px;
    }

    .mobile-discussion-tab .comment-input button {
        padding: 8px 16px;
        font-size: 1rem;
    }
}
</style>