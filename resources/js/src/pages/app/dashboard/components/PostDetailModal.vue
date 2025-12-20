<script lang="ts" setup>
import { ref, computed, watch, onMounted } from 'vue';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
import { getAvatarSrc } from '../../../../helper/avatar';
import { timeAgo } from '../../../../helper/utils';
import { showError } from '../../../../helper/alert';
import { useUserStore } from '../../../../state/userStore';

const props = defineProps<{
    visible: boolean;
    post: {
        id: number;
        content: string;
        images?: string[];
        image_url?: string;
        created_at: string;
        user: {
            id: number;
            name: string;
            avatar?: string;
        };
        likes_count?: number;
        comments_count?: number;
        likes?: Array<{
            id: number;
            user: {
                id: number;
                name: string;
                avatar?: string;
            };
        }>;
        comments?: Array<{
            id: number;
            content: string;
            created_at: string;
            user: {
                id: number;
                name: string;
                avatar?: string;
            };
        }>;
    } | null;
    startImageIndex?: number;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'postUpdated', payload: { postId: number; likes_count: number; likes: any[]; liked: boolean; reactionType: string | null }): void;
}>();

const userStore = useUserStore();
// @ts-expect-error - Pinia store type inference issue
const currentUser = computed(() => userStore.user);

const postData = ref<any>(null);
const isLoading = ref(false);
const isLiking = ref(false);
const isCommenting = ref(false);
const commentText = ref('');
const currentImageIndex = ref(0);
const commentInputRef = ref<HTMLTextAreaElement | null>(null);
const imageError = ref(false);
const imageLoading = ref(true);

const handleImageError = (e: Event) => {
    imageError.value = true;
    imageLoading.value = false;
    console.error('Failed to load image:', (e.target as HTMLImageElement).src);
};

const handleImageLoad = () => {
    imageError.value = false;
    imageLoading.value = false;
};

const postImages = computed(() => {
    if (!postData.value) return [];
    
    let images: string[] = [];
    
    // Check for images array first
    if (postData.value.images) {
        if (Array.isArray(postData.value.images)) {
            images = postData.value.images.filter((img: any) => img && typeof img === 'string' && img.trim() !== '');
        } else if (typeof postData.value.images === 'string') {
            // Handle case where images might be a JSON string
            try {
                const parsed = JSON.parse(postData.value.images);
                if (Array.isArray(parsed)) {
                    images = parsed.filter((img: any) => img && typeof img === 'string' && img.trim() !== '');
                }
            } catch (e) {
                // If parsing fails, treat as single image URL
                const imageStr = String(postData.value.images);
                if (imageStr.trim() !== '') {
                    images = [imageStr];
                }
            }
        }
    }
    
    // Fallback to image_url if no images found
    if (images.length === 0 && postData.value.image_url) {
        images = [postData.value.image_url];
    }
    
    return images;
});

const isLiked = computed(() => {
    if (!postData.value || !currentUser.value) return false;
    return postData.value.likes?.some((like: any) => like.user?.id === currentUser.value?.id) || false;
});

const currentUserReaction = computed<string | null>(() => {
    // Use local state for optimistic updates first
    if (localReactionType.value !== null) {
        return localReactionType.value;
    }
    
    if (!postData.value || !currentUser.value) return null;
    
    // Check user_reaction_type first
    if ((postData.value as any).user_reaction_type !== undefined && (postData.value as any).user_reaction_type !== null) {
        return (postData.value as any).user_reaction_type;
    }
    
    // Fallback: check likes array
    if (!postData.value.likes || !Array.isArray(postData.value.likes)) return null;
    const like = postData.value.likes.find((like: any) => like?.user?.id === currentUser.value?.id);
    if (!like) return null;
    return (like as any).type || 'like';
});

const reactionButtonEmoji = computed(() => {
    if (!currentUserReaction.value) return null;
    const meta = reactions.find((r) => r.type === currentUserReaction.value);
    return meta?.emoji ?? null;
});

const reactionTextClass = computed(() => {
    if (!currentUserReaction.value) return '';
    const map: Record<string, string> = {
        like: 'text-primary',
        love: 'text-danger',
        care: 'text-warning',
        haha: 'text-warning',
        sad: 'text-warning',
        angry: 'text-danger',
    };
    return map[currentUserReaction.value] || 'text-primary';
});

