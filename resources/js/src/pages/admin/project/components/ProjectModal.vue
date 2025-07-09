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
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-folder"></i> Project Name</label>
                            <BaseInput v-model="projectInput.name" placeholder="Enter project name..." />
                        </div>
                        <div class="form-group row">
                            <div class="col-6">
                                <label class="form-label"><i class="fas fa-calendar-alt"></i> Start Date</label>
                                <DateInput v-model="projectInput.startDate" placeholder="DD/MM/YYYY" />
                            </div>
                            <div class="col-6">
                                <label class="form-label"><i class="fas fa-calendar-check"></i> End Date</label>
                                <DateInput v-model="projectInput.endDate" placeholder="DD/MM/YYYY"
                                    :min="projectInput.startDate" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-users"></i> Invite Members</label>
                            <!-- Selected Members UI -->
                            <div v-if="selectedMembers.length > 0" class="selected-members mb-2">
                                <div class="selected-members-title">
                                    <i class="bi bi-people-fill"></i> Selected Members ({{ selectedMembers.length }})
                                </div>
                                <div class="selected-members-list">
                                    <span v-for="id in selectedMembers" :key="id" class="selected-member-tag">
                                        <span class="avatar-tag">
                                            {{ getMemberById(id)?.name?.charAt(0).toUpperCase() || '?' }}
                                        </span>
                                        {{ getMemberById(id)?.name || 'Unknown' }}
                                        <button type="button" class="remove-tag-btn" @click="toggleMember(id)">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                            <!-- Search box -->
                            <input v-model="searchQuery" type="text" class="form-control mb-2"
                                placeholder="Search members by name or email..." />
                            <!-- Available Members UI -->
                            <div class="members-list">
                                <template v-if="filteredMembers.length > 0">
                                    <div v-for="(memberObj, idx) in filteredMembers" :key="memberObj.member.id"
                                        class="member-item">
                                        <div class="member-info">
                                            <div class="member-avatar-small">
                                                <span>{{ memberObj.member.name ? memberObj.member.name.charAt(0).toUpperCase() : '?' }}</span>
                                            </div>
                                            <div class="member-details">
                                                <span class="member-name">{{ memberObj.member.name }}</span>
                                                <span class="member-id">#{{ memberObj.member.id }}</span>
                                            </div>
                                        </div>
                                        <button
                                            v-if="selectedMembers.includes(memberObj.member.id)"
                                            type="button"
                                            class="add-member-btn selected"
                                            disabled
                                        >
                                            <i class="fas fa-check"></i> Selected
                                        </button>
                                        <button
                                            v-else
                                            @click="toggleMember(memberObj.member.id)"
                                            type="button"
                                            class="add-member-btn"
                                        >
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="text-center text-muted py-2">No member found</div>
                                </template>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="bi bi-info-circle"></i>
                                You can select multiple members to invite.
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import BaseInput from '../../../../components/BaseInput.vue';
import DateInput from '../../../../components/DateInput.vue';
import { useGetMembers } from '../../member/actions/getMember';
import { projectStore } from '../store/projectStore';

const props = defineProps<{ isEdit: boolean, projectInput: any, loading: boolean }>()
const emit = defineEmits(['close', 'submit'])

const { getMembers, memberData } = useGetMembers();
const selectedMembers = ref<number[]>([]);
const currentUserId = ref<number | null>(null);
const searchQuery = ref('');

onMounted(async () => {
    projectStore.edit = props.isEdit;
    await getMembers(1, '');
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    currentUserId.value = userData.id;
    if (props.projectInput.members) {
        selectedMembers.value = [...props.projectInput.members];
    }
});

function toggleMember(id: number) {
    if (selectedMembers.value.includes(id)) {
        selectedMembers.value = selectedMembers.value.filter(m => m !== id);
    } else {
        selectedMembers.value.push(id);
    }
}

function submitProject() {
    emit('submit', { ...props.projectInput, members: selectedMembers.value });
}

const filteredMembers = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase();
    return (memberData.value?.data?.data || [])
        .filter(m => m.member.id !== currentUserId.value)
        .filter(m =>
            m.member.name.toLowerCase().includes(keyword) ||
            m.member.email.toLowerCase().includes(keyword)
        );
});

function getMemberById(id: number) {
    return (memberData.value?.data?.data || []).find(m => m.member.id === id)?.member;
}
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
</style>