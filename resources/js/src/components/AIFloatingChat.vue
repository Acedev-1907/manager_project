<template>
  <div class="ai-floating-chat">
    <!-- Floating Button -->
    <button 
      class="ai-chat-float-btn" 
      @click="toggleChat"
      :class="{ 'chat-open': isChatOpen }"
      title="Chat với AI"
    >
      <i v-if="!isChatOpen" class="fas fa-robot"></i>
      <i v-else class="fas fa-times"></i>
    </button>

    <!-- Chat Window -->
    <transition name="slide-up">
      <div v-if="isChatOpen" class="ai-chat-window">
        <!-- Header -->
        <div class="ai-chat-window-header">
          <div class="ai-chat-window-header-info">
            <div class="ai-avatar">
              <i class="fas fa-robot"></i>
            </div>
            <div>
              <div class="ai-chat-window-title">AI Assistant</div>
              <div class="ai-chat-window-subtitle">Trợ lý ảo thông minh</div>
            </div>
          </div>
          <div class="ai-chat-window-header-actions">
            <button 
              v-if="activeConversation"
              class="ai-chat-window-action-btn ai-chat-window-delete-btn" 
              @click="handleDeleteConversation"
              title="Xóa cuộc hội thoại"
              :disabled="deleting"
            >
              <i v-if="deleting" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-broom"></i>
            </button>
            <button 
              class="ai-chat-window-minimize" 
              @click="toggleChat"
              title="Thu gọn"
            >
              <i class="fas fa-minus"></i>
            </button>
          </div>
        </div>

        <!-- Messages Area -->
        <div ref="messageContainer" class="ai-chat-window-messages">
          <div v-if="messages.length === 0 && !loading" class="ai-chat-window-empty">
            <i class="fas fa-robot ai-empty-icon"></i>
            <p>Chào mừng! Hãy bắt đầu cuộc trò chuyện với AI.</p>
          </div>
          
          <div
            v-for="msg in messages"
            :key="msg.id"
            class="ai-chat-window-message-row"
            :class="{
              'from-user': msg.role === 'user',
              'from-ai': msg.role === 'assistant',
              'is-error': msg.role === 'assistant' && msg.metadata?.error
            }"
          >
            <div class="ai-chat-window-message-bubble" :class="{ 'error-bubble': msg.role === 'assistant' && msg.metadata?.error }">
              <div class="ai-chat-window-message-text" v-html="formatMessage(msg.content)"></div>
              <div class="ai-chat-window-message-time">
                {{ formatTime(msg.created_at) }}
              </div>
            </div>
          </div>
          
          <div v-if="sending" class="ai-chat-window-typing-indicator">
            <div class="typing-dots">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>

        <!-- Input Area -->
        <form class="ai-chat-window-input-area" @submit.prevent="handleSend">
          <textarea
            v-model="newMessage"
            class="ai-chat-window-input"
            placeholder="Nhập câu hỏi của bạn..."
            rows="1"
            @keydown.enter.exact.prevent="handleSend"
            @keydown.enter.shift.exact="handleNewLine"
            ref="inputRef"
          ></textarea>
          <button
            type="submit"
            class="ai-chat-window-send-btn"
            :disabled="sending || !newMessage.trim() || !activeConversation"
            title="Gửi tin nhắn"
          >
            <i class="fas fa-paper-plane"></i>
          </button>
        </form>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, watch } from "vue";
import { makeHttpReq } from "../helper/makeHttpReq";
import { showError, showConfirm, showSuccess } from "../helper/alert";

interface AIConversation {
  id: number;
  title: string | null;
  type: string;
  project_id?: number | null;
  updated_at: string;
}

interface AIMessage {
  id: number;
  ai_conversation_id: number;
  role: 'user' | 'assistant' | 'system';
  content: string;
  created_at: string;
  metadata?: any;
}

const isChatOpen = ref(false);
const messages = ref<AIMessage[]>([]);
const activeConversation = ref<AIConversation | null>(null);
const newMessage = ref("");
const sending = ref(false);
const loading = ref(false);
const deleting = ref(false);
const messageContainer = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLTextAreaElement | null>(null);

// Watch for chat open to focus input
watch(isChatOpen, (open) => {
  if (open) {
    nextTick(() => {
      if (inputRef.value) {
        inputRef.value.focus();
      }
      // Load or create conversation when opening
      if (!activeConversation.value) {
        initializeConversation();
      }
    });
  }
});

