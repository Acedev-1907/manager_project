<script setup lang="ts">
import { ref } from 'vue';
import { makeHttpReq } from '../../helper/makeHttpReq';

const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

async function sendResetPasswordEmail() {
    loading.value = true;
    successMessage.value = '';
    errorMessage.value = '';
    try {
        const res = await makeHttpReq<undefined, any>('user/send-reset-password-link', 'POST');
        successMessage.value = res.message || 'Password reset email sent!';
    } catch (err: any) {
        errorMessage.value = err?.message || err?.response?.data?.message || 'Failed to send reset email.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="change-password-card">
        <h2>Change Password</h2>
        <p class="desc">Click the button below to send a password reset link to your email.</p>
        <button @click="sendResetPasswordEmail" :disabled="loading">
            {{ loading ? 'Sending...' : 'Send password reset email' }}
        </button>
        <div v-if="successMessage" class="success-message">{{ successMessage }}</div>
        <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>
    </div>
</template>

<style scoped>
.change-password-card {
    max-width: 400px;
    margin: 60px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(36, 112, 220, 0.10);
    padding: 36px 28px 32px 28px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.change-password-card h2 {
    margin-bottom: 18px;
    font-size: 1.35rem;
    font-weight: 700;
    color: #222;
}

.change-password-card .desc {
    color: #444;
    margin-bottom: 22px;
    text-align: center;
}

.change-password-card button {
    width: 100%;
    padding: 12px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 10px;
    transition: background 0.2s;
}

.change-password-card button:disabled {
    background: #a5b4fc;
    cursor: not-allowed;
}

.success-message {
    color: #16a34a;
    margin-top: 18px;
    text-align: center;
}

.error-message {
    color: #dc2626;
    margin-top: 18px;
    text-align: center;
}
</style>