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

// Remove all date constraints - allow free selection
const minStartDate = computed(() => {
    return undefined; // No minimum limit for start date
});

const minEndDate = computed(() => {
    // Only constraint: end date must be >= start date
    if (projectStore.projectInput.startDate) {
        return projectStore.projectInput.startDate;
    }
    return undefined; // No minimum limit if no start date
});

// Remove max date limitation completely - allow any future date
const maxEndDate = computed(() => {
    return undefined; // No max limit
});

const debugInfo = computed(() => {
    return {
        startDate: projectStore.projectInput.startDate,
        endDate: projectStore.projectInput.endDate,
        minStartDate: minStartDate.value || 'No limit',
        minEndDate: minEndDate.value || 'No limit',
        maxEndDate: maxEndDate.value || 'No limit',
        today: today.value
    };
});

// Watch for start date changes - only validate end date
watch(() => projectStore.projectInput.startDate, (newStartDate) => {

    // If end date is before start date, clear it
    if (newStartDate && projectStore.projectInput.endDate && projectStore.projectInput.endDate < newStartDate) {
        projectStore.projectInput.endDate = '';
    }
});

// Watch for end date changes to log validation
watch(() => projectStore.projectInput.endDate, (newEndDate) => {
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
                        <DateInput v-model="projectStore.projectInput.startDate || ''" placeholder="DD/MM/YYYY" />
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle"></i>
                            Project start date (can be any date)
                        </small>
                    </div>
                    <div class="col-md-6 col-12">
                        <Error label="End Date" :errors="v$.endDate.$errors" />
                        <DateInput v-model="projectStore.projectInput.endDate || ''" placeholder="DD/MM/YYYY"
                            :min="projectStore.projectInput.startDate" />
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle"></i>
                            Project end date (must be after start date)
                        </small>
                    </div>
                </div>

                <!-- Debug information (remove in production) -->
                <div v-if="false" class="mt-3 p-2 bg-light rounded">
                    <small class="text-muted">
                        <strong>Debug Info:</strong><br>
                        Start Date: {{ debugInfo.startDate }}<br>
                        End Date: {{ debugInfo.endDate }}<br>
                        Min Start Date: {{ debugInfo.minStartDate }}<br>
                        Min End Date: {{ debugInfo.minEndDate }}<br>
                        Max End Date: {{ debugInfo.maxEndDate }}<br>
                        Today: {{ debugInfo.today }}
                    </small>
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