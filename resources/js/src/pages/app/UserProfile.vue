<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import { makeHttpReq } from '../../helper/makeHttpReq';
import { useRouter } from 'vue-router';
import { getUserData } from '../../helper/getUserData';
import { APP } from '../../App/APP';
import { useUserStore } from '../../state/userStore';
import imageCompression from 'browser-image-compression';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import { showSuccess, showError, showConfirm } from '../../helper/alert';
import { getAvatarSrc } from '../../helper/avatar';
import { useCacheFetch } from '../../helper/useCacheFetch';

const router = useRouter();
const user = ref({ id: 0, name: '', email: '', phone: '', avatar: '' });
const loading = ref(false); // loading cho Save Changes
const avatarLoading = ref(false); // loading cho modal crop avatar
const successMessage = ref('');
const errorMessage = ref('');
const avatarFile = ref<File | null>(null);
const showCropModal = ref(false);
const cropper = ref<Cropper | null>(null);
const cropImageUrl = ref('');
const cropImageFile = ref<File | null>(null);
const cropperContainer = ref<HTMLImageElement | null>(null);
const showViewAvatarModal = ref(false);
const avatarFileInput = ref<HTMLInputElement | null>(null);
const userStore = useUserStore();
const zoomValue = ref(1);
const minZoom = ref(1);
const maxZoom = ref(2);

const { getOrFetch, refetch } = useCacheFetch(
    { user: userStore.userInfoCache },
    (key, data) => userStore.setUserInfoCache(data),
    () => userStore.clearUserInfoCache()
);

