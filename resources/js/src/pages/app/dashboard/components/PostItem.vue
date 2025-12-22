<script lang="ts" setup>
import { computed, ref } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';
import { timeAgo } from '../../../../helper/utils';
import { useUserStore } from '../../../../state/userStore';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
import { showError } from '../../../../helper/alert';
import PostDetailModal from './PostDetailModal.vue';

const props = defineProps<{
    post: {
        id: number;
        content: string;
        image_url?: string;
        images?: string[];
        created_at: string;
        user: {
            id: number;
            name: string;
            avatar?: string;
        };
        likes_count?: number;
        comments_count?: number;
        share_count?: number;
        user_reaction_type?: string | null;
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
            replies?: Array<{
                id: number;
                content: string;
                created_at: string;
                user: {
                    id: number;
                    name: string;
                    avatar?: string;
                };
            }>;
        }>;
    }
}>();

const emit = defineEmits<{
    (e: 'postUpdated', payload: { postId: number; likes_count: number; likes: any[]; liked: boolean; reactionType: string | null }): void;
}>();

const showDetailModal = ref(false);
const startImageIndex = ref(0);
const showShareModal = ref(false);
const hasCopiedShareLink = ref(false);
const shareLinkInputRef = ref<HTMLInputElement | null>(null);
const showComments = ref(false); // Toggle inline comments display

const userStore = useUserStore();
// @ts-expect-error - Pinia store type inference issue
const currentUser = computed(() => userStore.user);

const postTimeAgo = computed(() => {
    return timeAgo(props.post.created_at);
});

const postImages = computed(() => {
    // Support both old image_url and new images array
    let images: string[] = [];
    
    // Check for images array first
    if (props.post.images) {
        if (Array.isArray(props.post.images)) {
            images = props.post.images.filter(img => img && typeof img === 'string' && img.trim() !== '');
        } else if (typeof props.post.images === 'string') {
            // Handle case where images might be a JSON string
            try {
                const parsed = JSON.parse(props.post.images);
                if (Array.isArray(parsed)) {
                    images = parsed.filter(img => img && typeof img === 'string' && img.trim() !== '');
                }
            } catch (e) {
                // If parsing fails, treat as single image URL
                const imageStr = String(props.post.images);
                if (imageStr.trim() !== '') {
                    images = [imageStr];
                }
            }
        }
    }
    
    // Fallback to image_url if no images found
    if (images.length === 0 && props.post.image_url) {
        images = [props.post.image_url];
    }
    
    return images;
});

const likesLabel = computed(() => {
    // Use local state for optimistic updates first
    const count = localLikesCount.value !== null ? localLikesCount.value : (props.post.likes_count || 0);
    const likes = localLikes.value !== null ? localLikes.value : props.post.likes;
    
    if (count === 1 && currentUser.value && likes && likes.some((like: any) => like.user?.id === currentUser.value.id)) {
        return 'Bạn';
    }
    return `${count} người`;
});

const isSingleLikeByCurrentUser = computed(() => {
    const count = props.post.likes_count || 0;
    return (
        count === 1 &&
        !!currentUser.value &&
        !!props.post.likes &&
        props.post.likes.some((like) => like.user?.id === currentUser.value.id)
    );
});

const currentUserReaction = computed<string | null>(() => {
    // Use local state for optimistic updates first
    if (localReactionType.value !== null) {
        return localReactionType.value;
    }
    
    // Then check props
    if (props.post.user_reaction_type !== undefined && props.post.user_reaction_type !== null) {
        return props.post.user_reaction_type;
    }
    
    // Fallback: check likes array for current user's reaction
    if (!currentUser.value || !props.post.likes || !Array.isArray(props.post.likes)) {
        return null;
    }
    
    const like = props.post.likes.find((like: any) => like?.user?.id === currentUser.value?.id);
    if (!like) return null;
    
    // Return the reaction type, default to 'like' if type is not specified
    return (like as any).type || 'like';
});

const isReacted = computed(() => !!currentUserReaction.value);

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

// Don't watch props to reset local state
// Local state will be cleared after server response is received and props are updated

const openImageGallery = (index: number) => {
    startImageIndex.value = index;
    showDetailModal.value = true;
};

const openComments = () => {
    // Toggle inline comments OR open modal
    // If post has no comments, open modal to allow adding first comment
    if (props.post.comments && props.post.comments.length > 0) {
        showComments.value = !showComments.value;
    } else {
        // No comments yet, open modal to add first comment
        startImageIndex.value = 0;
        showDetailModal.value = true;
    }
};

