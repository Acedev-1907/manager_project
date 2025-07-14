<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { makeHttpReq } from '../../helper/makeHttpReq';
import { useRouter } from 'vue-router';

const router = useRouter();
const user = ref({
    name: '',
    email: '',
    phone: '',
    avatar: '',
});
const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const avatarInput = ref('');
const showAvatarInput = ref(false);

async function fetchUser() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const res = await makeHttpReq<undefined, any>('user', 'GET');
        user.value = {
            name: res.data.name,
            email: res.data.email,
            phone: res.data.phone || '',
            avatar: res.data.avatar || '',
        };
        avatarInput.value = user.value.avatar;
    } catch (err: any) {
        errorMessage.value = err?.message || 'Failed to load user info.';
    } finally {
        loading.value = false;
    }
}

function openAvatarInput() {
    showAvatarInput.value = true;
    avatarInput.value = user.value.avatar;
}
function cancelAvatarInput() {
    showAvatarInput.value = false;
    avatarInput.value = user.value.avatar;
}
function saveAvatarInput() {
    user.value.avatar = avatarInput.value;
    showAvatarInput.value = false;
}

async function updateUser() {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    try {
        const payload: any = {
            name: user.value.name,
            phone: user.value.phone,
            avatar: user.value.avatar,
        };
        const res = await makeHttpReq<typeof payload, any>('user', 'PUT', payload);
        successMessage.value = res.message || 'Profile updated successfully!';
    } catch (err: any) {
        errorMessage.value = err?.message || 'Update failed.';
    } finally {
        loading.value = false;
    }
}

function goToChangePassword() {
    router.push('/change-password');
}

onMounted(fetchUser);
</script>

<template>
    <div class="user-profile-2col-card">
        <div class="profile-avatar-col">
            <div class="avatar-section">
                <div class="avatar-wrapper">
                    <img :src="user.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name)"
                        alt="Avatar" />
                    <button class="avatar-change-btn" @click="openAvatarInput" type="button">Change</button>
                </div>
                <div v-if="showAvatarInput" class="avatar-input-popup">
                    <input v-model="avatarInput" type="text" placeholder="Paste image URL..." />
                    <button @click="saveAvatarInput" type="button">Save</button>
                    <button @click="cancelAvatarInput" type="button" class="cancel">Cancel</button>
                </div>
            </div>
        </div>
        <div class="profile-info-col">
            <h2>User Profile</h2>
            <form @submit.prevent="updateUser" class="profile-form">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input id="name" v-model="user.name" type="text" required />
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" v-model="user.email" type="email" disabled
                        style="background:#f3f4f6; color:#888; cursor:not-allowed;" />
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input id="phone" v-model="user.phone" type="text" maxlength="20"
                        placeholder="Enter phone number" />
                </div>
                <button type="submit" :disabled="loading">{{ loading ? 'Saving...' : 'Save Changes' }}</button>
                <button type="button" class="change-password-btn" @click="goToChangePassword">Change Password</button>
                <div v-if="successMessage" class="success-message">{{ successMessage }}</div>
                <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.user-profile-2col-card {
    max-width: 700px;
    margin: 40px auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(36, 112, 220, 0.10);
    padding: 36px 28px 32px 28px;
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    gap: 36px;
}

.profile-avatar-col {
    flex: 0 0 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.avatar-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin-bottom: 8px;
}

.avatar-wrapper img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e0e7ef;
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.10);
    background: #f3f4f6;
}

.avatar-change-btn {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 4px 18px;
    font-size: 0.98rem;
    font-weight: 500;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(36, 112, 220, 0.08);
    transition: background 0.18s;
}

.avatar-change-btn:hover {
    background: #1d4ed8;
}

.avatar-input-popup {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.avatar-input-popup input {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
}

.avatar-input-popup button {
    padding: 6px 14px;
    border: none;
    border-radius: 6px;
    font-size: 0.98rem;
    font-weight: 500;
    cursor: pointer;
    background: #2563eb;
    color: #fff;
    transition: background 0.18s;
}

.avatar-input-popup button.cancel {
    background: #e5e7eb;
    color: #222;
}

.profile-info-col {
    flex: 1 1 0%;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.profile-info-col h2 {
    margin-bottom: 24px;
    font-size: 1.45rem;
    font-weight: 700;
    color: #222;
    text-align: left;
}

.profile-form {
    width: 100%;
}

.profile-form .form-group {
    margin-bottom: 18px;
}

.profile-form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
}

.profile-form input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 5px;
    font-size: 1rem;
}

.profile-form button[type="submit"] {
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
    box-shadow: 0 1px 4px rgba(36, 112, 220, 0.08);
}

.profile-form button[type="submit"]:disabled {
    background: #a5b4fc;
    cursor: not-allowed;
}

.change-password-btn {
    width: 100%;
    margin-top: 10px;
    padding: 10px;
    background: #fff;
    color: #2563eb;
    border: 1.5px solid #2563eb;
    border-radius: 7px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s, color 0.18s;
}

.change-password-btn:hover {
    background: #2563eb;
    color: #fff;
}

.success-message {
    color: #16a34a;
    margin-top: 14px;
    text-align: left;
}

.error-message {
    color: #dc2626;
    margin-top: 14px;
    text-align: left;
}

@media (max-width: 900px) {
    .user-profile-2col-card {
        flex-direction: column;
        align-items: center;
        gap: 18px;
        padding: 18px 4vw 18px 4vw;
        max-width: 98vw;
    }

    .profile-avatar-col {
        margin-bottom: 8px;
    }

    .profile-info-col {
        width: 100%;
        align-items: center;
    }

    .profile-info-col h2 {
        text-align: center;
        width: 100%;
    }

    .profile-form {
        width: 100%;
    }
}
</style>