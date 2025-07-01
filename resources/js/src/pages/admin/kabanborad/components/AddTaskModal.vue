<script setup lang="ts">
import { useVuelidate } from "@vuelidate/core";
import { required } from "@vuelidate/validators";
import { ref, watch } from "vue";
import { taskStore } from "../store/kabanStore";
import { GetMemberType } from "../../member/actions/getMember";
import { useSelectMember } from "../actions/selectMember";
import { myDebounce } from "../../../../helper/utils";
import { useCreateTask } from "../actions/CreateTask";
import { showError } from "../../../../helper/alert";
import BaseInput from "../../../../components/BaseInput.vue";

const props = defineProps<{
    members: GetMemberType;
    visible: boolean;
}>();

const emit = defineEmits<{
    (e: "closeModal"): void;
    (e: "refreshKabanBoard",): Promise<void>;
    (e: "getMembers", page: number, query: string): Promise<void>;
}>();

const rules = {
    name: { required },
};

const v$ = useVuelidate(rules, taskStore.taskInput);
const query = ref("");

const { selectMember, selectedMembers, unSelectedMember } = useSelectMember()
const { loading, createTask } = useCreateTask();

function closeModal() {
    emit('closeModal');
}

async function submitTask() {
    // Ensure memberIds is always an array before validate
    if (!Array.isArray(taskStore.taskInput.memberIds)) {
        taskStore.taskInput.memberIds = [];
    }
    const result = await v$.value.$validate();

    if (!result) return;

    // Ensure memberIds is always an array before checking length
    if (!Array.isArray(taskStore.taskInput.memberIds)) {
        taskStore.taskInput.memberIds = [];
    }
    if (taskStore.taskInput.memberIds.length > 0) {
        await createTask();
        taskStore.taskInput.memberIds = []
        taskStore.taskInput.name = ""
        v$.value.$reset();
        closeModal();
    } else {
        showError('please select a member');
    }
}

const searchMember = myDebounce(async function () {
    emit('getMembers', 1, query.value);
}, 200);

watch(() => props.visible, (newVal) => {
    if (newVal) {
        taskStore.taskInput.name = "";
        taskStore.taskInput.memberIds = [];
        selectedMembers.value = [];
        v$.value.$reset();
    }
});
</script>