const reactionButtonText = computed(() => {
    if (!currentUserReaction.value) return 'Thích';
    const meta = reactions.find((r) => r.type === currentUserReaction.value);
    return meta?.label || 'Thích';
});

const showReactions = ref(false);
let hideReactionsTimeout: number | null = null;

// Local state for optimistic updates
const localReactionType = ref<string | null>(null);
const localLikesCount = ref<number | null>(null);
const localLikes = ref<any[] | null>(null);

const reactions = [
    { type: 'like', label: 'Thích', emoji: '👍' },
    { type: 'love', label: 'Yêu thích', emoji: '❤️' },
    { type: 'care', label: 'Thương thương', emoji: '🥰' },
    { type: 'haha', label: 'Haha', emoji: '😂' },
    { type: 'sad', label: 'Buồn', emoji: '😢' },
    { type: 'angry', label: 'Phẫn nộ', emoji: '😡' },
];

const openReactions = () => {
    if (hideReactionsTimeout) {
        clearTimeout(hideReactionsTimeout);
        hideReactionsTimeout = null;
    }
    showReactions.value = true;
};

const scheduleHideReactions = () => {
    if (hideReactionsTimeout) {
        clearTimeout(hideReactionsTimeout);
    }
    hideReactionsTimeout = window.setTimeout(() => {
        showReactions.value = false;
    }, 200);
};

const postTimeAgo = computed(() => {
    if (!postData.value) return '';
    return timeAgo(postData.value.created_at);
});

const likesText = computed(() => {
    // Use local state for optimistic updates first
    const count = localLikesCount.value !== null ? localLikesCount.value : (postData.value?.likes_count || 0);
    const likes = localLikes.value !== null ? localLikes.value : postData.value?.likes;
    
    if (!count) return '';
    
    // If only 1 like and it's from current user, show "Bạn"
    if (count === 1 && currentUser.value && likes && likes.some((like: any) => like.user?.id === currentUser.value?.id)) {
        return 'Bạn';
    }
    
    if (count === 1) return '1 lượt thích';
    return `${count} lượt thích`;
});

const isSingleLikeByCurrentUser = computed(() => {
    const count = localLikesCount.value !== null ? localLikesCount.value : (postData.value?.likes_count || 0);
    const likes = localLikes.value !== null ? localLikes.value : postData.value?.likes;
    
    return (
        count === 1 &&
        !!currentUser.value &&
        !!likes &&
        Array.isArray(likes) &&
        likes.some((like: any) => like.user?.id === currentUser.value?.id)
    );
});

const commentsText = computed(() => {
    if (!postData.value || !postData.value.comments_count) return '';
    const count = postData.value.comments_count;
    if (count === 1) return '1 bình luận';
    return `${count} bình luận`;
});

watch(() => props.visible, async (newVal) => {
    if (newVal && props.post) {
        currentImageIndex.value = props.startImageIndex || 0;
        imageError.value = false;
        imageLoading.value = true;
        await fetchPostDetail();
    }
});

watch(() => currentImageIndex.value, () => {
    imageError.value = false;
    imageLoading.value = true;
});

watch(() => props.post, async (newVal) => {
    if (newVal && props.visible) {
        // NEVER update postData during optimistic update process
        // This prevents UI from "jumping" back to old state
        if (isLiking.value) {
            return; // Ignore all updates while processing reaction
        }
        
        // Only sync if we don't have local state (no ongoing optimistic update)
        if (localReactionType.value !== null || localLikesCount.value !== null) {
            return; // Ignore updates during optimistic update phase
        }
        
        const postAny = newVal as any;
        if (postAny.user_reaction_type !== undefined || newVal.likes_count !== undefined) {
            if (postData.value && postData.value.id === newVal.id) {
                // Only update if values are different
                if ((postData.value as any).user_reaction_type !== postAny.user_reaction_type) {
                    (postData.value as any).user_reaction_type = postAny.user_reaction_type;
                }
                if (postData.value.likes_count !== newVal.likes_count) {
                    postData.value.likes_count = newVal.likes_count;
                    postData.value.likes = newVal.likes;
                }
            }
        }
        
        // Only fetch if this is a different post or initial load
        if (!postData.value || postData.value.id !== newVal.id) {
            await fetchPostDetail();
        }
    }
});

