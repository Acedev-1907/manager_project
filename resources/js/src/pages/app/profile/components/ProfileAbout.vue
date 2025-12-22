<script setup lang="ts">
interface Props {
    isCurrentUser: boolean;
    aboutInfo: {
        work: { title: string; location: string };
        education: { title: string; location: string };
        relationship: string;
        location: { title: string; duration: string };
        bloodGroup: string;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <div class="left-column">
        <div class="about-card">
            <div class="card-header">
                <div>
                    <h6 class="card-title">About</h6>
                    <span class="card-subtitle">Intro My Self</span>
                </div>
                <button v-if="props.isCurrentUser" class="edit-icon-btn">
                    <i class="bi bi-pencil"></i>
                </button>
            </div>
            <div class="about-list">
                <div class="about-item">
                    <i class="bi bi-briefcase"></i>
                    <div>
                        <div class="about-title">{{ props.aboutInfo.work.title }}</div>
                        <div class="about-location">{{ props.aboutInfo.work.location }}</div>
                    </div>
                </div>
                <div class="about-item">
                    <i class="bi bi-mortarboard"></i>
                    <div>
                        <div class="about-title">{{ props.aboutInfo.education.title }}</div>
                        <div class="about-location">{{ props.aboutInfo.education.location }}</div>
                    </div>
                </div>
                <div class="about-item">
                    <i class="bi bi-heart"></i>
                    <div>
                        <div class="about-title">{{ props.aboutInfo.relationship }}</div>
                    </div>
                </div>
                <div class="about-item">
                    <i class="bi bi-geo-alt"></i>
                    <div>
                        <div class="about-title">{{ props.aboutInfo.location.title }}</div>
                        <div class="about-location">{{ props.aboutInfo.location.duration }}</div>
                    </div>
                </div>
                <div class="about-item">
                    <i class="bi bi-droplet"></i>
                    <div>
                        <div class="about-title">{{ props.aboutInfo.bloodGroup }}</div>
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
</template>

<style scoped>
.left-column {
    min-width: 0;
}

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
</style>

