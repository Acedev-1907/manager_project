<template>
    <div :style="containerStyle">
        <div class="main-card" style="position:relative;">
            <div class="main-card-header">
                <div class="header-mobile-fab d-flex d-md-none">
                    <span class="main-title-mobile-fab flex-grow-1">
                        <i :class="iconClass + ' icon-before-title'"></i>
                        {{ title }}
                    </span>
                </div>
                <div class="d-none d-md-flex align-items-center justify-content-between">
                    <h2 class="main-title mb-0">
                        <i :class="iconClass + ' me-2'"></i>{{ title }}
                    </h2>
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

<style scoped>
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

.main-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #22223b;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
}

.create-btn {
    font-size: 0.8rem;
    font-weight: 500;
    border-radius: 1.25rem;
    padding: 0.35rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    box-shadow: 0 2px 8px rgba(34, 34, 59, 0.07);
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
}

.create-btn:hover {
    background: #2563eb;
    color: #fff;
    box-shadow: 0 4px 16px rgba(34, 34, 59, 0.13);
}

.main-card-body {
    padding-top: 0.5rem;
}

@media (max-width: 768px) {
    .main-card {
        padding: 0.4rem 0.1rem 0.3rem 0.1rem;
        border-radius: 0.7rem;
        margin-bottom: 1rem;
        margin-left: 0.3rem;
        margin-right: 0.3rem;
    }

    .main-title {
        font-size: 1rem;
        font-weight: 600;
        letter-spacing: 0.2px;
    }

    .main-card-header {
        background: transparent;
        border-bottom: none;
        padding-bottom: 0.4rem;
    }

    .d-md-inline {
        display: none !important;
    }

    .d-md-none {
        display: inline !important;
    }

    .header-mobile-fab {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.4rem 0.3rem 0.4rem 0.3rem;
        background: transparent;
        border-radius: 0;
        box-shadow: none;
        min-height: 2.5rem;
    }

    .main-title-mobile-fab {
        font-size: 1.1rem;
        color: #22223b;
        font-weight: 800;
        text-align: center;
        flex: 1 1 auto;
        letter-spacing: 0.1px;
        margin: 0 0.3rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
    }

    .icon-before-title {
        font-size: 0.9rem;
        color: #2563eb;
        margin-right: 0.2rem;
        display: inline-block;
        vertical-align: middle;
    }

    .fab-add-project {
        position: fixed;
        top: auto;
        left: auto;
        right: 1rem;
        bottom: 4.5rem;
        z-index: 1002;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #fff;
        background: linear-gradient(135deg, #2563eb 60%, #60a5fa 100%);
        width: 3.2rem;
        height: 3.2rem;
        border-radius: 50%;
        box-shadow: 0 4px 18px rgba(34, 34, 59, 0.18);
        transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
        border: none;
        outline: none;
        cursor: pointer;
        padding: 0;
        margin: 0;
        text-align: center;
    }

    .fab-add-project:focus {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }

    .fab-add-project:hover {
        background: linear-gradient(135deg, #1d4ed8 60%, #2563eb 100%);
        box-shadow: 0 8px 24px rgba(34, 34, 59, 0.22);
        transform: scale(1.09);
    }

    .d-md-flex {
        display: none !important;
    }

    .fab-add-project svg {
        display: block;
        margin: 0 auto;
        margin-top: 12px;
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

@media (min-width: 769px) {
    .d-md-inline {
        display: inline !important;
    }

    .d-md-none {
        display: none !important;
    }

    .create-btn-mobile {
        display: none !important;
    }

    .d-md-flex {
        display: flex !important;
    }

    .header-mobile-fab,
    .fab-add-project {
        display: none !important;
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