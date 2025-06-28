<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import MemberTable from './components/MemberTable.vue';
import { MemberType, useGetMembers } from './actions/getMember';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { memberStore } from './store/MemberStore';
import { useRouter } from 'vue-router';
import { MemberInputType } from './actions/createMember';
import LoadingPage from '../../../components/LoadingPage.vue';

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
    <div style="padding:2.5rem 0 1.5rem 0; position:relative;">
        <div class="main-card" style="position:relative;">
            <div class="main-card-header">
                <div class="header-mobile-fab d-flex d-md-none">
                    <span class="main-title-mobile-fab flex-grow-1">
                        <i class="bi bi-people-fill icon-before-title"></i>
                        Member Management
                    </span>
                </div>
                <div class="d-none d-md-flex align-items-center justify-content-between">
                    <h2 class="main-title mb-0">
                        <i class="bi bi-people-fill me-2"></i>Member Management
                    </h2>
                    <RouterLink to="/create-members" class="btn btn-primary create-btn">
                        <i class="bi bi-plus-circle me-1"></i> Create Member
                    </RouterLink>
                </div>
            </div>
            <div class="main-card-body position-relative">
                <LoadingPage v-if="isLoading" />
                <MemberTable @getMember="fetchMembers" :loading="tableLoading" @editMember="handleEditMember"
                    :members="memberData">
                    <template #pagination>
                        <div class="d-flex justify-content-center mt-3">
                            <Bootstrap5Pagination v-if="memberData?.data" :data="memberData.data"
                                @pagination-change-page="fetchMembers" />
                        </div>
                    </template>
                </MemberTable>
            </div>
            <RouterLink to="/create-members" class="fab-add-project d-md-none">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="13" y="6" width="2" height="16" rx="1" fill="white" />
                    <rect x="6" y="13" width="16" height="2" rx="1" fill="white" />
                </svg>
            </RouterLink>
        </div>
    </div>
</template>
<style scoped>
.main-card {
    background: #fff;
    border-radius: 1.5rem;
    box-shadow: 0 4px 32px rgba(0, 0, 0, 0.09);
    padding: 2.2rem 1.5rem 1.5rem 1.5rem;
    margin-bottom: 2rem;
}

.main-card-header {
    border-bottom: 1.5px solid #e0e0e0;
    background: transparent;
}

.main-title {
    font-size: 2rem;
    font-weight: 700;
    color: #22223b;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
}

.create-btn {
    font-size: 1.1rem;
    font-weight: 500;
    border-radius: 2rem;
    padding: 0.55rem 1.5rem;
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
        padding: 0.7rem 0.2rem 0.5rem 0.2rem;
        border-radius: 0.7rem;
        margin-bottom: 1rem;
    }

    .main-title {
        font-size: 1.1rem;
        font-weight: 600;
        letter-spacing: 0.2px;
    }

    .main-card-header {
        background: transparent;
        border-bottom: none;
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
        padding: 0.7rem 0.5rem 0.7rem 0.5rem;
        background: transparent;
        border-radius: 0;
        box-shadow: none;
        min-height: 3.2rem;
    }

    .main-title-mobile-fab {
        font-size: 1.35rem;
        color: #22223b;
        font-weight: 800;
        text-align: center;
        flex: 1 1 auto;
        letter-spacing: 0.1px;
        margin: 0 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .icon-before-title {
        font-size: 1.1rem;
        color: #2563eb;
        margin-right: 0.3rem;
        display: inline-block;
        vertical-align: middle;
    }

    .fab-add-project {
        position: fixed;
        top: auto;
        left: auto;
        right: 1.2rem;
        bottom: 1.8rem;
        z-index: 1002;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        color: #fff;
        background: linear-gradient(135deg, #2563eb 60%, #60a5fa 100%);
        width: 3.5rem;
        height: 3.5rem;
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
        margin-top: 14px;
    }
}

@media (min-width: 769px) {
    .d-md-inline {
        display: inline !important;
    }

    .d-md-none {
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
</style>