async function initializeConversation() {
  if (activeConversation.value) return;
  
  loading.value = true;
  try {
    // Try to get the latest conversation
    const res = await makeHttpReq<never, any>('/ai/conversations', 'GET');
    console.log('Conversations response:', res);
    
    // Handle response format: { code, data, message } or direct array
    let conversations: AIConversation[] = [];
    if (res?.data) {
      if (Array.isArray(res.data)) {
        conversations = res.data;
      } else if (res.data?.data && Array.isArray(res.data.data)) {
        conversations = res.data.data;
      }
    } else if (Array.isArray(res)) {
      conversations = res;
    }
    
    // Use the latest conversation or create a new one
    if (conversations.length > 0) {
      activeConversation.value = conversations[0];
      await fetchMessages(conversations[0].id);
    } else {
      await createNewConversation();
    }
  } catch (e: any) {
    console.error('Failed to initialize conversation:', e);
    // Try to create a new one anyway
    try {
      await createNewConversation();
    } catch (createError: any) {
      console.error('Failed to create conversation:', createError);
      const errorMsg = createError?.message || 'Không thể khởi tạo cuộc hội thoại';
      showError(errorMsg);
    }
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
    
    console.log('Create conversation response:', res);
    
    // Handle response format: { code, data, message } or direct data
    let conversation;
    if (res?.data) {
      conversation = res.data;
    } else if (res && typeof res === 'object' && res.id) {
      conversation = res;
    } else {
      throw new Error('Invalid response format from server');
    }
    
    activeConversation.value = conversation;
    messages.value = [];
  } catch (e: any) {
    console.error('Failed to create conversation:', e);
    const errorMessage = e?.message || e?.response?.message || e?.error?.message || 'Không thể tạo cuộc hội thoại mới';
    showError(errorMessage);
    throw e;
  }
}

function toggleChat() {
  isChatOpen.value = !isChatOpen.value;
}

async function handleSend() {
  if (!activeConversation.value || !newMessage.value.trim() || sending.value) return;
  
  const messageText = newMessage.value.trim();
  newMessage.value = "";
  sending.value = true;

  // Optimistic update: Add user message immediately
  const tempUserMessage: AIMessage = {
    id: Date.now(), // Temporary ID
    ai_conversation_id: activeConversation.value.id,
    role: 'user',
    content: messageText,
    created_at: new Date().toISOString(),
  };
  messages.value.push(tempUserMessage);
  scrollToBottom();

  try {
    const res = await makeHttpReq<any, any>(
      `/ai/conversations/${activeConversation.value.id}/messages`,
      'POST',
      { message: messageText }
    );

    console.log('Send message response:', res);
    
    // Handle response format: res.data or res directly
    const data = res?.data || res;
    
    // Replace temporary user message with real one from server
    if (data?.user_message) {
      const tempIndex = messages.value.findIndex(m => m.id === tempUserMessage.id);
      if (tempIndex !== -1) {
        messages.value[tempIndex] = data.user_message;
      } else {
        // If not found, add it (shouldn't happen but safe fallback)
        messages.value.push(data.user_message);
      }
    }
    
    // Add AI message
    if (data?.ai_message) {
      messages.value.push(data.ai_message);
    }
    
    // If response doesn't have user_message/ai_message, try to extract from data
    if (!data?.user_message && !data?.ai_message) {
      console.warn('Unexpected response format:', data);
      // Try to reload messages to get both messages
      await fetchMessages(activeConversation.value.id);
    }
    
    scrollToBottom();
  } catch (e: any) {
    console.error('Error sending message:', e);
    
    // Remove temporary user message on error
    const tempIndex = messages.value.findIndex(m => m.id === tempUserMessage.id);
    if (tempIndex !== -1) {
      messages.value.splice(tempIndex, 1);
    }
    
    // Restore message on error
    newMessage.value = messageText;
    showError(e?.message || e?.response?.message || 'Không thể gửi tin nhắn');
  } finally {
    sending.value = false;
    nextTick(() => {
      if (inputRef.value) {
        inputRef.value.focus();
      }
    });
  }
}

function formatTime(dateString: string): string {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diff = now.getTime() - date.getTime();
  const seconds = Math.floor(diff / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);

  if (hours > 0) {
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

async function handleDeleteConversation() {
  if (!activeConversation.value || deleting.value) return;
  
  const confirmed = await showConfirm(
    'Bạn có chắc chắn muốn xóa cuộc hội thoại này?',
    'Xóa cuộc hội thoại'
  );
  
  if (!confirmed) return;

  deleting.value = true;

  try {
    await makeHttpReq<never, any>(`/ai/conversations/${activeConversation.value.id}`, 'DELETE');
    
    showSuccess('Đã xóa cuộc hội thoại thành công', 'Thành công');
    
    // Clear current conversation and create new one
    activeConversation.value = null;
    messages.value = [];
    
    // Create new conversation
    await createNewConversation();
  } catch (e: any) {
    showError(e?.message || 'Không thể xóa cuộc hội thoại');
  } finally {
    deleting.value = false;
  }
}
</script>

<style scoped>
.ai-floating-chat {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
}

/* Floating Button */
.ai-chat-float-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
  border: none;
  box-shadow: 0 4px 16px rgba(74, 144, 226, 0.4);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  color: white;
  font-size: 24px;
  position: relative;
}

.ai-chat-float-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 25px rgba(74, 144, 226, 0.6);
}

