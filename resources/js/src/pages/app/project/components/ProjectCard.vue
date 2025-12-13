<script setup lang="ts">
import { defineProps } from 'vue';
import { ProjectType } from '../actions/GetProject';
import { RouterLink, useRouter } from 'vue-router';
import { getAvatarSrc } from '../../../../helper/avatar';

const props = defineProps<{
    project: ProjectType;
    currentUserId: number | null;
    isPinned?: boolean; // Thêm prop để biết project có đang pin không
}>();

// Format date as DD/MM/YYYY
function formatDate(dateStr: string | undefined): string {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

const router = useRouter();

// Navigate to Kaban board for this project
function goToKaban() {
    router.push(`/kaban?query=${props.project.slug}`);
}

// Prevent click event from bubbling up from action buttons
function stopPropagation(e: Event) {
    e.stopPropagation();
}
</script>

<template>
    <div class="project-card clickable-card" :class="{ 'pinned-project': isPinned }" @click="goToKaban">
        <!-- Pin indicator ribbon -->
        <div v-if="isPinned" class="pin-ribbon">
            <i class="bi bi-pin-fill"></i>
        </div>
        
        <div class="project-card-header">
            <div class="project-title-wrapper">
                <h3 class="project-title">{{ project.name }}</h3>
                <span v-if="isPinned" class="pinned-badge" title="This project is pinned to dashboard">
                    <i class="bi bi-pin-fill"></i>
                    <span class="badge-text">Pinned</span>
                </span>
            </div>
        </div>
        
        <div class="project-meta-section">
            <div class="meta-row">
                <div class="meta-item">
                    <i class="bi bi-calendar-event meta-icon"></i>
                    <span class="meta-label">Start:</span>
                    <span class="meta-value">{{ formatDate(project.startDate) }}</span>
                </div>
                <div class="meta-item">
                    <i class="bi bi-calendar-check meta-icon"></i>
                    <span class="meta-label">End:</span>
                    <span class="meta-value">{{ formatDate(project.endDate) }}</span>
                </div>
            </div>
            <div class="meta-item meta-item-full">
                <i class="bi bi-person-circle meta-icon"></i>
                <span class="meta-label">Creator:</span>
                <span class="meta-value">{{ project.creator?.name || 'N/A' }}</span>
            </div>
        </div>
        
        <div class="project-bottom-section">
            <div class="project-members-section" v-if="project.users && project.users.length > 0">
                <div class="members-label">
                    <i class="bi bi-people"></i>
                </div>
                <div class="project-members">
                    <template v-for="user in project.users" :key="user.id">
                        <div class="member-avatar-wrapper" :title="user.name">
                            <img v-if="'avatar' in user && typeof user.avatar === 'string' && user.avatar"
                                :src="getAvatarSrc(user.avatar, user.name)" class="member-avatar" :alt="user.name" />
                            <span v-else class="member-avatar member-avatar-fallback">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                    </template>
                </div>
            </div>
            
            <div class="project-progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress</span>
                    <span class="progress-percentage">{{ project?.task_progress?.progress || 0 }}%</span>
                </div>
                <div class="project-progress">
                    <div class="progress custom-progress" role="progressbar" :aria-valuenow="project?.task_progress?.progress"
                        aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-gradient-success"
                            :style="{ width: (project?.task_progress?.progress || 0) + '%' }">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="project-actions">
            <button v-if="currentUserId !== null && project.creator?.id === currentUserId"
                @click.stop="stopPropagation($event); $emit('editProject', project)" type="button"
                class="btn action-btn action-btn-edit" title="Edit Project">
                <i class="bi bi-pencil-square"></i>
            </button>
            <button @click.stop="stopPropagation($event); $emit('pinnedProject', project.id)" type="button"
                class="btn action-btn action-btn-pin" :class="{ 'pinned-active': isPinned }" 
                :title="isPinned ? 'Unpin from dashboard' : 'Pin to dashboard'">
                <i :class="isPinned ? 'bi bi-pin-fill' : 'bi bi-pin-angle'"></i>
            </button>
            <RouterLink class="btn action-btn action-btn-view" :to="'/kaban?query=' + project.slug" title="View Details"
                @click.stop="stopPropagation($event)">
                <i class="bi bi-eye"></i>
            </RouterLink>
            <button v-if="currentUserId !== null && project.creator?.id === currentUserId"
                @click.stop="stopPropagation($event); $emit('deleteProject', project.id)" type="button"
                class="btn action-btn action-btn-delete" title="Delete Project">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>

<style scoped>
.project-card {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    padding: 1rem;
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.project-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.project-card:hover::before {
    opacity: 1;
}

.project-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: #d1d5db;
    transform: translateY(-1px);
}

/* Pin ribbon */
.pin-ribbon {
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 0 40px 40px 0;
    border-color: transparent #fbbf24 transparent transparent;
    z-index: 1;
}

.pin-ribbon i {
    position: absolute;
    top: 6px;
    right: -32px;
    color: white;
    font-size: 0.875rem;
    transform: rotate(45deg);
}

.project-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0;
    position: relative;
    z-index: 2;
}

