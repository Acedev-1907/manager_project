<template>
    <Teleport to="body">
        <div class="modal-backdrop">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="header-content-centered">
                            <h3 class="modal-title">{{ isEdit ? 'Update Project' : 'Add New Project' }}</h3>
                            <p class="modal-subtitle">
                                {{ isEdit ? 'Update your project details' : 'Create a project for your team' }}
                            </p>
                        </div>
                        <div class="header-actions">
                            <button @click="submitProject"
                                :class="['btn', isEdit ? 'btn-warning' : 'btn-primary', 'btn-create']"
                                :disabled="loading">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                <i v-else class="fas fa-plus"></i>
                                <span v-if="!loading">{{ isEdit ? ' Update' : ' Create' }}</span>
                                <span v-else>{{ isEdit ? 'Updating...' : 'Creating...' }}</span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-cancel" @click="emit('close')">
                                Cancel
                            </button>
                        </div>
                    </div>
                    <form @submit.prevent="submitProject">
                        <div class="modal-body">
                            <div class="mb-3">
                                <BaseInput v-model="projectInput.name" placeholder="Project Name"
                                    prefix-icon="fas fa-folder" />
                                <div v-if="errors.name" class="text-danger error-message">{{ errors.name }}</div>
                            </div>
                            <div class="form-group row mb-3" style="gap: 0.5rem;">
                                <div class="col">
                                    <DateInput v-model="projectInput.startDate" placeholder="Start Date"
                                        prefix-icon="fas fa-calendar-alt" />
                                    <div v-if="errors.startDate" class="text-danger error-message">{{ errors.startDate
                                    }}
                                    </div>
                                </div>
                                <div class="col">
                                    <DateInput v-model="projectInput.endDate" placeholder="End Date"
                                        prefix-icon="fas fa-calendar-check" :min="projectInput.startDate" />
                                    <div v-if="errors.endDate" class="text-danger error-message">{{ errors.endDate }}
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-align-left"></i>
                                    Project Description
                                </label>
                                <textarea v-model="projectInput.content" class="form-control"
                                    placeholder="Enter project description..." rows="4"
                                    style="resize: vertical; min-height: 100px;"></textarea>
                                <div v-if="errors.content" class="text-danger error-message">{{ errors.content }}</div>
                            </div>
                            <div class="form-group mb-2">
                                <div class="team-members-section">
                                    <div class="section-header">
                                        <div class="header-content">
                                            <i class="fas fa-users"></i>
                                            <span class="section-title">Team Members</span>
                                            <span class="member-count">({{ selectedMembers.length }})</span>
                                        </div>
                                        <button type="button" class="manage-btn" @click="openMemberModal">
                                            <i class="fas fa-user-plus"></i>
                                            <span>Manage</span>
                                        </button>
                                    </div>

                                    <div class="members-display">
                                        <div v-if="selectedMembers.length > 0" class="members-avatars-grid">
                                            <div v-for="(id, idx) in selectedMembers" :key="id"
                                                class="member-avatar-item">
                                                <img :src="getAvatarSrc(getMemberById(id)?.avatar, getMemberById(id)?.name)"
                                                    class="member-avatar" :alt="getMemberById(id)?.name"
                                                    :title="getMemberById(id)?.name" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Member Selection Modal -->
    <Teleport to="body">
        <div v-if="showMemberModal" class="member-modal-backdrop" @click="closeMemberModal">
            <div class="member-modal-dialog" @click.stop>
                <div class="member-modal-content">
                    <div class="member-modal-header">
                        <div class="header-content-centered">
                            <h3 class="member-modal-title">Manage Team Members</h3>
                            <p class="member-modal-subtitle">Select members for this project</p>
                        </div>
                        <button type="button" class="member-modal-close" @click="closeMemberModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="member-modal-body">
                        <!-- Search Box -->
                        <div class="member-search-box">
                            <i class="fas fa-search"></i>
                            <input v-model="searchQuery" type="text" placeholder="Search members..."
                                @focus="(event) => (event.target as HTMLInputElement)?.select()" ref="searchInput" />
                        </div>

                        <!-- Members List -->
                        <div class="member-modal-list">
                            <div v-for="(memberObj, idx) in filteredMembers" :key="memberObj.id"
                                class="member-modal-row" @click="toggleMember(memberObj.id)"
                                :class="{ 'selected-member': selectedMembers.includes(memberObj.id) }">
                                <div class="member-avatar-container">
                                    <img :src="getAvatarSrc(memberObj.avatar, memberObj.name)" class="member-avatar"
                                        :alt="memberObj.name" />
                                    <div v-if="selectedMembers.includes(memberObj.id)" class="selected-badge">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                                <div class="member-details">
                                    <div class="member-name">{{ memberObj.name }}</div>
                                    <div class="member-email">{{ memberObj.email }}</div>
                                </div>
                                <div class="member-action">
                                    <i v-if="selectedMembers.includes(memberObj.id)" class="fas fa-check selected"></i>
                                    <i v-else class="fas fa-plus available"></i>
                                </div>
                            </div>
                            <div v-if="filteredMembers.length === 0" class="no-results">
                                <i class="fas fa-search"></i>
                                <span>No members found</span>
                            </div>
                        </div>

                        <!-- Selected Members Summary -->
                        <div class="selected-summary">
                            <div class="summary-header">
                                <i class="fas fa-users"></i>
                                <span>Selected Members ({{ selectedMembers.length }})</span>
                                <span v-if="selectedMembers.length > 0" class="clear-all-btn" @click="clearAllMembers">
                                    Clear All
                                </span>
                            </div>
                            <div class="selected-members-display">
                                <div v-for="(id, idx) in selectedMembers" :key="id" class="member-tag">
                                    <img :src="getAvatarSrc(getMemberById(id)?.avatar, getMemberById(id)?.name)"
                                        class="member-avatar" :alt="getMemberById(id)?.name" />
                                    <span class="member-name">{{ getMemberById(id)?.name }}</span>
                                    <button type="button" class="remove-btn" @click.stop="toggleMember(id)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div v-if="selectedMembers.length === 0" class="empty-state">
                                    <i class="fas fa-user-plus"></i>
                                    <span>No members selected</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="member-modal-footer">
                        <button type="button" class="btn-cancel" @click="closeMemberModal">
                            Cancel
                        </button>
                        <button type="button" class="btn-confirm" @click="closeMemberModal">
                            Confirm Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue';
