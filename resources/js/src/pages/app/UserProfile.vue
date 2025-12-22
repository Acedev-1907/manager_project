<script setup lang="ts">
import { ref, onMounted, computed, nextTick, watch } from 'vue';
import { makeHttpReq } from '../../helper/makeHttpReq';
import { useRouter } from 'vue-router';
import { getUserData } from '../../helper/getUserData';
import { APP } from '../../App/APP';
import { useUserStore } from '../../state/userStore';
import imageCompression from 'browser-image-compression';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import { showSuccess, showError, showConfirm } from '../../helper/alert';
import { getAvatarSrc } from '../../helper/avatar';
import { useCacheFetch } from '../../helper/useCacheFetch';
import CreatePost from './dashboard/components/CreatePost.vue';
import PostList from './dashboard/components/PostList.vue';

const router = useRouter();
const user = ref({ id: 0, name: '', email: '', phone: '', avatar: '', cover_photo: '' });
const loading = ref(false);
const avatarLoading = ref(false);
const coverLoading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const avatarFile = ref<File | null>(null);
const coverFile = ref<File | null>(null);
const showCropModal = ref(false);
const showCoverCropModal = ref(false);
const cropper = ref<Cropper | null>(null);
const coverCropper = ref<Cropper | null>(null);
const cropImageUrl = ref('');
const coverCropImageUrl = ref('');
const cropImageFile = ref<File | null>(null);
const coverCropImageFile = ref<File | null>(null);
const cropperContainer = ref<HTMLImageElement | null>(null);
const coverCropperContainer = ref<HTMLImageElement | null>(null);
const showViewAvatarModal = ref(false);
const avatarFileInput = ref<HTMLInputElement | null>(null);
const coverFileInput = ref<HTMLInputElement | null>(null);
const userStore = useUserStore();
const zoomValue = ref(1);
const minZoom = ref(1);
const maxZoom = ref(2);
const activeTab = ref('timeline');

// User stats
const userStats = ref({
    following: 546,
    likes: 26335,
    followers: 6845
});

// About info
const aboutInfo = ref({
    work: { title: 'UX Designer At Google', location: 'Banglore - 2019' },
    education: { title: 'Studied Computer Science', location: 'At London Univercity - 2015' },
    relationship: 'Single',
    location: { title: 'Lived In London', duration: 'Last 5 Year' },
    bloodGroup: 'A+ Positive'
});

// Gallery images (mock)
const galleryImages = ref([
    { id: 1, url: 'https://via.placeholder.com/300x300?text=Photo1' },
    { id: 2, url: 'https://via.placeholder.com/300x300?text=Photo2' },
    { id: 3, url: 'https://via.placeholder.com/300x300?text=Photo3' }
]);

// Friends list
const friendsList = ref<any[]>([]);
const friendsLoading = ref(false);
const friendsSearchQuery = ref('');

// Fetch friends from API
const fetchFriends = async () => {
    friendsLoading.value = true;
    try {
        const res = await makeHttpReq<never, any>(`/members?per_page=100${friendsSearchQuery.value ? `&query=${encodeURIComponent(friendsSearchQuery.value)}` : ''}`, 'GET');
        
        // Handle response structure
        let friendsData: any[] = [];
        if (res && res.data) {
            if (Array.isArray(res.data.data)) {
                friendsData = res.data.data;
            } else if (Array.isArray(res.data)) {
                friendsData = res.data;
            }
        } else if (Array.isArray(res)) {
            friendsData = res;
        }
        
        friendsList.value = friendsData.map((friend: any) => ({
            id: friend.id,
            name: friend.name || 'Unknown',
            email: friend.email || '',
            avatar: friend.avatar || '',
            // Mock stats for now
            stats: {
                following: Math.floor(Math.random() * 1000) + 100,
                likes: Math.floor(Math.random() * 50000) + 1000,
                followers: Math.floor(Math.random() * 10000) + 500
            }
        }));
    } catch (error) {
        console.error('Error fetching friends:', error);
        friendsList.value = [];
    } finally {
        friendsLoading.value = false;
    }
};

// Watch for activeTab changes to fetch friends when tab is opened
watch(() => activeTab.value, (newTab) => {
    if (newTab === 'friends' && friendsList.value.length === 0) {
        fetchFriends();
    }
});

const { getOrFetch } = useCacheFetch(
    // @ts-expect-error - Pinia store type inference issue
    { user: userStore.userInfoCache },
    // @ts-expect-error - Pinia store type inference issue
    (_key, data) => userStore.setUserInfoCache(data),
    // @ts-expect-error - Pinia store type inference issue
    () => userStore.clearUserInfoCache()
);

