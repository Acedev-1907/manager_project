<script setup lang="ts">
interface Props {
    activeTab: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'tab-change', tab: string): void;
}>();

const tabs = [
    { id: 'timeline', label: 'Timeline', icon: 'bi-clock' },
    { id: 'about', label: 'About', icon: 'bi-info-circle' },
    { id: 'friends', label: 'Friends', icon: 'bi-people' },
    { id: 'photos', label: 'Photos', icon: 'bi-images' },
];
</script>

<template>
    <div class="nav-tabs-section">
        <div class="nav-tabs">
            <button 
                v-for="tab in tabs"
                :key="tab.id"
                class="nav-tab" 
                :class="{ active: props.activeTab === tab.id }"
                @click="emit('tab-change', tab.id)"
            >
                <i :class="tab.icon"></i>
                {{ tab.label }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.nav-tabs-section {
    max-width: 1400px;
    margin: 20px auto 0;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    border-radius: 12px;
    padding: 12px 20px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.nav-tabs {
    display: flex;
    gap: 8px;
}

.nav-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: #65676b;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
}

.nav-tab:hover {
    background: #f0f2f5;
}

.nav-tab.active {
    background: #1877f2;
    color: #fff;
}

@media (max-width: 768px) {
    .nav-tabs-section {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
        margin-top: 20px;
    }
    
    .nav-tabs {
        flex-wrap: wrap;
    }
}
</style>

