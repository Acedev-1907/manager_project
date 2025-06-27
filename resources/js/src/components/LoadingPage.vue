<template>
    <div v-if="visible" class="loading-overlay">
        <div class="loader">
            <svg class="spinner" viewBox="0 0 50 50">
                <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5" />
            </svg>
            <div class="loading-text">Wait a moment...</div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { eventBus } from '../helper/eventBus';
const visible = ref(false);
function show() { visible.value = true; }
function hide() { visible.value = false; }
onMounted(() => {
    eventBus.on('show-loading', show);
    eventBus.on('hide-loading', hide);
});
onUnmounted(() => {
    eventBus.off('show-loading', show);
    eventBus.off('hide-loading', hide);
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
}

.loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100vw;
    min-height: 100vh;
}

@media (max-width: 767.98px) {
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100vw;
        height: 100vh;
        min-height: 100vh;
    }

    .loader {
        width: 100vw;
        min-height: 100vh;
        align-items: center;
        justify-content: center;
        display: flex;
    }
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
</style>