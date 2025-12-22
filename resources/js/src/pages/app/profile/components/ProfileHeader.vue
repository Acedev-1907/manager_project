<script setup lang="ts">
import { type User } from '../../../../state/userStore';
import { getAvatarSrc } from '../../../../helper/avatar';

interface Props {
    user: User;
    isCurrentUser: boolean;
    userStats: {
        following: number;
        likes: number;
        followers: number;
    };
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'avatar-click'): void;
    (e: 'cover-click'): void;
    (e: 'edit-profile'): void;
}>();
</script>

<template>
    <div class="cover-photo-section">
        <div 
            class="cover-photo" 
            :style="{ backgroundImage: props.user.cover_photo ? `url(${props.user.cover_photo})` : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }"
        >
            <button v-if="props.isCurrentUser" class="edit-cover-btn" @click="emit('cover-click')">
                <i class="bi bi-camera-fill"></i>
                Edit Cover
            </button>
            
            <!-- Profile Card Overlay - Inside Cover Photo -->
            <div class="profile-card-overlay">
                <div class="profile-avatar-wrapper">
                    <img 
                        :src="getAvatarSrc(props.user.avatar, props.user.name)" 
                        :alt="props.user.name"
                        @click="emit('avatar-click')"
                        class="profile-avatar"
                    />
                    <button v-if="props.isCurrentUser" class="avatar-camera-btn" @click="emit('avatar-click')" type="button">
                        <i class="bi bi-camera-fill"></i>
                    </button>
                    <div class="verified-badge">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
                <div class="profile-info">
                    <div class="profile-name">{{ props.user.name || 'User' }} ❤️</div>
                    <div class="profile-email">{{ props.user.email || 'user@example.com' }}</div>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number">{{ props.userStats.following }}</div>
                        <div class="stat-label">Following</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">{{ props.userStats.likes }}</div>
                        <div class="stat-label">Likes</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">{{ props.userStats.followers }}</div>
                        <div class="stat-label">Followers</div>
                    </div>
                </div>
                <button v-if="props.isCurrentUser" class="edit-profile-btn" @click="emit('edit-profile')">
                    Edit Profile
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cover-photo-section {
    position: relative;
    margin-bottom: 20px;
}

.cover-photo {
    width: 100%;
    height: 400px;
    background-size: cover;
    background-position: center;
    background-color: #667eea;
    position: relative;
}

.edit-cover-btn {
    position: absolute;
    bottom: 20px;
    right: 20px;
    background: #e7f3ff;
    color: #1877f2;
    border: 1px solid #1877f2;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.edit-cover-btn:hover {
    background: #1877f2;
    color: #fff;
    box-shadow: 0 4px 8px rgba(24, 119, 242, 0.3);
}

.profile-card-overlay {
    position: absolute;
    bottom: 20px;
    left: 40px;
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    min-width: 260px;
    max-width: 300px;
    text-align: center;
    z-index: 10;
}

.profile-avatar-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 12px;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    cursor: pointer;
}

.avatar-camera-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #1877f2;
    color: #fff;
    border: 3px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1rem;
}

.verified-badge {
    position: absolute;
    top: 0;
    right: 0;
    width: 32px;
    height: 32px;
    background: #1877f2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #fff;
}

.verified-badge i {
    color: #fff;
    font-size: 1rem;
}

.profile-info {
    margin-bottom: 12px;
}

.profile-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #050505;
    margin-bottom: 2px;
}

.profile-email {
    font-size: 0.85rem;
    color: #65676b;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    padding: 12px 0;
    margin-bottom: 0;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #050505;
    margin-bottom: 2px;
}

.stat-label {
    font-size: 0.8rem;
    color: #65676b;
}

.edit-profile-btn {
    width: 100%;
    padding: 10px 16px;
    background: #1877f2;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 12px;
    box-shadow: 0 2px 4px rgba(24, 119, 242, 0.2);
}

.edit-profile-btn:hover {
    background: #166fe5;
    box-shadow: 0 4px 8px rgba(24, 119, 242, 0.3);
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .profile-card-overlay {
        left: 50%;
        transform: translateX(-50%);
        min-width: 260px;
    }
    
    .cover-photo {
        height: 300px;
    }
}
</style>