function onAvatarClick() {
    showViewAvatarModal.value = true;
}
function closeViewAvatarModal() {
    showViewAvatarModal.value = false;
}
function onCameraClick() {
    if (avatarFileInput.value) avatarFileInput.value.value = '';
    avatarFileInput.value?.click();
}
function onAvatarFileInputChange(e: Event) {
    const files = (e.target as HTMLInputElement).files;
    if (files && files.length > 0) {
        cropImageFile.value = files[0];
        cropImageUrl.value = URL.createObjectURL(files[0]);
        minZoom.value = 1;
        maxZoom.value = 2;
        zoomValue.value = 1;
        showCropModal.value = true;
    }
}
function onZoomInput(e: Event) {
    const val = +(e.target as HTMLInputElement).value;
    zoomValue.value = val;
    if (cropper.value) (cropper.value as any).zoomTo(val);
}
function zoomOut() {
    if (zoomValue.value > minZoom.value) {
        zoomValue.value = Math.max(zoomValue.value - 0.05, minZoom.value);
        if (cropper.value) (cropper.value as any).zoomTo(zoomValue.value);
    }
}
function zoomIn() {
    if (zoomValue.value < maxZoom.value) {
        zoomValue.value = Math.min(zoomValue.value + 0.05, maxZoom.value);
        if (cropper.value) (cropper.value as any).zoomTo(zoomValue.value);
    }
}
async function closeCropModalWithConfirm() {
    const ok = await showConfirm('Are you sure you want to cancel editing your profile picture?', 'Cancel Editing');
    if (ok) closeCropModal();
}
async function fetchUser() {
    loading.value = true;
    errorMessage.value = '';
    try {
        await getOrFetch('user', async () => {
            const res = await makeHttpReq<undefined, any>('user', 'GET');
            return {
                name: res.data.name,
                email: res.data.email,
                phone: res.data.phone || '',
                avatar: res.data.avatar || '',
                friend_code: res.data.friend_code || null,
            };
        }, (data) => {
            user.value = data;
            userStore.setUser({ 
                id: data.id,
                name: data.name, 
                avatar: data.avatar, 
                friend_code: data.friend_code 
            });
        });
    } catch (err: any) {
        errorMessage.value = err?.message || 'Failed to load user info.';
    } finally {
        loading.value = false;
    }
}
function closeCropModal() {
    showCropModal.value = false;
    cropImageUrl.value = '';
    cropImageFile.value = null;
    if (cropper.value) {
        (cropper.value as any).destroy();
        cropper.value = null;
    }
}
function onCropperReady() {
    if (cropperContainer.value) {
        if (cropper.value) (cropper.value as any).destroy();
        cropper.value = new Cropper(cropperContainer.value, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            background: false,
            guides: false,
            autoCropArea: 1,
            movable: true,
            zoomable: true,
            rotatable: false,
            scalable: false,
            cropBoxResizable: true,
            minContainerWidth: 320,
            minContainerHeight: 320,
            ready() {
                const cropperInstance = cropper.value as any;
                const imageData = cropperInstance.getImageData();
                const canvasData = cropperInstance.getCanvasData();
                const currentScale = canvasData.width / imageData.naturalWidth;
                minZoom.value = currentScale;
                maxZoom.value = Math.max(currentScale * 2, currentScale + 0.5);
                zoomValue.value = minZoom.value;
                cropperInstance.zoomTo(minZoom.value);
                nextTick(() => { zoomValue.value = minZoom.value; });
            }
        } as any);
    }
}
async function saveCroppedAvatar() {
    if (!cropper.value) return;
    (cropper.value as any).getCroppedCanvas({ width: 320, height: 320, imageSmoothingQuality: 'high' }).toBlob(async (blob: any) => {
        if (!blob) return;
        avatarFile.value = new File([blob], cropImageFile.value?.name || 'avatar.jpg', { type: 'image/jpeg' });
        avatarLoading.value = true;
        try {
            const options = { maxSizeMB: 0.3, maxWidthOrHeight: 400, useWebWorker: true };
            const compressedFile = await imageCompression(avatarFile.value, options);
            if (compressedFile.size > 2 * 1024 * 1024) {
                showError('Image is still larger than 2MB after compression. Please choose a smaller image.');
                avatarLoading.value = false;
                return;
            }
            const formData = new FormData();
            formData.append('avatar', compressedFile);
            const userData = getUserData();
            const res = await fetch(`${APP.apiBaseURL}/user/upload-avatar`, {
                method: 'POST',
                headers: { Authorization: userData?.token ? `Bearer ${userData.token}` : '' },
                body: formData,
            });
            const data = await res.json();
            if (data.code !== 1000) {
                showError(data.message || 'Upload failed.');
            } else {
                user.value.avatar = data.data.link;
                userStore.setAvatar(data.data.link);
                userStore.setUser({ ...user.value }); // Đảm bảo Navbar cập nhật avatar mới
                await nextTick();
                closeCropModal();
                showSuccess(data.message || 'Avatar updated successfully!');
            }
        } catch (err: any) {
            showError(err?.message || 'Upload failed.');
        } finally {
            avatarLoading.value = false;
            avatarFile.value = null;
        }
    }, 'image/jpeg', 0.7);
}
async function updateUser() {
    // Prevent spam clicking
    if (loading.value) return;

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
        userStore.setUser({ 
            id: user.value.id,
            name: user.value.name, 
            avatar: user.value.avatar, 
            friend_code: res.data.friend_code || null 
        });
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
                <div class="avatar-wrapper" style="position:relative;">
                    <img :src="getAvatarSrc(user.avatar, user.name)" alt="Avatar" @click="onAvatarClick"
                        style="cursor:pointer;" />
                    <button class="avatar-camera-btn" @click="onCameraClick" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="camera-heroicon">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 15.75V8.25c0-1.243 1.007-2.25 2.25-2.25h2.086a2.25 2.25 0 0 0 1.591-.659l.828-.828A2.25 2.25 0 0 1 11.091 4.5h1.818a2.25 2.25 0 0 1 1.591.659l.828.828a2.25 2.25 0 0 0 1.591.659h2.086c1.243 0 2.25 1.007 2.25 2.25v7.5c0 1.243-1.007 2.25-2.25 2.25H4.5a2.25 2.25 0 0 1-2.25-2.25z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z" />
                        </svg>
                    </button>
                    <input ref="avatarFileInput" type="file" accept="image/*" style="display:none"
                        @change="onAvatarFileInputChange" />
                </div>
            </div>
        </div>
        <!-- Modal crop ảnh -->
        <div v-if="showCropModal" class="modal-overlay">
            <div class="modal-cropper modal-cropper-edit-avatar modal-cropper-ui-strict">
                <div class="modal-cropper-header-ui-strict modal-cropper-header-ui-strict--with-border">
                    <span class="modal-cropper-title">Edit Profile Picture</span>
                    <button class="close-view-avatar-ui-strict" @click="closeCropModalWithConfirm">&times;</button>
                </div>
                <div class="cropper-container-ui-strict" style="position:relative;">
                    <img :src="cropImageUrl" ref="cropperContainer" @load="onCropperReady"
                        :style="{ opacity: avatarLoading ? 0.5 : 1 }" />
                    <div v-if="avatarLoading" class="avatar-loading-overlay">
                        <svg class="spinner spinner-large" viewBox="0 0 50 50">
                            <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                        </svg>
                    </div>
                </div>
                <div class="cropper-zoom-bar-ui-strict">
                    <button type="button" class="zoom-btn" @click="zoomOut" :disabled="zoomValue <= minZoom">-</button>
                    <input :type="'range'" :min="minZoom" :max="maxZoom" step="0.01" v-model.number="zoomValue"
                        :value="zoomValue" @input="onZoomInput" />
                    <button type="button" class="zoom-btn" @click="zoomIn" :disabled="zoomValue >= maxZoom">+</button>
                </div>
                <div class="modal-actions-ui-strict">
                    <button @click="closeCropModalWithConfirm" class="cancel-ui-strict"
                        :disabled="avatarLoading">Cancel</button>
                    <button @click="saveCroppedAvatar" class="save-ui-strict" :disabled="avatarLoading">
                        <span v-if="avatarLoading">
                            <svg class="spinner spinner-btn" width="28" height="28" viewBox="0 0 50 50">
                                <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="6"></circle>
                            </svg>
                            Saving...
                        </span>
                        <span v-else>Save</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal xem ảnh đại diện -->
        <div v-if="showViewAvatarModal" class="modal-overlay" @click.self="closeViewAvatarModal">
            <div class="modal-view-avatar">
                <img :src="getAvatarSrc(user.avatar, user.name)" alt="Avatar"
                    style="max-width: 90vw; max-height: 80vh; border-radius: 16px;" />
                <button class="close-view-avatar" @click="closeViewAvatarModal">&times;</button>
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
                <button type="submit" :disabled="loading">
                    <span v-if="loading">
                        <svg class="spinner" width="20" height="20" viewBox="0 0 50 50">
                            <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                        </svg>
                        Saving...
                    </span>
                    <span v-else>Save Changes</span>
                </button>
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

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 22;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeInModalBg 0.18s;
}

