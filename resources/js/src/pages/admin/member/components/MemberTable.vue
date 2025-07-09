<script lang="ts" setup>
import { ref } from 'vue';
import { GetMemberType, MemberType } from '../actions/getMember';
import SearchInput from '../../../../components/SearchInput.vue';
import MemberCard from './MemberCard.vue';

defineProps<{
    members: GetMemberType;
    loading: boolean;
}>()

const emit = defineEmits<{
    (e: "removeMember", member: MemberType): void;
    (e: "getMember", page: number, query: string, showGlobalLoading: boolean): Promise<void>;
}>();

const query = ref("");

const handleSearch = async (searchQuery: string) => {
    query.value = searchQuery;
    await emit("getMember", 1, searchQuery, false);
};
</script>
<template>
    <div class="member-table-container" style="position:relative;">
        <SearchInput v-model="query" placeholder="Search member..." :loading="loading" @search="handleSearch" />
        <div v-if="!members?.data?.data || members?.data?.data.length === 0" class="text-center text-muted py-4">
            No data
        </div>
        <div v-else class="member-card-grid">
            <MemberCard v-for="member in members?.data?.data" :key="member.id"
                :member="member.member ? { id: member.id, name: member.member.name, email: member.member.email } : { id: member.id, name: '', email: '' }"
                @remove="$emit('removeMember', member)" />
        </div>
        <div class="p-3 d-flex justify-content-center">
            <slot name="pagination"></slot>
        </div>
    </div>
</template>
<style scoped>
.member-table-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 1.5rem 0.5rem;
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