<script lang="ts" setup>
import { ref, onMounted, computed, type Ref } from 'vue';
import FabButton from '../../../components/FabButton.vue';
import LoadingPage from '../../../components/LoadingPage.vue';
import MemberTable from './components/MemberTable.vue';
import AddMemberModal from './components/AddMemberModal.vue';
import type { GetMemberType, MemberType } from './actions/getMember';
import { makeHttpReq } from '../../../helper/makeHttpReq';
import { showSuccess } from '../../../helper/alert';
import InvitationCard from './components/InvitationCard.vue';
import SentInvitationCard from './components/SentInvitationCard.vue';
import { handleMemberEvent, MemberEventPayload } from './actions/useMemberEventRealtime';
import {
    fetchMembers,
    fetchSentInvitations,
    fetchReceivedInvitations,
    handleRemoveMember,
} from './actions/memberActions';

const tabs = ['Members', 'Sent Invitations', 'Received Invitations'];
const activeTab = ref('Members');
const showAddModal = ref(false);
const isLoading = ref(false);
const searchQuery = ref('');
const friendsList = ref<GetMemberType>({ data: { data: [] } });
const sentInvitations = ref<GetMemberType>({ data: { data: [] } });
const receivedInvitations = ref<GetMemberType>({ data: { data: [] } });
const memberCacheRef = ref<{ [key: string]: GetMemberType }>({}) as Ref<{ [key: string]: GetMemberType }>;
const hasFetched = ref<{ [key: string]: boolean }>({});

const isMobile = computed(() => window.innerWidth <= 600);
const tabIcons = {
    'Members': 'bi-people',
    'Sent Invitations': 'bi-send',
    'Received Invitations': 'bi-inbox'
};

async function handleAddMember(friendCode: string, cb: (err: string | null) => void) {
    try {
        await makeHttpReq<{ friend_code: string }, any>('member-invitations/send', 'POST', { friend_code: friendCode });
        cb(null);
        showAddModal.value = false;
        // fetchSentInvitations(sentInvitations, isLoading); // bỏ dòng này, rely vào realtime
        showSuccess('Invitation sent!');
    } catch (e: any) {
        cb(e?.error || 'Add failed');
    }
}
async function handleAcceptInvitation(id: number) {
    await makeHttpReq<undefined, any>(`member-invitations/${id}/accept`, 'POST');
    // Xóa các dòng log accept/decline
    if (Array.isArray(receivedInvitations.value.data?.data)) {
        receivedInvitations.value.data.data = receivedInvitations.value.data.data.filter((inv: any) => String(inv.id) !== String(id));
    }
    showSuccess('Invitation accepted!');
}
async function handleDeclineInvitation(id: number) {
    await makeHttpReq<undefined, any>(`member-invitations/${id}/decline`, 'POST');
    // Xóa các dòng log accept/decline
    if (Array.isArray(receivedInvitations.value.data?.data)) {
        receivedInvitations.value.data.data = receivedInvitations.value.data.data.filter((inv: any) => String(inv.id) !== String(id));
    }
    showSuccess('Invitation declined!');
}
async function handleCancelInvitation(id: number) {
    await makeHttpReq<undefined, any>(`member-invitations/${id}/cancel`, 'DELETE');
    // showSuccess('Invitation cancelled!');
}
function setTab(tab: string) {
    activeTab.value = tab;
    if (tab === 'Members') {
        fetchMembers(friendsList, memberCacheRef, isLoading, searchQuery.value);
    } else if (tab === 'Sent Invitations' && !hasFetched.value['sent']) {
        fetchSentInvitations(sentInvitations, isLoading);
        hasFetched.value['sent'] = true;
    } else if (tab === 'Received Invitations' && !hasFetched.value['received']) {
        fetchReceivedInvitations(receivedInvitations, isLoading);
        hasFetched.value['received'] = true;
    }
}
function handleSearchMembers(q: string) {
    searchQuery.value = q;
    fetchMembers(friendsList, memberCacheRef, isLoading, q);
}

function handleRemoveMemberWrapper(member: MemberType) {
    handleRemoveMember(
        member,
        memberCacheRef,
        searchQuery,
        friendsList,
        isLoading
    );
}

