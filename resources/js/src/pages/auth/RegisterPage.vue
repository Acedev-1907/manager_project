<script lang="ts" setup>
import useVuelidate from '@vuelidate/core';
import { required, email, sameAs, helpers } from '@vuelidate/validators';
import { registerInput, useRegisterUser } from './action/register';
import { ref, computed } from 'vue';

const passwordValue = computed(() => registerInput.value.password);

const sameAsPassword = helpers.withMessage(
    'The value must be equal to the password',
    sameAs(passwordValue)
);

const requiredName = helpers.withMessage('Name is required', required);
const requiredEmail = helpers.withMessage('Email is required', required);
const requiredPassword = helpers.withMessage('Password is required', required);
const requiredPasswordConfirmation = helpers.withMessage('Confirm password is required', required);

const rules = {
    name: { required: requiredName },
    email: { required: requiredEmail, email },
    password: { required: requiredPassword },
    password_confirmation: { required: requiredPasswordConfirmation, sameAsPassword }
}

const v$ = useVuelidate(rules, registerInput);
const { loading, register } = useRegisterUser();

const lastSubmittedInput = ref({ ...registerInput.value });

type RegisterInputKey = keyof typeof registerInput.value;

async function submitRegister() {
    const result = await v$.value.$validate();
    if (!result) return;
    // So sánh dữ liệu hiện tại với lần submit trước
    const isChanged = Object.keys(registerInput.value).some(
        key => registerInput.value[key as RegisterInputKey] !== lastSubmittedInput.value[key as RegisterInputKey]
    );
    if (!isChanged) return;
    await register();
    lastSubmittedInput.value = { ...registerInput.value };
    v$.value.$reset();
}
</script>

<template>
    <div class="register-center">
        <div class="register-card">
            <div class="register-header">
                <i class="bi bi-person-plus-fill register-logo"></i>
                <h2>Create Account</h2>
                <p class="register-sub">Join us! Create your account to get started.</p>
            </div>
            <form @submit.prevent="submitRegister">
                <div class="form-group">
                    <Error label="Name" :errors="v$.name.$errors" />
                    <div class="input-icon-group">
                        <i class="bi bi-person input-icon"></i>
                        <BaseInput v-model="registerInput.name" placeholder="Full name" type="text" />
                    </div>
                </div>
                <div class="form-group">
                    <Error label="Email" :errors="v$.email.$errors" />
                    <div class="input-icon-group">
                        <i class="bi bi-envelope input-icon"></i>
                        <BaseInput v-model="registerInput.email" placeholder="Email address" type="email" />
                    </div>
                </div>
                <div class="form-group">
                    <Error label="Password" :errors="v$.password.$errors" />
                    <div class="input-icon-group">
                        <i class="bi bi-lock input-icon"></i>
                        <BaseInput v-model="registerInput.password" type="password" placeholder="Password" />
                    </div>
                </div>
                <div class="form-group">
                    <Error label="Confirm Password" :errors="v$.password_confirmation.$errors" />
                    <div class="input-icon-group">
                        <i class="bi bi-shield-check input-icon"></i>
                        <BaseInput v-model="registerInput.password_confirmation" type="password"
                            placeholder="Confirm password" />
                    </div>
                </div>
                <div class="form-group mt-4">
                    <BaseBtn label="Create Account" :loading="loading" size="lg" variant="primary" type="submit" />
                </div>
                <div class="form-group mt-3 text-center">
                    <RouterLink to="/login" class="register-link">Already have an account? Sign In</RouterLink>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.register-center {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #fff;
}

.register-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.10);
    padding: 2.5rem 2.5rem 2rem 2.5rem;
    min-width: 370px;
    max-width: 400px;
    width: 100%;
}

.register-header {
    text-align: center;
    margin-bottom: 2rem;
}

.register-logo {
    font-size: 2.5rem;
    color: #6366f1;
    margin-bottom: 0.5rem;
}

.register-header h2 {
    font-weight: 700;
    color: #22223b;
    margin-bottom: 0.25rem;
}

.register-sub {
    color: #6b7280;
    font-size: 1rem;
    margin-bottom: 0;
}

.input-icon-group {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #a5b4fc;
    font-size: 1.1rem;
    z-index: 2;
}

.input-icon-group :deep(.base-input-custom) {
    padding-left: 2.5rem;
}

.form-group {
    margin-bottom: 1.3rem;
}

.register-link {
    color: #6366f1;
    font-weight: 500;
    text-decoration: none;
    transition: color 0.2s;
}

.register-link:hover {
    color: #4338ca;
    text-decoration: underline;
}

@media (max-width: 900px) {
    .register-card {
        min-width: 90vw;
    }
}
</style>