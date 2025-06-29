<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import MemberTable from './components/MemberTable.vue';
import { MemberType, useGetMembers } from './actions/getMember';
import { memberStore } from './store/MemberStore';
import { useRouter } from 'vue-router';
import { MemberInputType } from './actions/createMember';
import LoadingPage from '../../../components/LoadingPage.vue';
import CustomPagination from '../../../components/CustomPagination.vue';
import MainCardLayout from '../../../components/MainCardLayout.vue';

const { getMembers, memberData, loading: tableLoading } = useGetMembers();
const isLoading = ref(true);
const router = useRouter();

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

function handleEditMember(member: MemberType) {
    memberStore.memberInput = {
        id: member.id,
        name: member.name,
        email: member.email
    };
    memberStore.edit = true;
    router.push('/create-members');
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
            <RouterLink to="/create-members" class="btn btn-primary create-btn">
                <i class="bi bi-plus-circle me-1"></i> Create Member
            </RouterLink>
        </template>
        <LoadingPage v-if="isLoading" />
        <MemberTable @getMember="fetchMembers" :loading="tableLoading" @editMember="handleEditMember"
            :members="memberData">
            <template #pagination>
                <CustomPagination v-if="memberData?.data" :data="memberData.data" :loading="tableLoading"
                    @pagination-change-page="fetchMembers" />
            </template>
        </MemberTable>
        <template #fab>
            <RouterLink to="/create-members" class="fab-add-project d-md-none">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="13" y="6" width="2" height="16" rx="1" fill="white" />
                    <rect x="6" y="13" width="16" height="2" rx="1" fill="white" />
                </svg>
            </RouterLink>
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