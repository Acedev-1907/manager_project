<template>
    <div class="skeleton-card" :class="{ 'skeleton-shimmer': shimmer }">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-content">
            <div class="skeleton-line skeleton-line-primary"></div>
            <div v-if="showSecondary" class="skeleton-line skeleton-line-secondary"></div>
        </div>
    </div>
</template>

<script lang="ts" setup>
interface Props {
    shimmer?: boolean;
    showSecondary?: boolean;
}

withDefaults(defineProps<Props>(), {
    shimmer: true,
    showSecondary: false
});
</script>

<style scoped>
.skeleton-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    background: transparent;
    transition: all 0.2s ease;
}

.skeleton-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
    background-size: 200% 100%;
    flex-shrink: 0;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-line {
    height: 16px;
    border-radius: 8px;
    background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
    background-size: 200% 100%;
}

.skeleton-line-primary {
    width: 70%;
}

.skeleton-line-secondary {
    width: 50%;
}

/* Shimmer animation */
.skeleton-shimmer .skeleton-avatar,
.skeleton-shimmer .skeleton-line {
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }

    100% {
        background-position: 200% 0;
    }
}

/* Dark theme optimization */
@media (prefers-color-scheme: dark) {

    .skeleton-avatar,
    .skeleton-line {
        background: linear-gradient(90deg, #4b5563 25%, #6b7280 50%, #4b5563 75%);
        background-size: 200% 100%;
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .skeleton-card {
        padding: 10px 12px;
        gap: 10px;
    }

    .skeleton-avatar {
        width: 36px;
        height: 36px;
    }

    .skeleton-line {
        height: 14px;
    }
}
</style>