import BaseInput from '../../../../components/BaseInput.vue';
import DateInput from '../../../../components/DateInput.vue';
import { useGetMembers } from '../../member/actions/getMember';
import { projectStore } from '../store/projectStore';
import { getAvatarSrc } from '../../../../helper/avatar';

const props = defineProps<{ isEdit: boolean, projectInput: any, loading: boolean }>()
const emit = defineEmits(['close', 'submit'])

const { getMembers, memberData } = useGetMembers();
const selectedMembers = ref<number[]>([]);
const currentUserId = ref<number | null>(null);
const searchQuery = ref('');
const showAvailable = ref(false);
const showAll = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);
const showMemberModal = ref(false);
// Thêm biến errors để lưu lỗi các trường
const errors = ref<{ name?: string; startDate?: string; endDate?: string; content?: string }>({});

let closeTimeout: ReturnType<typeof setTimeout> | null = null;
function handleMouseLeave() {
    closeTimeout = setTimeout(() => { showAll.value = false }, 180);
}
function handleMouseEnter() {
    if (closeTimeout) clearTimeout(closeTimeout);
    showAll.value = true;
}

onMounted(async () => {
    projectStore.edit = props.isEdit;
    await getMembers(1, '');
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    currentUserId.value = userData.id;

    // Khởi tạo selectedMembers từ project data
    if (props.projectInput.members && Array.isArray(props.projectInput.members)) {
        // Nếu có trường members là array
        selectedMembers.value = props.projectInput.members.filter((id: number) => id !== props.projectInput.creator?.id);
    } else if (props.projectInput.users && Array.isArray(props.projectInput.users)) {
        // Nếu có trường users là array (thường dùng cho edit mode)
        selectedMembers.value = props.projectInput.users
            .filter((user: any) => user.id !== props.projectInput.creator?.id)
            .map((user: any) => user.id);
    } else if (props.projectInput.project_users && Array.isArray(props.projectInput.project_users)) {
        // Nếu có trường project_users là array
        selectedMembers.value = props.projectInput.project_users
            .filter((user: any) => user.id !== props.projectInput.creator?.id)
            .map((user: any) => user.id);
    }



    // Add class to body when modal is mounted
    document.body.classList.add('modal-open');
});

onUnmounted(() => {
    // Remove class from body when modal is unmounted
    document.body.classList.remove('modal-open');
});

function toggleMember(id: number) {
    if (selectedMembers.value.includes(id)) {
        selectedMembers.value = selectedMembers.value.filter(m => m !== id);
        // Nếu xóa xong không còn user nào, tự động đóng popup
        if (selectedMembers.value.length === 0) {
            showAll.value = false;
        }
    } else {
        selectedMembers.value.push(id);
    }
}

// Hàm validate các trường bắt buộc
function validate() {
    errors.value = {};
    if (!props.projectInput.name || !props.projectInput.name.trim()) {
        errors.value.name = 'Project name is required';
    }
    if (!props.projectInput.startDate) {
        errors.value.startDate = 'Start date is required';
    }
    if (!props.projectInput.endDate) {
        errors.value.endDate = 'End date is required';
    }
    return Object.keys(errors.value).length === 0;
}

function submitProject() {
    if (!validate()) return;
    emit('submit', { ...props.projectInput, members: selectedMembers.value });
}

