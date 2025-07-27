<template>
    <div v-if="props.visible" class="loading-overlay">
        <div class="loader">
            <svg class="spinner" viewBox="0 0 50 50">
                <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5" />
            </svg>
            <div class="loading-text">{{ props.text }}</div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { defineProps } from 'vue';
const props = defineProps({
    visible: {
        type: Boolean,
        default: true
    },
    text: {
        type: String,
        default: 'Wait a moment...'
    }
});
</script>
<style scoped>
.loading-overlay {
    position: fixed;
    top: 62px;
    /* Chiều cao của navbar */
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    background: rgba(255, 255, 255, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.3s ease;
    backdrop-filter: blur(2px);
}

.loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 2rem;
}

.spinner {
    animation: spin 1s linear infinite;
    width: 80px;
    height: 80px;
    margin-bottom: 1rem;
}

.path {
    stroke: #2470dc;
    stroke-linecap: round;
    animation: dash 1.5s ease-in-out infinite;
}

@keyframes spin {
    100% {
        transform: rotate(360deg);
    }
}

@keyframes dash {
    0% {
        stroke-dasharray: 1, 150;
        stroke-dashoffset: 0;
    }

    50% {
        stroke-dasharray: 90, 150;
        stroke-dashoffset: -35;
    }

    100% {
        stroke-dasharray: 90, 150;
        stroke-dashoffset: -124;
    }
}

.loading-text {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2470dc;
    letter-spacing: 0.08em;
    text-shadow: 0 2px 8px rgba(36, 112, 220, 0.2);
    margin: 0;
}

@media (max-width: 768px) {
    .loading-overlay {
        top: 60px;
        /* Chiều cao navbar mobile */
    }

    .spinner {
        width: 60px;
        height: 60px;
    }

    .loading-text {
        font-size: 1.25rem;
    }
}
</style>