<template>
    <!-- Simple Input Trigger -->
    <div class="create-post-trigger" @click="showModal = true">
        <img 
            :src="getAvatarSrc(currentUser?.avatar, currentUser?.name)" 
            class="trigger-avatar" 
            alt="Avatar"
        />
        <div class="trigger-input">
            <span class="trigger-placeholder">{{ currentUser?.name ? `${currentUser.name} ơi, bạn đang nghĩ gì thế?` : 'Bạn đang nghĩ gì thế?' }}</span>
        </div>
        <div class="trigger-actions">
            <!-- <button class="trigger-action-btn video-btn" @click.stop="showModal = true" title="Live Video">
                <i class="bi bi-camera-video-fill"></i>
            </button> -->
            <button class="trigger-action-btn photo-btn" @click.stop="showModal = true" title="Photo/Video">
                <i class="bi bi-images"></i>
            </button>
            <!-- <button class="trigger-action-btn reel-btn" @click.stop="showModal = true" title="Reel">
                <i class="bi bi-play-circle-fill"></i>
            </button> -->
        </div>
    </div>

    <!-- Create Post Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
            <div class="create-post-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Tạo bài viết</h5>
                    <button class="modal-close-btn" @click="closeModal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="modal-user-info">
                        <img 
                            :src="getAvatarSrc(currentUser?.avatar, currentUser?.name)" 
                            class="modal-avatar" 
                            alt="Avatar"
                        />
                        <div class="user-details">
                            <div class="user-name">{{ currentUser?.name || 'User' }}</div>
                            <div class="privacy-selector">
                                <i class="bi bi-lock-fill"></i>
                                <span>Chỉ mình tôi</span>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        </div>
                    </div>

                    <div class="modal-content-area">
                        <div class="input-container">
                            <textarea 
                                v-model="content" 
                                :placeholder="currentUser?.name ? `${currentUser.name} ơi, bạn đang nghĩ gì thế?` : 'Bạn đang nghĩ gì thế?'" 
                                class="post-textarea"
                                :class="{ 'has-content': content.trim() }"
                                rows="8"
                                @input="adjustHeight"
                                ref="textareaRef"
                                name="post-content"
                                id="post-content"
                            ></textarea>
                            <button 
                                class="text-format-btn" 
                                :class="{ 'active': showColorPalette }"
                                @click="showColorPalette = !showColorPalette" 
                                title="Text formatting"
                            >
                                <span class="format-icon">Aa</span>
                            </button>
                            <button class="emoji-btn" title="Emoji">
                                <i class="bi bi-emoji-smile"></i>
                            </button>
                            <!-- Color Palette inside input -->
                            <div v-if="showColorPalette" class="color-palette">
                                <div 
                                    v-for="(color, index) in colorOptions" 
                                    :key="index"
                                    class="color-option"
                                    :class="{ 'selected': selectedColor === color }"
                                    :style="{ background: color }"
                                    @click="selectColor(color)"
                                ></div>
                            </div>
                        </div>

                        <!-- Preview selected images -->
                        <div v-if="selectedImages && selectedImages.length > 0" class="images-preview">
                            <div 
                                v-for="(image, index) in selectedImages" 
                                :key="`preview-${index}-${image}`"
                                class="image-preview-item"
                                :class="{ 'single-image': selectedImages.length === 1, 'multiple-images': selectedImages.length > 1 }"
                            >
                                <img 
                                    :src="image" 
                                    :alt="`Preview ${index + 1}`" 
                                    @error="handleImageError(index)"
                                    @load="console.log('Image loaded successfully:', image)"
                                />
                                <button class="remove-image-btn" @click="removeImage(index)" title="Xóa hình">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <div class="image-index">{{ index + 1 }}/{{ selectedImages.length }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <div class="action-label">Thêm vào bài viết của bạn</div>
                        <div class="action-buttons">
                            <button class="action-btn photo-action" @click="openImagePicker" title="Photo/Video">
                                <i class="bi bi-images"></i>
                            </button>
                            <button class="action-btn tag-action" title="Tag People">
                                <i class="bi bi-person-plus"></i>
                            </button>
                            <button class="action-btn messenger-action" title="Messenger Room">
                                <i class="bi bi-lightning-charge-fill"></i>
                            </button>
                            <button class="action-btn feeling-action" title="Feeling/Activity">
                                <i class="bi bi-emoji-smile"></i>
                            </button>
                            <button class="action-btn location-action" title="Location">
                                <i class="bi bi-geo-alt-fill"></i>
                            </button>
                            <button class="action-btn more-action" title="More">
                                <i class="bi bi-three-dots"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button 
                        @click="handlePost" 
                        :disabled="isSubmitting || !hasContent"
                        class="post-submit-btn"
                    >
                        <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                        {{ isSubmitting ? 'Đang đăng...' : 'Đăng' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Image Picker Modal -->
    <ImagePickerModal 
        :visible="showImagePicker" 
        @close="showImagePicker = false"
        @select="handleImageSelect"
    />
</template>

<script lang="ts" setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
import { showError } from '../../../../helper/alert';
import { useUserStore } from '../../../../state/userStore';
import { getAvatarSrc } from '../../../../helper/avatar';
import { notifications, notificationCount } from '../../../../state/notificationStore';
import ImagePickerModal from './ImagePickerModal.vue';

const emit = defineEmits(['postCreated']);
const userStore = useUserStore();
// @ts-expect-error - Pinia store type inference issue
const currentUser = computed(() => userStore.user);

const showModal = ref(false);
const content = ref('');
const isSubmitting = ref(false);
const textareaRef = ref<HTMLTextAreaElement | null>(null);
const selectedImages = ref<string[]>([]);
const showImagePicker = ref(false);
const selectedColor = ref<string | null>(null);
const showColorPalette = ref(false);

const colorOptions = [
    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', // Purple-pink
    '#e0e0e0', // Light grey
    '#ffffff', // White
    'linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%)', // Teal
    'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)', // Pink
    'linear-gradient(135deg, #f9ca24 0%, #f0932b 100%)', // Yellow
    'linear-gradient(135deg, #45b7d1 0%, #96c93d 100%)', // Blue-green
    'linear-gradient(135deg, #00b894 0%, #00cec9 100%)', // Teal
    'linear-gradient(135deg, #0984e3 0%, #74b9ff 100%)', // Blue
    'linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%)', // Purple
    'linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%)', // Pink-yellow
    'linear-gradient(135deg, #00b894 0%, #55efc4 100%)', // Green
    'linear-gradient(135deg, #e17055 0%, #d63031 100%)', // Red-orange
    'linear-gradient(135deg, #74b9ff 0%, #0984e3 100%)', // Blue
    'linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%)', // Purple
];

const isLightColor = (color: string): boolean => {
    if (!color) return false;
    
    if (color.toLowerCase() === '#ffffff' || color.toLowerCase() === '#fff' || color.toLowerCase() === 'white') {
        return true;
    }
    
    if (color.includes('gradient')) {
        if (color.includes('#ffffff') || color.includes('#fff') || color.includes('white')) {
            return true;
        }
        if (color.includes('#e0e0e0') || color.includes('#f0f0f0')) {
            return true;
        }
    }
    
    if (color.toLowerCase() === '#e0e0e0' || color.toLowerCase() === '#f0f0f0' || color.toLowerCase() === '#f5f5f5') {
        return true;
    }
    
    return false;
};

const selectColor = (color: string) => {
    selectedColor.value = color;
    showColorPalette.value = false; // Close palette after selection
    nextTick(() => {
        const container = textareaRef.value?.parentElement;
        if (container) {
            if (color.includes('gradient')) {
                container.style.background = color;
                container.style.backgroundImage = color;
            } else {
                container.style.background = color;
                container.style.removeProperty('background-image');
            }
        }
        if (textareaRef.value) {
            if (isLightColor(color)) {
                textareaRef.value.style.color = '#1c1e21';
            } else {
                textareaRef.value.style.color = '#fff';
            }
        }
    });
};

const hasContent = computed(() => {
    const hasText = content.value.trim().length > 0;
    const hasImages = selectedImages.value && selectedImages.value.length > 0;
    const result = hasText || hasImages;
    console.log('hasContent check:', { hasText, hasImages, result, imagesCount: selectedImages.value?.length });
    return result;
});

const adjustHeight = () => {
    if (textareaRef.value) {
        textareaRef.value.style.height = 'auto';
        const newHeight = Math.min(textareaRef.value.scrollHeight, 400);
        textareaRef.value.style.height = newHeight + 'px';
    }
};

const resetForm = async () => {
    content.value = '';
    selectedImages.value = [];
    selectedColor.value = null;
    showColorPalette.value = false;
    
    await nextTick();
    
    const container = textareaRef.value?.parentElement;
    if (container) {
        container.style.background = '#fff';
        container.style.removeProperty('background-image');
    }
    
    if (textareaRef.value) {
        textareaRef.value.value = '';
        textareaRef.value.style.height = 'auto';
        textareaRef.value.style.color = '#1c1e21';
        textareaRef.value.style.background = 'transparent';
        textareaRef.value.dispatchEvent(new Event('input', { bubbles: true }));
    }
    
    await nextTick();
};

const closeModal = () => {
    if (!isSubmitting.value) {
        showModal.value = false;
        resetForm();
    }
};

const openImagePicker = () => {
    showImagePicker.value = true;
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
};

const handleImageSelect = async (imageUrls: string[]) => {
    console.log('=== handleImageSelect START ===');
    console.log('Received imageUrls:', imageUrls);
    console.log('Type:', typeof imageUrls, 'IsArray:', Array.isArray(imageUrls));
    console.log('Current selectedImages before:', [...selectedImages.value]);
    
    if (imageUrls && Array.isArray(imageUrls) && imageUrls.length > 0) {
        // Merge với images đã chọn trước đó, loại bỏ duplicate
        const newImages = imageUrls.filter(url => url && !selectedImages.value.includes(url));
        console.log('New images to add (after filter):', newImages);
        
        if (newImages.length > 0) {
            selectedImages.value = [...selectedImages.value, ...newImages];
            console.log('Total images after merge:', selectedImages.value);
            console.log('selectedImages.value.length:', selectedImages.value.length);
            
            // Force reactivity bằng cách tạo array mới
            await nextTick();
            console.log('After nextTick - selectedImages:', selectedImages.value);
            console.log('Preview should show:', selectedImages.value.length > 0);
        } else {
            console.log('All images already selected, no new images to add');
        }
    } else {
        console.warn('handleImageSelect received invalid data:', imageUrls);
    }
    
    showImagePicker.value = false;
    document.body.style.overflow = '';
    console.log('=== handleImageSelect END ===');
};

const removeImage = (index: number) => {
    selectedImages.value.splice(index, 1);
    // Force reactivity update
    selectedImages.value = [...selectedImages.value];
};

const handleImageError = (index: number) => {
    console.error('Image load error at index:', index, selectedImages.value[index]);
    // Remove invalid image
    selectedImages.value.splice(index, 1);
    selectedImages.value = [...selectedImages.value];
};

const handlePost = async () => {
    if (!content.value.trim() && selectedImages.value.length === 0) return;
    if (isSubmitting.value) return;

    const postContent = content.value.trim();
    const postImages = [...selectedImages.value];
    
    isSubmitting.value = true;
    try {
        const res = await makeHttpReq<{ content: string; images: string[] | null }, { code?: number; data?: { post?: any }; message?: string }>('/posts', 'POST', {
            content: postContent,
            images: postImages.length > 0 ? postImages : null
        });
        
        const newPost = res.data?.post || (res as any).post;
        
        if (newPost) {
            emit('postCreated', newPost);
            await resetForm();
            
            // Add notification to bell
            const notificationMessage = res.message || 'Bài viết của bạn đã được đăng!';
            const newNotification = {
                id: `post-${Date.now()}`,
                message: notificationMessage,
                avatar: currentUser.value?.avatar || '',
                created_at: new Date().toISOString(),
                slug: '',
                project_id: '',
                read_at: null,
                invitation_id: null,
                sender_name: currentUser.value?.name || '',
                type: 'post_created'
            };
            notifications.value.unshift(newNotification);
            notificationCount.value = (notificationCount.value || 0) + 1;
            
            closeModal();
        } else {
            console.error('Post creation response missing post data:', res);
            showError('Không thể lấy thông tin bài viết mới. Vui lòng tải lại trang.');
        }
    } catch (error: any) {
        showError(error?.message || 'Không thể đăng bài. Vui lòng thử lại.');
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};

// Close modal on ESC key
const handleEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && showModal.value && !isSubmitting.value) {
        closeModal();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
});
</script>

<style scoped>
/* Trigger Input */
.create-post-trigger {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
    padding: 10px 14px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.create-post-trigger:hover {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.trigger-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.trigger-input {
    flex: 1;
    background: #f0f2f5;
    border-radius: 20px;
    padding: 10px 16px;
    cursor: pointer;
}

.trigger-placeholder {
    color: #65676b;
    font-size: 0.95rem;
}

.trigger-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.trigger-action-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.2s ease;
}

.video-btn {
    background: #f02849;
    color: #fff;
}

.photo-btn {
    background: #45bd62;
    color: #fff;
}

.reel-btn {
    background: #e41e3f;
    color: #fff;
}

.trigger-action-btn:hover {
    transform: scale(1.1);
    opacity: 0.9;
}

/* Modal */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

/* Ensure ImagePickerModal is above CreatePost modal */
:deep(.modal-overlay) {
    z-index: 1060 !important;
}

.create-post-modal {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #e4e6eb;
    position: relative;
}

.modal-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #050505;
    text-align: center;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
}

.modal-close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #65676b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.3rem;
    margin-left: auto;
    z-index: 1;
}

.modal-close-btn:hover {
    background: #f0f2f5;
}

.modal-body {
    padding: 12px 16px;
    overflow-y: auto;
    flex: 1;
}

.modal-user-info {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 12px;
}

.modal-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    color: #050505;
    margin-bottom: 4px;
    font-size: 0.95rem;
}