function onAvatarClick() {
    showViewAvatarModal.value = true;
}
function closeViewAvatarModal() {
    showViewAvatarModal.value = false;
}
function onCameraClick() {
    if (avatarFileInput.value) avatarFileInput.value.value = '';
    avatarFileInput.value?.click();
}
function onCoverClick() {
    if (coverFileInput.value) coverFileInput.value.value = '';
    coverFileInput.value?.click();
}
function onAvatarFileInputChange(e: Event) {
    const files = (e.target as HTMLInputElement).files;
    if (files && files.length > 0) {
        cropImageFile.value = files[0];
        cropImageUrl.value = URL.createObjectURL(files[0]);
        minZoom.value = 1;
        maxZoom.value = 2;
        zoomValue.value = 1;
        showCropModal.value = true;
    }
}
function onCoverFileInputChange(e: Event) {
    const files = (e.target as HTMLInputElement).files;
    if (files && files.length > 0) {
        coverCropImageFile.value = files[0];
        coverCropImageUrl.value = URL.createObjectURL(files[0]);
        showCoverCropModal.value = true;
    }
}
function onZoomInput(e: Event) {
    const val = +(e.target as HTMLInputElement).value;
    zoomValue.value = val;
    if (cropper.value) (cropper.value as any).zoomTo(val);
}
function zoomOut() {
    if (zoomValue.value > minZoom.value) {
        zoomValue.value = Math.max(zoomValue.value - 0.05, minZoom.value);
        if (cropper.value) (cropper.value as any).zoomTo(zoomValue.value);
    }
}
function zoomIn() {
    if (zoomValue.value < maxZoom.value) {
        zoomValue.value = Math.min(zoomValue.value + 0.05, maxZoom.value);
        if (cropper.value) (cropper.value as any).zoomTo(zoomValue.value);
    }
}
async function closeCropModalWithConfirm() {
    const ok = await showConfirm('Are you sure you want to cancel editing your profile picture?', 'Cancel Editing');
    if (ok) closeCropModal();
}
async function closeCoverCropModalWithConfirm() {
    const ok = await showConfirm('Are you sure you want to cancel editing your cover photo?', 'Cancel Editing');
    if (ok) closeCoverCropModal();
}
async function fetchUser() {
    loading.value = true;
    errorMessage.value = '';
    try {
        await getOrFetch('user', async () => {
            const res = await makeHttpReq<undefined, any>('user', 'GET');
            return {
                name: res.data.name,
                email: res.data.email,
                phone: res.data.phone || '',
                avatar: res.data.avatar || '',
                cover_photo: res.data.cover_photo || '',
                friend_code: res.data.friend_code || null,
            };
        }, (data) => {
            user.value = data;
            // @ts-expect-error - Pinia store type inference issue
            userStore.setUser({ 
                id: data.id,
                name: data.name, 
                avatar: data.avatar, 
                friend_code: data.friend_code 
            });
        });
    } catch (err: any) {
        errorMessage.value = err?.message || 'Failed to load user info.';
    } finally {
        loading.value = false;
    }
}
function closeCropModal() {
    showCropModal.value = false;
    cropImageUrl.value = '';
    cropImageFile.value = null;
    if (cropper.value) {
        (cropper.value as any).destroy();
        cropper.value = null;
    }
}
function closeCoverCropModal() {
    showCoverCropModal.value = false;
    coverCropImageUrl.value = '';
    coverCropImageFile.value = null;
    if (coverCropper.value) {
        (coverCropper.value as any).destroy();
        coverCropper.value = null;
    }
}
function onCropperReady() {
    if (cropperContainer.value) {
        if (cropper.value) (cropper.value as any).destroy();
        cropper.value = new Cropper(cropperContainer.value, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            background: false,
            guides: false,
            autoCropArea: 1,
            movable: true,
            zoomable: true,
            rotatable: false,
            scalable: false,
            cropBoxResizable: true,
            minContainerWidth: 320,
            minContainerHeight: 320,
            ready() {
                const cropperInstance = cropper.value as any;
                const imageData = cropperInstance.getImageData();
                const canvasData = cropperInstance.getCanvasData();
                const currentScale = canvasData.width / imageData.naturalWidth;
                minZoom.value = currentScale;
                maxZoom.value = Math.max(currentScale * 2, currentScale + 0.5);
                zoomValue.value = minZoom.value;
                cropperInstance.zoomTo(minZoom.value);
                nextTick(() => { zoomValue.value = minZoom.value; });
            }
        } as any);
    }
}
function onCoverCropperReady() {
    if (coverCropperContainer.value) {
        if (coverCropper.value) (coverCropper.value as any).destroy();
        coverCropper.value = new Cropper(coverCropperContainer.value, {
            aspectRatio: 16 / 9,
            viewMode: 1,
            dragMode: 'move',
            background: false,
            guides: false,
            autoCropArea: 1,
            movable: true,
            zoomable: true,
            rotatable: false,
            scalable: false,
            cropBoxResizable: true,
            ready() {
                // Cover photo cropper ready
            }
        } as any);
    }
}
async function saveCroppedAvatar() {
    if (!cropper.value) return;
    (cropper.value as any).getCroppedCanvas({ width: 320, height: 320, imageSmoothingQuality: 'high' }).toBlob(async (blob: any) => {
        if (!blob) return;
        avatarFile.value = new File([blob], cropImageFile.value?.name || 'avatar.jpg', { type: 'image/jpeg' });
        avatarLoading.value = true;
        try {
            const options = { maxSizeMB: 0.3, maxWidthOrHeight: 400, useWebWorker: true };
            const compressedFile = await imageCompression(avatarFile.value, options);
            if (compressedFile.size > 2 * 1024 * 1024) {
                showError('Image is still larger than 2MB after compression. Please choose a smaller image.');
                avatarLoading.value = false;
                return;
            }
            const formData = new FormData();
            formData.append('avatar', compressedFile);
            const userData = getUserData();
            const res = await fetch(`${APP.apiBaseURL}/user/upload-avatar`, {
                method: 'POST',
                headers: { Authorization: userData?.token ? `Bearer ${userData.token}` : '' },
                body: formData,
            });
            const data = await res.json();
            if (data.code !== 1000) {
                showError(data.message || 'Upload failed.');
            } else {
                user.value.avatar = data.data.link;
                // @ts-expect-error - Pinia store type inference issue
                userStore.setAvatar(data.data.link);
                // @ts-expect-error - Pinia store type inference issue
                userStore.setUser({ ...user.value });
                await nextTick();
                closeCropModal();
                showSuccess(data.message || 'Avatar updated successfully!');
            }
        } catch (err: any) {
            showError(err?.message || 'Upload failed.');
        } finally {
            avatarLoading.value = false;
            avatarFile.value = null;
        }
    }, 'image/jpeg', 0.7);
}
async function saveCroppedCover() {
    if (!coverCropper.value) return;
    (coverCropper.value as any).getCroppedCanvas({ width: 1200, height: 675, imageSmoothingQuality: 'high' }).toBlob(async (blob: any) => {
        if (!blob) return;
        coverFile.value = new File([blob], coverCropImageFile.value?.name || 'cover.jpg', { type: 'image/jpeg' });
        coverLoading.value = true;
        try {
            const options = { maxSizeMB: 1, maxWidthOrHeight: 1200, useWebWorker: true };
            const compressedFile = await imageCompression(coverFile.value, options);
            const formData = new FormData();
            formData.append('cover_photo', compressedFile);
            const userData = getUserData();
            const res = await fetch(`${APP.apiBaseURL}/user/upload-cover`, {
                method: 'POST',
                headers: { Authorization: userData?.token ? `Bearer ${userData.token}` : '' },
                body: formData,
            });
            const data = await res.json();
            if (data.code !== 1000) {
                showError(data.message || 'Upload failed.');
            } else {
                user.value.cover_photo = data.data.link;
                await nextTick();
                closeCoverCropModal();
                showSuccess(data.message || 'Cover photo updated successfully!');
            }
        } catch (err: any) {
            showError(err?.message || 'Upload failed.');
        } finally {
            coverLoading.value = false;
            coverFile.value = null;
        }
    }, 'image/jpeg', 0.8);
}
async function updateUser() {
    if (loading.value) return;
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    try {
        const payload: any = {
            name: user.value.name,
            phone: user.value.phone,
            avatar: user.value.avatar,
        };
        const res = await makeHttpReq<typeof payload, any>('user', 'PUT', payload);
        successMessage.value = res.message || 'Profile updated successfully!';
        // @ts-expect-error - Pinia store type inference issue
        userStore.setUser({ 
            id: user.value.id,
            name: user.value.name, 
            avatar: user.value.avatar, 
            friend_code: res.data.friend_code || null 
        });
    } catch (err: any) {
        errorMessage.value = err?.message || 'Update failed.';
    } finally {
        loading.value = false;
    }
}
function goToChangePassword() {
    router.push('/change-password');
}
onMounted(fetchUser);
</script>

