<script lang="ts" setup>
import { useRouter } from 'vue-router';
import useVuelidate from '@vuelidate/core';
import { required, email } from '@vuelidate/validators';
import { useCreateOrUpdateMember } from '../actions/createMember';
import { memberStore } from '../store/MemberStore';

const router = useRouter();

const rules = {
    name: { required },
    email: { required, email }
}

const v$ = useVuelidate(rules, memberStore.memberInput);
const { loading, createOrUpdate } = useCreateOrUpdateMember();

async function submitMember() {
    const result = await v$.value.$validate();

    if (!result) return;

    try {
        await createOrUpdate();
        v$.value.$reset();
        // Navigate to members list after successful creation/update
        router.push('/members');
    } catch (error) {
        console.error('Error submitting member:', error);
    }
}

// Initialize form data if editing
if (memberStore.edit && memberStore.memberInput.id) {
    // Form will be pre-filled with existing data
}
</script>

<template>
    <div class="member-form-table-style-container">
        <div class="member-form-table-style-card">
            <div class="member-form-table-style-header">
                <h3 class="member-form-table-style-title">
                    {{ memberStore.edit ? 'Update Member' : 'Create Member' }}
                </h3>
            </div>
            <form @submit.prevent="submitMember" class="member-form-table-style-form">
                <div class="row g-3">
                    <div class="col-12">
                        <Error label="Member Name" :errors="v$.name.$errors" />
                        <BaseInput v-model="memberStore.memberInput.name" placeholder="Enter member name" />
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle"></i>
                            Full name of the member
                        </small>
                    </div>
                    <div class="col-12">
                        <Error label="Email Address" :errors="v$.email.$errors" />
                        <BaseInput v-model="memberStore.memberInput.email" placeholder="Enter email address" />
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle"></i>
                            Valid email address for member communication
                        </small>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-4">
                    <RouterLink to="/members" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Members
                    </RouterLink>
                    <BaseBtn type="submit" :variant="memberStore.edit ? 'warning' : 'primary'"
                        :label="memberStore.edit ? 'Update Member' : 'Create Member'" :loading="loading" />
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.member-form-table-style-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 1.5rem 0.5rem;
}

.member-form-table-style-card {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
    padding: 2rem 1.5rem 1.5rem 1.5rem;
}

.member-form-table-style-header {
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 1rem;
}

.member-form-table-style-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
    margin: 0;
}

.member-form-table-style-form {
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
    .member-form-table-style-card {
        padding: 1.1rem 0.7rem 1rem 0.7rem;
        border-radius: 1rem;
    }

    .member-form-table-style-title {
        font-size: 1.15rem;
    }
}
</style>