const fetchPostDetail = async () => {
    if (!props.post) return;
    
    isLoading.value = true;
    try {
        const res = await makeHttpReq<never, any>(`/posts/${props.post.id}`, 'GET');
        // Handle response structure: res might be { data: {...} } or direct post object
        postData.value = res.data || res;
    } catch (error: any) {
        showError(error?.message || 'Không thể tải chi tiết bài viết');
    } finally {
        isLoading.value = false;
    }
};

const handleLike = async () => {
    await sendReaction('like');
};

const sendReaction = async (reactionType: string) => {
    if (!postData.value || isLiking.value) return;
    
    // Optimistic update: update local state immediately
    const currentReaction = currentUserReaction.value;
    const currentCount = postData.value.likes_count || 0;
    const currentLikes = postData.value.likes || [];
    
    // Determine if this is a toggle (same reaction) or change
    const isToggling = currentReaction === reactionType;
    
    if (isToggling) {
        // Toggling off
        localReactionType.value = null;
        localLikesCount.value = Math.max(0, currentCount - 1);
        // Remove current user's like from local likes
        localLikes.value = currentLikes.filter((like: any) => like?.user?.id !== currentUser.value?.id);
    } else {
        // Changing reaction or adding new
        localReactionType.value = reactionType;
        if (currentReaction) {
            // Changing reaction, count stays same
            localLikesCount.value = currentCount;
        } else {
            // Adding new reaction
            localLikesCount.value = currentCount + 1;
        }
        // Update local likes array
        const otherLikes = currentLikes.filter((like: any) => like?.user?.id !== currentUser.value?.id);
        localLikes.value = [...otherLikes, {
            id: Date.now(), // Temporary ID
            user: currentUser.value,
            type: reactionType
        }];
    }
    
    isLiking.value = true;
    try {
        const res = await makeHttpReq<{ type: string }, { liked: boolean; likes_count: number; likes: any[]; type?: string | null }>(
            `/posts/${postData.value.id}/like`,
            'POST',
            { type: reactionType }
        );
        const resp = (res as any)?.data ? (res as any).data : res;
        const reactionTypeFinal = resp.liked ? (resp.type || reactionType) : null;
        
        // Update local state with server response IMMEDIATELY
        localReactionType.value = reactionTypeFinal;
        localLikesCount.value = resp.likes_count;
        localLikes.value = resp.likes;
        
        // Update postData with server response
        if (postData.value) {
            postData.value.liked = resp.liked;
            postData.value.likes_count = resp.likes_count;
            postData.value.likes = resp.likes;
            (postData.value as any).user_reaction_type = reactionTypeFinal;
        }

        // Emit update to parent
        emit('postUpdated', {
            postId: postData.value.id,
            liked: resp.liked,
            likes_count: resp.likes_count,
            likes: resp.likes,
            reactionType: reactionTypeFinal,
        });
        
        // Clear local state after a delay to allow props to update
        // This ensures smooth transition from local state to props
        // Use longer delay (500ms) to prevent UI "jumping" when changing reactions
        setTimeout(() => {
            localReactionType.value = null;
            localLikesCount.value = null;
            localLikes.value = null;
        }, 500);
    } catch (error: any) {
        // Revert optimistic update on error
        localReactionType.value = null;
        localLikesCount.value = null;
        localLikes.value = null;
        showError(error?.message || 'Không thể thích bài viết');
    } finally {
        isLiking.value = false;
    }
};

const handleSelectReaction = async (reactionType: string) => {
    // Close reaction bar immediately for better UX
    showReactions.value = false;
    if (hideReactionsTimeout) {
        clearTimeout(hideReactionsTimeout);
        hideReactionsTimeout = null;
    }
    // Send reaction
    await sendReaction(reactionType);
};