<template>
    <div class="profile-page">
        <!-- Cover Photo Section -->
        <div class="cover-photo-section">
            <div 
                class="cover-photo" 
                :style="{ backgroundImage: user.cover_photo ? `url(${user.cover_photo})` : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }"
            >
                <button class="edit-cover-btn" @click="onCoverClick">
                    <i class="bi bi-camera-fill"></i>
                    Edit Cover
                </button>
                <input ref="coverFileInput" type="file" accept="image/*" style="display:none" @change="onCoverFileInputChange" />
            </div>
            
            <!-- Profile Card Overlay -->
            <div class="profile-card-overlay">
                <div class="profile-avatar-wrapper">
                    <img 
                        :src="getAvatarSrc(user.avatar, user.name)" 
                        :alt="user.name"
                        @click="onAvatarClick"
                        class="profile-avatar"
                    />
                    <button class="avatar-camera-btn" @click="onCameraClick" type="button">
                        <i class="bi bi-camera-fill"></i>
                    </button>
                    <div class="verified-badge">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <input ref="avatarFileInput" type="file" accept="image/*" style="display:none" @change="onAvatarFileInputChange" />
                </div>
                <div class="profile-info">
                    <div class="profile-name">{{ user.name || 'User' }} ❤️</div>
                    <div class="profile-email">{{ user.email || 'user@example.com' }}</div>
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
                <button class="edit-profile-btn" @click="updateUser">
                    Edit Profile
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs-section">
            <div class="nav-tabs">
                <button 
                    class="nav-tab" 
                    :class="{ active: activeTab === 'timeline' }"
                    @click="activeTab = 'timeline'"
                >
                    <i class="bi bi-clock"></i>
                    Timeline
                </button>
                <button 
                    class="nav-tab" 
                    :class="{ active: activeTab === 'about' }"
                    @click="activeTab = 'about'"
                >
                    <i class="bi bi-info-circle"></i>
                    About
                </button>
                <button 
                    class="nav-tab" 
                    :class="{ active: activeTab === 'friends' }"
                    @click="activeTab = 'friends'"
                >
                    <i class="bi bi-people"></i>
                    Friends
                </button>
                <button 
                    class="nav-tab" 
                    :class="{ active: activeTab === 'photos' }"
                    @click="activeTab = 'photos'"
                >
                    <i class="bi bi-images"></i>
                    Photos
                </button>
            </div>
            <div class="nav-actions">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search Here..." />
                </div>
                <button class="activity-feed-btn">
                    <i class="bi bi-list"></i>
                    Activity Feed
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="profile-content">
            <!-- Left Column - About -->
            <div class="left-column">
                <div class="about-card">
                    <div class="card-header">
                        <div>
                            <h6 class="card-title">About</h6>
                            <span class="card-subtitle">Intro My Self</span>
                        </div>
                        <button class="edit-icon-btn">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                    <div class="about-list">
                        <div class="about-item">
                            <i class="bi bi-briefcase"></i>
                            <div>
                                <div class="about-title">{{ aboutInfo.work.title }}</div>
                                <div class="about-location">{{ aboutInfo.work.location }}</div>
                            </div>
                        </div>
                        <div class="about-item">
                            <i class="bi bi-mortarboard"></i>
                            <div>
                                <div class="about-title">{{ aboutInfo.education.title }}</div>
                                <div class="about-location">{{ aboutInfo.education.location }}</div>
                            </div>
                        </div>
                        <div class="about-item">
                            <i class="bi bi-heart"></i>
                            <div>
                                <div class="about-title">{{ aboutInfo.relationship }}</div>
                            </div>
                        </div>
                        <div class="about-item">
                            <i class="bi bi-geo-alt"></i>
                            <div>
                                <div class="about-title">{{ aboutInfo.location.title }}</div>
                                <div class="about-location">{{ aboutInfo.location.duration }}</div>
                            </div>
                        </div>
                        <div class="about-item">
                            <i class="bi bi-droplet"></i>
                            <div>
                                <div class="about-title">{{ aboutInfo.bloodGroup }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="social-links">
                        <a href="#" class="social-link facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-link twitter"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="social-link whatsapp"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>
            </div>

            <!-- Center Column - Main Content -->
            <div class="center-column">
                <div v-if="activeTab === 'timeline'">
                    <CreatePost @postCreated="() => {}" />
                    <PostList />
                </div>
                <div v-else-if="activeTab === 'about'" class="about-tab-content">
                    <div class="about-detail-card">
                        <h5>About Me</h5>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    </div>
                </div>
                <div v-else-if="activeTab === 'friends'" class="friends-tab-content">
                    <div class="friends-header">
                        <h5 class="friends-title">Friends</h5>
                        <div class="friends-search-box">
                            <input 
                                type="text" 
                                placeholder="Find Friends..." 
                                v-model="friendsSearchQuery"
                                @input="fetchFriends"
                            />
                            <i class="bi bi-search"></i>
                            <button class="filter-btn">Filter</button>
                        </div>
                    </div>
                    <div v-if="friendsLoading" class="friends-loading">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Loading friends...
                    </div>
                    <div v-else-if="friendsList.length === 0" class="no-friends">
                        <p>No friends found</p>
                    </div>
                    <div v-else class="friends-grid">
                        <div v-for="friend in friendsList" :key="friend.id" class="friend-card">
                            <div class="friend-avatar-wrapper">
                                <img 
                                    :src="getAvatarSrc(friend.avatar, friend.name)" 
                                    :alt="friend.name"
                                    class="friend-avatar"
                                />
                                <div class="verified-badge-small">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                            </div>
                            <div class="friend-name">{{ friend.name }} ❤️</div>
                            <div class="friend-email">{{ friend.email }}</div>
                            <div class="friend-stats">
                                <div class="friend-stat-item">
                                    <div class="friend-stat-number">{{ friend.stats.following }}</div>
                                    <div class="friend-stat-label">Following</div>
                                </div>
                                <div class="friend-stat-divider"></div>
                                <div class="friend-stat-item">
                                    <div class="friend-stat-number">{{ friend.stats.likes }}</div>
                                    <div class="friend-stat-label">Likes</div>
                                </div>
                                <div class="friend-stat-divider"></div>
                                <div class="friend-stat-item">
                                    <div class="friend-stat-number">{{ friend.stats.followers }}</div>
                                    <div class="friend-stat-label">Followers</div>
                                </div>
                            </div>
                            <button class="view-profile-btn-small" @click="router.push(`/profile?user_id=${friend.id}`)">
                                View Profile
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else-if="activeTab === 'photos'" class="photos-tab-content">
                    <div class="photos-grid">
                        <div v-for="img in galleryImages" :key="img.id" class="photo-item">
                            <img :src="img.url" :alt="`Photo ${img.id}`" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Widgets -->
            <div class="right-column">
                <!-- College Meet Widget -->
                <div class="college-meet-card">
                    <div class="card-header">
                        <div>
                            <h6 class="card-title">College Meet</h6>
                            <span class="card-subtitle">Today Your Collge Group Meeting</span>
                        </div>
                        <div class="card-actions">
                            <button class="icon-btn-small">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button class="icon-btn-small">
                                <i class="bi bi-gear"></i>
                            </button>
                        </div>
                    </div>
                    <div class="meet-participants">
                        <div v-for="i in 4" :key="i" class="participant-avatar">
                            <img src="https://via.placeholder.com/50" :alt="`Participant ${i}`" />
                        </div>
                    </div>
                    <div class="meet-info">
                        <div class="meet-count">
                            <i class="bi bi-people"></i>
                            56 People
                        </div>
                        <div class="meet-location">
                            <i class="bi bi-geo-alt"></i>
                            Glasgow, Scotland
                        </div>
                    </div>
                    <div class="meet-description">
                        Lorem 5th Sept 2019 dummy text of the printing and typesetting industry.
                    </div>
                    <button class="send-invitation-btn">
                        Send Invitation
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                <!-- Gallery Widget -->
                <div class="gallery-card">
                    <div class="card-header">
                        <h6 class="card-title">Gallery <span class="count">156 Photos</span></h6>
                        <div class="card-actions">
                            <button class="icon-btn-small">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button class="icon-btn-small">
                                <i class="bi bi-gear"></i>
                            </button>
                        </div>
                    </div>
                    <div class="gallery-grid">
                        <div v-for="img in galleryImages" :key="img.id" class="gallery-item">
                            <img :src="img.url" :alt="`Gallery ${img.id}`" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Avatar Crop Modal -->
    <div v-if="showCropModal" class="modal-overlay">
        <div class="modal-cropper modal-cropper-edit-avatar modal-cropper-ui-strict">
            <div class="modal-cropper-header-ui-strict modal-cropper-header-ui-strict--with-border">
                <span class="modal-cropper-title">Edit Profile Picture</span>
                <button class="close-view-avatar-ui-strict" @click="closeCropModalWithConfirm">&times;</button>
            </div>
            <div class="cropper-container-ui-strict" style="position:relative;">
                <img :src="cropImageUrl" ref="cropperContainer" @load="onCropperReady" :style="{ opacity: avatarLoading ? 0.5 : 1 }" />
                <div v-if="avatarLoading" class="avatar-loading-overlay">
                    <svg class="spinner spinner-large" viewBox="0 0 50 50">
                        <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                    </svg>
                </div>
            </div>
            <div class="cropper-zoom-bar-ui-strict">
                <button type="button" class="zoom-btn" @click="zoomOut" :disabled="zoomValue <= minZoom">-</button>
                <input :type="'range'" :min="minZoom" :max="maxZoom" step="0.01" v-model.number="zoomValue" :value="zoomValue" @input="onZoomInput" />
                <button type="button" class="zoom-btn" @click="zoomIn" :disabled="zoomValue >= maxZoom">+</button>
            </div>
            <div class="modal-actions-ui-strict">
                <button @click="closeCropModalWithConfirm" class="cancel-ui-strict" :disabled="avatarLoading">Cancel</button>
                <button @click="saveCroppedAvatar" class="save-ui-strict" :disabled="avatarLoading">
                    <span v-if="avatarLoading">
                        <svg class="spinner spinner-btn" width="28" height="28" viewBox="0 0 50 50">
                            <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="6"></circle>
                        </svg>
                        Saving...
                    </span>
                    <span v-else>Save</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Cover Photo Crop Modal -->
    <div v-if="showCoverCropModal" class="modal-overlay">
        <div class="modal-cropper modal-cropper-edit-cover">
            <div class="modal-cropper-header-ui-strict modal-cropper-header-ui-strict--with-border">
                <span class="modal-cropper-title">Edit Cover Photo</span>
                <button class="close-view-avatar-ui-strict" @click="closeCoverCropModalWithConfirm">&times;</button>
            </div>
            <div class="cropper-container-cover" style="position:relative;">
                <img :src="coverCropImageUrl" ref="coverCropperContainer" @load="onCoverCropperReady" :style="{ opacity: coverLoading ? 0.5 : 1 }" />
                <div v-if="coverLoading" class="avatar-loading-overlay">
                    <svg class="spinner spinner-large" viewBox="0 0 50 50">
                        <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                    </svg>
                </div>
            </div>
            <div class="modal-actions-ui-strict">
                <button @click="closeCoverCropModalWithConfirm" class="cancel-ui-strict" :disabled="coverLoading">Cancel</button>
                <button @click="saveCroppedCover" class="save-ui-strict" :disabled="coverLoading">
                    <span v-if="coverLoading">
                        <svg class="spinner spinner-btn" width="28" height="28" viewBox="0 0 50 50">
                            <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="6"></circle>
                        </svg>
                        Saving...
                    </span>
                    <span v-else>Save</span>
                </button>
            </div>
        </div>
    </div>

    <!-- View Avatar Modal -->
    <div v-if="showViewAvatarModal" class="modal-overlay" @click.self="closeViewAvatarModal">
        <div class="modal-view-avatar">
            <img :src="getAvatarSrc(user.avatar, user.name)" alt="Avatar" style="max-width: 90vw; max-height: 80vh; border-radius: 16px;" />
            <button class="close-view-avatar" @click="closeViewAvatarModal">&times;</button>
        </div>
    </div>
</template>

<style scoped>
.profile-page {
    min-height: 100vh;
    background: #f0f2f5;
    padding-bottom: 40px;
}

/* Cover Photo Section */
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
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.edit-cover-btn:hover {
    background: rgba(0, 0, 0, 0.8);
}

.profile-card-overlay {
    position: absolute;
    bottom: -80px;
    left: 40px;
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    min-width: 280px;
    text-align: center;
}

.profile-avatar-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 16px;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fff;
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
    margin-bottom: 16px;
}

