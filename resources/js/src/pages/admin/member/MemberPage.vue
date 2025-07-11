<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import MemberTable from './components/MemberTable.vue';
import { MemberType, useGetMembers, removeMember } from './actions/getMember';
import { memberStore } from './store/MemberStore';
import { useRouter } from 'vue-router';
import { MemberInputType } from './actions/createMember';
import LoadingPage from '../../../components/LoadingPage.vue';
import CustomPagination from '../../../components/CustomPagination.vue';
import MainCardLayout from '../../../components/MainCardLayout.vue';
import AddMemberModal from './components/AddMemberModal.vue';
import { addMemberByNameOrEmail } from './actions/createMember';
import FabButton from '../../../components/FabButton.vue';

const { getMembers, memberData, loading: tableLoading } = useGetMembers();
const isLoading = ref(true);
const router = useRouter();
const showAddModal = ref(false);

async function fetchMembers(page = 1, query = '', showLoadingPage = true) {
    if (showLoadingPage) {
        isLoading.value = true;
        await getMembers(page, query);
        isLoading.value = false;
    } else {
        tableLoading.value = true;
        await getMembers(page, query);
        tableLoading.value = false;
    }
}

async function handleAddMember(input: string, cb: (err: string | null) => void) {
    try {
        await addMemberByNameOrEmail(input);
        cb(null);
        showAddModal.value = false;
        await fetchMembers();
    } catch (e: any) {
        let errMsg = e?.error;
        cb(errMsg || 'Add failed');
    }
}

async function handleRemoveMember(member: MemberType) {
    await removeMember(member.id);
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
            <button class="btn btn-primary create-btn" @click="showAddModal = true">
                <i class="bi bi-plus-circle me-1"></i> Add Member
            </button>
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

<style scoped>
.fab-add-project {
    position: fixed;
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
    text-decoration: none;
}

.fab-add-project:focus {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

.fab-add-project:hover {
    background: linear-gradient(135deg, #1d4ed8 60%, #2563eb 100%);
    box-shadow: 0 8px 24px rgba(34, 34, 59, 0.22);
    transform: scale(1.09);
    color: #fff;
    text-decoration: none;
}

.fab-add-project svg {
    display: block;
    margin: 0 auto;
}

@media (min-width: 769px) {
    .fab-add-project {
        display: none !important;
    }
}
</style>