const handleComment = async () => {
    if (!postData.value || !commentText.value.trim() || isCommenting.value) return;
    
    isCommenting.value = true;
    try {
        const res = await makeHttpReq<{ content: string }, { comment: any; comments_count: number }>(
            `/posts/${postData.value.id}/comment`,
            'POST',
            { content: commentText.value.trim() }
        );
        
        if (postData.value) {
            if (!postData.value.comments) {
                postData.value.comments = [];
            }
            postData.value.comments.push(res.comment);
            postData.value.comments_count = res.comments_count;
        }
        
        commentText.value = '';

        // Thông báo cho list để cập nhật lại likes/reaction (nếu cần)
        if (postData.value) {
            emit('postUpdated', {
                postId: postData.value.id,
                liked: isLiked.value,
                likes_count: postData.value.likes_count ?? 0,
                likes: postData.value.likes ?? [],
                reactionType: null,
            });
        }
        
        // Focus lại input sau khi comment
        setTimeout(() => {
            if (commentInputRef.value) {
                commentInputRef.value.focus();
            }
        }, 100);
    } catch (error: any) {
        showError(error?.message || 'Không thể thêm bình luận');
    } finally {
        isCommenting.value = false;
    }
};

const nextImage = () => {
    if (currentImageIndex.value < postImages.value.length - 1) {
        currentImageIndex.value++;
    }
};

const prevImage = () => {
    if (currentImageIndex.value > 0) {
        currentImageIndex.value--;
    }
};

const closeModal = () => {
    emit('close');
    commentText.value = '';
    currentImageIndex.value = 0;
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
        closeModal();
    }
};

onMounted(() => {
    if (props.visible && props.post) {
        fetchPostDetail();
    }
    window.addEventListener('keydown', handleKeyDown);
});

