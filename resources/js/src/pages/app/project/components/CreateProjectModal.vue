<script lang="ts" setup>
import { ref, computed, watch, onMounted } from 'vue';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import { useCreateOrUpdateProject } from '../actions/createtProject';
import { projectStore } from '../store/projectStore';
import DateInput from '../../../../components/DateInput.vue';
import BaseInput from '../../../../components/BaseInput.vue';
import BaseBtn from '../../../../components/BaseBtn.vue';
import Error from '../../../../components/ErrorMessage.vue';
import { useGetMembers } from '../../member/actions/getMember';

const emit = defineEmits(['close', 'created']);

const props = defineProps<{ isEdit: boolean }>();

const rules = {
    name: { required },
    startDate: { required },
    endDate: { required }
}

const v$ = useVuelidate(rules, projectStore.projectInput);
const { loading, createOrUpdate } = useCreateOrUpdateProject();
const { getMembers, memberData, loading: membersLoading } = useGetMembers();

const users = ref<any[]>([]);
const selectedMembers = ref<number[]>([]);

onMounted(async () => {
    await getMembers(1, '');
    projectStore.edit = props.isEdit;
});

watch(() => projectStore.projectInput.startDate, (newStartDate) => {
    if (newStartDate && projectStore.projectInput.endDate && projectStore.projectInput.endDate < newStartDate) {
        projectStore.projectInput.endDate = '';
    }
});

async function submitProject() {
    const result = await v$.value.$validate();
    if (!result) return;
    try {
        projectStore.projectInput.members = selectedMembers.value;
        const response = await createOrUpdate();
        if (response.success) {
            v$.value.$reset();
            emit('created');
            emit('close');
        }
    } catch (error) {
    }
}
</script>

<template>
    <div class="modal-backdrop">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ projectStore.edit ? 'Update Project' : 'Create Project' }}</h5>
                    <button type="button" class="btn-close" @click="emit('close')"></button>
                </div>
                <form @submit.prevent="submitProject">
                    <div class="modal-body">
                        <BaseInput v-model="projectStore.projectInput.name" placeholder="Enter project name" />
                        <Error label="Project Name" :errors="v$.name.$errors" />
                        <div class="row mt-3">
                            <div class="col-6">
                                <DateInput v-model="projectStore.projectInput.startDate" placeholder="DD/MM/YYYY" />
                                <Error label="Start Date" :errors="v$.startDate.$errors" />
                                <small class="text-muted mt-1 d-block">
                                    <i class="bi bi-info-circle"></i>
                                    Project start date (can be any date)
                                </small>
                            </div>
                            <div class="col-6">
                                <DateInput v-model="projectStore.projectInput.endDate" placeholder="DD/MM/YYYY"
                                    :min="projectStore.projectInput.startDate" />
                                <Error label="End Date" :errors="v$.endDate.$errors" />
                                <small class="text-muted mt-1 d-block">
                                    <i class="bi bi-info-circle"></i>
                                    Project end date (must be after start date)
                                </small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Invite Members</label>
                            <select v-model="selectedMembers" multiple class="form-select">
                                <option v-for="memberObj in memberData.data || []" :key="memberObj.id"
                                    :value="memberObj.id">
                                    {{ memberObj.name }} ({{ memberObj.email }})
                                </option>
                            </select>
                            <small class="text-muted mt-1 d-block">
                                <i class="bi bi-info-circle"></i>
                                You can select multiple members to invite.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="emit('close')">Cancel</button>
                        <BaseBtn type="submit" :variant="projectStore.edit ? 'warning' : 'primary'"
                            :label="projectStore.edit ? 'Update Project' : 'Create Project'" :loading="loading" />
                    </div>
                </form>
            </div>
        </div>
    </div>
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
    max-width: 500px;
    width: 100%;
}

.modal-content {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
    padding: 0;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem 1rem 1.5rem;
    border-bottom: 1px solid #eee;
}

.modal-title {
    font-size: 1.2rem;
    font-weight: bold;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.3rem;
    cursor: pointer;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.5rem 1.2rem 1.5rem;
    border-top: 1px solid #eee;
}
</style>

<style scoped>
@media (max-width: 600px) {
    .modal-dialog {
        max-width: 98vw;
        width: 98vw;
        margin: 0;
    }

    .modal-content {
        border-radius: 0.7rem;
        padding: 0;
    }

    .modal-header,
    .modal-footer {
        padding: 0.7rem 0.7rem 0.7rem 0.7rem;
    }

    .modal-body {
        padding: 1rem 0.7rem;
    }

    .form-label,
    .modal-title {
        font-size: 1rem;
    }

    .form-select,
    input,
    .BaseInput,
    .DateInput {
        font-size: 1rem !important;
        min-height: 2.4rem;
    }

    small.text-muted {
        font-size: 0.85rem;
    }
}
</style>