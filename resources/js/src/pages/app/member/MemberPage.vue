<script lang="ts" setup>
import { ref, onMounted, onUnmounted, computed, type Ref } from 'vue';
import FabButton from '../../../components/FabButton.vue';
import LoadingPage from '../../../components/LoadingPage.vue';
import MemberTable from './components/MemberTable.vue';
import AddMemberModal from './components/AddMemberModal.vue';
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
import type { Member, MemberListResponse } from '../../../types/common';
import { useResponsive } from '../../../helper/useResponsive';
import { useErrorHandler } from '../../../helper/useErrorHandler';
import SearchInput from '../../../components/SearchInput.vue';

const tabs = ['Members', 'Sent Invitations', 'Received Invitations'];
const activeTab = ref('Members');
const showAddModal = ref(false);
const isLoading = ref(false);
const searchLoading = ref(false);
const searchQuery = ref('');
const searchLoadingState = ref(false);
const friendsList = ref<MemberListResponse>({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 10 });
const sentInvitations = ref<MemberListResponse>({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 10 });
const receivedInvitations = ref<MemberListResponse>({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 10 });
const memberCacheRef = ref<{ [key: string]: MemberListResponse }>({}) as Ref<{ [key: string]: MemberListResponse }>;
// Restore cache from localStorage if available
const cacheFromStorage = localStorage.getItem('memberCache');
if (cacheFromStorage) {
    try {
        memberCacheRef.value = JSON.parse(cacheFromStorage);
    } catch (e) {
        memberCacheRef.value = {};
    }
}
const hasFetched = ref<{ [key: string]: boolean }>({});
const currentPage = ref(1);
const loadedPages = new Set<number>();
const totalPages = ref(1);
const totalItems = ref(0);
const isLoadingMore = ref(false);
const hasMoreData = ref(true);
const allMembers = ref<Member[]>([]);


const { state } = useResponsive();
const isMobile = computed(() => state.value.isMobile);
const { withErrorHandling } = useErrorHandler();

onMounted(() => {
    setTab('Members');
});

const tabIcons = {
    'Members': 'bi-people',
    'Sent Invitations': 'bi-send',
    'Received Invitations': 'bi-inbox'
};

async function handleAddMember(friendCode: string, cb: (err: string | null) => void) {
    const result = await withErrorHandling(
        async () => {
            await makeHttpReq<{ friend_code: string }, any>('member-invitations/send', 'POST', { friend_code: friendCode });
            return true;
        },
        'Failed to send invitation'
    );

    if (result) {
        cb(null);
        showAddModal.value = false;
        showSuccess('Invitation sent!');
    } else {
        cb('Add failed');
    }
}

async function handleAcceptInvitation(id: number) {
    const result = await withErrorHandling(
        async () => {
            await makeHttpReq<undefined, any>(`member-invitations/${id}/accept`, 'POST');
            if (Array.isArray(receivedInvitations.value.data)) {
                receivedInvitations.value.data = receivedInvitations.value.data.filter((inv: any) => String(inv.id) !== String(id));
            }
            return true;
        },
        'Failed to accept invitation'
    );

    if (result) {
        showSuccess('Invitation accepted!');
    }
}

async function handleDeclineInvitation(id: number) {
    const result = await withErrorHandling(
        async () => {
            await makeHttpReq<undefined, any>(`member-invitations/${id}/decline`, 'POST');
            if (Array.isArray(receivedInvitations.value.data)) {
                receivedInvitations.value.data = receivedInvitations.value.data.filter((inv: any) => String(inv.id) !== String(id));
            }
            return true;
        },
        'Failed to decline invitation'
    );

    if (result) {
        showSuccess('Invitation declined!');
    }
}

async function handleCancelInvitation(id: number) {
    await withErrorHandling(
        async () => {
            await makeHttpReq<undefined, any>(`member-invitations/${id}/cancel`, 'DELETE');
            return true;
        },
        'Failed to cancel invitation'
    );
}