// Cleanup
import { onUnmounted } from 'vue';
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <div v-if="visible" class="modal-overlay" @click.self="closeModal">
        <div class="modal-container" @click.stop>
            <button class="close-btn" @click="closeModal">
                <i class="bi bi-x-lg"></i>
            </button>

            <div v-if="isLoading" class="loading-container">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div v-else-if="postData" class="modal-content">
                <!-- Content Section (Left) -->
                <div class="content-section">
                    <!-- Header -->
                    <div class="post-header">
                        <div class="header-left">
                            <img 
                                :src="getAvatarSrc(postData.user?.avatar, postData.user?.name)" 
                                class="user-avatar" 
                                alt="Avatar"
                            />
                            <div class="user-info">
                                <h4 class="user-name">{{ postData.user?.name }}</h4>
                                <div class="post-meta">
                                    <span class="post-time">{{ postTimeAgo }}</span>
                                    <span class="dot">·</span>
                                    <i class="bi bi-globe-americas"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Post Content -->
                    <div class="post-content-wrapper">
                        <div class="post-content">
                            <p>{{ postData.content }}</p>
                        </div>

                        <!-- Comments List -->
                        <div class="comments-section">
                            <div v-if="postData.comments && postData.comments.length > 0" class="comments-list">
                                <div 
                                    v-for="comment in postData.comments" 
                                    :key="comment.id"
                                    class="comment-item"
                                >
                                    <img 
                                        :src="getAvatarSrc(comment.user?.avatar, comment.user?.name)" 
                                        class="comment-avatar" 
                                        alt="Avatar"
                                    />
                                    <div class="comment-content">
                                        <div class="comment-bubble">
                                            <span class="comment-author">{{ comment.user?.name }}</span>
                                            <span class="comment-text">{{ comment.content }}</span>
                                        </div>
                                        <div class="comment-meta">
                                            <span class="comment-time">{{ timeAgo(comment.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="no-comments">
                                <p>Chưa có bình luận nào</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="post-stats">
                        <div class="stats-left" v-if="postData.likes_count > 0">
                            <template v-if="isSingleLikeByCurrentUser && currentUser">
                                <img
                                    :src="getAvatarSrc(currentUser.avatar, currentUser.name)"
                                    alt="Avatar"
                                    class="like-avatar"
                                >
                            </template>
                            <template v-else>
                                <span class="like-icons">
                                    <i class="bi bi-hand-thumbs-up-fill text-primary"></i>
                                </span>
                            </template>
                            <span class="stats-text">{{ likesText }}</span>
                        </div>
                        <div class="stats-right">
                            <span class="stats-text">{{ commentsText }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="post-actions">
                        <div 
                            class="like-action" 
                            @mouseenter="openReactions" 
                            @mouseleave="scheduleHideReactions"
                        >
                            <button 
                                class="action-btn"
                                :class="['action-btn', { active: isLiked }, reactionTextClass]"
                                @click="handleLike"
                                :disabled="isLiking"
                            >
                                <span v-if="reactionButtonEmoji" class="reaction-emoji main-reaction-emoji">
                                    {{ reactionButtonEmoji }}
                                </span>
                                <i v-else :class="isLiked ? 'bi bi-hand-thumbs-up-fill' : 'bi bi-hand-thumbs-up'"></i>
                                <span>{{ reactionButtonText }}</span>
                            </button>

                            <transition name="fade">
                                <div 
                                    v-if="showReactions" 
                                    class="reaction-bar"
                                    @mouseenter="openReactions"
                                    @mouseleave="scheduleHideReactions"
                                >
                                    <button 
                                        v-for="reaction in reactions" 
                                        :key="reaction.type"
                                        class="reaction-item"
                                        @click.stop="handleSelectReaction(reaction.type)"
                                        :title="reaction.label"
                                    >
                                        <span class="reaction-emoji">{{ reaction.emoji }}</span>
                                    </button>
                                </div>
                            </transition>
                        </div>

                        <button class="action-btn" @click="commentInputRef?.focus()">
                            <i class="bi bi-chat"></i>
                            <span>Bình luận</span>
                        </button>
                        <button class="action-btn">
                            <i class="bi bi-share"></i>
                            <span>Chia sẻ</span>
                        </button>
                    </div>

                    <!-- Comment Input -->
                    <div class="comment-input-section">
                        <img 
                            :src="getAvatarSrc(currentUser?.avatar, currentUser?.name)" 
                            class="comment-input-avatar" 
                            alt="Avatar"
                        />
                        <div class="comment-input-wrapper">
                            <textarea
                                ref="commentInputRef"
                                v-model="commentText"
                                class="comment-input"
                                placeholder="Viết bình luận..."
                                rows="1"
                                @keydown.enter.exact.prevent="handleComment"
                                @keydown.shift.enter.exact.prevent
                            ></textarea>
                            <button 
                                class="comment-submit-btn"
                                @click="handleComment"
                                :disabled="!commentText.trim() || isCommenting"
                            >
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Image Section (Right) -->
                <div class="image-section" @click.stop>
                    <div v-if="postImages.length > 0" class="image-viewer">
                        <div v-if="imageLoading && !imageError" class="image-loading">
                            <div class="spinner-border text-light" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div v-else-if="imageError" class="image-error">
                            <i class="bi bi-image"></i>
                            <p>Không thể tải hình ảnh</p>
                        </div>
                        <img 
                            v-show="!imageError"
                            :src="postImages[currentImageIndex]" 
                            :alt="`Post image ${currentImageIndex + 1}`"
                            class="main-image"
                            @error="handleImageError"
                            @load="handleImageLoad"
                        />
                        
                        <!-- Navigation arrows -->
                        <button 
                            v-if="postImages.length > 1"
                            class="nav-arrow nav-arrow-left"
                            :class="{ 'nav-arrow-disabled': currentImageIndex === 0 }"
                            @click.stop="prevImage"
                        >
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button 
                            v-if="postImages.length > 1"
                            class="nav-arrow nav-arrow-right"
                            :class="{ 'nav-arrow-disabled': currentImageIndex === postImages.length - 1 }"
                            @click.stop="nextImage"
                        >
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        
                        <!-- Image indicators -->
                        <div v-if="postImages.length > 1" class="image-indicators">
                            <span 
                                v-for="(img, index) in postImages" 
                                :key="index"
                                class="indicator"
                                :class="{ active: index === currentImageIndex }"
                                @click.stop="currentImageIndex = index"
                            ></span>
                        </div>
                    </div>
                    <div v-else class="no-image">
                        <i class="bi bi-image"></i>
                    </div>
                </div>
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
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
    backdrop-filter: blur(4px);
}

.modal-container {
    position: relative;
    background: #18191a;
    border-radius: 12px;
    width: 100%;
    max-width: 1400px;
    max-height: 90vh;
    display: flex;
    overflow: hidden;
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.6);
    border: 1px solid #3a3b3c;
}

.close-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(58, 59, 60, 0.9);
    color: #e4e6eb;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10001;
    transition: all 0.2s;
    font-size: 1.1rem;
    backdrop-filter: blur(10px);
}

.close-btn:hover {
    background: rgba(58, 59, 60, 1);
    transform: scale(1.1);
}

.loading-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 500px;
}

