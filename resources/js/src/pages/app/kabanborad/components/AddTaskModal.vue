<script setup lang="ts">
import { useVuelidate } from "@vuelidate/core";
import { required } from "@vuelidate/validators";
import { ref, watch, onMounted, computed } from "vue";
import { taskStore } from "../store/kabanStore";
import { useCreateTask } from "../actions/CreateTask";
import { showError } from "../../../../helper/alert";
import BaseInput from "../../../../components/BaseInput.vue";
import { getAvatarSrc } from '../../../../helper/avatar';

const props = defineProps<{
    members: Array<{ id: number; name: string; email: string; avatar?: string }>;
    visible: boolean;
}>();

const emit = defineEmits<{
    (e: "closeModal"): void;
    (e: "refreshKabanBoard"): Promise<void>;
    (e: "getMembers", page: number, query: string): Promise<void>;
}>();

const rules = {
    name: { required },
};

const v$ = useVuelidate(rules, taskStore.taskInput);
const selectedMembers = ref<number[]>([]);
const { /* loading, */ createTask } = useCreateTask();
const currentUser = ref<{ id: number; name: string; email: string } | null>(null);

// Compute project members, always include current user
const projectMembers = computed(() => {
    const members = (props.members || []).slice();
    if (currentUser.value && currentUser.value.id && !members.some(m => m.id === currentUser.value!.id)) {
        members.push({
            id: currentUser.value.id,
            name: currentUser.value.name,
            email: currentUser.value.email
        });
    }
    return members;
});

const searchQuery = ref('');
const showAll = ref(false);

watch(() => taskStore.taskInput.memberIds, (val) => {
    selectedMembers.value = Array.isArray(val) ? val : [];
});

function toggleMember(id: number) {
    if (selectedMembers.value.includes(id)) {
        selectedMembers.value = selectedMembers.value.filter(m => m !== id);
        if (selectedMembers.value.length === 0) showAll.value = false;
    } else {
        selectedMembers.value.push(id);
    }
    taskStore.taskInput.memberIds = [...selectedMembers.value];
}

function handleMouseLeave() {
    showAll.value = false;
}
function handleMouseEnter() {
    showAll.value = true;
}

const filteredMembers = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase();
    return (projectMembers.value || []).filter(m =>
        m.name.toLowerCase().includes(keyword) ||
        m.email.toLowerCase().includes(keyword)
    );
});

function getMemberById(id: number) {
    return (projectMembers.value || []).find(m => m.id === id);
}

onMounted(() => {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    currentUser.value = userData;
    selectedMembers.value = Array.isArray(taskStore.taskInput.memberIds) ? [...taskStore.taskInput.memberIds] : [];
});

function closeModal() {
    emit('closeModal');
}

async function submitTask() {
    if (!Array.isArray(taskStore.taskInput.memberIds)) {
        taskStore.taskInput.memberIds = [];
    }
    const result = await v$.value.$validate();
    if (!result) return;

    await createTask();
    taskStore.taskInput.memberIds = [];
    taskStore.taskInput.name = "";
    v$.value.$reset();
    emit('refreshKabanBoard');
    emit('closeModal');
}

watch(() => props.visible, (newVal) => {
    if (newVal) {
        taskStore.taskInput.name = "";
        taskStore.taskInput.content = ""; // Reset content khi mở modal
        taskStore.taskInput.memberIds = [];
        selectedMembers.value = [];
        v$.value.$reset();
    }
});
</script>

