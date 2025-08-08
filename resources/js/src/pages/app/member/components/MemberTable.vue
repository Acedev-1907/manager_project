<script lang="ts" setup>
import MemberCard from './MemberCard.vue';
import SkeletonCard from '../../../../components/SkeletonCard.vue';

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
                <!-- Use SkeletonCard for better loading experience -->
                <div v-for="n in 12" :key="'skeleton-' + n" class="member-card-skeleton-wrapper">
                    <SkeletonCard :shimmer="true" :show-secondary="true" />
                </div>
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

.member-card-skeleton-wrapper {
    background: #fff;
    border-radius: 1rem;
    padding: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
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

/* Dark theme support */
@media (prefers-color-scheme: dark) {
    .member-card-skeleton-wrapper {
        background: #1f2937;
        border-color: #374151;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
}
</style>