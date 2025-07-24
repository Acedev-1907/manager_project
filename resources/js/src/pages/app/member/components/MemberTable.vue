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
            <template v-for="item in items" :key="item.id">
                <slot name="card" :item="item">
                    <!-- Default: MemberCard -->
                    <MemberCard :member="item" @remove="() => emit('removeMember', item)" />
                </slot>
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