.project-title-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    flex: 1;
}

.project-title {
    font-weight: 700;
    color: #111827;
    font-size: 1.125rem;
    word-break: break-word;
    letter-spacing: -0.01em;
    margin: 0;
    line-height: 1.4;
}

.project-meta-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.625rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    margin-bottom: 0;
}

.meta-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: #6b7280;
}

.meta-item-full {
    width: 100%;
}

.meta-icon {
    color: #6366f1;
    font-size: 0.875rem;
    width: 16px;
    text-align: center;
    flex-shrink: 0;
}

.meta-label {
    font-weight: 500;
    color: #9ca3af;
    font-size: 0.75rem;
}

.meta-value {
    font-weight: 600;
    color: #374151;
    font-size: 0.8125rem;
}

.project-bottom-section {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}

.project-members-section {
    margin-bottom: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.members-label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    flex-shrink: 0;
}

.members-label i {
    color: #6366f1;
    font-size: 0.875rem;
}

.project-members {
    display: flex;
    gap: -0.25rem;
    flex-wrap: wrap;
    flex: 1;
}

.member-avatar-wrapper {
    position: relative;
    transition: transform 0.2s ease;
    margin-left: -0.25rem;
}

.member-avatar-wrapper:first-child {
    margin-left: 0;
}

.member-avatar-wrapper:hover {
    transform: scale(1.15);
    z-index: 10;
}

.member-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
    border: 2px solid white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.member-avatar-fallback {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.project-progress-section {
    margin-bottom: 0;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.375rem;
}

.progress-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6b7280;
}

.progress-percentage {
    font-size: 0.875rem;
    font-weight: 700;
    color: #6366f1;
}

.project-progress {
    margin-bottom: 0;
}

.custom-progress {
    border-radius: 0.5rem;
    background: #e5e7eb;
    height: 8px;
    overflow: hidden;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
}

.bg-gradient-success {
    background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    height: 100%;
    border-radius: 0.5rem;
    transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.bg-gradient-success::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.project-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 0.25rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}

.action-btn {
    border-radius: 0.5rem;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9375rem;
    border: 1.5px solid transparent;
    transition: all 0.2s ease;
    background: #f9fafb;
    padding: 0;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

/* Edit (blue) */
.action-btn-edit {
    color: #2563eb;
    border-color: #dbeafe;
}

.action-btn-edit:hover {
    background: #dbeafe;
    color: #1d4ed8;
    border-color: #93c5fd;
}

/* Pin (yellow/gold) */
.action-btn-pin {
    color: #eab308;
    border-color: #fef3c7;
}

.action-btn-pin:hover {
    background: #fef3c7;
    color: #ca8a04;
    border-color: #fcd34d;
    transform: translateY(-1px) rotate(15deg);
}

.action-btn-pin.pinned-active {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    border-color: #f59e0b;
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3);
}

.action-btn-pin.pinned-active:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    transform: translateY(-1px) rotate(45deg);
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
}

/* View (green) */
.action-btn-view {
    color: #16a34a;
    border-color: #dcfce7;
    text-decoration: none;
}

.action-btn-view:hover {
    background: #dcfce7;
    color: #15803d;
    border-color: #86efac;
}

/* Delete (red) */
.action-btn-delete {
    color: #dc2626;
    border-color: #fee2e2;
}

.action-btn-delete:hover {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #fca5a5;
}

.clickable-card {
    cursor: pointer;
}

.clickable-card:hover {
    background: #fafafa;
}

/* Pinned badge */
.pinned-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.75rem;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    box-shadow: 0 2px 6px rgba(251, 191, 36, 0.3);
    white-space: nowrap;
}

.pinned-badge i {
    font-size: 0.75rem;
}

.badge-text {
    font-size: 0.625rem;
}

/* Pinned project highlight */
.pinned-project {
    border: 1.5px solid #fbbf24;
    background: linear-gradient(to bottom, #fffbeb 0%, #ffffff 50%);
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.15);
    position: relative;
}

.pinned-project::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
    opacity: 1;
}

.pinned-project:hover {
    box-shadow: 0 6px 16px rgba(251, 191, 36, 0.25);
    border-color: #f59e0b;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .project-card {
        padding: 0.875rem;
        border-radius: 0.875rem;
    }
    
    .project-title {
        font-size: 1rem;
    }
    
    .project-meta-section {
        padding: 0.5rem;
        gap: 0.375rem;
    }
    
    .meta-row {
        grid-template-columns: 1fr;
        gap: 0.375rem;
    }
    
    .action-btn {
        width: 1.875rem;
        height: 1.875rem;
        font-size: 0.875rem;
    }
    
    .pinned-badge .badge-text {
        display: none;
    }
    
    .member-avatar {
        width: 24px;
        height: 24px;
        font-size: 0.6875rem;
    }
}
</style>