.profile-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #050505;
    margin-bottom: 4px;
}

.profile-email {
    font-size: 0.9rem;
    color: #65676b;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    padding: 16px 0;
    border-top: 1px solid #e4e6eb;
    border-bottom: 1px solid #e4e6eb;
    margin-bottom: 16px;
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

.edit-profile-btn {
    width: 100%;
    padding: 10px 16px;
    background: #1877f2;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background 0.2s;
}

.edit-profile-btn:hover {
    background: #166fe5;
}

/* Navigation Tabs */
.nav-tabs-section {
    max-width: 1400px;
    margin: 100px auto 0;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    border-radius: 12px;
    padding: 12px 20px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.nav-tabs {
    display: flex;
    gap: 8px;
}

.nav-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: #65676b;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
}

.nav-tab:hover {
    background: #f0f2f5;
}

.nav-tab.active {
    background: #1877f2;
    color: #fff;
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f0f2f5;
    border-radius: 20px;
    padding: 8px 16px;
}

.search-box i {
    color: #65676b;
}

.search-box input {
    border: none;
    background: transparent;
    outline: none;
    font-size: 0.9rem;
    width: 200px;
}

.activity-feed-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #f0f2f5;
    border: none;
    border-radius: 8px;
    color: #050505;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.2s;
}