const filteredMembers = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase();
    // Lọc bỏ creator khỏi danh sách chọn member
    return (memberData.value?.data?.data || [])
        .filter(m => m.id !== currentUserId.value && m.id !== props.projectInput.creator?.id)
        .filter(m =>
            m.name.toLowerCase().includes(keyword) ||
            m.email.toLowerCase().includes(keyword)
        );
});

function getMemberById(id: number) {
    let user = (memberData.value?.data?.data || []).find(m => m.id === id);
    if (!user && props.projectInput.users) {
        user = props.projectInput.users.find((u: any) => u.id === id);
    }
    return user;
}

function openMemberModal() {
    showMemberModal.value = true;
}

function closeMemberModal() {
    showMemberModal.value = false;
}

function clearAllMembers() {
    selectedMembers.value = [];
}

// Tự động clear ngày kết thúc nếu ngày bắt đầu mới > ngày kết thúc cũ
watch(() => projectStore.projectInput.startDate, (newStart) => {
    if (projectStore.projectInput.endDate && newStart && projectStore.projectInput.endDate < newStart) {
        projectStore.projectInput.endDate = '';
    }
});

// Watch for dropdown open to focus search input
watch(showAvailable, async (newValue) => {
    if (newValue) {
        await nextTick();
        searchInput.value?.focus();
    }
});

// Watch for member modal open to focus search input
watch(showMemberModal, async (newValue) => {
    if (newValue) {
        await nextTick();
        searchInput.value?.focus();
    }
});
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999999 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    /* Ensure modal is above all elements including Kanban page */
    isolation: isolate;
}

/* Global style to hide navbar when modal is open */
body.modal-open .top-navbar {
    z-index: 1 !important;
    opacity: 0.3;
    pointer-events: none;
}

body.modal-open .navbar-avatar-btn {
    z-index: 1 !important;
}

body.modal-open .navbar-desktop-dropdown {
    z-index: 1 !important;
}

body.modal-open .navbar-mobile-overlay {
    z-index: 1 !important;
}

/* Force all navbar elements to be behind modal */
body.modal-open nav,
body.modal-open .top-navbar,
body.modal-open .navbar-avatar-btn,
body.modal-open .navbar-desktop-dropdown,
body.modal-open .navbar-mobile-overlay,
body.modal-open .navbar-mobile-menu,
body.modal-open .navbar-mobile-dropdown {
    z-index: 1 !important;
    opacity: 0.3;
    pointer-events: none;
}

/* Alternative: Hide navbar completely when modal is open */
body.modal-open .top-navbar {
    display: none !important;
}

/* Force hide all navbar elements when modal is open */
body.modal-open nav,
body.modal-open .top-navbar,
body.modal-open .navbar-avatar-btn,
body.modal-open .navbar-desktop-dropdown,
body.modal-open .navbar-mobile-overlay,
body.modal-open .navbar-mobile-menu,
body.modal-open .navbar-mobile-dropdown {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

/* Special handling for Kanban page */
body.modal-open .kanban-container {
    z-index: 1 !important;
}

/* Force all elements to be behind modal when modal is open */
body.modal-open * {
    z-index: auto !important;
}

body.modal-open .modal-backdrop,
body.modal-open .modal-content {
    z-index: 999999999 !important;
}

/* Ensure modal is rendered at document body level */
.modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    z-index: 999999999 !important;
}

/* Force modal to be above everything in Kanban context */
body.modal-open .modal-backdrop {
    z-index: 99999999 !important;
}

body.modal-open .modal-content {
    z-index: 100000000 !important;
}

/* Additional force for Kanban page */
body.modal-open .kanban-container .modal-backdrop {
    z-index: 999999999 !important;
}

body.modal-open .kanban-container .modal-content {
    z-index: 1000000000 !important;
}

/* Global override for any page with modal */
.modal-backdrop {
    z-index: 99999999 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
}

.modal-content {
    z-index: 100000000 !important;
    position: relative !important;
}

/* Force modal to be above Kanban page specifically */
.kanban-container .modal-backdrop {
    z-index: 999999999 !important;
}

.kanban-container .modal-content {
    z-index: 1000000000 !important;
}

.modal-dialog {
    max-width: 550px;
    width: 100%;
    max-height: 80vh;
}

.modal-content {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    z-index: 10000000 !important;
    /* Ensure content is above all elements */
    isolation: isolate;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}

