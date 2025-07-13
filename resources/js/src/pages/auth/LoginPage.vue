<script lang="ts" setup>
import useVuelidate from '@vuelidate/core';
import { required, email, helpers } from '@vuelidate/validators';
import { loginInput, useLoginUser } from './action/login';

// Custom error messages for login fields
const requiredEmail = helpers.withMessage('Email is required', required);
const requiredPassword = helpers.withMessage('Password is required', required);

const rules = {
    email: { required: requiredEmail, email: helpers.withMessage('Email is invalid', email) },
    password: { required: requiredPassword }
}

const v$ = useVuelidate(rules, loginInput);
const { loading, login } = useLoginUser();

async function submitLogin() {
    const result = await v$.value.$validate();
    if (!result) return;
    await login();
    v$.value.$reset();
}
</script>

<template>
    <div class="login-center">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-shield-lock-fill login-logo"></i>
                <h2>Sign In</h2>
                <p class="login-sub">Welcome back! Please login to your account.</p>
            </div>
            <form @submit.prevent="submitLogin">
                <div class="form-group">
                    <Error label="Email" :errors="v$.email.$errors" />
                    <div class="input-icon-group">
                        <i class="bi bi-envelope input-icon"></i>
                        <BaseInput v-model="loginInput.email" placeholder="Email address" type="email" />
                    </div>
                </div>
                <div class="form-group">
                    <Error label="Password" :errors="v$.password.$errors" />
                    <div class="input-icon-group">
                        <i class="bi bi-lock input-icon"></i>
                        <BaseInput v-model="loginInput.password" type="password" placeholder="Password" />
                    </div>
                </div>
                <div class="form-group mt-4">
                    <BaseBtn label="Sign In" :loading="loading" size="lg" variant="primary" type="submit" />
                </div>
                <div class="form-group mt-3 text-center">
                    <RouterLink to="/register" class="login-link">Don't have an account? Register</RouterLink>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.login-center {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.10);
    padding: 2.5rem 2.5rem 2rem 2.5rem;
    min-width: 370px;
    max-width: 400px;
    width: 100%;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-logo {
    font-size: 2.5rem;
    color: #6366f1;
    margin-bottom: 0.5rem;
}

.login-header h2 {
    font-weight: 700;
    color: #22223b;
    margin-bottom: 0.25rem;
}

.login-sub {
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

.login-link {
    color: #6366f1;
    font-weight: 500;
    text-decoration: none;
    transition: color 0.2s;
}

.login-link:hover {
    color: #4338ca;
    text-decoration: underline;
}

@media (max-width: 900px) {
    .login-card {
        min-width: 90vw;
    }
}
</style>