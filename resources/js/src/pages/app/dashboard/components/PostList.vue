<script lang="ts" setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
import PostItem from './PostItem.vue';
import CreatePost from './CreatePost.vue';
import SkeletonCard from '../../../../components/SkeletonCard.vue';

const posts = ref<any[]>([]);
const isLoading = ref(true);
const page = ref(1);
const hasMore = ref(true);
const loadMoreTrigger = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

// Helper function to filter out null/undefined posts
const filterValidPosts = (postsArray: any): any[] => {
    // Ensure postsArray is an array
    if (!Array.isArray(postsArray)) {
        return [];
    }
    return postsArray.filter(post => post != null && post.id != null);
};

// Computed property to ensure only valid posts are rendered
const validPosts = computed(() => {
    return filterValidPosts(posts.value);
});

const fetchPosts = async (isRefresh = false) => {
    if (isRefresh) {
        page.value = 1;
        posts.value = [];
        isLoading.value = true;
    }

    try {
        const res = await makeHttpReq<never, { data?: any; next_page_url?: string | null }>(`/posts?page=${page.value}`, 'GET');
        if (res.data) {
            // Handle paginated response: res.data might be an object with 'data' property
            let postsData: any[] = [];
            if (Array.isArray(res.data)) {
                postsData = res.data;
            } else if (res.data.data && Array.isArray(res.data.data)) {
                postsData = res.data.data;
            }
            
            const validPosts = filterValidPosts(postsData);
            if (isRefresh) {
                posts.value = validPosts;
            } else {
                posts.value = filterValidPosts([...posts.value, ...validPosts]);
            }
            
            // Check for next page
            const nextPageUrl = res.data.next_page_url || (res.data as any)?.next_page_url || null;
            hasMore.value = nextPageUrl !== null;
            page.value++;
        }
    } catch (error) {
        console.error('Lỗi khi tải bài viết:', error);
    } finally {
        isLoading.value = false;
    }
};

const handlePostCreated = (newPost: any) => {
    if (newPost != null && newPost.id != null) {
        posts.value.unshift(newPost);
    }
};

const handlePostUpdated = (payload: { postId: number; likes_count: number; likes: any[]; liked: boolean; reactionType: string | null }) => {
    const index = posts.value.findIndex((p) => p.id === payload.postId);
    if (index !== -1) {
        const current = posts.value[index];
        // Update the post object to ensure reactivity
        posts.value[index] = {
            ...current,
            likes_count: payload.likes_count,
            likes: payload.likes || current.likes || [],
            // Set user_reaction_type based on liked status and reactionType
            user_reaction_type: payload.liked ? (payload.reactionType || 'like') : null,
        };
    }
};

onMounted(() => {
    fetchPosts(true);

    // Infinite scroll: dùng IntersectionObserver quan sát phần tử cuối danh sách
    observer = new IntersectionObserver(
        (entries) => {
            const first = entries[0];
            if (first.isIntersecting && hasMore.value && !isLoading.value) {
                fetchPosts();
            }
        },
        {
            root: null,
            rootMargin: '0px 0px 200px 0px',
            threshold: 0.1,
        },
    );

    if (loadMoreTrigger.value) {
        observer.observe(loadMoreTrigger.value);
    }
});

// Khi ref của sentinel thay đổi (sau khi render danh sách), gắn lại observer
watch(loadMoreTrigger, (el) => {
    if (observer && el) {
        observer.observe(el);
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
        observer = null;
    }
});
</script>

<template>
    <div class="news-feed-container">
        <CreatePost @postCreated="handlePostCreated" />
        
        <div v-if="isLoading && posts.length === 0" class="loading-posts">
            <SkeletonCard v-for="i in 3" :key="i" class="mb-3" />
        </div>

        <div v-else-if="validPosts.length === 0" class="no-posts-container">
            <div class="no-posts-card">
                <div class="icon-circle">
                    <i class="bi bi-chat-square-text"></i>
                </div>
                <h3>Chưa có bài viết nào</h3>
                <p>Hãy là người đầu tiên chia sẻ suy nghĩ của bạn với mọi người!</p>
            </div>
        </div>

        <div v-else class="posts-list">
            <PostItem 
                v-for="post in validPosts" 
                :key="post.id" 
                :post="post" 
                @postUpdated="handlePostUpdated" 
            />

            <!-- Sentinel cho infinite scroll -->
            <div
                v-if="hasMore"
                ref="loadMoreTrigger"
                class="load-more-sentinel"
            >
                <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.news-feed-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.loading-posts {
    width: 100%;
}

.no-posts-container {
    width: 100%;
    display: flex;
    justify-content: center;
    padding: 20px 0;
}

.no-posts-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    padding: 40px;
    text-align: center;
    width: 100%;
    color: #65676b;
}

.icon-circle {
    width: 80px;
    height: 80px;
    background: #f0f2f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
}

.icon-circle i {
    font-size: 2.5rem;
    color: #bcc0c4;
}

.no-posts-card h3 {
    color: #1c1e21;
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.no-posts-card p {
    font-size: 1rem;
    max-width: 300px;
    margin: 0 auto;
}

.posts-list {
    width: 100%;
}

.load-more-sentinel {
    display: flex;
    justify-content: center;
    padding: 10px 0 20px 0;
}
</style>