.modal-content {
    display: grid;
    grid-template-columns: minmax(420px, 520px) 1fr;
    width: 100%;
    height: 100%;
    min-height: 520px;
}

.image-section {
    min-width: 0;
    height: 100%;
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.image-viewer {
    width: 100%;
    height: 100%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.main-image {
    max-width: 100%;
    /* Giới hạn theo chiều cao viewport để luôn thấy trọn hình,
       trừ đi header + viền + padding khoảng 160px */
    max-height: calc(100vh - 160px);
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
    user-select: none;
    -webkit-user-drag: none;
    margin: auto;
}

.image-loading,
.image-error {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #65676b;
    gap: 16px;
}

.image-loading {
    position: absolute;
    top: 0;
    left: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1;
}

.image-error {
    font-size: 3rem;
}

.image-error p {
    margin: 0;
    font-size: 1rem;
    color: #b0b3b8;
}

.no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #65676b;
    font-size: 4rem;
}

.nav-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(58, 59, 60, 0.9);
    border: none;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10002;
    transition: all 0.2s;
    color: #e4e6eb;
    font-size: 1.4rem;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    pointer-events: auto;
}

.nav-arrow:hover:not(.nav-arrow-disabled) {
    background: rgba(58, 59, 60, 1);
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.nav-arrow-disabled {
    opacity: 0.35;
    cursor: default;
    pointer-events: none;
    transform: translateY(-50%);
    box-shadow: none;
}

.nav-arrow-disabled:hover {
    transform: translateY(-50%);
    box-shadow: none;
    background: rgba(58, 59, 60, 0.9);
}

.nav-arrow-left {
    left: 20px;
}

.nav-arrow-right {
    right: 20px;
}

.image-indicators {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 10002;
    background: rgba(0, 0, 0, 0.5);
    padding: 8px 12px;
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    transition: all 0.2s;
}

.indicator:hover {
    background: rgba(255, 255, 255, 0.6);
    transform: scale(1.2);
}

.indicator.active {
    background: #2d88ff;
    width: 10px;
    height: 10px;
}

.content-section {
    min-width: 420px;
    max-width: 560px;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
    background: #242526;
    border-right: 2px solid #3a3b3c;
    position: relative;
}

.content-section::after {
    content: '';
    position: absolute;
    right: -1px;
    top: 0;
    bottom: 0;
    width: 1px;
    background: rgba(58, 59, 60, 0.5);
    pointer-events: none;
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #3a3b3c;
    flex-shrink: 0;
    background: #242526;
}

.header-left {
    display: flex;
    gap: 10px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #e4e6eb;
    line-height: 1.2;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8rem;
    color: #b0b3b8;
}

.dot {
    font-weight: bold;
}

.post-content-wrapper {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    min-height: 0;
}

/* Custom scrollbar for content section */
.post-content-wrapper::-webkit-scrollbar {
    width: 8px;
}

.post-content-wrapper::-webkit-scrollbar-track {
    background: transparent;
}

.post-content-wrapper::-webkit-scrollbar-thumb {
    background: #3a3b3c;
    border-radius: 4px;
}

.post-content-wrapper::-webkit-scrollbar-thumb:hover {
    background: #4e4f50;
}

.post-content {
    margin-bottom: 20px;
    font-size: 1rem;
    line-height: 1.5;
    color: #e4e6eb;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.post-content p {
    margin: 0;
}

.comments-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #3a3b3c;
}

.comments-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding-bottom: 8px;
}

.comment-item {
    display: flex;
    gap: 8px;
}

.comment-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.comment-content {
    flex: 1;
}

.comment-bubble {
    background: #3a3b3c;
    border-radius: 18px;
    padding: 8px 12px;
    display: inline-block;
    max-width: 100%;
}

.comment-author {
    font-weight: 600;
    color: #e4e6eb;
    margin-right: 6px;
    font-size: 0.9rem;
}

.comment-text {
    color: #e4e6eb;
    font-size: 0.9rem;
    word-wrap: break-word;
}

