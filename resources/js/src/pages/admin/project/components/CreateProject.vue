<script lang="ts" setup>
import { useRouter } from 'vue-router';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import { useCreateOrUpdateProject } from '../actions/createtProject';
import { projectStore } from '../store/projectStore';
import { computed, watch } from 'vue';
import DateInput from '../../../../components/DateInput.vue';

const router = useRouter();

const rules = {
    name: { required },
    startDate: { required },
    endDate: { required }
}

const v$ = useVuelidate(rules, projectStore.projectInput);
const { loading, createOrUpdate } = useCreateOrUpdateProject();

// Computed properties for date constraints
const today = computed(() => {
    const date = new Date();
    return date.toISOString().split('T')[0];
});

const minEndDate = computed(() => {
    return projectStore.projectInput.startDate || today.value;
});

// Watch for start date changes to auto-fill end date
watch(() => projectStore.projectInput.startDate, (newStartDate) => {
    if (newStartDate && !projectStore.projectInput.endDate) {
        // Auto-set end date to 30 days after start date
        const startDate = new Date(newStartDate);
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 30);
        projectStore.projectInput.endDate = endDate.toISOString().split('T')[0];
    }

    // If end date is before start date, clear it
    if (newStartDate && projectStore.projectInput.endDate && projectStore.projectInput.endDate < newStartDate) {
        projectStore.projectInput.endDate = '';
    }
});

async function submitProject() {
    const result = await v$.value.$validate();

    if (!result) return;

    try {
        const response = await createOrUpdate();
        if (response.success) {
            v$.value.$reset();
            // Navigate to projects list after successful creation/update
            router.push('/projects');
        }
    } catch (error) {
        console.error('Error submitting project:', error);
    }
}

// Initialize form data if editing
if (projectStore.edit && projectStore.projectInput.id) {
    // Form will be pre-filled with existing data
}
</script>

<template>
    <div class="project-form-table-style-container">
        <div class="project-form-table-style-card">
            <div class="project-form-table-style-header">
                <h3 class="project-form-table-style-title">
                    {{ projectStore.edit ? 'Update Project' : 'Create Project' }}
                </h3>
            </div>
            <form @submit.prevent="submitProject" class="project-form-table-style-form">
                <div class="row g-3">
                    <div class="col-12">
                        <Error label="Project Name" :errors="v$.name.$errors" />
                        <BaseInput v-model="projectStore.projectInput.name" placeholder="Enter project name" />
                    </div>
                    <div class="col-md-6 col-12">
                        <Error label="Start Date" :errors="v$.startDate.$errors" />
                        <DateInput v-model="projectStore.projectInput.startDate" :min="today"
                            placeholder="DD/MM/YYYY" />
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle"></i>
                            Project start date (cannot be in the past)
                        </small>
                    </div>
                    <div class="col-md-6 col-12">
                        <Error label="End Date" :errors="v$.endDate.$errors" />
                        <DateInput v-model="projectStore.projectInput.endDate" :min="minEndDate"
                            placeholder="DD/MM/YYYY" />
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle"></i>
                            Project end date (must be after start date)
                        </small>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-4">
                    <RouterLink to="/projects" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Projects
                    </RouterLink>
                    <BaseBtn type="submit" :variant="projectStore.edit ? 'warning' : 'primary'"
                        :label="projectStore.edit ? 'Update Project' : 'Create Project'" :loading="loading" />
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.project-form-table-style-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 1.5rem 0.5rem;
}

.project-form-table-style-card {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
    padding: 2rem 1.5rem 1.5rem 1.5rem;
}

.project-form-table-style-header {
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 1rem;
}

.project-form-table-style-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
    margin: 0;
}

.project-form-table-style-form {
    margin-top: 0.5rem;
}

.text-muted {
    font-size: 0.8rem;
    color: #6b7280;
}

.text-muted i {
    margin-right: 0.25rem;
}

@media (max-width: 768px) {
    .project-form-table-style-card {
        padding: 1.1rem 0.7rem 1rem 0.7rem;
        border-radius: 1rem;
    }

    .project-form-table-style-title {
        font-size: 1.15rem;
    }
}
</style>