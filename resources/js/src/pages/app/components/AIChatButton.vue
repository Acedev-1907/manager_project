<template>
  <div class="ai-chat-button-container">
    <button 
      class="ai-chat-button" 
      @click="toggleChat"
      :class="{ 'chat-open': isOpen }"
      title="Chat với AI"
    >
      <i v-if="!isOpen" class="fas fa-robot"></i>
      <i v-else class="fas fa-times"></i>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const isOpen = ref(false);

const emit = defineEmits<{
  (e: 'toggle'): void;
}>();

function toggleChat() {
  isOpen.value = !isOpen.value;
  emit('toggle');
}

// Expose method to close from parent
defineExpose({
  close: () => {
    isOpen.value = false;
  },
  open: () => {
    isOpen.value = true;
  }
});
</script>

<style scoped>
.ai-chat-button-container {
  position: fixed;
  bottom: 100px;
  right: 30px;
  z-index: 1000;
}

.ai-chat-button {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
  border: none;
  box-shadow: 0 4px 20px rgba(74, 144, 226, 0.4);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  color: white;
  font-size: 24px;
}

.ai-chat-button:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 25px rgba(74, 144, 226, 0.6);
}

.ai-chat-button.chat-open {
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  box-shadow: 0 4px 20px rgba(231, 76, 60, 0.4);
}

.ai-chat-button.chat-open:hover {
  box-shadow: 0 6px 25px rgba(231, 76, 60, 0.6);
}

/* Mobile responsive */
@media (max-width: 767.98px) {
  .ai-chat-button-container {
    bottom: 100px;
    right: 20px;
  }
  
  .ai-chat-button {
    width: 56px;
    height: 56px;
    font-size: 22px;
  }
}
</style>


