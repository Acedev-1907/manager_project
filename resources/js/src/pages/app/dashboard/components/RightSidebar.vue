<template>
    <div class="right-sidebar">
        <!-- Friends Section -->
        <div class="sidebar-card friends-card">
            <div class="card-header">
                <h5 class="card-title">Bạn bè</h5>
                <div class="card-actions">
                    <button class="icon-btn" @click="refreshFriends">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="icon-btn">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </div>
            <div class="card-subtitle">Bắt đầu cuộc trò chuyện mới</div>
            
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Tìm bạn bè..." v-model="friendSearch" />
            </div>

            <!-- Add Friend Button -->
            <button class="add-friend-btn" @click="showAddFriendModal = true">
                <i class="bi bi-person-plus"></i>
                <span>+Thêm bạn</span>
            </button>

            <!-- Close Friends -->
            <div class="friends-section">
                <div class="section-header" @click="toggleCloseFriends">
                    <span>Bạn thân</span>
                    <i :class="['bi', showCloseFriends ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
                </div>
                <div v-if="showCloseFriends" class="friends-list">
                    <div 
                        v-for="friend in filteredCloseFriends" 
                        :key="friend.id"
                        class="friend-item"
                    >
                        <div class="friend-avatar-wrapper">
                            <img 
                                :src="getAvatarSrc(friend.avatar, friend.name)" 
                                class="friend-avatar" 
                                :alt="friend.name"
                            />
                            <span v-if="friend.online" class="online-dot"></span>
                        </div>
                        <div class="friend-info">
                            <div class="friend-name">{{ friend.name }}</div>
                            <div class="friend-location">{{ friend.location || 'Alabma, USA' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Chats -->
            <div class="friends-section">
                <div class="section-header" @click="toggleRecentChats">
                    <span>Trò chuyện gần đây</span>
                    <i :class="['bi', showRecentChats ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
                </div>
                <div v-if="showRecentChats" class="friends-list">
                    <div 
                        v-for="friend in filteredRecentChats" 
                        :key="friend.id"
                        class="friend-item"
                    >
                        <div class="friend-avatar-wrapper">
                            <img 
                                :src="getAvatarSrc(friend.avatar, friend.name)" 
                                class="friend-avatar" 
                                :alt="friend.name"
                            />
                            <span v-if="friend.newMessage" class="new-message-dot"></span>
                        </div>
                        <div class="friend-info">
                            <div class="friend-name">{{ friend.name }}</div>
                            <div class="friend-location">{{ friend.location || 'Alabma, USA' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Birthday Section -->
        <!-- <div class="sidebar-card birthday-card">
            <div class="card-header">
                <h5 class="card-title">Sinh nhật !!!!</h5>
                <div class="card-actions">
                    <button class="icon-btn" @click="refreshBirthday">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="icon-btn">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </div>
            <div class="card-subtitle">Sinh nhật bạn bè hôm nay</div>
            
            <div v-if="birthdayFriend" class="birthday-content">
                <div class="birthday-icon">
                    <i class="bi bi-cake2-fill"></i>
                </div>
                <div class="birthday-avatar-wrapper">
                    <img 
                        :src="getAvatarSrc(birthdayFriend.avatar, birthdayFriend.name)" 
                        class="birthday-avatar" 
                        :alt="birthdayFriend.name"
                    />
                    <div class="birthday-badge">20+</div>
                </div>
                <div class="birthday-info">
                    <div class="birthday-name">{{ birthdayFriend.name }}</div>
                    <div class="birthday-location">{{ birthdayFriend.location || 'Glasgow, Scotland' }}</div>
                    <div class="birthday-date">Lorem 5th Sept 2019 dummy text of the printing and typesetting industry.</div>
                </div>
                <button class="birthday-btn">
                    Chúc mừng sinh nhật bạn
                    <i class="bi bi-arrow-right"></i>
                </button>
            </div>
            <div v-else class="no-birthday">
                <p>Không có sinh nhật nào hôm nay</p>
            </div>
        </div> -->

        <!-- Event Section -->
        <!-- <div class="sidebar-card event-card">
            <div class="card-header">
                <h5 class="card-title">Event</h5>
            </div>
            <div class="event-image">
                <div class="event-placeholder">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
            <div class="event-content">
                <div class="event-title">Christmas 2021</div>
                <div class="event-date">26 January 2021</div>
                <div class="event-description">
                    Lorem Ipsum is simply dummy text of the printing and typesetting industry
                </div>
                <div class="event-attendees">15256 People Going</div>
                <div class="event-actions">
                    <button class="event-btn going">Going</button>
                    <button class="event-btn not-going">Not Going</button>
                </div>
            </div>
        </div> -->

        <!-- Games Section -->
        <!-- <div class="sidebar-card games-card">
            <div class="card-header">
                <h5 class="card-title">Your Games <span class="count">24 Games</span></h5>
                <div class="card-actions">
                    <button class="icon-btn" @click="refreshGames">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="icon-btn">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </div>
            <div class="games-list">
                <div class="game-item">
                    <div class="game-avatar">J</div>
                    <div class="game-info">
                        <div class="game-name">Josephin Water</div>
                        <div class="game-followers">30k followers</div>
                    </div>
                </div>
                <div class="game-item">
                    <div class="game-avatar">J</div>
                    <div class="game-info">
                        <div class="game-name">Josephin Water</div>
                        <div class="game-followers">25k followers</div>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Gallery Section -->
        <div v-if="galleryPhotos.length > 0" class="sidebar-card gallery-card">
            <div class="card-header">
                <h5 class="card-title">Thư viện {{ galleryPhotos.length }} Ảnh</h5>
                <div class="card-actions">
                    <button class="icon-btn" @click="refreshGallery">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="icon-btn">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </div>
            
            <div class="gallery-grid">
                <div 
                    v-for="(photo, index) in galleryPhotos.slice(0, 9)" 
                    :key="index"
                    class="gallery-item"
                    @click="openGallery(index)"
                >
                    <img :src="photo" :alt="`Photo ${index + 1}`" />
                </div>
            </div>
        </div>

        <!-- Add Friend Modal -->
        <AddFriendModal 
            :visible="showAddFriendModal"
            @close="showAddFriendModal = false"
            @friendAdded="handleFriendAdded"
        />
    </div>
</template>

<script lang="ts" setup>
import { ref, computed, onMounted } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
import AddFriendModal from './AddFriendModal.vue';

const friendSearch = ref('');
const showCloseFriends = ref(true);
const showRecentChats = ref(true);
const showAddFriendModal = ref(false);

const closeFriends = ref<Array<{
    id: number;
    name: string;
    avatar?: string;
    location?: string;
    online?: boolean;
}>>([]);

const recentChats = ref<Array<{
    id: number;
    name: string;
    avatar?: string;
    location?: string;
    newMessage?: boolean;
}>>([]);

const allFriends = ref<Array<{
    id: number;
    name: string;
    avatar?: string;
    email?: string;
}>>([]);

const birthdayFriend = ref<{
    id: number;
    name: string;
    avatar?: string;
    location?: string;
} | null>(null);

const galleryPhotos = ref<string[]>([]);

const filteredCloseFriends = computed(() => {
    if (!friendSearch.value) return closeFriends.value;
    const search = friendSearch.value.toLowerCase();
    return closeFriends.value.filter(f => 
        f.name.toLowerCase().includes(search) || 
        (f.location && f.location.toLowerCase().includes(search))
    );
});

const filteredRecentChats = computed(() => {
    if (!friendSearch.value) return recentChats.value;
    const search = friendSearch.value.toLowerCase();
    return recentChats.value.filter(f => 
        f.name.toLowerCase().includes(search) || 
        (f.location && f.location.toLowerCase().includes(search))
    );
});

const toggleCloseFriends = () => {
    showCloseFriends.value = !showCloseFriends.value;
};

const toggleRecentChats = () => {
    showRecentChats.value = !showRecentChats.value;
};

const refreshFriends = async () => {
    await loadFriends();
};

// const refreshBirthday = async () => {
//     await loadBirthday();
// };

const refreshGallery = async () => {
    await loadGallery();
};

const refreshGames = async () => {
    // Refresh games if needed
};

const loadFriends = async () => {
    try {
        // Load friends from API
        const res = await makeHttpReq<never, { data?: any[] }>('/members?per_page=50', 'GET');
        
        if (res && res.data && Array.isArray(res.data.data)) {
            allFriends.value = res.data.data.map((member: any) => ({
                id: member.id,
                name: member.name,
                avatar: member.avatar,
                email: member.email
            }));

            // Split into close friends and recent chats (mock logic for now)
            // In real app, you'd have separate API endpoints or flags
            const friends = allFriends.value;
            closeFriends.value = friends.slice(0, Math.min(5, friends.length)).map(f => ({
                ...f,
                location: 'Alabma, USA',
                online: Math.random() > 0.5 // Mock online status
            }));
            
            recentChats.value = friends.slice(0, Math.min(10, friends.length)).map(f => ({
                ...f,
                location: 'Alabma, USA',
                newMessage: Math.random() > 0.7 // Mock new message status
            }));
        }
    } catch (error) {
        // Silently fail - use empty arrays
        allFriends.value = [];
        closeFriends.value = [];
        recentChats.value = [];
    }
};

const handleFriendAdded = () => {
    // Refresh friends list when a new friend is added
    loadFriends();
};

const loadBirthday = async () => {
    try {
        // Mock data - replace with actual API call
        birthdayFriend.value = {
            id: 1,
            name: 'Sufiya Elija',
            location: 'Glasgow, Scotland'
        };
    } catch (error) {
        console.error('Error loading birthday:', error);
    }
};

const loadGallery = async () => {
    try {
        // Try to fetch user's photos from posts
        // For now, use empty array - will be populated when API is available
        // You can replace this with actual API call: /posts?user_id=current&images_only=true
        galleryPhotos.value = [];
    } catch (error) {
        // Silently fail - gallery will be hidden if empty
        galleryPhotos.value = [];
    }
};

const openGallery = (index: number) => {
    // Open gallery modal or navigate to gallery page
    console.log('Open gallery at index:', index);
};

onMounted(() => {
    loadFriends();
    // loadBirthday();
    loadGallery();
});
</script>

<style scoped>
.right-sidebar {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    padding: 16px;
    width: 100%;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.card-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #050505;
}

.card-actions {
    display: flex;
    gap: 8px;
}

.icon-btn {
    background: none;
    border: none;
    color: #65676b;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.icon-btn:hover {
    background: #f0f2f5;
}

.card-subtitle {
    font-size: 0.9rem;
    color: #65676b;
    margin-bottom: 12px;
}

.search-box {
    position: relative;
    margin-bottom: 16px;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #65676b;
}

.search-box input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid #e4e6eb;
    border-radius: 20px;
    background: #f0f2f5;
    font-size: 0.9rem;
    outline: none;
    transition: background 0.2s;
}

.search-box input:focus {
    background: #fff;
    border-color: #1877f2;
}

.add-friend-btn {
    width: 100%;
    padding: 12px 16px;
    margin-bottom: 16px;
    background: #f0f2f5;
    color: #050505;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.add-friend-btn:hover {
    background: #e4e6eb;
    border-color: #b1b3b8;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.add-friend-btn:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.add-friend-btn i {
    font-size: 1.1rem;
    color: #1877f2;
}

.friends-section {
    margin-bottom: 16px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    cursor: pointer;
    font-weight: 600;
    color: #050505;
    font-size: 0.95rem;
}

.section-header:hover {
    color: #1877f2;
}

.friends-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 8px;
}

.friend-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.friend-item:hover {
    background: #f0f2f5;
}

.friend-avatar-wrapper {
    position: relative;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.friend-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.online-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    background: #31a24c;
    border: 2px solid #fff;
    border-radius: 50%;
}

.new-message-dot {
    position: absolute;
    top: 0;
    right: 0;
    width: 12px;
    height: 12px;
    background: #e41e3f;
    border: 2px solid #fff;
    border-radius: 50%;
}

.friend-info {
    flex: 1;
    min-width: 0;
}

.friend-name {
    font-weight: 600;
    color: #050505;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.friend-location {
    font-size: 0.85rem;
    color: #65676b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Birthday Card Styles */
.birthday-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.birthday-card .card-title,
.birthday-card .card-subtitle {
    color: #fff;
}

.birthday-card .icon-btn {
    color: #fff;
}

.birthday-card .icon-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.birthday-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
}

.birthday-icon {
    font-size: 2.5rem;
    margin-bottom: 8px;
}

.birthday-avatar-wrapper {
    position: relative;
    width: 80px;
    height: 80px;
}

.birthday-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
}

.birthday-badge {
    position: absolute;
    bottom: -4px;
    right: -4px;
    background: #e41e3f;
    color: #fff;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    border: 2px solid #fff;
}

.birthday-info {
    width: 100%;
}

.birthday-name {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.birthday-location {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 8px;
}

.birthday-date {
    font-size: 0.85rem;
    opacity: 0.8;
    margin-bottom: 12px;
}

.birthday-btn {
    width: 100%;
    background: #fff;
    color: #667eea;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: transform 0.2s;
}

.birthday-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.no-birthday {
    text-align: center;
    padding: 20px;
    color: #65676b;
}

/* Gallery Styles */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    margin-top: 12px;
}

.gallery-item {
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s;
}

.gallery-item:hover {
    transform: scale(1.05);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Event Card Styles */
.event-card {
    margin-bottom: 20px;
}

.event-image {
    width: 100%;
    height: 180px;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.event-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.7);
    font-size: 3rem;
}

.event-content {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.event-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #050505;
}

.event-date {
    font-size: 0.9rem;
    color: #65676b;
}

.event-description {
    font-size: 0.85rem;
    color: #65676b;
    line-height: 1.4;
}

.event-attendees {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1877f2;
    margin-top: 4px;
}

.event-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.event-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}

.event-btn.going {
    background: #1877f2;
    color: #fff;
}

.event-btn.going:hover {
    background: #166fe5;
}

.event-btn.not-going {
    background: #e4e6eb;
    color: #050505;
}

.event-btn.not-going:hover {
    background: #d1d5db;
}

/* Games Card Styles */
.games-card .count {
    font-weight: 400;
    color: #65676b;
    font-size: 0.85rem;
}

.games-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 12px;
}

.game-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.game-item:hover {
    background: #f0f2f5;
}

.game-avatar {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    background: #1877f2;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.game-info {
    flex: 1;
    min-width: 0;
}

.game-name {
    font-weight: 600;
    color: #050505;
    font-size: 0.95rem;
    margin-bottom: 2px;
}

.game-followers {
    font-size: 0.8rem;
    color: #65676b;
}

@media (max-width: 1200px) {
    .right-sidebar {
        display: none;
    }
}
</style>