const shareLink = computed(() => {
    return `${window.location.origin}/app/dashboard?postId=${props.post.id}`;
});

const openShareModal = async () => {
    try {
        // Tăng share_count nhưng không chặn UI nếu lỗi
        makeHttpReq<never, { share_count: number }>(`/posts/${props.post.id}/share`, 'POST').catch(() => {});
    } catch {
        // ignore
    }
    hasCopiedShareLink.value = false;
    showShareModal.value = true;
};

const copyShareLink = async () => {
    const url = shareLink.value;
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(url);
            // Copy thành công: đổi text nút thành "Đã copy"
            hasCopiedShareLink.value = true;
        } else {
            // Trình duyệt không hỗ trợ clipboard API (HTTP,...)
            // -> chọn sẵn text trong input để user tự Ctrl+C
            if (shareLinkInputRef.value) {
                shareLinkInputRef.value.focus();
                shareLinkInputRef.value.select();
            }
            hasCopiedShareLink.value = true;
        }
    } catch {
        // Nếu lỗi, vẫn chỉ chọn text để user tự copy, không bật prompt
        if (shareLinkInputRef.value) {
            shareLinkInputRef.value.focus();
            shareLinkInputRef.value.select();
        }
        hasCopiedShareLink.value = true;
    }
};

const handlePostUpdated = (payload: { postId: number; likes_count: number; likes: any[]; liked: boolean; reactionType: string | null }) => {
    emit('postUpdated', payload);
};

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