function setTab(tab: string) {
    activeTab.value = tab;
    if (tab === 'Members') {
        currentPage.value = 1; // Reset to first page when switching tab
        hasMoreData.value = true; // Reset infinite scroll state
        const cacheKey = `member_page_${searchQuery.value}_${currentPage.value}`;
        if (memberCacheRef.value[cacheKey]) {
            friendsList.value = memberCacheRef.value[cacheKey];
            isLoading.value = false;
        } else {
            fetchMembers(friendsList, memberCacheRef, isLoading, searchQuery.value, currentPage.value);
        }
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
    searchLoading.value = true;
    currentPage.value = 1; // Reset to first page when searching
    hasMoreData.value = true; // Reset infinite scroll state
    // Use a separate searchLoadingState for search, does not affect main isLoading
    fetchMembers(friendsList, memberCacheRef, searchLoadingState, q, currentPage.value).finally(() => {
        searchLoading.value = false;
    });
}

function handleRemoveMemberWrapper(member: Member) {
    handleRemoveMember(
        member,
        memberCacheRef,
        searchQuery,
        friendsList,
        isLoading
    );
}

// Removed pagination functions - now using infinite scroll

// Update pagination info when data changes
function updatePaginationInfo() {
    if (friendsList.value.data) {
        totalPages.value = (friendsList.value.data as any).last_page || 1;
        totalItems.value = (friendsList.value.data as any).total || 0;
    }
}

// Load more members for infinite scroll
async function loadMoreMembers() {
    if (isLoadingMore.value || !hasMoreData.value) return;

    const total = friendsList.value.total || 0;
    const loaded = Array.isArray(friendsList.value.data) ? friendsList.value.data.length : 0;
    const lastPage = friendsList.value.last_page || 1;
    const perPage = friendsList.value.per_page || 52;
    // If all data is loaded, do not call API again
    if (loaded >= total && total > 0) {
        hasMoreData.value = false;
        return;
    }

    const nextPage = Math.floor(loaded / perPage) + 1;
    if (nextPage > lastPage || loadedPages.has(nextPage)) {
        hasMoreData.value = false;
        return;
    }

    isLoadingMore.value = true;
    try {
        const beforeCount = Array.isArray(friendsList.value.data) ? friendsList.value.data.length : 0;
        await fetchMembers(friendsList, memberCacheRef, isLoadingMore, searchQuery.value, nextPage, false, true);
        const afterCount = Array.isArray(friendsList.value.data) ? friendsList.value.data.length : 0;
        const addedCount = afterCount - beforeCount;
        if (addedCount < perPage || nextPage >= lastPage) {
            hasMoreData.value = false;
        }
        if (addedCount > 0) {
            loadedPages.add(nextPage);
            currentPage.value = nextPage;
        }
    } catch (error) {
        console.error('Error loading more members:', error);
    } finally {
        isLoadingMore.value = false;
    }
}



// Debounce util
function debounce<T extends (...args: any[]) => void>(fn: T, delay: number): (...args: Parameters<T>) => void {
    let timer: ReturnType<typeof setTimeout> | null = null;
    return function (this: unknown, ...args: Parameters<T>) {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// Infinite scroll: listen to window scroll (debounced)
const handleWindowScroll = debounce(() => {
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    const clientHeight = window.innerHeight;
    const scrollHeight = document.documentElement.scrollHeight;
    // Only call API when scrolled near the end of the current list (95%)
    if (scrollTop + clientHeight >= scrollHeight * 0.95 && hasMoreData.value && !isLoadingMore.value) {
        loadMoreMembers();
    }
}, 200);

onMounted(() => {
    hasMoreData.value = true; // Reset infinite scroll state
    loadedPages.clear();
    const cacheKey = `member_page_${searchQuery.value}_${currentPage.value}`;
    const cached = memberCacheRef.value[cacheKey];
    if (cached && Array.isArray(cached.data) && cached.data.length > 0) {
        friendsList.value = cached;
        isLoading.value = false;
    } else {
        fetchMembers(friendsList, memberCacheRef, isLoading, searchQuery.value, currentPage.value).then(() => {
            updatePaginationInfo();
            loadedPages.add(1);
        });
    }
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
    // Listen to window scroll event
    window.addEventListener('scroll', handleWindowScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleWindowScroll);
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
                    <SearchInput v-model="searchQuery" placeholder="Search member..." :loading="searchLoading"
                        @search="handleSearchMembers" />
                    <button v-if="!isMobile" class="btn btn-primary create-btn" @click="showAddModal = true">
                        <i class="bi bi-plus-circle me-1"></i> Add Member
                    </button>
                </div>
            </div>
            <FabButton v-if="isMobile" @click="showAddModal = true" icon="bi-person-plus" />
            <AddMemberModal v-if="showAddModal" @close="showAddModal = false" @add="handleAddMember" />
            <LoadingPage v-if="isLoading && (!friendsList.data || friendsList.data.length === 0) && !searchLoading" />
            <div v-else>
                <div v-if="activeTab === 'Members'" class="members-container">
                    <MemberTable :items="Array.isArray(friendsList.data) ? friendsList.data : []"
                        :loading="isLoading && friendsList.data.length === 0"
                        @removeMember="handleRemoveMemberWrapper" />

                    <!-- Infinite scroll loading indicator -->
                    <div v-if="isLoadingMore" class="loading-more">
                        <div class="loading-spinner"></div>
                        <span>Loading more members...</span>
                    </div>

                    <!-- End of list indicator -->
                    <div v-if="!hasMoreData && Array.isArray(friendsList.data) && friendsList.data.length > 0"
                        class="end-of-list">
                        <span>No more members to load</span>
                    </div>
                </div>

                <MemberTable v-if="activeTab === 'Sent Invitations'"
                    :items="Array.isArray(sentInvitations.data) ? sentInvitations.data : []" :loading="false">
                    <template #card="{ item }">
                        <SentInvitationCard :invitation="item" @cancel="handleCancelInvitation" />
                    </template>
                </MemberTable>
                <MemberTable v-if="activeTab === 'Received Invitations'"
                    :items="Array.isArray(receivedInvitations.data) ? receivedInvitations.data : []" :loading="false">
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
}

.card-container {
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 2px 16px rgba(34, 34, 59, 0.07);
    padding: 2.2rem 2.2rem 2.5rem 2.2rem;
    margin-top: 1.2rem;
    min-height: 80vh;
}

.page-title {
    display: flex;
    align-items: center;
    font-size: 1.4rem;
    font-weight: 500;
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

    .pagination-controls {
        flex-direction: column;
        gap: 1rem;
    }

    .pagination-info {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
}

/* Infinite Scroll Styles */
.members-container {
    /* Bỏ max-height và overflow để không cuộn trong card */
    scroll-behavior: smooth;
}

.loading-more {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #6c757d;
    font-size: 0.9rem;
}

.loading-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid #e9ecef;
    border-top: 2px solid #2563eb;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 0.5rem;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

.end-of-list {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    color: #6c757d;
    font-size: 0.9rem;
    font-style: italic;
}

@media (max-width: 600px) {
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
</style>