.privacy-selector {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: #f0f2f5;
    border-radius: 6px;
    font-size: 0.8125rem;
    color: #050505;
    cursor: pointer;
    width: fit-content;
    font-weight: 500;
}

.privacy-selector i:first-child {
    font-size: 0.75rem;
    color: #050505;
}

.privacy-selector i:last-child {
    font-size: 0.7rem;
    color: #65676b;
}

.modal-content-area {
    margin-bottom: 16px;
}

.input-container {
    position: relative;
    width: 100%;
    background: #fff;
    border-radius: 0;
    padding: 12px 12px 60px 12px;
    transition: all 0.2s ease;
    min-height: 200px;
    border: none;
}

.post-textarea {
    width: 100%;
    border: none;
    background: transparent;
    border-radius: 0;
    padding: 0 40px 0 0;
    resize: none;
    outline: none;
    font-size: 1rem;
    color: #1c1e21;
    min-height: 180px;
    max-height: 400px;
    transition: all 0.2s ease;
    font-family: inherit;
    line-height: 1.5;
    box-sizing: border-box;
}

.post-textarea::placeholder {
    color: #65676b;
}

.text-format-btn {
    position: absolute;
    left: 12px;
    bottom: 12px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #4facfe 100%);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 2;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.text-format-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.format-icon {
    color: #fff;
    font-weight: 700;
    font-size: 0.9rem;
    letter-spacing: -0.5px;
}

