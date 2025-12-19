<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
import { showError } from '../../../../helper/alert';

const props = defineProps<{
    visible: boolean;
}>();

const emit = defineEmits(['close', 'select']);

const userImages = ref<any[]>([]);
const isLoading = ref(false);
const isUploading = ref(false);
const selectedImages = ref<string[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);

const hasImages = computed(() => userImages.value.length > 0);

const fetchUserImages = async () => {
    isLoading.value = true;
    try {
        const res = await makeHttpReq<never, any[]>('/posts/user-images', 'GET');
        userImages.value = Array.isArray(res) ? res : [];
    } catch (error) {
        console.error('Lỗi khi tải thư viện hình:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleFileSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = target.files;
    if (!files || files.length === 0) return;

    const validFiles: File[] = [];
    
    // Validate all files
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/')) {
            showError(`File "${file.name}" không phải là hình ảnh`);
            continue;
        }
        if (file.size > 10 * 1024 * 1024) {
            showError(`File "${file.name}" quá lớn. Vui lòng chọn file nhỏ hơn 10MB`);
            continue;
        }
        validFiles.push(file);
    }

    if (validFiles.length === 0) return;

    isUploading.value = true;
    try {
        const formData = new FormData();
        validFiles.forEach(file => {
            formData.append('images[]', file);
        });

        // Use fetch directly for FormData
        const userData = (await import('../../../../helper/getUserData')).getUserData();
        const authHeader = userData?.token ? `Bearer ${userData.token}` : '';
        const { APP } = await import('../../../../App/APP');
        
        const cleanEndpoint = 'posts/upload-image';
        const cleanBaseURL = APP.apiBaseURL.endsWith('/') ? APP.apiBaseURL.slice(0, -1) : APP.apiBaseURL;
        const url = `${cleanBaseURL}/${cleanEndpoint}`;

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                ...(authHeader && { Authorization: authHeader }),
            },
            credentials: 'include',
            body: formData
        });

        const res = await response.json();

        if (!response.ok) {
            throw new Error(res.error || 'Upload failed');
        }

        if (res.images && res.images.length > 0) {
            const uploadedUrls = res.images.map((img: any) => img.url);
            selectedImages.value = [...selectedImages.value, ...uploadedUrls];
            emit('select', uploadedUrls);
        }
    } catch (error: any) {
        showError(error?.message || 'Không thể upload hình. Vui lòng thử lại.');
        console.error(error);
    } finally {
        isUploading.value = false;
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    }
};

const toggleImage = (url: string) => {
    const index = selectedImages.value.indexOf(url);
    if (index > -1) {
        selectedImages.value.splice(index, 1);
    } else {
        selectedImages.value.push(url);
    }
};

const isImageSelected = (url: string) => {
    return selectedImages.value.includes(url);
};

const openFileDialog = () => {
    fileInputRef.value?.click();
};

const confirmSelection = () => {
    if (selectedImages.value.length > 0) {
        emit('select', [...selectedImages.value]);
        closeModal();
    }
};

const closeModal = () => {
    selectedImages.value = [];
    emit('close');
};

onMounted(() => {
    if (props.visible) {
        fetchUserImages();
    }
});

// Watch for visibility changes
import { watch } from 'vue';
watch(() => props.visible, (newVal) => {
    if (newVal) {
        fetchUserImages();
    }
});
</script>