.activity-feed-btn:hover {
    background: #e4e6eb;
}

/* Main Content */
.profile-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 320px 1fr 320px;
    gap: 20px;
    align-items: start;
}

/* Left Column - About */
.about-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #050505;
    margin: 0 0 4px 0;
}

.card-subtitle {
    font-size: 0.85rem;
    color: #65676b;
}

.edit-icon-btn {
    background: none;
    border: none;
    color: #65676b;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background 0.2s;
}

.edit-icon-btn:hover {
    background: #f0f2f5;
}

.about-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 20px;
}

.about-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.about-item i {
    font-size: 1.2rem;
    color: #65676b;
    margin-top: 2px;
}

.about-title {
    font-weight: 600;
    color: #050505;
    font-size: 0.95rem;
    margin-bottom: 2px;
}

.about-location {
    font-size: 0.85rem;
    color: #65676b;
}

.social-links {
    display: flex;
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid #e4e6eb;
}

.social-link {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
    text-decoration: none;
    transition: transform 0.2s;
}

.social-link:hover {
    transform: scale(1.1);
}

.social-link.facebook {
    background: #1877f2;
}

.social-link.twitter {
    background: #1da1f2;
}

.social-link.whatsapp {
    background: #25d366;
}

/* Center Column */
.center-column {
    min-width: 0;
}

