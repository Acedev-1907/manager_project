<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { makeHttpReq } from '../../helper/makeHttpReq';

const route = useRoute();
const router = useRouter();
const email = ref(route.query.email || '');
const token = ref(route.query.token || '');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const tokenValid = ref(false);

onMounted(async () => {
    if (!token.value || !email.value) {
        errorMessage.value = 'Invalid reset link.';
        return;
    }
    loading.value = true;
    try {
        const res = await makeHttpReq<any, any>('check-reset-token', 'POST', {
            token: token.value,
            email: email.value,
        }, { showGlobalLoading: false });
        if (res.success) {
            tokenValid.value = true;
        } else {
            errorMessage.value = res.message || 'Token is invalid or expired.';
        }
    } catch (err: any) {
        errorMessage.value = err?.message || 'Token is invalid or expired.';
    } finally {
        loading.value = false;
    }
});

async function resetPassword() {
    loading.value = true;
    successMessage.value = '';
    errorMessage.value = '';
    try {
        const res = await makeHttpReq<any, any>('reset-password', 'POST', {
            email: email.value,
            token: token.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        }, { showGlobalLoading: false });
        successMessage.value = res.message || 'Password has been reset!';
        setTimeout(() => router.push('/login'), 2000);
    } catch (err: any) {
        errorMessage.value = err?.message || 'Reset failed.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="reset-bg">
        <div class="reset-card">
            <div class="reset-icon-gradient">
                <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <circle cx="24" cy="24" r="24" fill="url(#grad)" />
                    <g>
                        <path
                            d="M32.5 23.5a8.5 8.5 0 1 0-7.1 8.4v2.1a1.6 1.6 0 1 0 3.2 0v-2.1a8.5 8.5 0 0 0 3.9-8.4Zm-8.5 5.3a5.3 5.3 0 1 1 0-10.6 5.3 5.3 0 0 1 0 10.6Z"
                            fill="#fff" />
                    </g>
                    <defs>
                        <linearGradient id="grad" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#60a5fa" />
                            <stop offset="1" stop-color="#2563eb" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h2>Reset Your Password</h2>
            <form v-if="tokenValid" @submit.prevent="resetPassword" class="form-reset">
                <!-- <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" v-model="email" type="email" required disabled />
                </div> -->
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input id="password" v-model="password" type="password" required minlength="6"
                        placeholder="Enter new password" />
                </div>
                <div class="form-group">
                    <label for="passwordConfirmation">Confirm New Password</label>
                    <input id="passwordConfirmation" v-model="passwordConfirmation" type="password" required
                        minlength="6" placeholder="Confirm new password" />
                </div>
                <button type="submit" :disabled="loading">{{ loading ? 'Saving...' : 'Reset Password' }}</button>
                <div v-if="successMessage" class="success-message">{{ successMessage }}</div>
                <div v-if="errorMessage && !tokenValid" class="error-message">{{ errorMessage }}</div>
            </form>
            <div v-else-if="errorMessage" class="error-message">{{ errorMessage }}</div>
        </div>
    </div>
</template>

<style scoped>
.reset-bg {
    min-height: 100vh;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.reset-card {
    width: 100%;
    max-width: 600px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 18px rgba(36, 112, 220, 0.10);
    padding: 32px 12px 24px 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 32px 0;
    position: relative;
}

.reset-icon-gradient {
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.reset-card h2 {
    margin-bottom: 20px;
    font-size: 1.18rem;
    font-weight: 800;
    color: #222;
    text-align: center;
    letter-spacing: 0.01em;
}

.reset-card .form-group {
    margin-bottom: 16px;
    width: 100%;
}

.reset-card label {
    display: block;
    margin-bottom: 7px;
    font-weight: 600;
    color: #222;
    font-size: 0.98rem;
    letter-spacing: 0.01em;
}

.reset-card input {
    width: 100%;
    box-sizing: border-box;
    padding: 14px 16px;
    border: 1.2px solid #d1d5db;
    border-radius: 10px;
    font-size: 1rem;
    background: #f8fafc;
    transition: border 0.18s, box-shadow 0.18s;
    font-family: inherit;
    margin: 0;
    box-shadow: 0 1px 4px rgba(36, 112, 220, 0.03);
}

.reset-card input:focus {
    border: 1.2px solid #2563eb;
    outline: none;
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.08);
}

.reset-card button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(90deg, #2563eb 0%, #60a5fa 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 8px;
    transition: background 0.2s, box-shadow 0.2s;
    box-shadow: 0 1px 4px rgba(36, 112, 220, 0.08);
    letter-spacing: 0.01em;
}

.reset-card button:hover:not(:disabled) {
    background: linear-gradient(90deg, #1d4ed8 0%, #60a5fa 100%);
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.13);
}

.reset-card button:disabled {
    background: #a5b4fc;
    cursor: not-allowed;
}

.success-message {
    color: #16a34a;
    margin-top: 14px;
    text-align: center;
    font-weight: 700;
    font-size: 1rem;
}

.error-message {
    color: #dc2626;
    margin-top: 14px;
    text-align: center;
    font-weight: 700;
    font-size: 1rem;
}

.form-reset {
    width: 60%;
    margin: 0 auto;
}

@media (max-width: 600px) {
    .reset-card {
        padding: 12px 2vw 12px 2vw;
        max-width: 98vw;
    }
}
</style>