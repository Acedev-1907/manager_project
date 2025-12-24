<template>
  <div class="ai-chat-page">
    <div class="ai-chat-sidebar">
      <div class="ai-chat-sidebar-header">
        <h2>AI Assistant</h2>
        <button class="ai-new-chat-btn" @click="createNewConversation">
          <i class="fas fa-plus"></i> Cuộc hội thoại mới
        </button>
      </div>
      <div class="ai-chat-conversation-list">
        <div
          v-for="conv in conversations"
          :key="conv.id"
          class="ai-chat-conversation-item"
          :class="{ active: activeConversationId === conv.id }"
          @click="openConversation(conv)"
        >
          <div class="ai-chat-conversation-info">
            <div class="ai-chat-conversation-top">
              <i class="fas fa-robot ai-icon"></i>
              <span class="ai-chat-title">{{ conv.title || 'Cuộc hội thoại mới' }}</span>
            </div>
            <div class="ai-chat-conversation-bottom">
              <span class="ai-chat-last-message">
                {{ getLastMessagePreview(conv) }}
              </span>
              <span class="ai-chat-time">
                {{ formatTime(conv.updated_at) }}
              </span>
            </div>
          </div>
          <button
            class="ai-delete-btn"
            @click.stop="handleDeleteConversation(conv.id)"
          >
            <i class="fas fa-trash"></i>
          </button>
        </div>
        <div
          v-if="conversations.length === 0 && !loading"
          class="ai-chat-empty"
        >
          Chưa có cuộc hội thoại nào. Tạo cuộc hội thoại mới để bắt đầu.
        </div>
      </div>
    </div>

    <div class="ai-chat-main">
      <div v-if="!activeConversation" class="ai-chat-main-empty">
        <div class="ai-empty-content">
          <i class="fas fa-robot ai-empty-icon"></i>
          <h3>Chào mừng đến với AI Assistant</h3>
          <p>Hãy chọn một cuộc hội thoại hoặc tạo cuộc hội thoại mới để bắt đầu</p>
          <button class="ai-start-btn" @click="createNewConversation">
            Bắt đầu trò chuyện
          </button>
        </div>
      </div>

      <div v-else class="ai-chat-main-content">
        <div class="ai-chat-main-header">
          <div class="ai-chat-main-info">
            <i class="fas fa-robot ai-header-icon"></i>
            <div>
              <div class="ai-chat-name">{{ activeConversation.title || 'AI Assistant' }}</div>
              <div class="ai-chat-subtitle">Trợ lý ảo thông minh</div>
            </div>
          </div>
          <button
            class="ai-delete-conversation-btn"
            @click="handleDeleteConversation(activeConversation.id)"
          >
            <i class="fas fa-trash"></i> Xóa
          </button>
        </div>

        <div ref="messageContainer" class="ai-chat-messages">
          <div
            v-for="msg in messages"
            :key="msg.id"
            class="ai-chat-message-row"
            :class="{
              'from-user': msg.role === 'user',
              'from-ai': msg.role === 'assistant'
            }"
          >
            <div class="ai-chat-message-bubble">
              <div class="ai-chat-message-text" v-html="formatMessage(msg.content)"></div>
              <div class="ai-chat-message-time">
                {{ formatTime(msg.created_at) }}
              </div>
            </div>
          </div>
          <div v-if="sending" class="ai-chat-typing-indicator">
            <div class="typing-dots">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>

        <form class="ai-chat-input-area" @submit.prevent="handleSend">
          <textarea
            v-model="newMessage"
            class="ai-chat-input"
            placeholder="Nhập câu hỏi của bạn..."
            rows="1"
            @keydown.enter.exact.prevent="handleSend"
            @keydown.enter.shift.exact="handleNewLine"
          ></textarea>
          <button
            type="submit"
            class="ai-chat-send-btn"
            :disabled="sending || !newMessage.trim() || !activeConversation"
          >
            {{ sending ? "Đang gửi..." : "Gửi" }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, nextTick, watch } from "vue";
import { makeHttpReq } from "../../../helper/makeHttpReq";
import { showConfirm, showError } from "../../../helper/alert";

interface AIConversation {
  id: number;
  title: string | null;
  type: string;
  project_id?: number | null;
  updated_at: string;
  last_message?: {
    content: string;
    created_at: string;
  };
}

interface AIMessage {
  id: number;
  ai_conversation_id: number;
  role: 'user' | 'assistant' | 'system';
  content: string;
  created_at: string;
  metadata?: any;
}

const conversations = ref<AIConversation[]>([]);
const messages = ref<AIMessage[]>([]);
const activeConversation = ref<AIConversation | null>(null);
const activeConversationId = ref<number | null>(null);
const newMessage = ref("");
const sending = ref(false);
const loading = ref(false);
const messageContainer = ref<HTMLElement | null>(null);

