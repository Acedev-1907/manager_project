<template>
    <div :style="containerStyle">
        <div class="main-card" style="position:relative;">
            <div class="main-card-header">
                <div class="header-mobile-fab d-flex d-md-none">
                    <span class="main-title-mobile-fab flex-grow-1">
                        <i :class="iconClass + ' icon-before-title'"></i>
                        {{ title }}
                    </span>
                    <div class="mobile-action-bar">
                        <slot name="action"></slot>
                    </div>
                </div>
                <div class="d-none d-md-flex align-items-center justify-content-between" style="padding: 1.2rem">
                    <h4 class="main-title mb-0">
                        <i :class="iconClass + ' me-2'"></i>{{ title }}
                    </h4>
                    <slot name="action"></slot>
                </div>
            </div>
            <div class="main-card-body position-relative">
                <slot />
            </div>
            <slot name="fab"></slot>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string,
    iconClass: string,
    containerStyle?: string
}>();
</script>

<style>
.main-card {
    background: #fff;
    border-radius: 0.875rem;
    box-shadow: 0 4px 32px rgba(0, 0, 0, 0.09);
    padding: 1rem 0.75rem 0.75rem 0.75rem;
    margin-bottom: 1rem;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

.main-card-header {
    border-bottom: 1.5px solid #e0e0e0;
    background: transparent;
    padding-bottom: 0.75rem;
}

.main-card-body {
    padding-top: 1rem;
}

.main-title-mobile-fab.flex-grow-1 {
    padding: 12px 0 12px 14px;
    font-weight: bold;
}

/* ================= PC/Laptop/Desktop ================= */
.main-action-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* ================= Mobile (max-width: 768px) ================= */
@media (max-width: 768px) {
    .main-card {
        padding: 0.4rem 0.1rem 0.3rem 0.1rem;
        border-radius: 0.7rem;
        margin-bottom: 1rem;
        margin-left: 0.3rem;
        margin-right: 0.3rem;
    }

    .main-card-header {
        background: transparent;
        border-bottom: none;
        padding-bottom: 0.4rem;
    }

    .header-mobile-fab {
        padding-top: 0.5rem;
    }
}

@media (min-width: 769px) and (max-width: 1200px) {
    .main-card {
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }
}

@media (min-width: 1201px) {
    .main-card {
        max-width: 1100px;
        margin-left: auto;
        margin-right: auto;
    }
}

:deep(.loading-overlay-inside) {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.7);
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 1.25rem;
}
</style>