const sendReaction = async (reactionType: string) => {
    // Optimistic update: update UI immediately
    const currentReaction = currentUserReaction.value;
    const currentCount = props.post.likes_count || 0;
    const currentLikes = props.post.likes || [];
    
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
    
    try {
        const res = await makeHttpReq<{ type: string }, { liked: boolean; type?: string | null; likes_count: number; likes: any[] }>(
            `/posts/${props.post.id}/like`,
            'POST',
            { type: reactionType }
        );
        const resp = (res as any)?.data ? (res as any).data : res;
        const finalType = resp.liked ? (resp.type || reactionType) : null;
        
        // Update local state with server response IMMEDIATELY
        localReactionType.value = finalType;
        localLikesCount.value = resp.likes_count;
        localLikes.value = resp.likes;
        
        // Emit update to parent
        emit('postUpdated', {
            postId: props.post.id,
            liked: resp.liked,
            likes_count: resp.likes_count,
            likes: resp.likes,
            reactionType: finalType,
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
        showError(error?.message || 'Không thể gửi tương tác');
    }
};

const handleLikeClick = async () => {
    await sendReaction('like');
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
</script>

<template>
    <div class="post-item" :data-post-id="post.id">
        <div class="post-header">
            <div class="header-left">
                <img :src="getAvatarSrc(post.user.avatar, post.user.name)" class="user-avatar" alt="Avatar">
                <div class="user-info">
                    <h4 class="user-name">{{ post.user.name }}</h4>
                    <div class="post-meta">
                        <span class="post-time">{{ postTimeAgo }}</span>
                        <span class="dot">·</span>
                        <i class="bi bi-globe-americas"></i>
                    </div>
                </div>
            </div>
            <button class="more-btn">
                <i class="bi bi-three-dots"></i>
            </button>
        </div>

        <div class="post-content">
            <p>{{ post.content }}</p>
            <div v-if="postImages.length > 0" class="post-images-container" :class="`images-count-${postImages.length}`">
                <div 
                    v-for="(image, index) in postImages" 
                    :key="index"
                    class="post-image-item"
                    @click="openImageGallery(index)"
                >
                    <img :src="image" :alt="`Post image ${index + 1}`" />
                    <div v-if="postImages.length > 1 && index === 0 && postImages.length > 4" class="more-images-overlay">
                        +{{ postImages.length - 4 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="post-stats" v-if="(post.likes_count || 0) > 0 || (post.comments_count || 0) > 0 || (post.share_count || 0) > 0">
            <div class="stats-left" v-if="(post.likes_count || 0) > 0">
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
                <span class="stats-text">{{ likesLabel }}</span>
            </div>
            <div class="stats-right" v-if="(post.comments_count || 0) > 0 || (post.share_count || 0) > 0">
                <span v-if="(post.comments_count || 0) > 0" class="stats-text">{{ post.comments_count }} Bình luận</span>
                <span v-if="(post.share_count || 0) > 0" class="stats-text">{{ post.share_count }} Chia sẻ</span>
            </div>
        </div>

        <div class="post-actions">
            <div 
                class="like-action" 
                @mouseenter="openReactions" 
                @mouseleave="scheduleHideReactions"
            >
                <button 
                    :class="['action-btn', { active: isReacted }, reactionTextClass]"
                    @click.stop="handleLikeClick"
                >
                    <span v-if="reactionButtonEmoji" class="reaction-emoji main-reaction-emoji">
                        {{ reactionButtonEmoji }}
                    </span>
                    <i v-else class="bi bi-hand-thumbs-up"></i> 
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

            <button class="action-btn" @click.stop="openComments">
                <i class="bi bi-chat-dots"></i> 
                <span>Bình luận</span>
            </button>
            <button class="action-btn" @click.stop="openShareModal">
                <i class="bi bi-share"></i> 
                <span>Chia sẻ</span>
            </button>
        </div>


        <!-- Featured Comments Section (Always visible if has comments) -->
        <div v-if="post.comments && post.comments.length > 0" class="featured-comments">
            <div class="comments-list">
                <div 
                    v-for="comment in post.comments.slice(0, 2)" 
                    :key="comment.id"
                    class="comment-item"
                >
                    <img 
                        :src="getAvatarSrc(comment.user?.avatar, comment.user?.name)" 
                        class="comment-avatar" 
                        alt="Avatar"
                    />
                    <div class="comment-content">
                        <div class="comment-header">
                            <span class="comment-author">{{ comment.user?.name }}</span>
                            <span class="comment-time">{{ timeAgo(comment.created_at) }}</span>
                        </div>
                        <div class="comment-text">{{ comment.content }}</div>
                        <div class="comment-actions">
                            <button class="comment-action-btn">
                                <i class="bi bi-hand-thumbs-up"></i>
                                <span>Thích</span>
                            </button>
                            <button class="comment-action-btn" @click="startImageIndex = 0; showDetailModal = true;">
                                <i class="bi bi-reply"></i>
                                <span>Phản hồi</span>
                            </button>
                        </div>
                        
                        <!-- Replies -->
                        <div v-if="comment.replies && comment.replies.length > 0" class="replies-list">
                            <div 
                                v-for="reply in (comment.replies || []).filter((r: any) => r && r.id).slice(0, 2)" 
                                :key="reply.id"
                                class="reply-item"
                            >
                                <img 
                                    :src="getAvatarSrc(reply.user?.avatar, reply.user?.name)" 
                                    class="reply-avatar" 
                                    alt="Avatar"
                                />
                                <div class="reply-content">
                                    <div class="reply-header">
                                        <span class="reply-author">{{ reply.user?.name }}</span>
                                        <span class="reply-time">{{ timeAgo(reply.created_at) }}</span>
                                    </div>
                                    <div class="reply-text">{{ reply.content }}</div>
                                </div>
                            </div>
                            <div v-if="comment.replies.length > 2" class="view-more-replies">
                                <button @click="startImageIndex = 0; showDetailModal = true;">
                                    Xem thêm {{ comment.replies.length - 2 }} phản hồi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="post.comments.length > 2" class="view-all-comments">
                <button @click="startImageIndex = 0; showDetailModal = true;">
                    Xem tất cả {{ post.comments.length }} bình luận
                </button>
            </div>
        </div>

        <!-- Inline Comments Section -->
        <div v-if="showComments && post.comments && post.comments.length > 0" class="inline-comments">
            <div class="comments-header">
                <h5>Bình luận ({{ post.comments.length }})</h5>
                <button class="view-all-btn" @click="startImageIndex = 0; showDetailModal = true;">
                    Xem tất cả
                </button>
            </div>
            <div class="comments-list">
                <div 
                    v-for="comment in post.comments.slice(0, 3)" 
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
            <div v-if="post.comments.length > 3" class="show-more-comments">
                <button @click="startImageIndex = 0; showDetailModal = true;">
                    Xem thêm {{ post.comments.length - 3 }} bình luận
                </button>
            </div>
        </div>

        <!-- Post Detail Modal -->
        <PostDetailModal 
            :visible="showDetailModal"
            :post="{ ...post, user_reaction_type: currentUserReaction, likes_count: localLikesCount !== null ? localLikesCount : post.likes_count, likes: localLikes !== null ? localLikes : post.likes } as any"
            :startImageIndex="startImageIndex"
            @close="showDetailModal = false"
            @postUpdated="handlePostUpdated"
        />

        <!-- Share Modal -->
        <div v-if="showShareModal" class="share-modal-overlay" @click.self="showShareModal = false">
            <div class="share-modal" @click.stop>
                <div class="share-modal-header">
                    <h4>Chia sẻ bài viết</h4>
                    <button class="share-modal-close" @click="showShareModal = false">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="share-modal-body">
                    <p class="share-modal-text">Link bài viết:</p>
                    <div class="share-link-wrapper">
                        <input ref="shareLinkInputRef" class="share-link-input" :value="shareLink" readonly />
                        <button class="share-copy-btn" @click="copyShareLink">
                            <i :class="hasCopiedShareLink ? 'bi bi-clipboard-check' : 'bi bi-clipboard'"></i>
                            <span>{{ hasCopiedShareLink ? 'Đã copy' : 'Copy' }}</span>
                        </button>
                    </div>
                    <p class="share-tip">Bạn có thể dán link này vào Facebook, Zalo,... để chia sẻ.</p>
                </div>

                <div class="share-modal-footer">
                    <button class="share-close-btn" @click="showShareModal = false">Đóng</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.post-item {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    padding: 16px;
    margin-bottom: 20px;
    width: 100%;
    transition: box-shadow 0.2s;
}

.post-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
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
    border: 2px solid #e4e6eb;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #050505;
    line-height: 1.2;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8rem;
    color: #65676b;
}

.dot {
    font-weight: bold;
}

.more-btn {
    background: none;
    border: none;
    color: #65676b;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.more-btn:hover {
    background: #f2f2f2;
}

.post-content {
    margin-bottom: 16px;
    font-size: 1rem;
    line-height: 1.5;
    color: #050505;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.post-images-container {
    margin: 12px -16px;
    display: grid;
    gap: 2px;
    background: #000;
    border-radius: 8px;
    overflow: hidden;
}

.post-images-container.images-count-1 {
    grid-template-columns: 1fr;
}

.post-images-container.images-count-2 {
    grid-template-columns: 1fr 1fr;
}

.post-images-container.images-count-3 {
    grid-template-columns: 2fr 1fr;
    grid-template-rows: 1fr 1fr;
}

.post-images-container.images-count-3 .post-image-item:first-child {
    grid-row: 1 / 3;
}

.post-images-container.images-count-4 {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
}

.post-images-container.images-count-5,
.post-images-container.images-count-6,
.post-images-container.images-count-7,
.post-images-container.images-count-8,
.post-images-container.images-count-9 {
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(3, 1fr);
}

.post-images-container.images-count-5 .post-image-item:nth-child(1),
.post-images-container.images-count-6 .post-image-item:nth-child(1),
.post-images-container.images-count-7 .post-image-item:nth-child(1),
.post-images-container.images-count-8 .post-image-item:nth-child(1),
.post-images-container.images-count-9 .post-image-item:nth-child(1) {
    grid-column: 1 / 3;
    grid-row: 1 / 3;
}

.post-image-item {
    position: relative;
    overflow: hidden;
    cursor: pointer;
    background: #f0f2f5;
    min-height: 200px;
    aspect-ratio: 1;
}

.post-image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.2s;
}

.post-images-container.images-count-1 .post-image-item {
    min-height: 400px;
    aspect-ratio: auto;
}

.post-image-item:hover img {
    transform: scale(1.05);
}

.more-images-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
}

.post-stats {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e4e6e9;
}

.stats-right {
    display: flex;
    gap: 16px;
}

.stats-left, .stats-right {
    display: flex;
    align-items: center;
}

.like-icons {
    background: #1877f2;
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
    color: #65676b;
}

.post-actions {
    display: flex;
    padding: 4px 0;
    gap: 4px;
}

.like-action {
    position: relative;
    flex: 1;
}

.action-btn {
    flex: 1;
    background: none;
    border: none;
    color: #65676b;
    font-weight: 600;
    padding: 8px;
    border-radius: 4px;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.action-btn:hover {
    background: #f2f2f2;
}

.action-btn i {
    font-size: 1.2rem;
}

.action-btn span {
    font-size: 0.9rem;
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

.action-btn .main-reaction-emoji {
    font-size: 1.4rem;
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

.share-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.share-modal {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    padding: 16px 20px 18px;
}

.share-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.share-modal-header h4 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 600;
    color: #111827;
}

.share-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    color: #6b7280;
}

.share-modal-body {
    margin-bottom: 14px;
}

.share-modal-text {
    margin: 0 0 6px;
    font-size: 0.9rem;
    color: #4b5563;
}

.share-link-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

.share-link-input {
    flex: 1;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    padding: 6px 10px;
    font-size: 0.9rem;
    color: #111827;
    background: #f9fafb;
}

.share-copy-btn {
    border: none;
    background: #2563eb;
    color: #fff;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
}

.share-tip {
    margin: 8px 0 0;
    font-size: 0.8rem;
    color: #6b7280;
}

.share-modal-footer {
    display: flex;
    justify-content: flex-end;
}

.share-close-btn {
    border: none;
    background: #e5e7eb;
    color: #111827;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 0.85rem;
    cursor: pointer;
}

/* Featured Comments Styles */
.featured-comments {
    margin-top: 8px;
    padding-top: 12px;
    border-top: 1px solid #e4e6e9;
}

.featured-comments .comments-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 8px;
}

.featured-comments .comment-item {
    display: flex;
    gap: 12px;
    padding: 4px 0;
}

.featured-comments .comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.featured-comments .comment-content {
    flex: 1;
    min-width: 0;
}

.featured-comments .comment-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.featured-comments .comment-author {
    font-weight: 600;
    color: #050505;
    font-size: 0.9rem;
}

.featured-comments .comment-time {
    font-size: 0.75rem;
    color: #65676b;
}

.featured-comments .comment-text {
    color: #050505;
    font-size: 0.9rem;
    line-height: 1.5;
    word-wrap: break-word;
    margin-bottom: 8px;
}

.featured-comments .comment-actions {
    display: flex;
    gap: 16px;
    margin-top: 4px;
}

.featured-comments .comment-action-btn {
    background: none;
    border: none;
    color: #65676b;
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background 0.2s;
}

.featured-comments .comment-action-btn:hover {
    background: #f2f2f2;
    color: #050505;
}

.featured-comments .comment-action-btn i {
    font-size: 0.9rem;
}

.view-all-comments {
    margin-top: 8px;
    padding-top: 8px;
}

.view-all-comments button {
    background: none;
    border: none;
    color: #65676b;
    font-size: 0.9rem;
    cursor: pointer;
    padding: 4px 0;
    font-weight: 500;
}

.view-all-comments button:hover {
    color: #1877f2;
    text-decoration: underline;
}

/* Replies Styles */
.featured-comments .replies-list {
    margin-top: 12px;
    padding-left: 12px;
    border-left: 2px solid #e4e6e9;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.featured-comments .reply-item {
    display: flex;
    gap: 12px;
    padding: 4px 0;
}

.featured-comments .reply-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.featured-comments .reply-content {
    flex: 1;
    min-width: 0;
}

.featured-comments .reply-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.featured-comments .reply-author {
    font-weight: 600;
    color: #050505;
    font-size: 0.85rem;
}

.featured-comments .reply-time {
    font-size: 0.75rem;
    color: #65676b;
}

.featured-comments .reply-text {
    color: #050505;
    font-size: 0.85rem;
    line-height: 1.5;
    word-wrap: break-word;
}

.view-more-replies {
    margin-top: 8px;
    padding-left: 44px;
}

.view-more-replies button {
    background: none;
    border: none;
    color: #65676b;
    font-size: 0.85rem;
    cursor: pointer;
    padding: 4px 0;
    font-weight: 500;
}

.view-more-replies button:hover {
    color: #1877f2;
    text-decoration: underline;
}

/* Inline Comments Styles */
.inline-comments {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e4e6e9;
}

.comments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.comments-header h5 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #050505;
}

.view-all-btn,
.show-more-comments button {
    background: none;
    border: none;
    color: #1877f2;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    padding: 4px 8px;
}

.view-all-btn:hover,
.show-more-comments button:hover {
    text-decoration: underline;
}

.inline-comments .comments-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.inline-comments .comment-item {
    display: flex;
    gap: 8px;
}

.inline-comments .comment-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.inline-comments .comment-content {
    flex: 1;
}

.inline-comments .comment-bubble {
    background: #f0f2f5;
    border-radius: 18px;
    padding: 8px 12px;
    display: inline-block;
    max-width: 100%;
}

.inline-comments .comment-author {
    font-weight: 600;
    color: #050505;
    margin-right: 6px;
    font-size: 0.85rem;
}

.inline-comments .comment-text {
    color: #050505;
    font-size: 0.85rem;
    word-wrap: break-word;
}

.inline-comments .comment-meta {
    margin-top: 4px;
    padding-left: 12px;
}

.inline-comments .comment-time {
    font-size: 0.75rem;
    color: #65676b;
}

.show-more-comments {
    margin-top: 8px;
    text-align: center;
}

.show-more-comments button {
    padding: 8px 16px;
}
</style>