onMounted(() => {
  fetchConversations();
});

// Watch for active conversation changes
watch(activeConversationId, (newId) => {
  if (newId) {
    fetchMessages(newId);
  } else {
    messages.value = [];
  }
});

async function fetchConversations() {
  loading.value = true;
  try {
    const res = await makeHttpReq<never, any>('/ai/conversations', 'GET');
    const data = res?.data || res;
    
    if (data?.data && Array.isArray(data.data)) {
      conversations.value = data.data;
    } else if (Array.isArray(data)) {
      conversations.value = data;
    }
  } catch (e: any) {
    console.error('Failed to fetch conversations:', e);
    showError(e?.message || 'Không thể tải danh sách cuộc hội thoại');
  } finally {
    loading.value = false;
  }
}

async function fetchMessages(conversationId: number) {
  try {
    const res = await makeHttpReq<never, any>(`/ai/conversations/${conversationId}/messages`, 'GET');
    const data = res?.data || res;
    
    if (data?.data && Array.isArray(data.data)) {
      messages.value = data.data;
    } else if (Array.isArray(data)) {
      messages.value = data;
    }
    
    scrollToBottom();
  } catch (e: any) {
    console.error('Failed to fetch messages:', e);
    showError(e?.message || 'Không thể tải tin nhắn');
  }
}

async function createNewConversation() {
  try {
    const res = await makeHttpReq<any, any>('/ai/conversations', 'POST', {
      type: 'general',
      title: null
    });
    
    const conversation = res?.data || res;
    conversations.value.unshift(conversation);
    openConversation(conversation);
  } catch (e: any) {
    console.error('Failed to create conversation:', e);
    showError(e?.message || 'Không thể tạo cuộc hội thoại mới');
  }
}

function openConversation(conversation: AIConversation) {
  activeConversation.value = conversation;
  activeConversationId.value = conversation.id;
}

async function handleSend() {
  if (!activeConversation.value || !newMessage.value.trim() || sending.value) return;
  
  const messageText = newMessage.value.trim();
  newMessage.value = "";
  sending.value = true;

  try {
    const res = await makeHttpReq<any, any>(
      `/ai/conversations/${activeConversation.value.id}/messages`,
      'POST',
      { message: messageText }
    );

    const data = res?.data || res;
    
    // Add user message
    if (data?.user_message) {
      messages.value.push(data.user_message);
    }
    
    // Add AI message
    if (data?.ai_message) {
      messages.value.push(data.ai_message);
    }
    
    scrollToBottom();
    
    // Refresh conversations list to update last message
    fetchConversations();
  } catch (e: any) {
    // Restore message on error
    newMessage.value = messageText;
    showError(e?.message || 'Không thể gửi tin nhắn');
  } finally {
    sending.value = false;
  }
}

async function handleDeleteConversation(conversationId: number) {
  const confirmed = await showConfirm(
    'Bạn có chắc chắn muốn xóa cuộc hội thoại này?',
    'Xóa cuộc hội thoại'
  );
  
  if (!confirmed) return;

  try {
    await makeHttpReq<never, any>(`/ai/conversations/${conversationId}`, 'DELETE');
    
    // Remove from list
    conversations.value = conversations.value.filter(c => c.id !== conversationId);
    
    // Clear active if deleted
    if (activeConversationId.value === conversationId) {
      activeConversation.value = null;
      activeConversationId.value = null;
      messages.value = [];
    }
  } catch (e: any) {
    showError(e?.message || 'Không thể xóa cuộc hội thoại');
  }
}

function getLastMessagePreview(conversation: AIConversation): string {
  if (conversation.last_message?.content) {
    const content = conversation.last_message.content;
    return content.length > 50 ? content.substring(0, 50) + '...' : content;
  }
  return 'Chưa có tin nhắn';
}

function formatTime(dateString: string): string {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diff = now.getTime() - date.getTime();
  const seconds = Math.floor(diff / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);

  if (days > 0) {
    return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
  } else if (hours > 0) {
    return `${hours} giờ trước`;
  } else if (minutes > 0) {
    return `${minutes} phút trước`;
  } else {
    return 'Vừa xong';
  }
}

function formatMessage(content: string): string {
  // Simple markdown-like formatting
  return content
    .replace(/\n/g, '<br>')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>');
}

function scrollToBottom() {
  nextTick(() => {
    if (messageContainer.value) {
      messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
    }
  });
}

function handleNewLine() {
  // Allow Shift+Enter to add new line
  // Do nothing, let browser handle it
}
</script>

<style scoped>
.ai-chat-page {
  display: flex;
  height: calc(100vh - 60px);
  background: #f5f5f5;
}

/* Sidebar */
.ai-chat-sidebar {
  width: 350px;
  background: white;
  border-right: 1px solid #e0e0e0;
  display: flex;
  flex-direction: column;
}

