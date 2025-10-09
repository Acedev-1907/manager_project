<template>
    <div class="stats-card">
        <div class="stats-icon" :style="{ background: iconColor }">
            <i :class="icon"></i>
        </div>
        <div class="stats-content">
            <h3 class="stats-title">{{ title }}</h3>
            <div class="stats-value">{{ value }}</div>
            <p v-if="subtitle" class="stats-subtitle">{{ subtitle }}</p>
        </div>
    </div>
</template>

<script lang="ts" setup>
interface Props {
    title: string;
    value: string | number;
    subtitle?: string;
    icon?: string;
    iconColor?: string;
}

withDefaults(defineProps<Props>(), {
    icon: 'fas fa-chart-line',
    iconColor: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
});
</script>

<style scoped>
.stats-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stats-icon {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
    flex-shrink: 0;
}

.stats-content {
    flex: 1;
}

.stats-title {
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    margin: 0 0 8px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stats-value {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
    margin-bottom: 4px;
}

.stats-subtitle {
    font-size: 13px;
    color: #94a3b8;
    margin: 0;
}

@media (max-width: 768px) {
    .stats-card {
        padding: 16px;
    }

    .stats-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }

    .stats-value {
        font-size: 24px;
    }

    .stats-title {
        font-size: 12px;
    }
}
</style>