/* Mobile responsive for modal header */
@media (max-width: 768px) {
    .modal-header {
        padding: 1rem 1.25rem 0.75rem 1.25rem;
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }

    .header-content-centered {
        text-align: center;
    }

    .header-actions {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .modal-header {
        padding: 0.75rem 1rem 0.5rem 1rem;
        gap: 10px;
    }
}

.header-content-centered {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

/* Mobile responsive for header actions */
@media (max-width: 768px) {
    .header-actions {
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .header-actions {
        gap: 6px;
        flex-direction: column;
        align-items: stretch;
    }

    .header-actions .btn-create,
    .header-actions .btn-cancel {
        width: 100%;
        justify-content: center;
    }
}

.btn-create,
.btn-cancel {
    min-width: 80px;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-cancel {
    background: transparent !important;
    color: #6b7280 !important;
    border: none !important;
}

.btn-cancel:hover {
    background: #f3f4f6 !important;
    color: #374151 !important;
}

.btn-create:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Mobile responsive for buttons */
@media (max-width: 768px) {

    .btn-create,
    .btn-cancel {
        min-width: 70px;
        padding: 8px 16px;
        font-size: 0.9rem;
        border-radius: 6px;
        gap: 4px;
    }

    .btn-create i,
    .btn-cancel i {
        font-size: 0.85rem;
    }
}

@media (max-width: 480px) {

    .btn-create,
    .btn-cancel {
        min-width: 60px;
        padding: 6px 12px;
        font-size: 0.85rem;
        border-radius: 5px;
        gap: 3px;
    }

    .btn-create i,
    .btn-cancel i {
        font-size: 0.8rem;
    }
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    text-align: center;
    line-height: 1.2;
}

.modal-subtitle {
    font-size: 1rem;
    color: #64748b;
    margin: 0;
    text-align: center;
}

/* Mobile responsive for modal title and subtitle */
@media (max-width: 768px) {
    .modal-title {
        font-size: 1.3rem;
    }

    .modal-subtitle {
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .modal-title {
        font-size: 1.2rem;
    }

    .modal-subtitle {
        font-size: 0.85rem;
    }
}

.modal-body {
    padding: 1.25rem 1.5rem 1.5rem 1.5rem;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

/* Mobile responsive for modal body */
@media (max-width: 768px) {
    .modal-body {
        padding: 1rem 1.25rem 1.25rem 1.25rem;
    }
}

@media (max-width: 480px) {
    .modal-body {
        padding: 0.75rem 1rem 1rem 1rem;
    }
}

.form-group {
    margin-bottom: 18px;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.members-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #f9fafb;
    padding: 6px 0;
}

.member-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-bottom: 1px solid #f3f4f6;
    background: white;
    transition: all 0.3s ease;
}

.member-item:last-child {
    border-bottom: none;
}

.member-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.member-avatar-small {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
}

.member-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.member-details .member-name {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
}

.member-details .member-id {
    font-size: 11px;
    color: #6b7280;
    font-weight: 400;
}

.add-member-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 4px;
}

.add-member-btn.selected {
    background: #e5e7eb !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    border: 1px solid #d1d5db;
    box-shadow: none;
    opacity: 0.8;
}

.add-member-btn.selected .fa-check {
    color: #22c55e;
}

.selected-members {
    margin-bottom: 0.5rem;
}

.selected-members-title {
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: #2563eb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.selected-members-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.selected-member-tag {
    display: flex;
    align-items: center;
    background: #2563eb;
    color: #fff;
    border-radius: 999px;
    padding: 0.25rem 0.75rem 0.25rem 0.5rem;
    font-size: 0.95rem;
    gap: 0.5rem;
    box-shadow: 0 2px 6px rgba(34, 34, 59, 0.08);
    max-width: 180px;
    min-width: 0;
    flex-shrink: 0;
    overflow: hidden;
}

.avatar-tag {
    background: #fff;
    color: #2563eb;
    border-radius: 50%;
    width: 1.7em;
    height: 1.7em;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 0.4em;
    font-size: 1em;
    object-fit: cover;
    border: 2px solid #e0e7ff;
}

.remove-tag-btn {
    background: none;
    border: none;
    color: #fff;
    margin-left: 0.2em;
    font-size: 1.1em;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
}

.remove-tag-btn:hover {
    color: #ff4d4f;
}

.selected-members-minimal {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f3f6fd;
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    min-height: 2.2rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.selected-members-minimal .select-hint {
    color: #2563eb;
    font-size: 0.95em;
    margin-left: 0.5em;
}

.tag-minimal {
    display: inline-flex;
    align-items: center;
    background: #2563eb;
    color: #fff;
    border-radius: 999px;
    padding: 0.12rem 0.7rem 0.12rem 0.6rem;
    font-size: 0.95rem;
    gap: 0.35rem;
    box-shadow: 0 1px 4px rgba(34, 34, 59, 0.07);
    line-height: 1.1;
    height: 1.7rem;
    min-width: 0;
    max-width: 160px;
    flex-shrink: 0;
    overflow: hidden;
}

.tag-minimal.more {
    background: #60a5fa;
    cursor: pointer;
}

.tag-minimal span,
.selected-member-tag span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

.remove-tag-btn-minimal {
    background: none;
    border: none;
    color: #2563eb;
    font-size: 1em;
    cursor: pointer;
    margin-left: 0.1em;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 1.2em;
    width: 1.2em;
    padding: 0;
    border-radius: 50%;
    transition: background 0.15s;
}

.remove-tag-btn-minimal:hover {
    background: #fee2e2;
    color: #b91c1c;
}

.badge-count {
    background: #bfdbfe;
    color: #2563eb;
    font-weight: 500;
    border-radius: 999px;
    padding: 0.12rem 0.7rem;
    font-size: 0.97rem;
    margin-right: 0.3rem;
    cursor: default;
    box-shadow: none;
    transition: none;
}

.badge-count:hover {
    background: #bfdbfe;
    color: #2563eb;
}

.user-card-list {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    max-height: 180px;
    overflow-y: auto;
}

.user-card {
    display: flex;
    align-items: center;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(34, 34, 59, 0.07);
    padding: 0.5rem 0.75rem;
    gap: 0.75rem;
    transition: box-shadow 0.2s;
}

.user-card:hover {
    box-shadow: 0 4px 16px rgba(34, 34, 59, 0.13);
}

.user-avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e0e7ff;
    background: #fff;
    display: block;
}

.user-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.user-name {
    font-weight: 500;
    font-size: 1rem;
}

.user-email {
    font-size: 0.92rem;
    color: #64748b;
}

.user-select-btn {
    background: #f3f6fd;
    border: none;
    border-radius: 6px;
    color: #2563eb;
    font-size: 1.1em;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
}

.user-select-btn.selected {
    color: #22c55e;
    background: #e0fbe0;
    cursor: default;
}

.user-select-btn:hover:not(.selected) {
    background: #2563eb;
    color: #fff;
}

.text-danger {
    color: #e3342f;
    margin-top: 2px;
    margin-bottom: 2px;
}

.error-message {
    font-size: 0.95em;
    margin-top: 2px;
    min-height: 18px;
    line-height: 1.2;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    color: #212529;
    background-color: #fff;
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-control::placeholder {
    color: #6c757d;
    opacity: 1;
}

/* Team Members Section - Compact Design */
.team-members-section {
    margin-bottom: 1rem;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-content i {
    color: #3b82f6;
    font-size: 1rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.member-count {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 500;
}

.manage-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.4rem 0.8rem;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    box-shadow: 0 1px 3px rgba(16, 185, 129, 0.2);
}

.manage-btn:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
}

.manage-btn i {
    font-size: 0.75rem;
}

/* Members Display - Compact Avatars */
.members-display {
    min-height: 40px;
}

.members-avatars-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    min-height: 40px;
    width: 100%;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.members-avatars-grid::-webkit-scrollbar {
    height: 4px;
}

.members-avatars-grid::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 2px;
}

.members-avatars-grid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.members-avatars-grid::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.member-avatar-item {
    position: relative;
    transition: all 0.2s ease;
}

.member-avatar-item:hover {
    transform: scale(1.05);
}

.member-avatar {
    width: 42px;
    height: 42px;
    border: 2px solid #e5e7eb;
    border-radius: 50%;
    object-fit: cover;
    transition: all 0.2s ease;
    cursor: pointer;
}

.member-avatar:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
}

/* Empty State - Compact */
.empty-members-state {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    color: #6b7280;
    font-size: 0.9rem;
    min-height: 40px;
}

.empty-members-state i {
    color: #9ca3af;
    font-size: 0.9rem;
}

.add-members-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.3rem 0.6rem;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    box-shadow: 0 1px 3px rgba(59, 130, 246, 0.2);
    margin-left: auto;
}

.add-members-btn:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
}

.add-members-btn i {
    font-size: 0.7rem;
}

/* Header */
.members-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 7px 7px 0 0;
    cursor: pointer;
    transition: background 0.2s ease;
}