<template>
    <Teleport to="body">
        <div v-if="visible" class="modal-backdrop">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="header-content-centered">
                            <h3 class="modal-title">Add New Task</h3>
                            <p class="modal-subtitle">Create a task for your project</p>
                        </div>
                        <div class="header-actions">
                            <button type="submit" class="btn btn-primary btn-create" @click="submitTask">
                                <i class="fas fa-plus"></i>
                                <span> Create</span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-cancel" @click="closeModal">
                                Cancel
                            </button>
                        </div>
                    </div>
                    <form @submit.prevent="submitTask">
                        <div class="modal-body">
                            <div class="mb-3">
                                <BaseInput v-model="taskStore.taskInput.name" placeholder="Task Name" />
                                <div v-if="v$.name.$error" class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ v$.name.$errors[0].$message }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <textarea v-model="taskStore.taskInput.content" class="form-control" rows="3"
                                    placeholder="Task Content (optional)"></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label class="form-label">Select Members</label>
                                <div class="selected-members-minimal">
                                    <i class="bi bi-people-fill"></i>
                                    <span v-for="(id, idx) in selectedMembers.slice(0, 2)" :key="id"
                                        class="tag-minimal member-tag" :title="getMemberById(id)?.email">
                                        <img :src="getAvatarSrc(getMemberById(id)?.avatar, getMemberById(id)?.name)"
                                            class="avatar-tag" :alt="getMemberById(id)?.name" />
                                        <span class="member-name-short">{{ getMemberById(id)?.name }}</span>
                                        <button type="button" class="remove-tag-btn-minimal"
                                            @click.stop="toggleMember(id)" title="Remove">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </span>
                                    <div v-if="selectedMembers.length > 2"
                                        style="position: relative; display: inline-block; vertical-align: top;">
                                        <span class="tag-minimal more member-tag"
                                            style="position: relative; z-index: 10; cursor: pointer;"
                                            @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
                                            +{{ selectedMembers.length - 2 }}
                                        </span>
                                        <div v-if="showAll" class="selected-members-popup-wrapper"
                                            @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
                                            <div v-for="id in selectedMembers.slice(2)" :key="id"
                                                class="popup-member-row">
                                                <img :src="getAvatarSrc(getMemberById(id)?.avatar, getMemberById(id)?.name)"
                                                    class="avatar-tag" :alt="getMemberById(id)?.name" />
                                                <span class="member-name-short">{{ getMemberById(id)?.name }}</span>
                                                <button class="remove-tag-btn-minimal"
                                                    @mousedown.prevent.stop="toggleMember(id); showAll = true"
                                                    title="Remove">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="select-hint">Select Members</span>
                                </div>
                                <div class="members-list-minimal">
                                    <input v-model="searchQuery" type="text" class="form-control mb-2"
                                        placeholder="Search members..." />
                                    <div v-if="filteredMembers.length > 0" class="user-card-list">
                                        <div v-for="member in filteredMembers" :key="member.id" class="user-card">
                                            <img :src="getAvatarSrc(member.avatar, member.name)" class="user-avatar"
                                                :alt="member.name" />
                                            <div class="user-info">
                                                <div class="user-name">{{ member.name }}</div>
                                                <div class="user-email">{{ member.email }}</div>
                                            </div>
                                            <button v-if="selectedMembers.includes(member.id)"
                                                class="user-select-btn selected" disabled>
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button v-else @click="toggleMember(member.id)" class="user-select-btn">
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
    </Teleport>
</template>

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
    /* KHÔNG đặt max-height hoặc overflow-y ở đây */
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
    font-size: 1.2rem;
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
    padding: 1.5rem;
}

.error-message {
    color: #ef4444;
    font-size: 12px;
    margin-top: 6px;
    padding: 8px 12px;
    background: #fef2f2;
    border-radius: 6px;
    border-left: 3px solid #ef4444;
}

/* --- Select Members styles giống ProjectModal --- */
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

.tag-minimal.more {
    background: #60a5fa;
    color: #fff;
    cursor: pointer;
    margin-right: 0;
    padding: 0.12rem 0.7rem 0.12rem 0.7rem;
}

.tag-minimal:hover {
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

.selected-members-popup-wrapper {
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 0.5rem;
    z-index: 2000;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(34, 34, 59, 0.13);
    padding: 0.6rem 0.7rem;
    width: 220px;
    max-height: 220px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
}

.popup-all-selected {
    max-height: 180px;
    overflow-y: auto;
}

.popup-member-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.6rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;
}

.popup-member-row:hover {
    background: #f3f6fd;
}

.popup-member-row .avatar-tag {
    width: 1.5em;
    height: 1.5em;
    font-size: 0.8em;
    margin-right: 0.3em;
}

/* Trong style, ghi đè .popup-member-row .member-name-short để hiển thị đầy đủ tên */
.popup-member-row .member-name-short {
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 500;
}

.popup-member-row .remove-tag-btn-minimal {
    margin-left: auto;
    padding: 0.2em 0.4em;
    font-size: 0.9em;
}

.members-list-minimal {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(34, 34, 59, 0.13);
    padding: 0.6rem 0.7rem 0.6rem 0.7rem;
    z-index: 3000;
    min-width: 170px;
    margin-top: 0.5rem;
    border: 1px solid #e5e7eb;
}

.user-card-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 180px;
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
</style>