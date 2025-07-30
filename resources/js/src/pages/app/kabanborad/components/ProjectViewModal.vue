<template>
    <Teleport to="body">
        <div v-if="visible" class="modal-backdrop" @click="handleBackdropClick">
            <div class="modal-dialog" @click.stop>
                <div class="modal-content">
                    <!-- Header -->
                    <div class="modal-header">
                        <div class="header-content">
                            <div class="header-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="header-text">
                                <h3 class="modal-title">Project Details</h3>
                            </div>
                        </div>
                        <button type="button" class="btn-close" @click="$emit('close')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">
                        <!-- Project Overview -->
                        <div class="project-overview">
                            <h2 class="project-name">{{ projectData?.name || 'N/A' }}</h2>
                            <div class="project-meta">
                                <span class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    {{ formatDate(projectData?.startDate) }} - {{ formatDate(projectData?.endDate) }}
                                </span>
                                <span class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    Created {{ formatDate(projectData?.created_at) }}
                                </span>
                            </div>
                        </div>

                        <!-- Project Description -->
                        <div v-if="projectData?.content" class="content-section">
                            <h4 class="section-title">
                                <i class="fas fa-align-left"></i>
                                Description
                            </h4>
                            <div class="content-text">
                                {{ projectData.content }}
                            </div>
                        </div>

                        <!-- Project Members -->
                        <div class="members-section">
                            <div class="section-header">
                                <h4 class="section-title">
                                    <i class="fas fa-users"></i>
                                    Team Members
                                </h4>
                            </div>
                            <div class="members-container">
                                <div v-if="projectMembers.length > 0" class="members-avatars">
                                    <div v-for="member in projectMembers" :key="member.id"
                                        class="member-avatar-wrapper">
                                        <div class="member-avatar" :title="member.name">
                                            <img v-if="member.avatar" :src="member.avatar" :alt="member.name" />
                                            <div v-else class="avatar-placeholder">
                                                {{ getInitials(member.name) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="empty-members">
                                    <div class="empty-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <h5>No members assigned</h5>
                                    <p>This project doesn't have any team members yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" @click="$emit('close')">
                            <i class="fas fa-check"></i>
                            Got it
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, watch, onUnmounted } from 'vue';

const props = defineProps<{
    visible: boolean;
    projectData: any;
}>();

const emit = defineEmits(['close']);

const projectMembers = computed(() => {
    if (!props.projectData?.users) return [];
    return props.projectData.users;
});

function formatDate(dateString: string) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getInitials(name: string) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function handleBackdropClick() {
    emit('close');
}

// Watch for visible changes to manage body class
watch(() => props.visible, (newVisible) => {
    if (newVisible) {
        // Add class to body when modal is opened
        document.body.classList.add('modal-open');
    } else {
        // Remove class from body when modal is closed
        document.body.classList.remove('modal-open');
    }
});

onUnmounted(() => {
    // Remove class from body when component is unmounted
    document.body.classList.remove('modal-open');
});
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999999999 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    overflow: hidden;
    /* Ensure modal is above all elements including Kanban page */
    isolation: isolate;
}

.modal-dialog {
    max-width: 850px;
    width: 100%;
    height: 66vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.modal-content {
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    animation: modalSlideIn 0.3s ease-out;
    height: 100vh;
    z-index: 1000000000 !important;
    /* Ensure content is above all elements */
    isolation: isolate;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Header */
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: #3b82f6;
    color: white;
    flex-shrink: 0;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-icon {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.modal-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.btn-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 1rem;
    cursor: pointer;
    padding: 0.375rem;
    border-radius: 4px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
}

.btn-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Body */
.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    min-height: 0;
}

/* Project Overview */
.project-overview {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.project-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
}

.project-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.meta-item i {
    color: #3b82f6;
    font-size: 0.875rem;
}

/* Content Section */
.content-section {
    margin-bottom: 1rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.section-title i {
    color: #3b82f6;
    font-size: 0.75rem;
}

.content-text {
    color: #374151;
    line-height: 1.5;
    font-size: 0.9rem;
    max-height: 272px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.content-text::-webkit-scrollbar {
    width: 4px;
}

.content-text::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 2px;
}

.content-text::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.content-text::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Members Section */
.members-section {
    margin-bottom: 0;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.member-count {
    background: #e0f2fe;
    color: #0284c7;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.4rem;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.members-container {
    margin-top: 0.25rem;
}

.members-avatars {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.125rem 0 0.25rem 0;
}

.member-avatar-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.member-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #e5e7eb;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: box-shadow 0.2s;
    cursor: pointer;
    position: relative;
}

.member-avatar:hover {
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.15);
}

.member-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}

/* Empty State */
.empty-members {
    text-align: center;
    padding: 1rem;
    color: #6b7280;
}

.empty-icon {
    width: 32px;
    height: 32px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-size: 0.875rem;
    color: #9ca3af;
}

.empty-members h5 {
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 0.25rem 0;
}

.empty-members p {
    font-size: 0.7rem;
    margin: 0;
    opacity: 0.8;
}

/* Footer */
.modal-footer {
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    flex-shrink: 0;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

/* Responsive */
@media (max-width: 1024px) {
    .modal-dialog {
        max-width: 95vw;
    }
}

@media (max-width: 768px) {
    .modal-backdrop {
        padding: 1rem;
    }

    .modal-dialog {
        max-width: 95vw;
        height: auto;
        max-height: 85vh;
        margin: 0;
    }

    .modal-content {
        height: auto;
        max-height: 85vh;
    }

    .modal-header {
        padding: 0.75rem 1rem;
    }

    .header-icon {
        width: 32px;
        height: 32px;
        font-size: 1rem;
    }

    .modal-title {
        font-size: 1rem;
    }

    .btn-close {
        width: 32px;
        height: 32px;
        font-size: 1rem;
    }

    .modal-body {
        padding: 1rem;
        max-height: 60vh;
        overflow-y: auto;
    }

    .project-name {
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
    }

    .project-meta {
        flex-direction: column;
        gap: 0.5rem;
    }

    .meta-item {
        font-size: 0.875rem;
    }

    .content-text {
        max-height: 270px;
        font-size: 0.9rem;
    }

    .members-avatars {
        gap: 0.75rem;
        justify-content: center;
    }

    .member-avatar {
        width: 44px;
        height: 44px;
    }

    .modal-footer {
        padding: 0.75rem 1rem;
    }

    .btn {
        width: 100%;
        justify-content: center;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
}
</style>