onMounted(() => {
    fetchMembers(friendsList, memberCacheRef, isLoading);
    fetchSentInvitations(sentInvitations, isLoading);
    fetchReceivedInvitations(receivedInvitations, isLoading);
    const data = JSON.parse(localStorage.getItem('userData') || '{}');
    const userId = data.user.id;

    if (window.Echo && userId) {
        window.Echo.private(`user.${userId}`)
            .listen('MemberEvent', (e: MemberEventPayload) => {
                handleMemberEvent(
                    e,
                    friendsList,
                    receivedInvitations,
                    sentInvitations,
                    memberCacheRef
                );
            });
    }
});
</script>
<template>
    <div class="friend-page">
        <div class="card-container">
            <div class="page-title">
                <i class="bi bi-people-fill page-title-icon"></i>
                <span>Member Management</span>
            </div>
            <div class="friend-header">
                <div class="tab-bar">
                    <button v-for="tab in tabs" :key="tab" :class="['tab-btn', { active: activeTab === tab }]"
                        @click="setTab(tab)" style="display: flex; flex-direction: column; align-items: center;">
                        <i :class="['bi', tabIcons[tab as keyof typeof tabIcons]]"></i>
                        <span v-if="!isMobile || activeTab === tab" class="tab-label">{{ tab }}</span>
                    </button>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="bi bi-search search-icon"></i>
                        <input v-model="searchQuery" placeholder="Search member..."
                            @input="handleSearchMembers(searchQuery)" />
                    </div>
                    <button v-if="!isMobile" class="btn btn-primary create-btn" @click="showAddModal = true">
                        <i class="bi bi-plus-circle me-1"></i> Add Member
                    </button>
                </div>
            </div>
            <FabButton v-if="isMobile" @click="showAddModal = true" icon="bi-person-plus" />
            <AddMemberModal v-if="showAddModal" @close="showAddModal = false" @add="handleAddMember" />
            <LoadingPage v-if="isLoading" />
            <div v-else>
                <MemberTable v-if="activeTab === 'Members'" :items="friendsList.data?.data || []" :loading="false"
                    @removeMember="handleRemoveMemberWrapper" />
                <MemberTable v-if="activeTab === 'Sent Invitations'"
                    :items="Array.isArray(sentInvitations.data?.data) ? sentInvitations.data.data : []"
                    :loading="false">
                    <template #card="{ item }">
                        <SentInvitationCard :invitation="item" @cancel="handleCancelInvitation" />
                    </template>
                </MemberTable>
                <MemberTable v-if="activeTab === 'Received Invitations'"
                    :items="Array.isArray(receivedInvitations.data?.data) ? receivedInvitations.data.data : []"
                    :loading="false">
                    <template #card="{ item }">
                        <InvitationCard :invitation="item" @accept="handleAcceptInvitation"
                            @decline="handleDeclineInvitation" />
                    </template>
                </MemberTable>
            </div>
        </div>
    </div>
</template>
<style scoped>
.friend-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 1.5rem 0 2rem 0;
}

.card-container {
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 2px 16px rgba(34, 34, 59, 0.07);
    padding: 2.2rem 2.2rem 2.5rem 2.2rem;
    margin-top: 1.2rem;
}

.page-title {
    display: flex;
    align-items: center;
    font-size: 1.4rem;
    font-weight: 700;
    color: #22223b;
    margin-bottom: 1.2rem;
    gap: 0.7rem;
}

.page-title-icon {
    color: #222;
}

.friend-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.2rem;
    gap: 1.5rem;
}

.tab-bar {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid #e5e7eb;
    flex: 1;
}

.tab-btn {
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.7rem 1.5rem 0.5rem 1.5rem;
    font-weight: 500;
    color: #22223b;
    font-size: 1.08rem;
    cursor: pointer;
    transition: border 0.15s, color 0.15s;
}

.tab-btn.active {
    border-bottom: 2.5px solid #2563eb;
    color: #2563eb;
    background: #f3f4f6;
    border-radius: 8px 8px 0 0;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1.2rem;
}

.search-bar {
    position: relative;
    background: #f3f4f6;
    border-radius: 2rem;
    padding: 0.2rem 1.2rem 0.2rem 2.2rem;
    display: flex;
    align-items: center;
    min-width: 260px;
    max-width: 340px;
}

.search-bar input {
    border: none;
    background: transparent;
    outline: none;
    font-size: 1.05rem;
    width: 100%;
    padding: 0.4rem 0;
}

.search-icon {
    position: absolute;
    left: 0.8rem;
    color: #94a3b8;
    font-size: 1.15rem;
}

.create-btn {
    font-weight: 500;
    border-radius: 8px;
    padding: 0.5rem 1.2rem;
    font-size: 1.05rem;
}

.tab-label {
    display: block;
    font-size: 0.92rem;
    margin-top: 2px;
}

@media (max-width: 900px) {
    .friend-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.7rem;
    }

    .header-actions {
        flex-direction: column;
        gap: 0.7rem;
        align-items: stretch;
    }

    .search-bar {
        min-width: 0;
        max-width: 100%;
    }
}

@media (max-width: 600px) {
    .tab-label {
        font-size: 0.85rem;
        margin-top: 1px;
    }

    .tab-btn {
        flex-direction: column;
        align-items: center;
        min-width: 48px;
        padding: 0.5rem 0.5rem 0.2rem 0.5rem;
    }

    .create-btn {
        display: none !important;
    }
}

.invitation-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.invitation-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.7rem 0.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.invitation-name {
    font-weight: 500;
    margin-right: 0.5rem;
}

/* 
.btn-success.btn-sm {
    background: #16a34a;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0.3rem 0.9rem;
    font-size: 0.98rem;
    margin-left: auto;
}

.btn-danger.btn-sm {
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0.3rem 0.9rem;
    font-size: 0.98rem;
    margin-left: 0.5rem;
}

.action-btns {
    display: flex;
    gap: 0.7rem;
    justify-content: flex-end;
    margin-top: 0.5rem;
} */

/* .btn-accept {
    background: #16a34a;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    transition: background 0.15s;
}

.btn-accept:hover {
    background: #22c55e;
}

.btn-decline {
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    transition: background 0.15s;
}

.btn-decline:hover {
    background: #b91c1c;
} */
</style>