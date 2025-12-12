<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
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
            <div class="meta-item">
                <i class="bi bi-person-circle meta-icon"></i>
                <span class="meta-label">Creator:</span>
                <span class="meta-value">{{ project.creator?.name || 'N/A' }}</span>
            </div>
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
        
        <div class="project-members-section" v-if="project.users && project.users.length > 0">
            <div class="members-label">
                <i class="bi bi-people"></i>
                <span>Team Members</span>
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
    border-radius: 1.25rem;
    border: 1.5px solid #eef2f7;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
    padding: 1.4rem 1.35rem;
    margin-bottom: 1.1rem;
    font-size: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.project-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.project-card:hover::before {
    opacity: 1;
}

.project-card:hover {
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.1);
    border-color: #e2e8f0;
    transform: translateY(-2px);
}

/* Pin ribbon */
.pin-ribbon {
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 0 50px 50px 0;
    border-color: transparent #fbbf24 transparent transparent;
    z-index: 1;
}

.pin-ribbon i {
    position: absolute;
    top: 8px;
    right: -40px;
    color: white;
    font-size: 1rem;
    transform: rotate(45deg);
}

.project-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.25rem;
    position: relative;
    z-index: 2;
}

.project-title-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    flex: 1;
}

.project-title {
    font-weight: 700;
    color: #1e293b;
    font-size: 1.35rem;
    word-break: break-word;
    letter-spacing: -0.01em;
    margin: 0;
    line-height: 1.3;
}

.project-meta-section {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    padding: 0.85rem 0.9rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    margin-bottom: 0.35rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #64748b;
}

.meta-icon {
    color: #6366f1;
    font-size: 1rem;
    width: 18px;
    text-align: center;
}

.meta-label {
    font-weight: 500;
    color: #94a3b8;
}

.meta-value {
    font-weight: 600;
    color: #334155;
}

.project-members-section {
    margin-bottom: 0.5rem;
}

.members-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.75rem;
}

.members-label i {
    color: #6366f1;
}

.project-members {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.member-avatar-wrapper {
    position: relative;
    transition: transform 0.2s ease;
}

.member-avatar-wrapper:hover {
    transform: scale(1.1);
    z-index: 10;
}

.member-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    font-weight: 600;
    color: white;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.member-avatar-fallback {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.project-progress-section {
    margin-bottom: 0.35rem;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.4rem;
}

.progress-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #64748b;
}

.progress-percentage {
    font-size: 0.95rem;
    font-weight: 700;
    color: #6366f1;
}

.project-progress {
    margin-bottom: 0;
}

.custom-progress {
    border-radius: 1rem;
    background: #e2e8f0;
    height: 10px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
}

.bg-gradient-success {
    background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    height: 100%;
    border-radius: 1rem;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
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
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
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
    gap: 0.75rem;
    margin-top: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.action-btn {
    border-radius: 0.75rem;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    border: 2px solid transparent;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    background: #f8fafc;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Edit (blue) */
.action-btn-edit {
    color: #2563eb;
    border-color: #dbeafe;
}

.action-btn-edit:hover {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1d4ed8;
    border-color: #93c5fd;
}

/* Pin (yellow/gold) */
.action-btn-pin {
    color: #eab308;
    border-color: #fef3c7;
}

.action-btn-pin:hover {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #ca8a04;
    border-color: #fcd34d;
    transform: translateY(-2px) rotate(15deg);
}

.action-btn-pin.pinned-active {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    border-color: #f59e0b;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
}

.action-btn-pin.pinned-active:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    transform: translateY(-2px) rotate(45deg);
    box-shadow: 0 6px 16px rgba(251, 191, 36, 0.5);
}

/* View (green) */
.action-btn-view {
    color: #16a34a;
    border-color: #dcfce7;
    text-decoration: none;
}

.action-btn-view:hover {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #15803d;
    border-color: #86efac;
}

/* Delete (red) */
.action-btn-delete {
    color: #dc2626;
    border-color: #fee2e2;
}

.action-btn-delete:hover {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #b91c1c;
    border-color: #fca5a5;
}

.clickable-card {
    cursor: pointer;
}

.clickable-card:hover {
    background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
}

/* Pinned badge */
.pinned-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    padding: 0.35rem 0.75rem;
    border-radius: 1.25rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.35);
    animation: pulse-badge 2s ease-in-out infinite;
    white-space: nowrap;
}

.pinned-badge i {
    font-size: 0.85rem;
}

.badge-text {
    font-size: 0.7rem;
}

@keyframes pulse-badge {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(251, 191, 36, 0.35);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 6px 16px rgba(251, 191, 36, 0.5);
    }
}

/* Pinned project highlight */
.pinned-project {
    border: 2px solid #fbbf24;
    background: linear-gradient(to bottom, #fffbeb 0%, #ffffff 50%);
    box-shadow: 0 8px 24px rgba(251, 191, 36, 0.2);
    position: relative;
}

.pinned-project::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
    opacity: 1;
}

.pinned-project:hover {
    box-shadow: 0 12px 32px rgba(251, 191, 36, 0.3);
    border-color: #f59e0b;
    transform: translateY(-3px);
}

/* Responsive */
@media (max-width: 768px) {
    .project-card {
        padding: 1.25rem;
        border-radius: 1.25rem;
    }
    
    .project-title {
        font-size: 1.2rem;
    }
    
    .project-meta-section {
        padding: 0.75rem;
        gap: 0.5rem;
    }
    
    .action-btn {
        width: 2.25rem;
        height: 2.25rem;
        font-size: 1rem;
    }
    
    .pinned-badge .badge-text {
        display: none;
    }
}
</style>