.members-header:hover {
    background: #f1f5f9;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.header-left i {
    color: #3b82f6;
    font-size: 0.9rem;
}

.header-left span {
    color: #374151;
    font-weight: 500;
    font-size: 0.9rem;
}

.member-count {
    color: #6b7280;
    font-size: 0.8rem;
}

.toggle-btn {
    background: none;
    border: none;
    color: #6b7280;
    font-size: 0.8rem;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.toggle-btn:hover {
    background: #e5e7eb;
    color: #3b82f6;
}

/* Selected Members */
.selected-members {
    padding: 8px 12px;
    min-height: 40px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}

.member-tag {
    display: flex;
    align-items: center;
    background: #3b82f6;
    color: white;
    border-radius: 16px;
    padding: 4px 8px;
    font-size: 0.8rem;
    gap: 6px;
    box-shadow: 0 1px 3px rgba(59, 130, 246, 0.2);
    transition: all 0.2s ease;
    animation: fadeIn 0.2s ease;
}

.member-tag:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
}

.member-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.member-name {
    font-weight: 500;
    white-space: nowrap;
    font-size: 0.8rem;
}

.remove-btn {
    background: none;
    border: none;
    color: white;
    font-size: 0.7rem;
    cursor: pointer;
    padding: 0;
    width: 14px;
    height: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
    line-height: 1;
}