.ai-chat-sidebar-header {
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.ai-chat-sidebar-header h2 {
  margin: 0 0 15px 0;
  font-size: 24px;
  color: #333;
}

.ai-new-chat-btn {
  width: 100%;
  padding: 10px;
  background: #4a90e2;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.ai-new-chat-btn:hover {
  background: #357abd;
}

.ai-chat-conversation-list {
  flex: 1;
  overflow-y: auto;
}

.ai-chat-conversation-item {
  padding: 15px 20px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: background 0.2s;
}

.ai-chat-conversation-item:hover {
  background: #f9f9f9;
}

.ai-chat-conversation-item.active {
  background: #e8f4fd;
  border-left: 3px solid #4a90e2;
}

.ai-chat-conversation-info {
  flex: 1;
  min-width: 0;
}

.ai-chat-conversation-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 5px;
}

.ai-icon {
  color: #4a90e2;
  font-size: 16px;
}

.ai-chat-title {
  font-weight: 600;
  color: #333;
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ai-chat-conversation-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.ai-chat-last-message {
  font-size: 12px;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

.ai-chat-time {
  font-size: 11px;
  color: #999;
  white-space: nowrap;
}

.ai-delete-btn {
  padding: 5px 10px;
  background: transparent;
  border: none;
  color: #999;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.2s;
}

.ai-chat-conversation-item:hover .ai-delete-btn {
  opacity: 1;
}

.ai-delete-btn:hover {
  color: #e74c3c;
}

.ai-chat-empty {
  padding: 40px 20px;
  text-align: center;
  color: #999;
}

/* Main Chat Area */
.ai-chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: white;
}

.ai-chat-main-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ai-empty-content {
  text-align: center;
  max-width: 400px;
}

.ai-empty-icon {
  font-size: 64px;
  color: #4a90e2;
  margin-bottom: 20px;
}

.ai-empty-content h3 {
  margin: 0 0 10px 0;
  color: #333;
}

.ai-empty-content p {
  color: #666;
  margin-bottom: 20px;
}

.ai-start-btn {
  padding: 12px 24px;
  background: #4a90e2;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
}

.ai-start-btn:hover {
  background: #357abd;
}

.ai-chat-main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.ai-chat-main-header {
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ai-chat-main-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.ai-header-icon {
  font-size: 24px;
  color: #4a90e2;
}

.ai-chat-name {
  font-weight: 600;
  color: #333;
  font-size: 16px;
}

.ai-chat-subtitle {
  font-size: 12px;
  color: #999;
}

.ai-delete-conversation-btn {
  padding: 8px 16px;
  background: transparent;
  border: 1px solid #e0e0e0;
  color: #e74c3c;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.ai-delete-conversation-btn:hover {
  background: #fff5f5;
}

.ai-chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.ai-chat-message-row {
  display: flex;
  width: 100%;
}

.ai-chat-message-row.from-user {
  justify-content: flex-end;
}

.ai-chat-message-row.from-ai {
  justify-content: flex-start;
}

.ai-chat-message-bubble {
  max-width: 70%;
  padding: 12px 16px;
  border-radius: 18px;
  position: relative;
}

.from-user .ai-chat-message-bubble {
  background: #4a90e2;
  color: white;
  border-bottom-right-radius: 4px;
}

.from-ai .ai-chat-message-bubble {
  background: #f0f0f0;
  color: #333;
  border-bottom-left-radius: 4px;
}

.ai-chat-message-text {
  line-height: 1.5;
  word-wrap: break-word;
}

.from-ai .ai-chat-message-text {
  white-space: pre-wrap;
}

.ai-chat-message-time {
  font-size: 11px;
  margin-top: 5px;
  opacity: 0.7;
}

.ai-chat-typing-indicator {
  display: flex;
  justify-content: flex-start;
}

.typing-dots {
  display: flex;
  gap: 4px;
  padding: 12px 16px;
  background: #f0f0f0;
  border-radius: 18px;
}

.typing-dots span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #999;
  animation: typing 1.4s infinite;
}

.typing-dots span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
    opacity: 0.7;
  }
  30% {
    transform: translateY(-10px);
    opacity: 1;
  }
}

.ai-chat-input-area {
  padding: 20px;
  border-top: 1px solid #e0e0e0;
  display: flex;
  gap: 10px;
  align-items: flex-end;
}

.ai-chat-input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 24px;
  font-size: 14px;
  font-family: inherit;
  resize: none;
  max-height: 120px;
  min-height: 44px;
}

.ai-chat-input:focus {
  outline: none;
  border-color: #4a90e2;
}

.ai-chat-send-btn {
  padding: 12px 24px;
  background: #4a90e2;
  color: white;
  border: none;
  border-radius: 24px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  min-width: 80px;
}

.ai-chat-send-btn:hover:not(:disabled) {
  background: #357abd;
}

.ai-chat-send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>

