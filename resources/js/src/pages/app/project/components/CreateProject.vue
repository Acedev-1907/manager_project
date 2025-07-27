<script lang="ts" setup>
import { useRouter } from 'vue-router';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import { useCreateOrUpdateProject } from '../actions/createtProject';
import { projectStore } from '../store/projectStore';
import { watch } from 'vue';
import DateInput from '../../../../components/DateInput.vue';

const router = useRouter();

const rules = {
    name: { required },
    startDate: { required },
    endDate: { required }
}

const v$ = useVuelidate(rules, projectStore.projectInput);
const { loading, createOrUpdate } = useCreateOrUpdateProject();

// Watch for start date changes - only validate end date
watch(() => projectStore.projectInput.startDate, (newStartDate) => {
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
            router.push('/projects');
        }
    } catch (error) {
        // Silent error handling
    }
}
</script>

<template>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white border-bottom-0 pb-0">
                        <h3 class="card-title mb-0 fw-bold text-dark">
                            {{ projectStore.edit ? 'Edit Project' : 'Create New Project' }}
                        </h3>
                        <p class="text-muted mb-0 mt-1">
                            {{ projectStore.edit ?
                                'Update your project details below.' :
                                'Fill in the details to create a new project.' }}
                        </p>
                    </div>
                    <div class="card-body p-4">
                        <form @submit.prevent="submitProject">
                            <div class="row g-3">
                                <div class="col-12">
                                    <Error label="Project Name" :errors="v$.name.$errors" />
                                    <BaseInput v-model="projectStore.projectInput.name"
                                        placeholder="Enter project name" />
                                </div>
                                <div class="col-md-6 col-12">
                                    <Error label="Start Date" :errors="v$.startDate.$errors" />
                                    <DateInput v-model="projectStore.projectInput.startDate" placeholder="DD/MM/YYYY" />
                                    <small class="text-muted mt-1 d-block">
                                        <i class="bi bi-info-circle"></i>
                                        Project start date (can be any date)
                                    </small>
                                </div>
                                <div class="col-md-6 col-12">
                                    <Error label="End Date" :errors="v$.endDate.$errors" />
                                    <DateInput v-model="projectStore.projectInput.endDate" placeholder="DD/MM/YYYY"
                                        :min="projectStore.projectInput.startDate" />
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
                                    :label="projectStore.edit ? 'Update Project' : 'Create Project'"
                                    :loading="loading" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom styles only for specific design requirements */
.card {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
}

.card-header {
    padding: 2rem 1.5rem 1rem 1.5rem;
}

.card-body {
    padding: 0 1.5rem 1.5rem 1.5rem;
}

.text-muted i {
    margin-right: 0.25rem;
}

@media (max-width: 768px) {
    .card-header {
        padding: 1.1rem 0.7rem 0.5rem 0.7rem;
    }

    .card-body {
        padding: 0 0.7rem 1rem 0.7rem;
    }

    .card-title {
        font-size: 1.15rem;
    }
}
</style>