.remove-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

.remove-btn i {
    font-size: 0.7rem;
}

.empty-state {
    color: #9ca3af;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px;
}

.empty-state i {
    font-size: 0.8rem;
    opacity: 0.6;
}

/* Inline Dropdown */
.members-dropdown-inline {
    background: white;
    border: 1px solid #3b82f6;
    border-top: none;
    border-bottom: 1px solid #e5e7eb;
    animation: slideDown 0.2s ease;
    max-height: 250px;
    overflow: hidden;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.search-box {
    position: relative;
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
}

.search-box i {
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 0.9rem;
}

.search-box input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    background: white;
}

.search-box input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.members-list {
    max-height: 200px;
    overflow-y: auto;
    padding-bottom: 8px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.members-list::-webkit-scrollbar {
    width: 6px;
}

.members-list::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.members-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.members-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.member-row {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f3f4f6;
    position: relative;
}

.member-row:hover {
    background: #f8fafc;
    transform: translateX(2px);
}

.member-row.selected-member {
    background: #eff6ff;
    border-left: 3px solid #3b82f6;
}

.member-row:last-child {
    border-bottom: none;
    margin-bottom: 4px;
}

.member-avatar-container {
    position: relative;
    margin-right: 12px;
    flex-shrink: 0;
}

.member-row .member-avatar {
    width: 36px;
    height: 36px;
    border: 2px solid #e5e7eb;
    border-radius: 50%;
    object-fit: cover;
    transition: all 0.2s ease;
}

.selected-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 16px;
    height: 16px;
    background: #10b981;
    border: 2px solid white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.6rem;
}

