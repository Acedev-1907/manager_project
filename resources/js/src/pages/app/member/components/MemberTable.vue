<script lang="ts" setup>
import MemberCard from './MemberCard.vue';

defineProps<{
    items: any[];
    loading: boolean;
}>();
const emit = defineEmits(['removeMember']);
</script>
<template>
    <div class="member-table-container" style="position:relative;">
        <div v-if="!items || items.length === 0" class="text-center text-muted py-4">
            No data
        </div>
        <div v-else class="member-card-grid">
            <template v-if="loading">
                <div v-for="n in 12" :key="'skeleton-' + n" class="member-card-skeleton"></div>
            </template>
            <template v-else>
                <template v-for="item in items" :key="item.id">
                    <slot name="card" :item="item">
                        <!-- Default: MemberCard -->
                        <MemberCard :member="item" @remove="() => emit('removeMember', item)" />
                    </slot>
                </template>
            </template>
        </div>
        <div class="d-flex justify-content-center">
            <slot name="pagination"></slot>
        </div>
    </div>
</template>
<style scoped>
.member-table-container {
    max-width: 1000px;
    margin: 0 auto;
}

.member-card-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.member-card-skeleton {
    height: 120px;
    border-radius: 1rem;
    background: linear-gradient(90deg, #f3f3f3 25%, #ececec 50%, #f3f3f3 75%);
    animation: skeleton-loading 1.2s infinite linear;
    opacity: 0.6;
}

@keyframes skeleton-loading {
    0% {
        background-position: -200px 0;
    }

    100% {
        background-position: calc(200px + 100%) 0;
    }
}

@media (max-width: 900px) {
    .member-card-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .member-card-grid {
        grid-template-columns: 1fr;
    }
}
</style>