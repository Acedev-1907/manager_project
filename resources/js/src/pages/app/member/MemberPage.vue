<script lang="ts" setup>
import { onMounted, ref, watch } from 'vue';
import MemberTable from './components/MemberTable.vue';
import { MemberType, useGetMembers, removeMember } from './actions/getMember';
import { memberStore } from './store/MemberStore';
import { MemberInputType } from './actions/createMember';
import LoadingPage from '../../../components/LoadingPage.vue';
import CustomPagination from '../../../components/CustomPagination.vue';
import MainCardLayout from '../../../components/MainCardLayout.vue';
import AddMemberModal from './components/AddMemberModal.vue';
import { addMemberByNameOrEmail } from './actions/createMember';
import FabButton from '../../../components/FabButton.vue';
import { useCacheFetch } from '../../../helper/useCacheFetch';
import SearchInput from '../../../components/SearchInput.vue';

const { getMembers, memberData, loading: tableLoading } = useGetMembers();
const isLoading = ref(true);
const showAddModal = ref(false);
const memberCache = ref<Record<string, any>>(JSON.parse(localStorage.getItem('memberCache') || '{}'));
const query = ref("");

watch(memberCache, (val) => {
    localStorage.setItem('memberCache', JSON.stringify(val));
}, { deep: true });

const { getOrFetch, refetch } = useCacheFetch(
    memberCache.value,
    (key, data) => { memberCache.value[key] = data; },
    (key) => { if (key) delete memberCache.value[key]; else memberCache.value = {}; }
);

async function fetchMembers(page = 1, searchQuery = '', showLoadingPage = true) {
    const cacheKey = `member_page_${page}_${searchQuery}`;
    if (memberCache.value[cacheKey]) {
        memberData.value = memberCache.value[cacheKey];
        isLoading.value = false;
        tableLoading.value = false;
        return;
    }
    if (showLoadingPage) isLoading.value = true;
    else tableLoading.value = true;
    await getOrFetch(cacheKey, async () => {
        await getMembers(page, searchQuery);
        return memberData.value;
    }, (data) => {
        memberData.value = data;
    });
    isLoading.value = false;
    tableLoading.value = false;
}

async function handleAddMember(input: string, cb: (err: string | null) => void) {
    try {
        await addMemberByNameOrEmail(input);
        cb(null);
        showAddModal.value = false;
        memberCache.value = {}; // Xóa toàn bộ cache member
        await fetchMembers();
    } catch (e: any) {
        let errMsg = e?.error;
        cb(errMsg || 'Add failed');
    }
}

async function handleRemoveMember(member: MemberType) {
    await removeMember(member.id);
    memberCache.value = {}; // Xóa toàn bộ cache member
    await fetchMembers();
}

onMounted(async () => {
    await fetchMembers();
    memberStore.edit = false;
    memberStore.memberInput = {} as MemberInputType;
});
</script>
<template>
    <MainCardLayout title="Member Management" iconClass="bi bi-people-fill"
        containerStyle="padding:1rem 0 1rem 0; position:relative;">
        <template #action>
            <div class="main-action-bar">
                <SearchInput v-model="query" placeholder="Search member..." :loading="tableLoading"
                    @search="(q) => fetchMembers(1, q, false)" />
                <button class="btn btn-primary create-btn d-none d-md-block" @click="showAddModal = true">
                    <i class="bi bi-plus-circle me-1"></i> Add Member
                </button>
            </div>
        </template>
        <AddMemberModal v-if="showAddModal" @close="showAddModal = false" @add="handleAddMember" />
        <LoadingPage v-if="isLoading" />
        <MemberTable @getMember="fetchMembers" :loading="tableLoading" @removeMember="handleRemoveMember"
            :members="memberData">
            <template #pagination>
                <CustomPagination v-if="memberData?.data" :data="memberData.data" :loading="tableLoading"
                    @pagination-change-page="fetchMembers" />
            </template>
        </MemberTable>
        <template #fab>
            <FabButton @click="showAddModal = true">
                <i class="bi bi-person-plus" style="font-size: 1.8rem; color: white;"></i>
            </FabButton>
        </template>
    </MainCardLayout>
</template>