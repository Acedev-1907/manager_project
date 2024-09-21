<script lang="ts" setup>
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import { useCreateOrUpdateProject } from '../actions/createtProject';
import { projectStore } from '../store/ProjectStore';
const rules = {
    name: { required },
    startDate: { required },
    endDate: { required }
}

const v$ = useVuelidate(rules, projectStore.projectInput);
const { loading, createOrUpdate } = useCreateOrUpdateProject();

async function submitProject() {
    const result = await v$.value.$validate();

    if (!result) return;

    await createOrUpdate();
    v$.value.$reset()
}

</script>

<template>
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h3>Create Project</h3>
                <br />
                <form @submit.prevent="submitProject">
                    <div class="form-group">
                        <div class="form-group">
                            <Error label="Name" :errors="v$.name.$errors" />
                            <BaseInput v-model="projectStore.projectInput.name" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-group">
                            <Error label="Start Date" :errors="v$.startDate.$errors" />
                            <BaseInput type="date" v-model="projectStore.projectInput.startDate" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-group">
                            <Error label="End Date" :errors="v$.endDate.$errors" />
                            <BaseInput type="date" v-model="projectStore.projectInput.endDate" />
                        </div>
                    </div>
                    <br />
                    <RouterLink to="/projects">See project list</RouterLink>
                    <div class="form-group mt-3">
                        <BaseBtn :class="projectStore.edit ? 'btn btn-warning' : 'btn btn-primary'"
                            :label="projectStore.edit ? 'Update Project' : 'Create Project'" :loading="loading" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>