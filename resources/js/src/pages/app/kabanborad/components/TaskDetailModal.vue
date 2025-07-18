<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';
const props = defineProps<{ visible: boolean, task: any }>();
const emit = defineEmits(['close']);
const comments = ref<any[]>([]);
const newComment = ref('');
function close() { emit('close'); }
function sendComment() {
    if (newComment.value.trim()) {
        comments.value.push({ id: Date.now(), user: 'You', text: newComment.value });
        newComment.value = '';
    }
}
function getStatusText(status: number) {
    if (status === 0) return 'Not Started';
    if (status === 1) return 'Pending';
    if (status === 2) return 'Completed';
    return '';
}
watch(() => props.task, () => { comments.value = []; }); // reset comment khi đổi task

function getMemberName(member: any) {
    return member?.user?.name || member?.members?.name || member?.member?.name || member?.name || '';
}
function getMemberAvatar(member: any) {
    return member?.avatar || member?.user?.avatar || member?.members?.avatar || member?.member?.avatar || '';
}
</script>

<template>
    <div v-if="visible" class="modal-overlay">
        <div class="modal-content split-layout">
            <button class="close-btn" @click="close">×</button>
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
            </div>
            <div class="task-chat-col">
                <div class="comments-section">
                    <h4>Discussion</h4>
                    <div class="comments-list">
                        <div v-for="c in comments" :key="c.id"
                            :class="['comment-item', c.user === 'You' ? 'my-message' : 'other-message']">
                            <span class="comment-user">{{ c.user }}</span>
                            <span class="comment-text">{{ c.text }}</span>
                        </div>
                    </div>
                    <div class="comment-input">
                        <input v-model="newComment" @keyup.enter="sendComment" placeholder="Type a message..." />
                        <button @click="sendComment">Send</button>
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
    z-index: 2000;
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
}

.task-chat-col {
    flex: 1.2;
    padding-left: 28px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.close-btn {
    position: absolute;
    right: 18px;
    top: 12px;
    font-size: 1.6rem;
    background: none;
    border: none;
    color: #888;
    cursor: pointer;
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

.comments-section {
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.comments-list {
    max-height: 220px;
    overflow-y: auto;
    margin-bottom: 8px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
    padding-right: 2px;
}

.comment-item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    max-width: 80%;
    padding: 7px 14px;
    border-radius: 16px;
    font-size: 1rem;
    background: #f3f4f6;
    color: #22223b;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
    word-break: break-word;
}

.my-message {
    align-self: flex-end;
    background: #2563eb;
    color: #fff;
}

.other-message {
    align-self: flex-start;
    background: #f3f4f6;
    color: #22223b;
}

.comment-user {
    font-weight: 600;
    font-size: 0.93rem;
    margin-bottom: 2px;
}

.comment-text {
    font-size: 1rem;
}

.comment-input {
    display: flex;
    gap: 8px;
    margin-top: 8px;
    background: #f8fafc;
    border-radius: 12px;
    padding: 7px 10px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.comment-input input {
    flex: 1;
    border-radius: 8px;
    border: 1px solid #e0e7ef;
    padding: 7px 12px;
    font-size: 1rem;
    background: transparent;
}

.comment-input button {
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 7px 18px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.18s;
}

.comment-input button:hover {
    background: #1d4ed8;
}

@media (max-width: 900px) {
    .modal-content {
        flex-direction: column;
        min-width: 90vw;
        max-width: 98vw;
        padding: 18px 8px 18px 8px;
    }

    .split-layout {
        flex-direction: column;
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
}
</style>