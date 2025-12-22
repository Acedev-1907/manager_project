<template>
    <div v-if="visible" class="modal-overlay" @click.self="close">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Thêm bạn bè</h4>
                <button class="close-btn" @click="close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="search-section">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input 
                            type="text" 
                            placeholder="Tìm kiếm theo tên hoặc email..." 
                            v-model="searchQuery"
                            @input="handleSearch"
                        />
                    </div>
                </div>

                <div v-if="isLoading" class="loading-state">
                    <div class="spinner-border spinner-border-sm"></div>
                    <span>Đang tải...</span>
                </div>

                <div v-else-if="searchResults.length === 0 && searchQuery" class="no-results">
                    <i class="bi bi-search"></i>
                    <p>Không tìm thấy người dùng nào</p>
                </div>

                <div v-else-if="searchResults.length > 0" class="users-list">
                    <div 
                        v-for="user in searchResults" 
                        :key="user.id"
                        class="user-item"
                    >
                        <div class="user-avatar-wrapper">
                            <img 
                                :src="getAvatarSrc(user.avatar, user.name)" 
                                class="user-avatar" 
                                :alt="user.name"
                            />
                        </div>
                        <div class="user-info">
                            <div class="user-name">{{ user.name }}</div>
                            <div class="user-email">{{ user.email }}</div>
                        </div>
                        <button 
                            class="add-btn"
                            :disabled="isAdding !== null || isFriend(user.id)"
                            @click="addFriend(user.id)"
                        >
                            <span v-if="isAdding === user.id" class="spinner-border spinner-border-sm"></span>
                            <span v-else-if="isFriend(user.id)">
                                <i class="bi bi-check-circle-fill"></i>
                                Đã là bạn
                            </span>
                            <span v-else>
                                <i class="bi bi-person-plus"></i>
                                Thêm bạn
                            </span>
                        </button>
                    </div>
                </div>

                <div v-else class="empty-state">
                    <i class="bi bi-people"></i>
                    <p>Nhập tên hoặc email để tìm kiếm bạn bè</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
import { showSuccess, showError } from '../../../../helper/alert';
import { useUserStore } from '../../../../state/userStore';

const props = defineProps<{
    visible: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'friendAdded'): void;
}>();

const userStore = useUserStore();
// @ts-expect-error - Pinia store type inference issue
const currentUser = computed(() => userStore.user);

const searchQuery = ref('');
const searchResults = ref<Array<{
    id: number;
    name: string;
    email: string;
    avatar?: string;
}>>([]);
const isLoading = ref(false);
const isAdding = ref<number | null>(null);
const myFriends = ref<number[]>([]);

let searchTimeout: number | null = null;

const isFriend = (userId: number) => {
    return myFriends.value.includes(userId);
};

const loadMyFriends = async () => {
    try {
        const res = await makeHttpReq<never, any>('/members?per_page=100', 'GET');
        // Handle different response structures
        let data: any;
        if (res && res.data && Array.isArray(res.data.data)) {
            // Laravel resource format { data: { data: [], ...paging... } }
            data = res.data;
        } else if (res && res.data) {
            // Direct response format
            data = res.data;
        } else {
            data = res;
        }
        
        if (data && Array.isArray(data.data)) {
            myFriends.value = data.data.map((member: any) => member.id);
        }
    } catch (error) {
        // Silently fail
    }
};

const handleSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }

    searchTimeout = window.setTimeout(async () => {
        await searchUsers();
    }, 500);
};

const searchUsers = async () => {
    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }

    isLoading.value = true;
    try {
        const res = await makeHttpReq<never, { users?: any[] }>(
            `/users/all?query=${encodeURIComponent(searchQuery.value)}`,
            'GET'
        );
        
        if (res && res.users && Array.isArray(res.users)) {
            // Filter out current user and existing friends
            searchResults.value = res.users
                .filter((user: any) => 
                    user.id !== currentUser.value?.id && 
                    !myFriends.value.includes(user.id)
                )
                .map((user: any) => ({
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    avatar: user.avatar
                }));
        }
    } catch (error) {
        console.error('Error searching users:', error);
        searchResults.value = [];
    } finally {
        isLoading.value = false;
    }
};

const addFriend = async (userId: number) => {
    if (isAdding.value !== null) return;

    isAdding.value = userId;
    try {
        // Send friend request via member invitation
        await makeHttpReq<{ member_id: number }, any>(
            '/member-invitations/send',
            'POST',
            { member_id: userId }
        );
        
        showSuccess('Đã gửi lời mời kết bạn!');
        myFriends.value.push(userId); // Optimistically update
        emit('friendAdded');
    } catch (error: any) {
        showError(error?.message || 'Không thể gửi lời mời kết bạn');
    } finally {
        isAdding.value = null;
    }
};

const close = () => {
    searchQuery.value = '';
    searchResults.value = [];
    emit('close');
};

// Load friends when modal opens
if (props.visible) {
    loadMyFriends();
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 20px;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e4e6eb;
}

.modal-header h4 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: #050505;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #65676b;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
}

.close-btn:hover {
    background: #f0f2f5;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.search-section {
    margin-bottom: 20px;
}

.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #65676b;
}

.search-box input {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid #e4e6eb;
    border-radius: 20px;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s;
}

.search-box input:focus {
    border-color: #1877f2;
}

.loading-state,
.no-results,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: #65676b;
}

.loading-state {
    gap: 12px;
}

.no-results i,
.empty-state i {
    font-size: 3rem;
    margin-bottom: 12px;
    color: #bcc0c4;
}

.users-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 400px;
    overflow-y: auto;
}

.user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    transition: background 0.2s;
}

.user-item:hover {
    background: #f0f2f5;
}

.user-avatar-wrapper {
    width: 50px;
    height: 50px;
    flex-shrink: 0;
}

.user-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e4e6eb;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    color: #050505;
    font-size: 1rem;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-email {
    font-size: 0.85rem;
    color: #65676b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.add-btn {
    padding: 8px 16px;
    background: #1877f2;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s;
    white-space: nowrap;
}

.add-btn:hover:not(:disabled) {
    background: #166fe5;
}

.add-btn:disabled {
    background: #e4e6eb;
    color: #bcc0c4;
    cursor: not-allowed;
}

.add-btn i {
    font-size: 1rem;
}
</style>