.emoji-btn {
    position: absolute;
    right: 12px;
    bottom: 12px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    border-radius: 50%;
    color: #65676b;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.2rem;
    z-index: 2;
}

.emoji-btn:hover {
    background: #f0f2f5;
    color: #1877f2;
}

.color-palette {
    display: flex;
    gap: 6px;
    padding: 8px 0 0 0;
    flex-wrap: wrap;
    border-top: 1px solid #e4e6eb;
    margin-top: 8px;
    animation: slideDown 0.2s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.color-option {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid #e4e6eb;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.color-option:hover {
    transform: scale(1.15);
    border-color: #1877f2;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.color-option.selected {
    border: 2.5px solid #1877f2;
    box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.15);
    transform: scale(1.15);
}

.images-preview {
    display: grid;
    gap: 8px;
    margin-top: 12px;
}

.images-preview:has(.single-image) {
    grid-template-columns: 1fr;
}

.images-preview:has(.multiple-images) {
    grid-template-columns: repeat(2, 1fr);
}

.image-preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e4e6e9;
    background: #f0f2f5;
}

.image-preview-item.single-image {
    aspect-ratio: 16/9;
    max-height: 400px;
}

.image-preview-item.multiple-images {
    aspect-ratio: 1;
}

.image-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.remove-image-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(0, 0, 0, 0.7);
    color: #fff;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    z-index: 2;
    backdrop-filter: blur(4px);
}