/* Right Column */
.right-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.college-meet-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 20px;
    color: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.college-meet-card .card-header {
    margin-bottom: 16px;
}

.college-meet-card .card-title {
    color: #fff;
    font-size: 1.3rem;
}

.college-meet-card .card-subtitle {
    color: rgba(255, 255, 255, 0.9);
}

.college-meet-card .icon-btn-small {
    color: #fff;
}

.college-meet-card .icon-btn-small:hover {
    background: rgba(255, 255, 255, 0.2);
}

.meet-participants {
    display: flex;
    gap: -10px;
    margin-bottom: 16px;
}

.participant-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #fff;
    overflow: hidden;
    margin-left: -10px;
}

.participant-avatar:first-child {
    margin-left: 0;
}

.participant-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.meet-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 0.9rem;
}

.meet-description {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 16px;
    line-height: 1.5;
}

.send-invitation-btn {
    width: 100%;
    padding: 12px;
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    color: #fff;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.2s;
}

.send-invitation-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.gallery-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.gallery-card .card-title .count {
    font-weight: 400;
    color: #65676b;
    font-size: 0.85rem;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    margin-top: 12px;
}

.gallery-item {
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 4px;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
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

.card-actions {
    display: flex;
    gap: 4px;
}

/* Tab Content */
.about-tab-content,
.friends-tab-content,
.photos-tab-content {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.friends-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e4e6eb;
}

.friends-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #050505;
    margin: 0;
}

