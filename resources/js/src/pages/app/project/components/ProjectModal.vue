<template>
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
                            :class="['btn', isEdit ? 'btn-warning' : 'btn-primary', 'btn-create']" :disabled="loading">
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
                                <div v-if="errors.startDate" class="text-danger error-message">{{ errors.startDate }}
                                </div>
                            </div>
                            <div class="col">
                                <DateInput v-model="projectInput.endDate" placeholder="End Date"
                                    prefix-icon="fas fa-calendar-check" :min="projectInput.startDate" />
                                <div v-if="errors.endDate" class="text-danger error-message">{{ errors.endDate }}</div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <div class="selected-members-minimal" @click="showAvailable = !showAvailable">
                                <i class="bi bi-people-fill"></i>
                                <span v-for="(id, idx) in selectedMembers.slice(0, 2)" :key="id"
                                    class="tag-minimal member-tag" :title="getMemberById(id)?.email">
                                    <img :src="getAvatarSrc(getMemberById(id)?.avatar, getMemberById(id)?.name)"
                                        class="avatar-tag" :alt="getMemberById(id)?.name" />
                                    <span class="member-name-short">{{ getMemberById(id)?.name }}</span>
                                    <button type="button" class="remove-tag-btn-minimal" @click.stop="toggleMember(id)"
                                        title="Remove">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </span>
                                <div v-if="selectedMembers.length > 2" class="selected-members-popup-wrapper"
                                    @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave"
                                    style="display: inline-block; position: relative;">
                                    <span class="tag-minimal more member-tag"
                                        style="position: relative; z-index: 10; cursor: pointer;">
                                        +{{ selectedMembers.length - 2 }}
                                    </span>
                                    <div v-if="showAll" class="popup-all-selected">
                                        <div v-for="id in selectedMembers.slice(2)" :key="id" class="popup-member-row">
                                            <img :src="getAvatarSrc(getMemberById(id)?.avatar, getMemberById(id)?.name)"
                                                class="avatar-tag" :alt="getMemberById(id)?.name" />
                                            <span class="member-name-short">{{ getMemberById(id)?.name }}</span>
                                            <button v-if="id !== props.projectInput.creator?.id"
                                                @mousedown.prevent.stop="toggleMember(id); showAll = true"
                                                title="Remove">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <span class="select-hint">Select Members</span>
                            </div>
                            <!-- Available Members xổ xuống -->
                            <div v-if="showAvailable" class="members-list-minimal">
                                <input v-model="searchQuery" type="text" class="form-control mb-2"
                                    placeholder="Search members..." />
                                <div v-if="filteredMembers.length > 0" class="user-card-list">
                                    <div v-for="(memberObj, idx) in filteredMembers" :key="memberObj.id"
                                        class="user-card">
                                        <img :src="getAvatarSrc(memberObj.avatar, memberObj.name)" class="user-avatar"
                                            :alt="memberObj.name" />
                                        <div class="user-info">
                                            <div class="user-name">{{ memberObj.name }}</div>
                                            <div class="user-email">{{ memberObj.email }}</div>
                                        </div>
                                        <button v-if="selectedMembers.includes(memberObj.id)"
                                            class="user-select-btn selected" disabled>
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button v-else @click="toggleMember(memberObj.id)" class="user-select-btn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="text-center text-muted py-2">No member found</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
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
const showTooltip = ref(false);
// Thêm biến errors để lưu lỗi các trường
const errors = ref<{ name?: string; startDate?: string; endDate?: string }>({});

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
    if (props.projectInput.members) {
        selectedMembers.value = props.projectInput.members.filter((id: number) => id !== props.projectInput.creator?.id);
    }
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

// Tự động clear ngày kết thúc nếu ngày bắt đầu mới > ngày kết thúc cũ
watch(() => projectStore.projectInput.startDate, (newStart) => {
    if (projectStore.projectInput.endDate && newStart && projectStore.projectInput.endDate < newStart) {
        projectStore.projectInput.endDate = '';
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
    background: rgba(0, 0, 0, 0.18);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-dialog {
    max-width: 650px;
    width: 100%;
}

.modal-content {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
    padding: 0 1.5rem 1.5rem 1.5rem;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    border-bottom: 1px solid #eee;
}

.header-content-centered {
    flex: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
}

.header-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-create,
.btn-cancel {
    min-width: 70px;
    padding: 8px 16px;
}

.modal-title {
    font-size: 1.3rem;
    font-weight: bold;
    color: #333;
    margin: 0;
    text-align: center;
}

.modal-subtitle {
    font-size: 1rem;
    color: #64748b;
    margin: 0;
    text-align: center;
}

.modal-body {
    padding: 1.5rem 0 0 0;
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
    gap: 10px;
    max-height: 150px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #f9fafb;
    padding: 8px 0;
}

.member-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
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
}

.tag-minimal.more {
    background: #60a5fa;
    cursor: pointer;
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
    gap: 0.5rem;
    max-height: 320px;
    overflow-y: auto;
}

.user-card {
    display: flex;
    align-items: center;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 6px rgba(34, 34, 59, 0.07);
    padding: 0.6rem 1rem;
    gap: 1rem;
    transition: box-shadow 0.2s;
}

.user-card:hover {
    box-shadow: 0 4px 16px rgba(34, 34, 59, 0.13);
}

.user-avatar {
    width: 2.2rem;
    height: 2.2rem;
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
    border-radius: 8px;
    color: #2563eb;
    font-size: 1.2em;
    width: 2.1rem;
    height: 2.1rem;
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

.member-tag {
    display: flex;
    align-items: center;
    background: #e0e7ff;
    color: #2563eb;
    border-radius: 999px;
    padding: 0.12rem 0.7rem 0.12rem 0.3rem;
    font-size: 0.97rem;
    gap: 0.3rem;
    margin-right: 0.3rem;
    box-shadow: 0 1px 4px rgba(34, 34, 59, 0.07);
    transition: background 0.18s;
    cursor: pointer;
}

.member-tag:hover {
    background: #c7d2fe;
}

.avatar-tag {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 0.3em;
    border: 2px solid #fff;
    background: #fff;
}

.member-name-short {
    max-width: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 500;
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

.popup-all-selected {
    position: absolute;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(34, 34, 59, 0.13);
    padding: 0.6rem 0.7rem 0.6rem 0.7rem;
    z-index: 3000;
    min-width: 170px;
    right: 0;
    top: 2.1rem;
    margin-top: 0;
    max-height: 200px;
    overflow-y: auto;
    animation: fadeIn 0.18s;
    border: 1px solid #e5e7eb;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tag-minimal.more.member-tag {
    position: relative;
    z-index: 10;
}

.popup-all-selected::before {
    content: '';
    position: absolute;
    top: -10px;
    right: 24px;
    border-width: 0 10px 10px 10px;
    border-style: solid;
    border-color: transparent transparent #fff transparent;
    filter: drop-shadow(0 -2px 2px rgba(34, 34, 59, 0.08));
}

.popup-member-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.15rem;
    padding: 0.15rem 0.1rem;
}

.popup-member-row:last-child {
    margin-bottom: 0;
}

.member-email-popup {
    color: #64748b;
    font-size: 0.92rem;
}
</style>