.member-details {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.member-details .member-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9rem;
    margin-bottom: 2px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.member-details .member-email {
    color: #64748b;
    font-size: 0.8rem;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.member-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    transition: all 0.2s ease;
    flex-shrink: 0;
    margin-left: 8px;
}

.member-action i {
    font-size: 0.8rem;
}

.member-action i.selected {
    color: #10b981;
}

.member-action i.available {
    color: #3b82f6;
}

.member-row:hover .member-action {
    background: #f1f5f9;
}

.no-results {
    padding: 24px 16px;
    text-align: center;
    color: #6b7280;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #f8fafc;
    border-radius: 8px;
    margin: 8px;
}

.no-results i {
    font-size: 1rem;
    opacity: 0.6;
    color: #9ca3af;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Remove old unused CSS */

/* Modal Body */
.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modal-backdrop {
        padding: 12px;
    }

    .modal-dialog {
        max-width: 95vw;
        max-height: 90vh;
    }

    .modal-content {
        max-height: 90vh;
    }

    .modal-header {
        padding: 1rem;
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }

    .header-content-centered {
        text-align: center;
    }

    .header-actions {
        justify-content: center;
        gap: 8px;
    }

    .btn-create,
    .btn-cancel {
        min-width: 70px;
        padding: 8px 16px;
        font-size: 0.9rem;
    }

    .modal-title {
        font-size: 1.3rem;
    }

    .modal-body {
        padding: 1rem;
    }

    .form-group.row {
        flex-direction: column;
        gap: 1rem;
    }

    .form-group.row .col {
        width: 100%;
    }

    .members-section {
        margin-bottom: 1rem;
    }

    .members-header {
        padding: 8px 10px;
    }

    .selected-members {
        padding: 6px 10px;
    }

    .member-tag {
        font-size: 0.75rem;
        padding: 3px 6px;
    }

    .member-avatar {
        width: 18px;
        height: 18px;
    }

    .members-dropdown-inline {
        max-height: 220px;
    }

    .members-list {
        max-height: 170px;
    }

    .search-box {
        padding: 10px 12px;
    }

    .search-box input {
        padding: 8px 10px 8px 32px;
        font-size: 0.85rem;
    }

    .search-box i {
        left: 22px;
        font-size: 0.85rem;
    }

    .member-row {
        padding: 10px 12px;
    }

    .member-row .member-avatar {
        width: 28px;
        height: 28px;
    }

    .member-action {
        width: 18px;
        height: 18px;
        margin-left: 4px;
    }

    .member-details .member-name {
        font-size: 0.85rem;
    }

    .member-details .member-email {
        font-size: 0.75rem;
    }

    .member-action {
        width: 20px;
        height: 20px;
    }

    .member-action i {
        font-size: 0.75rem;
    }

    .selected-badge {
        width: 14px;
        height: 14px;
        font-size: 0.5rem;
    }
}

@media (max-width: 480px) {
    .modal-backdrop {
        padding: 8px;
    }

    .modal-dialog {
        max-height: 95vh;
    }

    .modal-content {
        max-height: 95vh;
    }

    .modal-header {
        padding: 0.75rem;
    }

    .modal-title {
        font-size: 1.2rem;
    }

    .modal-subtitle {
        font-size: 0.9rem;
    }

    .modal-body {
        padding: 0.75rem;
    }

    .btn-create,
    .btn-cancel {
        min-width: 60px;
        padding: 6px 12px;
        font-size: 0.85rem;
    }

    .members-section {
        margin-bottom: 0.75rem;
    }

    .members-header {
        padding: 6px 8px;
    }

    .header-left span {
        font-size: 0.8rem;
    }

    .member-count {
        font-size: 0.7rem;
    }

    .selected-members {
        padding: 4px 8px;
        min-height: 32px;
    }

    .member-tag {
        font-size: 0.7rem;
        padding: 2px 5px;
        gap: 4px;
    }

    .member-avatar {
        width: 16px;
        height: 16px;
    }

    .members-dropdown-inline {
        max-height: 180px;
    }

    .search-box {
        padding: 8px 10px;
    }

    .search-box input {
        padding: 6px 8px 6px 28px;
        font-size: 0.8rem;
    }

    .search-box i {
        left: 20px;
        font-size: 0.8rem;
    }

    .members-list {
        max-height: 140px;
    }

    .member-row {
        padding: 8px 10px;
    }

    .member-row .member-avatar {
        width: 24px;
        height: 24px;
    }

    .member-details .member-name {
        font-size: 0.8rem;
    }

    .member-details .member-email {
        font-size: 0.7rem;
    }

    .member-action {
        width: 18px;
        height: 18px;
    }

    .member-action i {
        font-size: 0.7rem;
    }

    .selected-badge {
        width: 12px;
        height: 12px;
        font-size: 0.45rem;
    }

    .remove-btn {
        width: 12px;
        height: 12px;
    }

    .remove-btn i {
        font-size: 0.6rem;
    }

    .empty-state {
        font-size: 0.7rem;
        padding: 6px;
    }

    .members-dropdown-inline {
        max-height: 180px;
    }

    .search-box {
        padding: 6px 8px;
    }

    .search-box input {
        padding: 4px 6px 4px 24px;
        font-size: 0.7rem;
    }

    .search-box i {
        left: 16px;
        font-size: 0.7rem;
    }

    .members-list {
        max-height: 140px;
    }

    .member-row {
        padding: 6px 8px;
    }

    .member-row .member-avatar {
        width: 32px;
        height: 32px;
    }

    .member-action {
        width: 20px;
        height: 20px;
        margin-left: 6px;
    }

    .member-details .member-name {
        font-size: 0.7rem;
    }

    .member-details .member-email {
        font-size: 0.6rem;
    }

    .member-action {
        width: 16px;
        height: 16px;
    }

    .member-action i {
        font-size: 0.6rem;
    }

    .no-results {
        padding: 12px;
        font-size: 0.7rem;
    }
}

/* Member Modal Styles */
.member-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    z-index: 999999999 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    isolation: isolate;
}

.member-modal-dialog {
    max-width: 650px;
    width: 100%;
    max-height: 85vh;
}

.member-modal-content {
    background: white;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    z-index: 1000000000 !important;
    isolation: isolate;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.member-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.member-modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    text-align: center;
    line-height: 1.2;
}

.member-modal-subtitle {
    font-size: 0.9rem;
    color: #64748b;
    margin: 0;
    text-align: center;
}

.member-modal-close {
    background: none;
    border: none;
    color: #6b7280;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
    transition: all 0.2s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.member-modal-close:hover {
    background: #e2e8f0;
    color: #374151;
    transform: scale(1.1);
}

.member-modal-body {
    padding: 1rem 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.member-search-box {
    position: relative;
    margin-bottom: 1rem;
}

.member-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 0.9rem;
}

.member-search-box input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    background: #f9fafb;
}

.member-search-box input:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.member-modal-list {
    max-height: 250px;
    overflow-y: auto;
    margin-bottom: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #f9fafb;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.member-modal-list::-webkit-scrollbar {
    width: 4px;
}

.member-modal-list::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 2px;
}

.member-modal-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.member-modal-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.member-modal-row {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f3f4f6;
    background: white;
    position: relative;
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
}

.member-modal-row:hover {
    background: #f8fafc;
}

.member-modal-row.selected-member {
    background: #eff6ff;
    border-left: 3px solid #3b82f6;
}

.member-modal-row:last-child {
    border-bottom: none;
}

.member-modal-row .member-avatar {
    width: 36px;
    height: 36px;
    border: 2px solid #e5e7eb;
    border-radius: 50%;
    object-fit: cover;
    transition: all 0.2s ease;
}

.selected-summary {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 0.75rem;
}

.summary-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 0.75rem;
    color: #374151;
    font-weight: 600;
    font-size: 0.9rem;
}