.friends-search-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f0f2f5;
    border-radius: 20px;
    padding: 8px 16px;
    flex: 1;
    max-width: 400px;
}

.friends-search-box input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-size: 0.9rem;
    color: #050505;
}

.friends-search-box i {
    color: #65676b;
    font-size: 1rem;
}

.filter-btn {
    padding: 6px 16px;
    background: #1877f2;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.2s;
}

.filter-btn:hover {
    background: #166fe5;
}

.friends-loading,
.no-friends {
    text-align: center;
    padding: 40px;
    color: #65676b;
}

.friends-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.friend-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.2s;
}

.friend-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.friend-avatar-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 16px;
}

.friend-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e4e6eb;
}

.verified-badge-small {
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
}

.verified-badge-small i {
    color: #fff;
    font-size: 0.9rem;
}

.friend-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #050505;
    margin-bottom: 6px;
}

.friend-email {
    font-size: 0.9rem;
    color: #65676b;
    margin-bottom: 16px;
}

.friend-stats {
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 16px 0;
    border-top: 1px solid #e4e6eb;
    border-bottom: 1px solid #e4e6eb;
    margin-bottom: 16px;
}

.friend-stat-item {
    text-align: center;
    flex: 1;
}

.friend-stat-divider {
    width: 1px;
    height: 30px;
    background: #e4e6eb;
}