.ai-chat-float-btn.chat-open {
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  box-shadow: 0 4px 20px rgba(231, 76, 60, 0.4);
}

.ai-chat-float-btn.chat-open:hover {
  box-shadow: 0 6px 25px rgba(231, 76, 60, 0.6);
}

/* Chat Window */
.ai-chat-window {
  position: absolute;
  bottom: 70px;
  right: 0;
  width: 340px;
  height: 500px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Header */
.ai-chat-window-header {
  padding: 12px 16px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
  color: white;
}

.ai-chat-window-header-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.ai-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.ai-chat-window-title {
  font-weight: 600;
  font-size: 14px;
}

.ai-chat-window-subtitle {
  font-size: 11px;
  opacity: 0.9;
}

.ai-chat-window-header-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.ai-chat-window-action-btn {
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: background 0.2s;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.ai-chat-window-action-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.ai-chat-window-action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.ai-chat-window-delete-btn:hover:not(:disabled) {
  background: rgba(231, 76, 60, 0.3);
}

.ai-chat-window-minimize {
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: background 0.2s;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.ai-chat-window-minimize:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Messages Area */
.ai-chat-window-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: #f8f9fa;
}

.ai-chat-window-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #999;
  padding: 40px 20px;
}

.ai-empty-icon {
  font-size: 48px;
  color: #4a90e2;
  margin-bottom: 16px;
  opacity: 0.5;
}

.ai-chat-window-empty p {
  margin: 0;
  font-size: 14px;
}

.ai-chat-window-message-row {
  display: flex;
  width: 100%;
}

.ai-chat-window-message-row.from-user {
  justify-content: flex-end;
}

.ai-chat-window-message-row.from-ai {
  justify-content: flex-start;
}

.ai-chat-window-message-bubble {
  max-width: 80%;
  padding: 8px 12px;
  border-radius: 16px;
  position: relative;
}

.from-user .ai-chat-window-message-bubble {
  background: #4a90e2;
  color: white;
  border-bottom-right-radius: 4px;
}

.from-ai .ai-chat-window-message-bubble {
  background: white;
  color: #333;
  border: 1px solid #e0e0e0;
  border-bottom-left-radius: 4px;
}

.from-ai .ai-chat-window-message-bubble.error-bubble {
  background: #fff5f5;
  border-color: #feb2b2;
  color: #c53030;
}

.ai-chat-window-message-text {
  line-height: 1.4;
  word-wrap: break-word;
  font-size: 13px;
}

.from-ai .ai-chat-window-message-text {
  white-space: pre-wrap;
}

.ai-chat-window-message-time {
  font-size: 10px;
  margin-top: 4px;
  opacity: 0.7;
}

.ai-chat-window-typing-indicator {
  display: flex;
  justify-content: flex-start;
}

.typing-dots {
  display: flex;
  gap: 4px;
  padding: 10px 14px;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 18px;
}

.typing-dots span {
  width: 6px;
  height: 6px;
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
    transform: translateY(-8px);
    opacity: 1;
  }
}

/* Input Area */
.ai-chat-window-input-area {
  padding: 12px;
  border-top: 1px solid #e0e0e0;
  display: flex;
  gap: 8px;
  align-items: flex-end;
  background: white;
}


.ai-chat-window-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #e0e0e0;
  border-radius: 18px;
  font-size: 13px;
  font-family: inherit;
  resize: none;
  max-height: 80px;
  min-height: 36px;
  line-height: 1.4;
}

.ai-chat-window-input:focus {
  outline: none;
  border-color: #4a90e2;
}

.ai-chat-window-send-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #4a90e2;
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
  font-size: 14px;
}

.ai-chat-window-send-btn:hover:not(:disabled) {
  background: #357abd;
  transform: scale(1.05);
}

.ai-chat-window-send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Slide Up Animation */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

/* Mobile Responsive */
@media (max-width: 767.98px) {
  .ai-floating-chat {
    bottom: 100px;
    right: 16px;
  }

  .ai-chat-float-btn {
    width: 52px;
    height: 52px;
    font-size: 22px;
  }

  .ai-chat-window {
    width: calc(100vw - 32px);
    max-width: 340px;
    height: calc(100vh - 200px);
    max-height: 500px;
    bottom: 70px;
    right: 16px;
  }
}

@media (max-width: 480px) {
  .ai-chat-window {
    width: calc(100vw - 20px);
    right: 10px;
    bottom: 70px;
  }
}
</style>

