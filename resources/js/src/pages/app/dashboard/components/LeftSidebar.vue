<script lang="ts" setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '../../../../state/userStore';
import { getAvatarSrc } from '../../../../helper/avatar';
import { makeHttpReq } from '../../../../helper/makeHttpReq';

const router = useRouter();

const userStore = useUserStore();
// Get user from store, fallback to userInfoCache for email
const currentUser = computed(() => {
    const user = (userStore as any).user;
    const cache = (userStore as any).userInfoCache;
    
    // If user has email, return user
    if (user?.email) {
        return user;
    }
    
    // If cache has email, merge with user
    if (cache?.email && user) {
        return {
            ...user,
            email: cache.email,
            phone: cache.phone || user.phone,
        };
    }
    
    // Return cache if it exists and user doesn't
    if (cache) {
        return cache;
    }
    
    return user;
});

// User stats (mock data for now, can be fetched from API later)
const userStats = ref({
    following: 546,
    likes: 26335,
    followers: 6845
});

// Friend suggestions
const friendSuggestions = ref<any[]>([]);

// Liked pages
const likedPages = ref<any[]>([]);

// Weather data (mock)
const weatherData = ref({
    temperature: 28,
    time: '4.45 PM',
    condition: 'Sunny Day',
    date: '21 March 2021 (Monday)',
    location: 'Denmark'
});

// Fetch friend suggestions
const fetchFriendSuggestions = async () => {
    try {
        // TODO: Replace with actual API endpoint
        // const res = await makeHttpReq('/friends/suggestions', 'GET');
        // friendSuggestions.value = res.data || [];
        
        // Mock data for now
        friendSuggestions.value = [
            {
                id: 1,
                name: 'JESSICA SMIT',
                avatar: '',
                gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
            },
            {
                id: 2,
                name: 'JOHN DOE',
                avatar: '',
                gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
            }
        ];
    } catch (error) {
        console.error('Error fetching friend suggestions:', error);
    }
};

// Fetch liked pages
const fetchLikedPages = async () => {
    try {
        // TODO: Replace with actual API endpoint
        // const res = await makeHttpReq('/pages/liked', 'GET');
        // likedPages.value = res.data || [];
        
        // Mock data for now
        likedPages.value = [
            {
                id: 1,
                name: 'Chrimson Agency',
                category: 'Clothing Store',
                followers: '15k',
                icon: '💎',
                color: '#e74c3c'
            },
            {
                id: 2,
                name: 'Digital Pixel',
                category: 'Software Company',
                followers: '158k',
                icon: '🐙',
                color: '#27ae60'
            },
            {
                id: 3,
                name: 'The Angle Bar',
                category: 'Disco Bar',
                followers: '8k',
                icon: 'S',
                color: '#f39c12'
            },
            {
                id: 4,
                name: 'Fivestar Food',
                category: 'Restaurant',
                followers: '38k',
                icon: '❤️',
                color: '#e74c3c'
            },
            {
                id: 5,
                name: 'Royal Watch',
                category: 'Watch Shop',
                followers: '8k',
                icon: '🔗',
                color: '#3498db'
            }
        ];
    } catch (error) {
        console.error('Error fetching liked pages:', error);
    }
};

// Fetch user info if email is missing
const fetchUserInfo = async () => {
    const user = (userStore as any).user;
    const cache = (userStore as any).userInfoCache;
    
    // If user already has email, no need to fetch
    if (user?.email) {
        return;
    }
    
    // If cache has email, update userStore
    if (cache?.email && user) {
        (userStore as any).setUser({
            ...user,
            email: cache.email,
            phone: cache.phone || user.phone,
        });
        return;
    }
    
    // Fetch from API if no email in both user and cache
    try {
        const res = await makeHttpReq<undefined, { data: any }>('user', 'GET');
        if (res.data) {
            (userStore as any).setUser({
                id: res.data.id,
                name: res.data.name,
                email: res.data.email,
                phone: res.data.phone,
                avatar: res.data.avatar || '',
                friend_code: res.data.friend_code || null,
            });
            // Also update cache
            (userStore as any).setUserInfoCache({
                id: res.data.id,
                name: res.data.name,
                email: res.data.email,
                phone: res.data.phone,
                avatar: res.data.avatar || '',
                cover_photo: res.data.cover_photo || '',
                friend_code: res.data.friend_code || null,
            });
        }
    } catch (error) {
        console.warn('Failed to fetch user info in LeftSidebar:', error);
    }
};

onMounted(() => {
    fetchUserInfo();
    fetchFriendSuggestions();
    fetchLikedPages();
});
</script>