.friend-stat-number {
    font-size: 1.2rem;
    font-weight: 700;
    color: #050505;
    margin-bottom: 4px;
}

.friend-stat-label {
    font-size: 0.85rem;
    color: #65676b;
}

.view-profile-btn-small {
    width: 100%;
    padding: 10px 16px;
    background: #1877f2;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background 0.2s;
}

.view-profile-btn-small:hover {
    background: #166fe5;
}

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.photo-item {
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
}

.photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Modals */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-cropper {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    min-width: 420px;
    max-width: 98vw;
    box-shadow: 0 4px 32px rgba(0, 0, 0, 0.15);
}

.modal-cropper-edit-cover {
    min-width: 800px;
    max-width: 95vw;
}

.cropper-container-cover {
    width: 100%;
    max-height: 60vh;
    margin: 24px 0;
}

.cropper-container-cover img {
    max-width: 100%;
    max-height: 60vh;
    display: block;
}

.modal-cropper-header-ui-strict {
    width: 100%;
    padding: 0 0 16px 0;
    border-bottom: 1.5px solid #e5e7eb;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-cropper-title {
    font-size: 1.18rem;
    font-weight: 600;
}

.close-view-avatar-ui-strict {
    background: none;
    border: none;
    font-size: 2rem;
    color: #222;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
}

.close-view-avatar-ui-strict:hover {
    background: #f0f2f5;
    color: #e11d48;
}

.cropper-container-ui-strict {
    width: 320px;
    height: 320px;
    margin: 24px auto;
    border-radius: 50%;
    overflow: hidden;
    background: #f3f4f6;
}

.cropper-container-ui-strict img {
    width: 320px;
    height: 320px;
    object-fit: cover;
    display: block;
}

.cropper-zoom-bar-ui-strict {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
    margin: 16px 0;
}

.cropper-zoom-bar-ui-strict input[type="range"] {
    flex: 1;
    accent-color: #1877f2;
    height: 3px;
}

.zoom-btn {
    font-size: 1.5rem;
    color: #1877f2;
    font-weight: 700;
    width: 32px;
    height: 32px;
    border: none;
    background: none;
    cursor: pointer;
}

.zoom-btn:disabled {
    color: #bbb;
    cursor: not-allowed;
}

.modal-actions-ui-strict {
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    margin-top: 16px;
}

.cancel-ui-strict {
    padding: 10px 32px;
    background: #f3f4f6;
    color: #222;
    border: none;
    border-radius: 12px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
}

.cancel-ui-strict:hover {
    background: #e5e7eb;
}

.save-ui-strict {
    padding: 10px 32px;
    background: #1877f2;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
}

.save-ui-strict:hover {
    background: #166fe5;
}

.save-ui-strict:disabled {
    background: #a5b4fc;
    cursor: not-allowed;
}

.modal-view-avatar {
    position: relative;
    background: #fff;
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.18);
}

.close-view-avatar {
    position: absolute;
    top: 8px;
    right: 8px;
    background: none;
    border: none;
    font-size: 2rem;
    color: #222;
    cursor: pointer;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
}

.close-view-avatar:hover {
    background: #f0f2f5;
    color: #e11d48;
}

.avatar-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.5);
    z-index: 10;
}

.spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    100% {
        transform: rotate(360deg);
    }
}

.spinner .path {
    stroke: #1877f2;
    stroke-linecap: round;
    stroke-dasharray: 90, 150;
    stroke-dashoffset: 0;
}

.spinner-large {
    width: 48px;
    height: 48px;
}

.spinner-btn {
    width: 28px;
    height: 28px;
    margin-right: 8px;
}

.spinner-btn .path {
    stroke: #fff;
    stroke-width: 6;
}

@media (max-width: 1200px) {
    .profile-content {
        grid-template-columns: 280px 1fr;
    }
    
    .right-column {
        display: none;
    }
}

@media (max-width: 768px) {
    .profile-content {
        grid-template-columns: 1fr;
        padding: 0 12px;
    }
    
    .nav-tabs-section {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .nav-tabs {
        flex-wrap: wrap;
    }
    
    .nav-actions {
        width: 100%;
    }
    
    .search-box {
        flex: 1;
    }
    
    .profile-card-overlay {
        left: 50%;
        transform: translateX(-50%);
        min-width: 260px;
    }
    
    .cover-photo {
        height: 300px;
    }
    
    .nav-tabs-section {
        margin-top: 80px;
    }
}
</style>
