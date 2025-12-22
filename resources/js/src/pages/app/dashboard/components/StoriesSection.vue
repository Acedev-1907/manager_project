<template>
    <div class="stories-section">
        <div class="stories-container">
            <!-- Add Story Card -->
            <!-- <div class="story-card add-story-card">
                <div class="story-avatar-wrapper">
                    <img 
                        :src="getAvatarSrc(currentUser?.avatar, currentUser?.name)" 
                        class="story-avatar" 
                        alt="Your avatar"
                    />
                    <div class="add-story-icon">
                        <i class="bi bi-plus-lg"></i>
                    </div>
                </div>
                <div class="story-label">Thêm tin</div>
            </div> -->

            <!-- User Stories -->
            <div 
                v-for="story in stories" 
                :key="story.id"
                class="story-card"
                :style="{ background: story.color || '#1877f2' }"
            >
                <div class="story-avatar-wrapper">
                    <img 
                        :src="getAvatarSrc(story.avatar, story.name)" 
                        class="story-avatar" 
                        :alt="story.name"
                    />
                    <div v-if="story.active" class="active-badge">
                        <span class="active-dot"></span>
                    </div>
                </div>
                <div class="story-label">{{ story.name }}</div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';
import { makeHttpReq } from '../../../../helper/makeHttpReq';

const stories = ref<Array<{
    id: number;
    name: string;
    avatar?: string;
    active?: boolean;
    color?: string;
}>>([]);

// Mock colors for stories
const storyColors = [
    '#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#f0932b', 
    '#eb4d4b', '#6c5ce7', '#a29bfe', '#00b894', '#00cec9'
];

onMounted(async () => {
    try {
        // Try to fetch active users/friends for stories
        // If API doesn't exist, silently fail and use empty array
        const res = await makeHttpReq<never, { users?: any[] }>('/users/active', 'GET');
        if (res && res.users && Array.isArray(res.users)) {
            stories.value = res.users.slice(0, 10).map((user: any, index: number) => ({
                id: user.id,
                name: user.name,
                avatar: user.avatar,
                active: true,
                color: storyColors[index % storyColors.length]
            }));
        }
    } catch (error: any) {
        // API endpoint doesn't exist or other error - use empty array
        // Don't log error to avoid console noise
        stories.value = [];
    }
});
</script>

<style scoped>
.stories-section {
    margin-bottom: 20px;
    width: 100%;
}

.stories-container {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding: 8px 0;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 transparent;
}

.stories-container::-webkit-scrollbar {
    height: 4px;
}

.stories-container::-webkit-scrollbar-track {
    background: transparent;
}

.stories-container::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 2px;
}

.story-card {
    flex-shrink: 0;
    width: 120px;
    height: 200px;
    border-radius: 12px;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    padding: 12px;
    cursor: pointer;
    transition: transform 0.2s;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    overflow: hidden;
}

.story-card:hover {
    transform: scale(1.02);
}

.add-story-card {
    background: #1877f2 !important;
    border: 2px solid #e4e6eb;
}

.story-avatar-wrapper {
    position: relative;
    width: 56px;
    height: 56px;
    margin-bottom: 8px;
}

.story-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.add-story-icon {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 24px;
    height: 24px;
    background: #1877f2;
    border: 3px solid #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    font-weight: bold;
}

.active-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 16px;
    height: 16px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.active-dot {
    width: 12px;
    height: 12px;
    background: #31a24c;
    border-radius: 50%;
}

.story-label {
    color: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

@media (max-width: 768px) {
    .story-card {
        width: 100px;
        height: 160px;
    }
    
    .story-avatar-wrapper {
        width: 48px;
        height: 48px;
    }
}
</style>