<template>
    <div class="left-sidebar">
        <!-- User Profile Card -->
        <div class="sidebar-card profile-card">
            <!-- <div class="card-header">
                <button class="icon-btn-small">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button class="icon-btn-small">
                    <i class="bi bi-gear"></i>
                </button>
            </div> -->
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar">
                    <img 
                        :src="getAvatarSrc(currentUser?.avatar, currentUser?.name)" 
                        :alt="currentUser?.name || 'User'"
                    />
                </div>
                <div class="verified-badge">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            <div class="profile-name">
                {{ currentUser?.name || 'User' }}
            </div>
            <div class="profile-email" v-if="currentUser?.email">
                {{ currentUser.email }}
            </div>
            <div class="profile-bio">
                Lorem Ipsum is simply dummy text of the printing and typesetting industry.
            </div>
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-number">{{ userStats.following }}</div>
                    <div class="stat-label">Following</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ userStats.likes }}</div>
                    <div class="stat-label">Likes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ userStats.followers }}</div>
                    <div class="stat-label">Followers</div>
                </div>
            </div>
            <button class="view-profile-btn" @click="router.push('/profile')">
                View Profile
            </button>
        </div>

        <!-- Friend Suggestions -->
        <div class="sidebar-card suggestions-card">
            <div class="card-header">
                <h6 class="card-title">Friend Suggestion</h6>
                <div class="card-actions">
                    <button class="icon-btn-small">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="icon-btn-small">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </div>
            <div class="suggestions-grid">
                <div 
                    v-for="suggestion in friendSuggestions" 
                    :key="suggestion.id"
                    class="suggestion-item"
                    :style="{ background: suggestion.gradient }"
                >
                    <img 
                        :src="getAvatarSrc(suggestion.avatar, suggestion.name)" 
                        :alt="suggestion.name"
                        class="suggestion-avatar"
                    />
                    <div class="suggestion-name">{{ suggestion.name }}</div>
                    <button class="suggestion-add-btn">Add Friend</button>
                </div>
            </div>
        </div>

        <!-- Liked Pages -->
        <div class="sidebar-card pages-card">
            <div class="card-header">
                <h6 class="card-title">
                    Liked Pages 
                    <span class="count">18 Pages</span>
                </h6>
                <div class="card-actions">
                    <button class="icon-btn-small">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="icon-btn-small">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </div>
            <div class="pages-list">
                <div 
                    v-for="page in likedPages" 
                    :key="page.id"
                    class="page-item"
                >
                    <div class="page-icon" :style="{ background: page.color }">
                        {{ page.icon }}
                    </div>
                    <div class="page-info">
                        <div class="page-name">{{ page.name }}</div>
                        <div class="page-category">{{ page.category }} · {{ page.followers }}</div>
                    </div>
                    <button class="page-more-btn">
                        <i class="bi bi-three-dots"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Weather Widget -->
        <div class="sidebar-card weather-card">
            <div class="card-header">
                <h6 class="card-title">Weather</h6>
                <div class="card-actions">
                    <button class="icon-btn-small">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="icon-btn-small">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </div>
            <div class="weather-content">
                <div class="weather-main">
                    <div class="weather-temp">{{ weatherData.temperature }}°C</div>
                    <div class="weather-time">{{ weatherData.time }}</div>
                </div>
                <div class="weather-condition">{{ weatherData.condition }}</div>
                <div class="weather-details">
                    {{ weatherData.date }} {{ weatherData.location }}
                </div>
                <div class="weather-icon">
                    <i class="bi bi-snow"></i>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.left-sidebar {
    height: fit-content;
    width: 320px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.card-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: #050505;
}

.card-title .count {
    font-weight: 400;
    color: #65676b;
    font-size: 0.85rem;
}

.card-actions {
    display: flex;
    gap: 4px;
}

.icon-btn-small {
    background: none;
    border: none;
    color: #1877f2;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 0.85rem;
}

.icon-btn-small:hover {
    background: #f0f2f5;
}

/* Profile Card */
.profile-card {
    text-align: center;
    padding: 24px 20px;
    position: relative;
}

.profile-card .card-header {
    position: absolute;
    top: 16px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 4px;
    margin-bottom: 0;
}

.profile-card .card-header .icon-btn-small {
    color: #1877f2;
}

.profile-avatar-wrapper {
    position: relative;
    display: inline-block;
    margin: 20px auto 16px;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #e0e7ff;
    position: relative;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.verified-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 28px;
    height: 28px;
    background: #1877f2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #fff;
    z-index: 1;
}

.verified-badge i {
    color: #fff;
    font-size: 0.9rem;
}

.profile-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #050505;
    margin-bottom: 6px;
}

.profile-email {
    font-size: 0.9rem;
    color: #65676b;
    margin-bottom: 12px;
}

.profile-bio {
    font-size: 0.85rem;
    color: #65676b;
    line-height: 1.4;
    margin-bottom: 20px;
    padding: 0 10px;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
    padding: 16px 0;
    border-top: 1px solid #e4e6eb;
    border-bottom: 1px solid #e4e6eb;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #050505;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.85rem;
    color: #65676b;
}

.view-profile-btn {
    width: 100%;
    padding: 10px 16px;
    background: #1877f2;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
}

.view-profile-btn:hover {
    background: #166fe5;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(24, 119, 242, 0.3);
}

/* Friend Suggestions */
.suggestions-card {
    padding: 16px;
}

.suggestions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.suggestion-item {
    border-radius: 12px;
    padding: 16px 12px;
    text-align: center;
    color: #fff;
    position: relative;
    overflow: hidden;
}

.suggestion-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255, 255, 255, 0.3);
    margin-bottom: 10px;
}

.suggestion-name {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #fff;
}

.suggestion-add-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    padding: 6px 12px;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
}

.suggestion-add-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Liked Pages */
.pages-card {
    padding: 16px;
}

.pages-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.page-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.page-item:hover {
    background: #f0f2f5;
}

.page-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    color: #fff;
    font-weight: 700;
}

.page-info {
    flex: 1;
    min-width: 0;
}

.page-name {
    font-weight: 600;
    color: #050505;
    font-size: 0.9rem;
    margin-bottom: 2px;
}

.page-category {
    font-size: 0.8rem;
    color: #65676b;
}

.page-more-btn {
    background: none;
    border: none;
    color: #65676b;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.page-more-btn:hover {
    background: #e4e6eb;
}

/* Weather Widget */
.weather-card {
    padding: 16px;
}

.weather-content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 20px;
    color: #fff;
    position: relative;
    overflow: hidden;
}

.weather-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.weather-temp {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
}

.weather-time {
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
}

.weather-condition {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.weather-details {
    font-size: 0.85rem;
    opacity: 0.9;
}

.weather-icon {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 3rem;
    opacity: 0.3;
}

@media (max-width: 1200px) {
    .left-sidebar {
        display: none;
    }
}
</style>

