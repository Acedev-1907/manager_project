<template>
    <div v-if="props.visible" class="loading-overlay">
        <div class="loader">
            <svg class="spinner" viewBox="0 0 50 50">
                <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5" />
            </svg>
            <div class="loading-text">Wait a moment...</div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { defineProps } from 'vue';
const props = defineProps({
    visible: {
        type: Boolean,
        default: true
    }
});
</script>
<style scoped>
.loading-overlay {
    position: absolute;
    z-index: 20;
    inset: 0;
    background: rgba(255, 255, 255, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.3s;
    min-height: 100%;
}

.loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 100vh;
}

.spinner {
    animation: spin 1s linear infinite;
    width: 64px;
    height: 64px;
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
    margin-top: 18px;
    font-size: 1.25rem;
    font-weight: 600;
    color: #2470dc;
    letter-spacing: 0.08em;
    text-shadow: 0 2px 8px #b3c6e0;
}

@media (max-width: 768px) {
    .loading-overlay {
        min-height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        width: 100vw;
        height: 100vh;
        border-radius: 0 !important;
    }

    .loader {
        min-height: 100vh;
    }
}
</style>