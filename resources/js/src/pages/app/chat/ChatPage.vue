<template>
  <div class="chat-page">
    <div class="chat-sidebar">
      <div class="chat-sidebar-header">
        <h2>Chat</h2>
        <input
          v-model="search"
          type="text"
          placeholder="Tìm bạn bè..."
          class="chat-search"
          @keyup.enter="fetchMembers"
        />
      </div>
      <div class="chat-conversation-list">
        <div
          v-for="item in conversations"
          :key="item.other_user.id"
          class="chat-conversation-item"
          :class="{ active: activeUserId === item.other_user.id }"
          @click="openConversation(item.other_user)"
        >
          <img
            :src="item.other_user.avatar || defaultAvatar"
            class="chat-avatar"
            alt="avatar"
          />
          <div class="chat-conversation-info">
            <div class="chat-conversation-top">
              <span class="chat-name">{{ item.other_user.name }}</span>
              <span class="chat-time">
                {{ formatTime(item.last_message?.created_at) }}
              </span>
            </div>
            <div class="chat-conversation-bottom">
              <span class="chat-last-message">
                {{ item.last_message?.body || "Bắt đầu cuộc trò chuyện" }}
              </span>
              <span
                v-if="item.unread_count > 0"
                class="chat-unread-badge"
              >
                {{ item.unread_count }}
              </span>
            </div>
          </div>
        </div>
        <div
          v-if="conversations.length === 0 && !loading"
          class="chat-empty"
        >
          Chưa có cuộc trò chuyện nào. Hãy chọn bạn bè để bắt đầu chat.
        </div>
      </div>
    </div>

    <div class="chat-main">
      <div v-if="!activeUser" class="chat-main-empty">
        <p>Chọn một bạn bè ở bên trái để bắt đầu chat</p>
      </div>

      <div v-else class="chat-main-content">
        <div class="chat-main-header">
          <div class="chat-main-user">
            <img
              :src="activeUser.avatar || defaultAvatar"
              class="chat-avatar-large"
              alt="avatar"
            />
            <div>
              <div class="chat-name">{{ activeUser.name }}</div>
              <div class="chat-email">{{ activeUser.email }}</div>
            </div>
          </div>
        </div>

        <div ref="messageContainer" class="chat-messages">
          <div
            v-for="msg in messages"
            :key="msg.id"
            class="chat-message-row"
            :class="{
              'from-me': msg.sender_id === currentUserId,
              'from-them': msg.sender_id !== currentUserId
            }"
          >
            <div class="chat-message-bubble">
              <div class="chat-message-text">{{ msg.body }}</div>
              <div class="chat-message-time">
                {{ formatTime(msg.created_at) }}
              </div>
            </div>
          </div>
        </div>

        <form class="chat-input-area" @submit.prevent="handleSend">
          <textarea
            v-model="newMessage"
            class="chat-input"
            placeholder="Nhập tin nhắn..."
            rows="1"
            @keydown.enter.exact.prevent="handleSend"
          ></textarea>
          <button
            type="submit"
            class="chat-send-btn"
            :disabled="sending || !newMessage.trim() || !activeUser"
          >
            {{ sending ? "Đang gửi..." : "Gửi" }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import eventBus from "../../../helper/eventBus";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { getUserData } from "../../../helper/getUserData";

interface UserBrief {
  id: number;
  name: string;
  email: string;
  avatar?: string | null;
}

interface ConversationItem {
  id: number;
  other_user: UserBrief;
  last_message: {
    id: number;
    sender_id: number;
    receiver_id: number;
    body: string;
    created_at: string;
    read_at?: string | null;
  } | null;
  unread_count: number;
}

interface MessageItem {
  id: number;
  conversation_id: number;
  sender_id: number;
  receiver_id: number;
  body: string;
  created_at: string;
  read_at?: string | null;
}

const conversations = ref<ConversationItem[]>([]);
const messages = ref<MessageItem[]>([]);
const activeUser = ref<UserBrief | null>(null);
const activeUserId = ref<number | null>(null);
const newMessage = ref("");
const search = ref("");
const loading = ref(false);
const sending = ref(false);

const messageContainer = ref<HTMLElement | null>(null);

// Debounce để tránh gọi fetchConversations quá nhiều lần
let fetchConversationsTimeout: ReturnType<typeof setTimeout> | null = null;

const defaultAvatar =
  "https://ui-avatars.com/api/?background=0D8ABC&color=fff&name=User";

const userData = getUserData();
const currentUserId = userData?.user?.id || null;
const route = useRoute();

function formatTime(value?: string | null) {
  if (!value) return "";
  try {
    const d = new Date(value);
    return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  } catch {
    return "";
  }
}

async function fetchConversations() {
  // Clear timeout nếu có
  if (fetchConversationsTimeout) {
    clearTimeout(fetchConversationsTimeout);
    fetchConversationsTimeout = null;
  }

  loading.value = true;
  try {
    const res = await makeHttpReq<any, any>("/chat/conversations", "GET", {});
    conversations.value = res?.data?.data || res?.data || [];
    
    // Nếu có user_id trong query, tự động mở conversation với user đó
    const userIdFromQuery = route.query.user_id;
    if (userIdFromQuery && !activeUser.value) {
      const userId = parseInt(String(userIdFromQuery));
      if (userId && !isNaN(userId)) {
        // Tìm user trong danh sách conversations
        const foundConversation = conversations.value.find(
          (conv) => conv.other_user.id === userId
        );
        
        if (foundConversation) {
          // Nếu đã có conversation, mở luôn
          openConversation(foundConversation.other_user);
        } else {
          // Nếu chưa có conversation, fetch thông tin user từ API members
          try {
            const membersRes = await makeHttpReq<any, any>(
              "/members",
              "GET",
              { query: "", per_page: 100, page: 1 }
            );
            const membersList = membersRes?.data?.data?.data || membersRes?.data?.data || [];
            const foundMember = membersList.find((m: any) => m.id === userId);
            
            if (foundMember) {
              const userBrief: UserBrief = {
                id: foundMember.id,
                name: foundMember.name,
                email: foundMember.email,
                avatar: foundMember.avatar,
              };
              openConversation(userBrief);
            }
          } catch (e) {
            // Silent error
          }
        }
      }
    }
  } catch (e) {
    // Silent error
  } finally {
    loading.value = false;
  }
}

// Debounced version để dùng trong realtime handler
function debouncedFetchConversations() {
  if (fetchConversationsTimeout) {
    clearTimeout(fetchConversationsTimeout);
  }
  fetchConversationsTimeout = setTimeout(() => {
    fetchConversations();
  }, 300);
}

async function fetchMessagesForUser(user: UserBrief) {
  if (!user) return;
  try {
    const res = await makeHttpReq<any, any>(
      "/chat/messages",
      "GET",
      { user_id: user.id }
    );
    const list =
      res?.data?.messages?.data ||
      res?.data?.messages ||
      res?.messages?.data ||
      res?.messages ||
      [];
    messages.value = list;
    scrollToBottom();
  } catch (e) {
    // silent
  }
}

async function fetchMembers() {
  // Optional: có thể dùng search để lọc bạn bè nếu cần
  await fetchConversations();
}

function openConversation(user: UserBrief) {
  activeUser.value = user;
  activeUserId.value = user.id;
  fetchMessagesForUser(user);
}

function scrollToBottom() {
  requestAnimationFrame(() => {
    if (messageContainer.value) {
      messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
    }
  });
}

async function handleSend() {
  if (!activeUser.value || !newMessage.value.trim() || sending.value) return;
  const body = newMessage.value.trim();
  newMessage.value = "";
  sending.value = true;

  try {
    const res = await makeHttpReq<any, any>("/chat/messages", "POST", {
      receiver_id: activeUser.value.id,
      body,
    });

    const msg = res?.data || res;
    if (msg && msg.id) {
      messages.value.push(msg);
      scrollToBottom();
      // Cập nhật lại danh sách conversation (last message, unread)
      fetchConversations();
    }
  } catch (e) {
    // Nếu lỗi thì trả lại nội dung
    newMessage.value = body;
  } finally {
    sending.value = false;
  }
}

function handleRealtimeMessage(e: any) {
  const message = e?.message;
  if (!message) return;

  const currentUser = getUserData();
  const myUserId = currentUser?.user?.id;
  
  // Chỉ xử lý tin nhắn gửi cho mình (receiver_id là mình)
  if (!myUserId || message.receiver_id !== myUserId) {
    // Nếu không phải tin nhắn gửi cho mình, chỉ cập nhật conversation list (debounced)
    debouncedFetchConversations();
    return;
  }

  // Nếu đang mở đúng cuộc hội thoại với người gửi, append luôn tin nhắn
  if (activeUserId.value && message.sender_id === activeUserId.value) {
    const exists = messages.value.some((m) => m.id === message.id);
    if (!exists) {
      messages.value.push({
        id: message.id,
        conversation_id: message.conversation_id,
        sender_id: message.sender_id,
        receiver_id: message.receiver_id,
        body: message.body,
        created_at: message.created_at,
        read_at: message.read_at,
      });
      scrollToBottom();
    }
  }

  // Cập nhật lại sidebar (unread, last message) khi có tin nhắn mới (debounced)
  debouncedFetchConversations();
}

onMounted(() => {
  fetchConversations();
  eventBus.on("chat-new-message", handleRealtimeMessage);
});

onUnmounted(() => {
  eventBus.off("chat-new-message", handleRealtimeMessage);
  // Clear timeout khi unmount
  if (fetchConversationsTimeout) {
    clearTimeout(fetchConversationsTimeout);
    fetchConversationsTimeout = null;
  }
});
</script>

<style scoped>
.chat-page {
  display: flex;
  height: calc(100vh - 80px);
  background: #f7f7f9;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.1);
}

.chat-sidebar {
  width: 320px;
  border-right: 1px solid #e5e7eb;
  background: #fff;
  display: flex;
  flex-direction: column;
}

.chat-sidebar-header {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.chat-sidebar-header h2 {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 600;
}

.chat-search {
  width: 100%;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  font-size: 14px;
}

.chat-conversation-list {
  flex: 1;
  overflow-y: auto;
}

.chat-conversation-item {
  display: flex;
  padding: 10px 12px;
  cursor: pointer;
  transition: background 0.15s ease;
}

.chat-conversation-item:hover {
  background: #f3f4f6;
}

.chat-conversation-item.active {
  background: #e5f0ff;
}

.chat-avatar {
  width: 40px;
  height: 40px;
  border-radius: 999px;
  object-fit: cover;
  margin-right: 10px;
}

.chat-avatar-large {
  width: 46px;
  height: 46px;
  border-radius: 999px;
  object-fit: cover;
  margin-right: 12px;
}

.chat-conversation-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.chat-conversation-top {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.chat-name {
  font-weight: 600;
  font-size: 14px;
  color: #111827;
}

.chat-time {
  font-size: 12px;
  color: #9ca3af;
  margin-left: 8px;
  white-space: nowrap;
}

.chat-conversation-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chat-last-message {
  font-size: 13px;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  margin-right: 8px;
}

.chat-unread-badge {
  background: #2563eb;
  color: white;
  border-radius: 999px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 600;
}

.chat-empty {
  padding: 16px;
  font-size: 13px;
  color: #6b7280;
}

.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.chat-main-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  font-size: 14px;
}

.chat-main-content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.chat-main-header {
  padding: 14px 16px;
  border-bottom: 1px solid #e5e7eb;
  background: #fff;
}

.chat-main-user {
  display: flex;
  align-items: center;
}

.chat-email {
  font-size: 12px;
  color: #9ca3af;
}

.chat-messages {
  flex: 1;
  padding: 16px;
  overflow-y: auto;
  background: #f9fafb;
}

.chat-message-row {
  display: flex;
  margin-bottom: 8px;
}

.chat-message-row.from-me {
  justify-content: flex-end;
}

.chat-message-row.from-them {
  justify-content: flex-start;
}

.chat-message-bubble {
  max-width: 70%;
  padding: 8px 12px;
  border-radius: 12px;
  font-size: 14px;
  line-height: 1.4;
  box-shadow: 0 2px 4px rgba(15, 23, 42, 0.08);
}

.from-me .chat-message-bubble {
  background: #2563eb;
  color: white;
  border-bottom-right-radius: 4px;
}

.from-them .chat-message-bubble {
  background: #ffffff;
  color: #111827;
  border-bottom-left-radius: 4px;
}

.chat-message-text {
  white-space: pre-wrap;
  word-wrap: break-word;
}

.chat-message-time {
  margin-top: 4px;
  font-size: 11px;
  opacity: 0.8;
  text-align: right;
}

.chat-input-area {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  gap: 8px;
}

.chat-input {
  flex: 1;
  border-radius: 999px;
  resize: none;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  font-size: 14px;
  max-height: 120px;
}

.chat-send-btn {
  border-radius: 999px;
  padding: 8px 18px;
  border: none;
  background: #2563eb;
  color: white;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s ease, transform 0.05s ease;
}

.chat-send-btn:hover:not(:disabled) {
  background: #1d4ed8;
}

.chat-send-btn:disabled {
  opacity: 0.5;
  cursor: default;
}

@media (max-width: 1024px) {
  .chat-page {
    flex-direction: column;
  }

  .chat-sidebar {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid #e5e7eb;
    height: 260px;
  }

  .chat-main {
    height: calc(100vh - 260px - 80px);
  }
}
</style>