@keyframes fadeInModalBg {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.modal-cropper {
    background: #fff;
    border-radius: 16px;
    padding: 24px 28px 18px 28px;
    min-width: 340px;
    max-width: 98vw;
    box-shadow: 0 4px 24px rgba(36, 112, 220, 0.13);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.modal-cropper-edit-avatar {
    min-width: 340px;
    max-width: 98vw;
    border-radius: 18px;
    padding: 0 0 18px 0;
    box-shadow: 0 8px 40px rgba(36, 112, 220, 0.18);
    display: flex;
    flex-direction: column;
    align-items: center;
    background: #fff;
}

.modal-cropper-header {
    width: 100%;
    padding: 12px 0 12px 0;
    border-bottom: 1.5px solid #e5e7eb;
    text-align: center;
    font-size: 1.18rem;
    font-weight: 500;
    letter-spacing: 0.01em;
    color: #222;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cropper-container {
    width: 320px;
    height: 320px;
    margin-bottom: 18px;
    border-radius: 50%;
    overflow: hidden;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-actions {
    display: flex;
    gap: 18px;
    margin-top: 12px;
}

.modal-actions button {
    padding: 8px 22px;
    border-radius: 8px;
    border: none;
    font-size: 1.05rem;
    font-weight: 600;
    cursor: pointer;
    background: #2563eb;
    color: #fff;
    transition: background 0.18s;
}

.modal-actions button.cancel {
    background: #e5e7eb;
    color: #222;
}

.avatar-camera-btn {
    position: absolute;
    top: 90px;
    right: 8px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(36, 112, 220, 0.10);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 2;
    transition: box-shadow 0.18s;
    padding: 0;
}

.avatar-camera-btn:hover {
    box-shadow: 0 4px 16px rgba(36, 112, 220, 0.18);
}

.camera-heroicon {
    width: 22px;
    height: 22px;
    color: #222;
    display: block;
}

.avatar-wrapper img {
    cursor: pointer;
}

.modal-view-avatar {
    position: relative;
    background: #fff;
    border-radius: 24px;
    padding: 24px 24px 24px 24px;
    box-shadow: 0 8px 40px rgba(36, 112, 220, 0.18);
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 96vw;
    max-height: 90vh;
    animation: scaleInModal 0.22s cubic-bezier(.4, 1.4, .6, 1);
}

@keyframes scaleInModal {
    from {
        transform: scale(0.85);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

.modal-view-avatar img {
    max-width: 70vw;
    max-height: 70vh;
    border-radius: 18px;
    box-shadow: 0 2px 16px rgba(36, 112, 220, 0.10);
    background: #f3f4f6;
    display: block;
}

.close-view-avatar {
    position: absolute;
    top: 0;
    right: 0;
    background: none;
    border: none;
    font-size: 2.2rem;
    color: #222;
    cursor: pointer;
    z-index: 2;
    line-height: 1;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.18s;
}

.close-view-avatar:hover {
    background: none;
    color: #e11d48;
    box-shadow: none;
}

.cropper-zoom-bar {
    width: 90%;
    margin: 18px auto 0 auto;
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
}

.cropper-zoom-bar input[type="range"] {
    flex: 1;
    accent-color: #2563eb;
    height: 3px;
    background: #e5e7eb;
    border-radius: 3px;
}

.zoom-label {
    font-size: 0.98rem;
    color: #444;
    min-width: 70px;
    text-align: right;
}

@media (max-width: 600px) {
    .modal-view-avatar {
        padding: 6vw 2vw 6vw 2vw;
        border-radius: 16px;
    }

    .modal-view-avatar img {
        max-width: 92vw;
        max-height: 60vh;
        border-radius: 12px;
    }

    .close-view-avatar {
        top: 4px;
        right: 6px;
        font-size: 2rem;
        width: 36px;
        height: 36px;
    }
}

.modal-cropper-ui-strict {
    min-width: 420px;
    max-width: 98vw;
    border-radius: 28px;
    padding: 0 0 32px 0;
    box-shadow: 0 4px 32px rgba(36, 112, 220, 0.10);
    display: flex;
    flex-direction: column;
    align-items: center;
    background: #fff;
}

.modal-cropper-header-ui-strict {
    width: 100%;
    padding: 28px 0 0 0;
    text-align: center;
    font-size: 1.45rem;
    font-weight: 700;
    color: #222;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-cropper-header-ui-strict--with-border {
    padding: 8px 0 8px 0;
    border-bottom: 1.5px solid #e5e7eb;
    margin-bottom: 0;
    justify-content: space-between;
}

.modal-cropper-title {
    flex: 1;
    text-align: left;
    font-size: 1.18rem;
    font-weight: 600;
    padding-left: 18px;
}

.close-view-avatar-ui-strict {
    position: static;
    background: none;
    border: none;
    font-size: 2.2rem;
    color: #222;
    cursor: pointer;
    z-index: 2;
    line-height: 1;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.18s, color 0.18s;
    margin-right: 8px;
}

.close-view-avatar-ui-strict:hover {
    color: #e11d48;
}

.cropper-container-ui-strict {
    width: 320px;
    height: 320px;
    margin: 24px auto 0 auto;
    border-radius: 50%;
    overflow: hidden;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cropper-container-ui-strict img {
    width: 320px;
    height: 320px;
    object-fit: cover;
    border-radius: 50%;
    display: block;
}

.cropper-zoom-bar-ui-strict {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
}

.cropper-zoom-bar-ui-strict input[type="range"] {
    z-index: 1;
    flex: 1;
    accent-color: #2563eb;
    height: 3px;
    background: #e5e7eb;
    border-radius: 3px;
}

.zoom-btn {
    font-size: 1.5rem;
    color: #2563eb;
    font-weight: 700;
    user-select: none;
    width: 32px;
    height: 32px;
    text-align: center;
    cursor: pointer;
    border: none;
    background: none;
    transition: color 0.18s;
    display: flex;
    align-items: center;
    justify-content: center;
    touch-action: manipulation;
    pointer-events: auto;
    z-index: 2;
}

.zoom-btn:disabled {
    color: #bbb;
    cursor: not-allowed;
}

.zoom-btn:active {
    color: #1d4ed8;
}

.modal-actions-ui-strict {
    width: 320px;
    margin: 32px auto 0 auto;
    display: flex;
    justify-content: flex-end;
    gap: 16px;
}

.cancel-ui-strict {
    padding: 10px 32px;
    background: #f3f4f6;
    color: #222;
    border: none;
    border-radius: 12px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s;
}

.cancel-ui-strict:hover {
    background: #e5e7eb;
}

.save-ui-strict {
    padding: 10px 32px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s;
}

.save-ui-strict:hover {
    background: #1d4ed8;
}

.save-ui-strict:disabled {
    background: #a5b4fc !important;
    color: #e0e7ef !important;
    opacity: 0.7;
    cursor: not-allowed;
}

.spinner {
    animation: spin 1s linear infinite;
    vertical-align: middle;
    margin-right: 8px;
}

@keyframes spin {
    100% {
        transform: rotate(360deg);
    }
}

.spinner .path {
    stroke: #2563eb;
    stroke-linecap: round;
    stroke-dasharray: 90, 150;
    stroke-dashoffset: 0;
}

@media (max-width: 600px) {
    .modal-cropper-ui-strict {
        min-width: 0;
        width: 96vw;
        max-width: 340px;
        padding: 0 0 12px 0;
        border-radius: 14px;
    }

    .cropper-container-ui-strict {
        width: 96vw;
        max-width: 320px;
        height: 96vw;
        max-height: 320px;
    }

    .cropper-container-ui-strict img {
        width: 96vw;
        max-width: 320px;
        height: 96vw;
        max-height: 320px;
    }

    .cropper-zoom-bar-ui-strict,
    .modal-actions-ui-strict {
        width: 96vw;
        max-width: 320px;
    }

    .modal-cropper-header-ui-strict {
        font-size: 1rem;
        padding: 12px 0 0 0;
    }
}

.avatar-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.5);
    z-index: 10;
    border-radius: 50%;
}

.spinner-large {
    width: 48px;
    height: 48px;
}

.spinner-btn {
    width: 28px;
    height: 28px;
    margin-right: 8px;
}

.spinner-btn .path {
    stroke: #fff;
    stroke-width: 6;
}
</style>