<template>
    <div v-if="visible" class="modal-overlay" @click.self="closeModal">
        <div class="modal-container" @click.stop>
            <div class="modal-header">
                <h3>Chọn hình ảnh</h3>
                <button class="close-btn" @click="closeModal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-body">
                <input 
                    ref="fileInputRef"
                    type="file" 
                    accept="image/*" 
                    multiple
                    @change="handleFileSelect"
                    style="display: none;"
                />

                <!-- Selected images preview - Compact at top -->
                <div v-if="selectedImages.length > 0" class="selected-preview">
                    <div class="selected-header">
                        <span class="selected-count">{{ selectedImages.length }} đã chọn</span>
                        <button class="clear-all-btn" @click="selectedImages = []">Xóa tất cả</button>
                    </div>
                    <div class="selected-scroll">
                        <div v-for="(url, index) in selectedImages" :key="index" class="selected-item">
                            <img :src="url" :alt="`Selected ${index}`" />
                            <button class="remove-selected" @click="toggleImage(url)">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Image Library -->
                <div v-if="hasImages" class="library-section">
                    <div class="library-header">
                        <h4>Thư viện của bạn</h4>
                        <span class="image-count">{{ userImages.length }} hình</span>
                    </div>
                    <div v-if="isLoading" class="loading-images">
                        <div class="skeleton-image" v-for="i in 6" :key="i"></div>
                    </div>
                    <div v-else class="image-grid">
                        <!-- Add new image button -->
                        <div class="image-item add-image-item" @click="openFileDialog" :class="{ uploading: isUploading }">
                            <div class="add-image-content">
                                <i v-if="isUploading" class="bi bi-hourglass-split"></i>
                                <i v-else class="bi bi-plus-lg"></i>
                            </div>
                        </div>
                        <!-- Existing images -->
                        <div 
                            v-for="img in userImages" 
                            :key="img.id"
                            class="image-item"
                            :class="{ selected: isImageSelected(img.url) }"
                            @click="toggleImage(img.url)"
                        >
                            <img :src="img.url" :alt="`Image ${img.id}`" />
                            <div class="image-overlay">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div v-if="isImageSelected(img.url)" class="selected-badge">
                                <i class="bi bi-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="!hasImages && !isLoading" class="empty-library">
                    <div 
                        class="empty-upload-card" 
                        @click="openFileDialog" 
                        :class="{ uploading: isUploading }"
                    >
                        <div class="empty-upload-icon">
                            <i v-if="isUploading" class="bi bi-hourglass-split"></i>
                            <i v-else class="bi bi-plus-lg"></i>
                        </div>
                        <p class="empty-upload-title">
                            {{ selectedImages.length > 0 ? 'Thêm hình mới từ máy' : 'Chưa có hình ảnh nào' }}
                        </p>
                        <p class="empty-upload-subtitle">
                            {{ selectedImages.length > 0 
                                ? 'Nhấn để tải thêm hình lên từ máy của bạn' 
                                : 'Nhấn để tải hình lên từ máy của bạn' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="cancel-btn" @click="closeModal">Hủy</button>
                <button 
                    class="confirm-btn" 
                    @click="confirmSelection"
                    :disabled="selectedImages.length === 0"
                >
                    Chọn ({{ selectedImages.length }})
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
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.modal-container {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 560px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e4e6e9;
    flex-shrink: 0;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1c1e21;
    letter-spacing: -0.3px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #65676b;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 50%;
    transition: background 0.2s;
}

.close-btn:hover {
    background: #f2f2f2;
}

.modal-body {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.library-section {
    flex: 1;
    min-height: 0;
}

.library-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.library-header h4 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #1c1e21;
}

.image-count {
    font-size: 0.85rem;
    color: #65676b;
}

.loading-images {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.skeleton-image {
    aspect-ratio: 1;
    background: #f0f2f5;
    border-radius: 8px;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.image-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s;
}

.image-item:hover {
    border-color: #1877f2;
    transform: scale(1.02);
}

.image-item.selected {
    border-color: #1877f2;
}

.image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}

.image-item:hover .image-overlay,
.image-item.selected .image-overlay {
    opacity: 1;
}

.image-overlay i {
    font-size: 2rem;
    color: #fff;
}

.image-item.selected .image-overlay {
    background: rgba(24, 119, 242, 0.5);
}

.add-image-item {
    background: #f0f2f5;
    border: 2px dashed #bcc0c4;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.add-image-item:hover {
    background: #e4e6e9;
    border-color: #1877f2;
    transform: scale(1.02);
}

.add-image-item.uploading {
    background: #e3f2fd;
    border-color: #1877f2;
    cursor: wait;
}

.add-image-content {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #65676b;
    font-size: 2rem;
    transition: color 0.2s;
}

.add-image-item:hover .add-image-content {
    color: #1877f2;
}

.add-image-item.uploading .add-image-content {
    color: #1877f2;
}

.selected-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    background: #1877f2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.875rem;
}

.selected-preview {
    flex-shrink: 0;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
}

.selected-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.selected-count {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1877f2;
}

.clear-all-btn {
    background: none;
    border: none;
    color: #65676b;
    font-size: 0.85rem;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.clear-all-btn:hover {
    background: #e4e6e9;
    color: #1c1e21;
}

.selected-scroll {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
}

.selected-scroll::-webkit-scrollbar {
    height: 4px;
}

.selected-scroll::-webkit-scrollbar-thumb {
    background: #bcc0c4;
    border-radius: 2px;
}

.selected-item {
    position: relative;
    width: 80px;
    height: 80px;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #1877f2;
    background: #fff;
}

.selected-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-selected {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 20px;
    height: 20px;
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.75rem;
    transition: background 0.2s;
}

.remove-selected:hover {
    background: rgba(0, 0, 0, 0.8);
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    border-top: 1px solid #e4e6e9;
    flex-shrink: 0;
    background: #fafbfc;
}

.cancel-btn, .confirm-btn {
    padding: 9px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 80px;
}

.cancel-btn {
    background: #f0f2f5;
    color: #1c1e21;
    border: none;
}

.cancel-btn:hover {
    background: #e4e6e9;
}

.confirm-btn {
    background: #1877f2;
    color: #fff;
    border: none;
}

.confirm-btn:hover:not(:disabled) {
    background: #166fe5;
}

.confirm-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.empty-library {
    text-align: center;
    padding: 40px 20px;
    color: #65676b;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.empty-upload-card {
    border: 2px dashed #d1d5db;
    border-radius: 16px;
    padding: 32px 24px;
    max-width: 320px;
    width: 100%;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    background: #f9fafb;
}

.empty-upload-card:hover {
    border-color: #4f46e5;
    background: #eef2ff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
}

.empty-upload-card.uploading {
    cursor: wait;
    opacity: 0.8;
}

.empty-upload-icon i {
    font-size: 2.2rem;
    color: #4f46e5;
}

.empty-upload-title {
    margin: 8px 0 0;
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
}

.empty-upload-subtitle {
    margin: 0;
    font-size: 0.9rem;
    color: #6b7280;
}
</style>