.comment-meta {
    margin-top: 4px;
    padding-left: 12px;
}

.comment-time {
    font-size: 0.75rem;
    color: #b0b3b8;
}

.no-comments {
    text-align: center;
    padding: 40px 20px;
    color: #b0b3b8;
}

.post-stats {
    display: flex;
    justify-content: space-between;
    padding: 12px 20px;
    border-top: 1px solid #3a3b3c;
    border-bottom: 1px solid #3a3b3c;
    flex-shrink: 0;
    background: #242526;
}

.stats-left, .stats-right {
    display: flex;
    align-items: center;
}

.like-icons {
    background: #2d88ff;
    color: white;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    margin-right: 6px;
}

.like-avatar {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 6px;
}

.stats-text {
    font-size: 0.9rem;
    color: #b0b3b8;
}

.post-actions {
    display: flex;
    padding: 4px 12px;
    gap: 8px;
    flex-shrink: 0;
    background: #242526;
}

.like-action {
    position: relative;
    flex: 1;
}

.action-btn {
    flex: 1;
    background: none;
    border: none;
    color: #b0b3b8;
    font-weight: 600;
    padding: 8px;
    border-radius: 4px;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
}

.action-btn:hover {
    background: #3a3b3c;
}

.action-btn.active {
    color: #2d88ff;
}

.action-btn i {
    font-size: 1.2rem;
}

.action-btn span {
    font-size: 0.9rem;
}

.action-btn .main-reaction-emoji {
    font-size: 1.4rem;
}

.reaction-bar {
    position: absolute;
    bottom: 40px;
    left: 0;
    display: flex;
    gap: 4px;
    padding: 6px 8px;
    background: rgba(36, 37, 38, 0.97);
    border-radius: 999px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    z-index: 20;
}

.reaction-item {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 22px;
    line-height: 1;
    transition: transform 0.12s ease-out;
}

.reaction-item:hover {
    transform: translateY(-3px) scale(1.25);
}

.reaction-emoji {
    display: block;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease-out, transform 0.15s ease-out;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
}

.comment-input-section {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-top: 1px solid #3a3b3c;
    flex-shrink: 0;
    background: #242526;
}

.comment-input-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    align-self: flex-start;
    margin-top: 2px;
}

.comment-input-wrapper {
    flex: 1;
    display: flex;
    gap: 8px;
    align-items: center;
    background: #3a3b3c;
    border-radius: 24px;
    padding: 8px 16px;
    transition: background 0.2s;
    min-height: 40px;
}

.comment-input-wrapper:focus-within {
    background: #4e4f50;
}

.comment-input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    resize: none;
    font-size: 0.9rem;
    color: #e4e6eb;
    max-height: 100px;
    font-family: inherit;
    line-height: 1.5;
    padding: 0;
    margin: 0;
    vertical-align: middle;
}

.comment-input::placeholder {
    color: #b0b3b8;
}

.comment-submit-btn {
    background: none;
    border: none;
    color: #2d88ff;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.2s;
    flex-shrink: 0;
    align-self: flex-end;
    margin-bottom: 2px;
}

.comment-submit-btn:hover:not(:disabled) {
    opacity: 0.7;
}

.comment-submit-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.comment-submit-btn i {
    font-size: 1.1rem;
}

@media (max-width: 1024px) {
    .modal-content {
        grid-template-columns: minmax(380px, 460px) 1fr;
    }

    .content-section {
        min-width: 380px;
    }
}

@media (max-width: 768px) {
    .modal-container {
        flex-direction: column-reverse;
        max-height: 100vh;
        height: 100vh;
        border-radius: 0;
        border: none;
    }

    .modal-content {
        display: flex;
        flex-direction: column-reverse;
    }

    .content-section {
        flex: 0 0 50vh;
        min-width: 100%;
        min-height: 50vh;
        border-right: none;
        border-top: 2px solid #3a3b3c;
    }

    .content-section::after {
        display: none;
    }

    .image-section {
        flex: 1;
        min-height: 0;
    }

    .post-header,
    .post-stats,
    .comment-input-section {
        padding-left: 16px;
        padding-right: 16px;
    }

    .post-content-wrapper {
        padding: 16px;
    }
}
</style>

