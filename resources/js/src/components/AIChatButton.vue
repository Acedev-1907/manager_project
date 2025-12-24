<template>
    <button 
        class="ai-chat-button" 
        @click="$emit('click')"
        :class="{ 'pulse': hasUnread }"
        title="Chat với AI"
    >
        <i class="fas fa-robot"></i>
        <span v-if="hasUnread" class="ai-chat-badge"></span>
    </button>
</template>

<script setup lang="ts">
defineProps<{
    hasUnread?: boolean;
}>();

defineEmits<{
    click: [];
}>();
</script>

<style scoped>
.ai-chat-button {
    position: fixed;
    right: 2rem;
    bottom: 2rem;
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
    border: none;
    box-shadow: 0 4px 20px rgba(74, 144, 226, 0.4);
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
    font-size: 24px;
}

.ai-chat-button:hover {
    background: linear-gradient(135deg, #357abd 0%, #2563a0 100%);
    box-shadow: 0 6px 30px rgba(74, 144, 226, 0.6);
    transform: scale(1.1);
}

.ai-chat-button:active {
    transform: scale(0.95);
}

.ai-chat-button.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 4px 20px rgba(74, 144, 226, 0.4);
    }
    50% {
        box-shadow: 0 4px 30px rgba(74, 144, 226, 0.8), 0 0 0 10px rgba(74, 144, 226, 0.1);
    }
    100% {
        box-shadow: 0 4px 20px rgba(74, 144, 226, 0.4);
    }
}

.ai-chat-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #e74c3c;
    border: 2px solid white;
    animation: bounce 1s infinite;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-3px);
    }
}

@media (max-width: 768px) {
    .ai-chat-button {
        right: 1rem;
        bottom: 5rem;
        width: 56px;
        height: 56px;
        font-size: 22px;
    }
}

@media (max-width: 480px) {
    .ai-chat-button {
        right: 1rem;
        bottom: 5.5rem;
        width: 52px;
        height: 52px;
        font-size: 20px;
    }
}
</style>