.summary-header i {
    color: #3b82f6;
}

.clear-all-btn {
    margin-left: auto;
    color: #ef4444;
    font-size: 0.8rem;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.clear-all-btn:hover {
    background: #fef2f2;
    color: #dc2626;
}

.selected-members-display {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    overflow-y: auto;
    max-height: 120px;
    width: 100%;
    box-sizing: border-box;
    padding: 8px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.selected-members-display::-webkit-scrollbar {
    width: 4px;
}

.selected-members-display::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 2px;
}

.selected-members-display::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.selected-members-display::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.member-modal-footer {
    display: flex;
    gap: 10px;
    padding: 1rem 1.5rem;
    border-top: 1px solid #f1f5f9;
    flex-shrink: 0;
    justify-content: flex-end;
    background: #f8fafc;
}

.btn-confirm {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
}

.btn-confirm:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
}

/* Responsive for Member Modal */
@media (max-width: 768px) {
    .member-modal-backdrop {
        padding: 16px;
    }

    .member-modal-dialog {
        max-width: 95vw;
        max-height: 90vh;
    }

    .tag-minimal span,
    .selected-member-tag span {
        max-width: 80px;
    }

    .selected-members-display {
        gap: 4px;
        padding: 6px;
        max-height: 100px;
        grid-template-columns: repeat(4, 1fr);
    }

    .selected-members-display::-webkit-scrollbar {
        width: 3px;
    }

    .member-modal-content {
        max-height: 90vh;
    }

    .member-modal-header {
        padding: 1rem 1.25rem;
    }

    .member-modal-title {
        font-size: 1.2rem;
    }

    .member-modal-body {
        padding: 0.75rem 1.25rem;
    }

    .member-modal-list {
        max-height: 220px;
    }

    .member-modal-row {
        padding: 10px 14px;
    }

    .member-modal-row .member-avatar {
        width: 32px;
        height: 32px;
    }

    .member-modal-footer {
        padding: 0.75rem 1.25rem;
    }
}

@media (max-width: 480px) {
    .member-modal-backdrop {
        padding: 12px;
    }

    .member-modal-dialog {
        max-height: 95vh;
    }

    .tag-minimal span,
    .selected-member-tag span {
        max-width: 60px;
    }

    .selected-members-display {
        gap: 3px;
        padding: 4px;
        max-height: 80px;
        grid-template-columns: repeat(3, 1fr);
    }

    .selected-members-display::-webkit-scrollbar {
        width: 2px;
    }

    .member-modal-content {
        max-height: 95vh;
    }

    .member-modal-header {
        padding: 0.75rem 1rem;
    }

    .member-modal-title {
        font-size: 1.1rem;
    }

    .member-modal-subtitle {
        font-size: 0.8rem;
    }

    .member-modal-body {
        padding: 0.5rem 1rem;
    }

    .member-modal-list {
        max-height: 180px;
    }

    .member-modal-row {
        padding: 8px 12px;
    }

    .member-modal-row .member-avatar {
        width: 28px;
        height: 28px;
    }

    .member-modal-footer {
        padding: 0.75rem 1rem;
        flex-direction: column;
        gap: 8px;
    }

    .btn-confirm,
    .btn-cancel {
        width: 100%;
        padding: 12px 16px;
    }
}

/* Team Members Section Responsive */
@media (max-width: 768px) {
    .section-header {
        margin-bottom: 0.5rem;
    }

    .header-content {
        gap: 0.4rem;
    }

    .header-content i {
        font-size: 0.9rem;
    }

    .section-title {
        font-size: 0.95rem;
    }

    .member-count {
        font-size: 0.85rem;
    }

    .manage-btn {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
    }

    .members-avatars-grid {
        gap: 0.3rem;
        min-height: 35px;
    }

    .member-avatar {
        width: 36px;
        height: 36px;
    }

    .members-avatars-grid {
        gap: 6px;
        padding-bottom: 2px;
    }

    .members-avatars-grid::-webkit-scrollbar {
        height: 3px;
    }


}

@media (max-width: 480px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .header-content {
        width: 100%;
    }

    .header-content i {
        font-size: 0.85rem;
    }

    .section-title {
        font-size: 0.9rem;
    }

    .member-count {
        font-size: 0.8rem;
    }

    .manage-btn {
        width: 100%;
        justify-content: center;
        padding: 0.4rem 0.8rem;
    }

    .members-avatars-grid {
        gap: 0.25rem;
        min-height: 30px;
    }

    .member-avatar {
        width: 32px;
        height: 32px;
    }

    .members-avatars-grid {
        gap: 4px;
        padding-bottom: 1px;
    }

    .members-avatars-grid::-webkit-scrollbar {
        height: 2px;
    }



    .add-members-btn {
        width: 100%;
        justify-content: center;
        padding: 0.5rem 1rem;
    }
}
</style>