<template>
    <!-- Custom Modal -->
    <Teleport to="body">
        <div v-if="visible" class="custom-modal-overlay" @click="closeModal">
            <div class="custom-modal" @click.stop>
                <div class="custom-modal-content">
                    <form enctype="multipart/form-data" @submit.prevent="submitTask">
                        <!-- Simple Header -->
                        <div class="modal-header">
                            <div class="header-content-centered">
                                <h5 class="modal-title">Add New Task</h5>
                                <p class="modal-subtitle">Create a task for your project</p>
                            </div>
                            <div class="header-actions">
                                <button type="submit" class="btn btn-primary btn-create" :disabled="loading">
                                    <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-plus"></i>
                                    <span v-if="!loading"> Create</span>
                                    <span v-else>Creating...</span>
                                </button>
                                <button type="button" class="btn btn-secondary btn-cancel" @click="closeModal">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        <div class="modal-body">
                            <!-- Task Name Input -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tasks"></i>
                                    Task Name
                                </label>
                                <BaseInput placeholder="Enter task name..." v-model="taskStore.taskInput.name" />
                                <div v-if="v$.name.$error" class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ v$.name.$errors[0].$message }}
                                </div>
                            </div>

                            <!-- Member Search -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-search"></i>
                                    Search Members
                                </label>
                                <BaseInput type="text" v-model="query" @keydown="searchMember"
                                    placeholder="Type to search team members..." />
                            </div>

                            <!-- Selected Members -->
                            <div v-if="selectedMembers.length > 0" class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-users"></i>
                                    Selected Members ({{ selectedMembers.length }})
                                </label>
                                <div class="selected-members">
                                    <div v-for="member in selectedMembers" :key="member.id" class="member-tag"
                                        @click="unSelectedMember(member.id)">
                                        <div class="member-avatar">
                                            <span>{{ member.name.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <span class="member-name">{{ member.name }}</span>
                                        <i class="fas fa-times remove-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Members List -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-list"></i>
                                    Available Members
                                </label>
                                <div class="members-list">
                                    <div v-for="member in members?.data?.data" :key="member.id" class="member-item">
                                        <div class="member-info">
                                            <div class="member-avatar-small">
                                                <span>{{ member.name.charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <div class="member-details">
                                                <span class="member-name">{{ member.name }}</span>
                                                <span class="member-id">#{{ member.id }}</span>
                                            </div>
                                        </div>
                                        <button @click="selectMember(member)" type="button" class="add-member-btn"
                                            :disabled="selectedMembers.some(m => m.id === member.id)">
                                            <i v-if="!selectedMembers.some(m => m.id === member.id)"
                                                class="fas fa-plus"></i>
                                            <i v-else class="fas fa-check"></i>
                                            <span v-if="!selectedMembers.some(m => m.id === member.id)">Add</span>
                                            <span v-else>Selected</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Custom Modal Styles */
.custom-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 12px;
    box-sizing: border-box;
}

.custom-modal {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    max-width: 480px;
    width: 100%;
    max-height: 80vh;
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.custom-modal-content {
    width: 100%;
}

/* Header */
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0;
    padding: 16px 20px 8px 20px;
    border-bottom: none;
    position: relative;
    background: none;
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
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    text-align: center;
}

.modal-subtitle {
    font-size: 0.95rem;
    color: #64748b;
    margin: 0;
    text-align: center;
}

.error-message {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #ef4444;
    font-size: 12px;
    margin-top: 6px;
    padding: 8px 12px;
    background: #fef2f2;
    border-radius: 6px;
    border-left: 3px solid #ef4444;
}

.error-message i {
    font-size: 10px;
}

/* Selected Members */
.selected-members {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.member-tag {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.member-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.member-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
}

.member-name {
    font-weight: 500;
}

.remove-icon {
    font-size: 10px;
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.member-tag:hover .remove-icon {
    opacity: 1;
}

/* Members List */
.members-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 200px;
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
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    background: white;
    transition: all 0.3s ease;
}

.member-item:hover {
    background: #f8fafc;
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
    width: 32px;
    height: 32px;
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

.add-member-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
}

.add-member-btn i {
    font-size: 8px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .custom-modal-overlay {
        padding: 10px;
    }

    .custom-modal {
        max-width: 95%;
    }

    .modal-header {
        padding: 14px 16px;
    }

    .header-icon {
        width: 32px;
        height: 32px;
        font-size: 12px;
    }

    .modal-title {
        font-size: 16px;
    }

    .modal-subtitle {
        font-size: 12px;
    }

    .modal-body {
        padding: 12px;
    }

    .form-group {
        margin-bottom: 12px;
    }

    .members-list {
        max-height: 120px;
    }

    .member-item {
        padding: 8px 10px;
    }

    .member-info {
        gap: 8px;
    }

    .member-avatar-small {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }

    .btn-cancel {
        padding: 8px 16px;
        font-size: 13px;
    }

    .btn-create {
        padding: 8px 16px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .custom-modal-overlay {
        padding: 8px;
    }

    .modal-header {
        padding: 12px 14px;
    }

    .header-content {
        gap: 10px;
    }

    .header-icon {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }

    .modal-title {
        font-size: 15px;
    }

    .modal-subtitle {
        font-size: 11px;
    }

    .modal-body {
        padding: 10px;
    }

    .form-group {
        margin-bottom: 10px;
    }

    .members-list {
        max-height: 195px;
    }

    .member-item {
        padding: 6px 8px;
    }

    .member-avatar-small {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }

    .add-member-btn {
        padding: 4px 8px;
        font-size: 10px;
    }

    .btn-cancel {
        padding: 10px 16px;
    }

    .btn-create {
        padding: 10px 16px;
    }
}

.modal-body {
    padding: 24px 20px 12px 20px;
    background: white;
}

.form-group {
    margin-bottom: 20px;
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

.form-label i {
    color: #3b82f6;
    font-size: 1rem;
}

input,
.base-input,
input[type='text'] {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    font-size: 1rem;
    background: #f8fafc;
    transition: border 0.2s, box-shadow 0.2s;
    margin-bottom: 2px;
}

input:focus,
.base-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px #dbeafe;
    outline: none;
}

.add-member-btn:disabled {
    background: #e5e7eb !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    border: 1px solid #d1d5db;
    box-shadow: none;
    opacity: 0.8;
}

.add-member-btn:disabled .fa-check {
    color: #22c55e;
}
</style>