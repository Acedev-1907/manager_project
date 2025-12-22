<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import imageCompression from 'browser-image-compression';
import { showSuccess, showError } from '../../../../helper/alert';
import { getUserData } from '../../../../helper/getUserData';
import { APP } from '../../../../App/APP';
import { useUserStore } from '../../../../state/userStore';

interface Props {
    visible: boolean;
    imageUrl: string;
    imageFile: File | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved', avatarUrl: string): void;
}>();

const userStore = useUserStore();
const cropper = ref<Cropper | null>(null);
const cropperContainer = ref<HTMLImageElement | null>(null);
const avatarLoading = ref(false);
const zoomValue = ref(1);
const minZoom = ref(1);
const maxZoom = ref(2);

watch(() => props.visible, (visible) => {
    if (visible && cropperContainer.value) {
        nextTick(() => {
            initCropper();
        });
    } else if (!visible) {
        destroyCropper();
    }
});

function initCropper() {
    if (!cropperContainer.value) return;
    
    if (cropper.value) {
        cropper.value.destroy();
    }
    
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
            nextTick(() => { 
                zoomValue.value = minZoom.value; 
            });
        }
    } as any);
}

function destroyCropper() {
    if (cropper.value) {
        cropper.value.destroy();
        cropper.value = null;
    }
}

function onZoomInput(e: Event) {
    const val = +(e.target as HTMLInputElement).value;
    zoomValue.value = val;
    if (cropper.value) {
        (cropper.value as any).zoomTo(val);
    }
}

function zoomOut() {
    if (zoomValue.value > minZoom.value) {
        zoomValue.value = Math.max(zoomValue.value - 0.05, minZoom.value);
        if (cropper.value) {
            (cropper.value as any).zoomTo(zoomValue.value);
        }
    }
}

function zoomIn() {
    if (zoomValue.value < maxZoom.value) {
        zoomValue.value = Math.min(zoomValue.value + 0.05, maxZoom.value);
        if (cropper.value) {
            (cropper.value as any).zoomTo(zoomValue.value);
        }
    }
}

async function saveCroppedAvatar() {
    if (!cropper.value || !props.imageFile) return;
    
    (cropper.value as any).getCroppedCanvas({ 
        width: 320, 
        height: 320, 
        imageSmoothingQuality: 'high' 
    }).toBlob(async (blob: any) => {
        if (!blob) return;
        
        avatarLoading.value = true;
        try {
            const avatarFile = new File([blob], props.imageFile?.name || 'avatar.jpg', { 
                type: 'image/jpeg' 
            });
            
            const options = { 
                maxSizeMB: 0.3, 
                maxWidthOrHeight: 400, 
                useWebWorker: true 
            };
            const compressedFile = await imageCompression(avatarFile, options);
            
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
                headers: { 
                    Authorization: userData?.token ? `Bearer ${userData.token}` : '' 
                },
                body: formData,
            });
            
            const data = await res.json();
            if (data.code !== 1000) {
                showError(data.message || 'Upload failed.');
            } else {
                (userStore as any).setAvatar(data.data.link);
                emit('saved', data.data.link);
                emit('close');
                showSuccess(data.message || 'Avatar updated successfully!');
            }
        } catch (err: any) {
            showError(err?.message || 'Upload failed.');
        } finally {
            avatarLoading.value = false;
        }
    }, 'image/jpeg', 0.7);
}

function handleClose() {
    destroyCropper();
    emit('close');
}
</script>

<template>
    <div v-if="props.visible" class="modal-overlay">
        <div class="modal-cropper modal-cropper-edit-avatar modal-cropper-ui-strict">
            <div class="modal-cropper-header-ui-strict modal-cropper-header-ui-strict--with-border">
                <span class="modal-cropper-title">Edit Profile Picture</span>
                <button class="close-view-avatar-ui-strict" @click="handleClose">&times;</button>
            </div>
            <div class="cropper-container-ui-strict" style="position:relative;">
                <img 
                    :src="props.imageUrl" 
                    ref="cropperContainer" 
                    :style="{ opacity: avatarLoading ? 0.5 : 1 }" 
                />
                <div v-if="avatarLoading" class="avatar-loading-overlay">
                    <svg class="spinner spinner-large" viewBox="0 0 50 50">
                        <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                    </svg>
                </div>
            </div>
            <div class="cropper-zoom-bar-ui-strict">
                <button 
                    type="button" 
                    class="zoom-btn" 
                    @click="zoomOut" 
                    :disabled="zoomValue <= minZoom"
                >
                    -
                </button>
                <input 
                    type="range" 
                    :min="minZoom" 
                    :max="maxZoom" 
                    step="0.01" 
                    v-model.number="zoomValue" 
                    :value="zoomValue" 
                    @input="onZoomInput" 
                />
                <button 
                    type="button" 
                    class="zoom-btn" 
                    @click="zoomIn" 
                    :disabled="zoomValue >= maxZoom"
                >
                    +
                </button>
            </div>
            <div class="modal-actions-ui-strict">
                <button 
                    @click="handleClose" 
                    class="cancel-ui-strict" 
                    :disabled="avatarLoading"
                >
                    Cancel
                </button>
                <button 
                    @click="saveCroppedAvatar" 
                    class="save-ui-strict" 
                    :disabled="avatarLoading"
                >
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
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-cropper {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    min-width: 420px;
    max-width: 98vw;
    box-shadow: 0 4px 32px rgba(0, 0, 0, 0.15);
}

.modal-cropper-header-ui-strict {
    width: 100%;
    padding: 0 0 16px 0;
    border-bottom: 1.5px solid #e5e7eb;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-cropper-title {
    font-size: 1.18rem;
    font-weight: 600;
}

.close-view-avatar-ui-strict {
    background: none;
    border: none;
    font-size: 2rem;
    color: #222;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
}

.close-view-avatar-ui-strict:hover {
    background: #f0f2f5;
    color: #e11d48;
}

.cropper-container-ui-strict {
    width: 320px;
    height: 320px;
    margin: 24px auto;
    border-radius: 50%;
    overflow: hidden;
    background: #f3f4f6;
}

.cropper-container-ui-strict img {
    width: 320px;
    height: 320px;
    object-fit: cover;
    display: block;
}

.cropper-zoom-bar-ui-strict {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
    margin: 16px 0;
}

.cropper-zoom-bar-ui-strict input[type="range"] {
    flex: 1;
    accent-color: #1877f2;
    height: 3px;
}

.zoom-btn {
    font-size: 1.5rem;
    color: #1877f2;
    font-weight: 700;
    width: 32px;
    height: 32px;
    border: none;
    background: none;
    cursor: pointer;
}

.zoom-btn:disabled {
    color: #bbb;
    cursor: not-allowed;
}

.modal-actions-ui-strict {
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    margin-top: 16px;
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
}

.cancel-ui-strict:hover {
    background: #e5e7eb;
}

.save-ui-strict {
    padding: 10px 32px;
    background: #1877f2;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
}

.save-ui-strict:hover {
    background: #166fe5;
}

.save-ui-strict:disabled {
    background: #a5b4fc;
    cursor: not-allowed;
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
}

.spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    100% {
        transform: rotate(360deg);
    }
}

.spinner .path {
    stroke: #1877f2;
    stroke-linecap: round;
    stroke-dasharray: 90, 150;
    stroke-dashoffset: 0;
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