.remove-image-btn:hover {
    background: rgba(0, 0, 0, 0.9);
    transform: scale(1.1);
}

.image-index {
    position: absolute;
    bottom: 8px;
    left: 8px;
    background: rgba(0, 0, 0, 0.7);
    color: #fff;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    backdrop-filter: blur(4px);
}

.modal-actions {
    border-top: 1px solid #e4e6eb;
    padding-top: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.action-label {
    font-size: 0.9375rem;
    color: #050505;
    font-weight: 600;
    flex: 1;
}

.action-buttons {
    display: flex;
    gap: 4px;
}

.action-btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.3rem;
}

.photo-action {
    background: #45bd62;
    color: #fff;
}

.tag-action {
    background: #1877f2;
    color: #fff;
}

.messenger-action {
    background: #0084ff;
    color: #fff;
}

.feeling-action {
    background: #f7b928;
    color: #fff;
}

.location-action {
    background: #f02849;
    color: #fff;
}

.more-action {
    background: #e4e6eb;
    color: #65676b;
    font-size: 1.1rem;
}

.action-btn:hover {
    transform: scale(1.1);
    opacity: 0.9;
}

.modal-footer {
    padding: 10px 16px;
    border-top: 1px solid #e4e6eb;
}

.post-submit-btn {
    width: 100%;
    padding: 8px;
    background: #e4e6eb;
    color: #bcc0c4;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9375rem;
    cursor: not-allowed;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.post-submit-btn:not(:disabled) {
    background: #1877f2;
    color: #fff;
    cursor: pointer;
}

.post-submit-btn:hover:not(:disabled) {
    background: #166fe5;
}

.post-submit-btn:disabled {
    opacity: 1;
}

@media (max-width: 768px) {
    .create-post-modal {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
    
    .modal-backdrop {
        padding: 0;
    }

    .images-preview:has(.multiple-images) {
        grid-template-columns: 1fr;
    }
}
</style>
