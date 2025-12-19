<template>
    <div class="create-post-card">
        <div class="create-post-top">
            <img :src="getAvatarSrc(currentUser?.avatar, currentUser?.name)" class="user-avatar" alt="User Avatar">
            <div class="input-container">
                <textarea 
                    v-model="content" 
                    :placeholder="`Bạn đang nghĩ gì, ${currentUser?.name}?`" 
                    class="post-textarea"
                    rows="1"
                    @input="adjustHeight"
                    ref="textareaRef"
                ></textarea>
            </div>
        </div>

        <!-- Preview selected images -->
        <div v-if="selectedImages.length > 0" class="images-preview">
            <div 
                v-for="(image, index) in selectedImages" 
                :key="index"
                class="image-preview-item"
            >
                <img :src="image" :alt="`Preview ${index + 1}`" />
                <button class="remove-image-btn" @click="removeImage(index)">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="create-post-actions">
            <div class="action-item" @click="openImagePicker">
                <i class="bi bi-image-fill text-success"></i>
                <span>Ảnh/Video</span>
            </div>
            <div class="action-item">
                <i class="bi bi-person-plus-fill text-primary"></i>
                <span>Gắn thẻ</span>
            </div>
            <div class="action-item">
                <i class="bi bi-emoji-smile-fill text-warning"></i>
                <span>Cảm xúc</span>
            </div>
        </div>

        <button 
            @click="handlePost" 
            :disabled="isSubmitting || !content.trim()"
            class="post-btn"
        >
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
            {{ isSubmitting ? 'Đang đăng...' : 'Đăng bài' }}
        </button>

        <!-- Image Picker Modal -->
        <ImagePickerModal 
            :visible="showImagePicker" 
            @close="showImagePicker = false"
            @select="handleImageSelect"
        />
    </div>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
import { useUserStore } from '../../../../state/userStore';
import { getAvatarSrc } from '../../../../helper/avatar';
import { showSuccess, showError } from '../../../../helper/alert';
import ImagePickerModal from './ImagePickerModal.vue';

const emit = defineEmits(['postCreated']);
const userStore = useUserStore();
// @ts-expect-error - Pinia store type inference issue
const currentUser = computed(() => userStore.user);
const content = ref('');
const isSubmitting = ref(false);
const textareaRef = ref<HTMLTextAreaElement | null>(null);
const selectedImages = ref<string[]>([]);
const showImagePicker = ref(false);

const adjustHeight = () => {
    if (textareaRef.value) {
        textareaRef.value.style.height = 'auto';
        textareaRef.value.style.height = textareaRef.value.scrollHeight + 'px';
    }
};

const openImagePicker = () => {
    showImagePicker.value = true;
};

const handleImageSelect = (imageUrls: string[]) => {
    selectedImages.value = [...selectedImages.value, ...imageUrls];
    showImagePicker.value = false;
};

const removeImage = (index: number) => {
    selectedImages.value.splice(index, 1);
};

const handlePost = async () => {
    if (!content.value.trim()) return;

    isSubmitting.value = true;
    try {
        const res = await makeHttpReq<{ content: string; images: string[] | null }, { post?: any }>('/posts', 'POST', {
            content: content.value,
            images: selectedImages.value.length > 0 ? selectedImages.value : null
        });
        
        if (res.post) {
            content.value = '';
            selectedImages.value = [];
            if (textareaRef.value) textareaRef.value.style.height = 'auto';
            showSuccess('Bài viết của bạn đã được đăng!');
            emit('postCreated', res.post);
        }
    } catch (error) {
        showError('Không thể đăng bài. Vui lòng thử lại.');
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<style scoped>
.create-post-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    padding: 12px 16px 10px 16px;
    margin-bottom: 16px;
    width: 100%;
    display: flex;
    flex-direction: column;
}

.create-post-top {
    display: flex;
    gap: 8px;
    align-items: flex-start;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.input-container {
    flex: 1;
}

.post-textarea {
    width: 100%;
    border: none;
    background: #f0f2f5;
    border-radius: 20px;
    padding: 10px 16px;
    resize: none;
    outline: none;
    font-size: 1.05rem;
    color: #1c1e21;
    min-height: 40px;
    transition: background 0.2s;
}

.post-textarea:hover {
    background: #e4e6e9;
}

.divider {
    height: 1px;
    background: #e4e6e9;
    margin: 12px 0 8px 0;
}

.create-post-actions {
    display: flex;
    justify-content: space-around;
    padding-bottom: 8px;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
    flex: 1;
    justify-content: center;
}

.action-item:hover {
    background: #f2f2f2;
}

.action-item i {
    font-size: 1.3rem;
}

.action-item span {
    font-weight: 600;
    color: #65676b;
    font-size: 0.95rem;
}

.post-btn {
    background: #1877f2;
    color: #fff;
    border: none;
    padding: 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 1rem;
    margin-top: 4px;
    transition: filter 0.2s;
}

.post-btn:hover:not(:disabled) {
    filter: brightness(0.9);
}

.post-btn:disabled {
    background: #e4e6e9;
    color: #bcc0c4;
    cursor: not-allowed;
}

.images-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
    margin: 12px 0;
}

.image-preview-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e4e6e9;
}

.image-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.remove-image-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 0.75rem;
}

.remove-image-btn:hover {
    background: rgba(0, 0, 0, 0.8);
}

@media (max-width: 500px) {
    .action-item span {
        display: none;
    }
}
</style>

