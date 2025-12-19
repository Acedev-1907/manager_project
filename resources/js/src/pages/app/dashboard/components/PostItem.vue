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
        }>;
    }
}>();

const emit = defineEmits<{
    (e: 'postUpdated', payload: { postId: number; likes_count: number; likes: any[]; liked: boolean; reactionType: string | null }): void;
}>();

const showDetailModal = ref(false);
const startImageIndex = ref(0);

const userStore = useUserStore();
// @ts-expect-error - Pinia store type inference issue
const currentUser = computed(() => userStore.user);

const postTimeAgo = computed(() => {
    return timeAgo(props.post.created_at);
});

const postImages = computed(() => {
    // Support both old image_url and new images array
    if (props.post.images && Array.isArray(props.post.images) && props.post.images.length > 0) {
        return props.post.images;
    }
    if (props.post.image_url) {
        return [props.post.image_url];
    }
    return [];
});

const likesLabel = computed(() => {
    const count = props.post.likes_count || 0;
    if (count === 1 && currentUser.value && props.post.likes && props.post.likes.some((like) => like.user?.id === currentUser.value.id)) {
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
    // Ưu tiên dùng trường user_reaction_type được frontend cập nhật
    if (typeof props.post.user_reaction_type !== 'undefined') {
        return props.post.user_reaction_type || null;
    }

    if (!currentUser.value || !props.post.likes) return null;
    const like = props.post.likes.find((like) => like.user?.id === currentUser.value.id);
    if (!like) return null;
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

const showReactions = ref(false);
let hideReactionsTimeout: number | null = null;

const reactions = [
    { type: 'like', label: 'Thích', emoji: '👍' },
    { type: 'love', label: 'Yêu thích', emoji: '❤️' },
    { type: 'care', label: 'Thương thương', emoji: '🥰' },
    { type: 'haha', label: 'Haha', emoji: '😂' },
    { type: 'sad', label: 'Buồn', emoji: '😢' },
    { type: 'angry', label: 'Phẫn nộ', emoji: '😡' },
];

const openImageGallery = (index: number) => {
    startImageIndex.value = index;
    showDetailModal.value = true;
};

const openComments = () => {
    // Mở modal chi tiết, tập trung vào phần bình luận
    startImageIndex.value = 0;
    showDetailModal.value = true;
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
    try {
        const res = await makeHttpReq<{ type: string }, { liked: boolean; type?: string | null; likes_count: number; likes: any[] }>(
            `/posts/${props.post.id}/like`,
            'POST',
            { type: reactionType }
        );
        const finalType = res.liked ? (res.type || reactionType) : null;
        emit('postUpdated', {
            postId: props.post.id,
            liked: res.liked,
            likes_count: res.likes_count,
            likes: res.likes,
            reactionType: finalType,
        });
    } catch (error: any) {
        showError(error?.message || 'Không thể gửi tương tác');
    }
};

const handleLikeClick = async () => {
    await sendReaction('like');
};

const handleSelectReaction = async (reactionType: string) => {
    showReactions.value = false;
    await sendReaction(reactionType);
};
</script>

<template>
    <div class="post-item">
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

        <div class="post-stats" v-if="(post.likes_count || 0) > 0 || (post.comments_count || 0) > 0">
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
            <div class="stats-right" v-if="(post.comments_count || 0) > 0">
                <span class="stats-text">{{ post.comments_count }} bình luận</span>
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
                    <span>Thích</span>
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
                <i class="bi bi-chat"></i> 
                <span>Bình luận</span>
            </button>
            <button class="action-btn">
                <i class="bi bi-share"></i> 
                <span>Chia sẻ</span>
            </button>
        </div>

        <!-- Post Detail Modal -->
        <PostDetailModal 
            :visible="showDetailModal"
            :post="post"
            :startImageIndex="startImageIndex"
            @close="showDetailModal = false"
            @postUpdated="handlePostUpdated"
        />
    </div>
</template>

<style scoped>
.post-item {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    padding: 12px 16px;
    margin-bottom: 16px;
    width: 100%;
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
    margin-bottom: 12px;
    font-size: 0.95rem;
    line-height: 1.4;
    color: #050505;
    white-space: pre-wrap;
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
}

.post-image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.2s;
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
</style>

