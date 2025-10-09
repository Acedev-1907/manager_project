<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
import { ProjectType } from '../actions/GetProject';
import { RouterLink, useRouter } from 'vue-router';
import { APP } from '../../../../App/APP';
import { getAvatarSrc } from '../../../../helper/avatar';

const props = defineProps<{
    project: ProjectType;
    currentUserId: number | null;
    isPinned?: boolean; // Thêm prop để biết project có đang pin không
}>();

const emit = defineEmits<{
    (e: 'editProject', project: ProjectType): void;
    (e: 'deleteProject', projectId: number): void;
    (e: 'pinnedProject', projectId: number): void;
    (e: 'viewProjectDetail', projectId: number): void;
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
        <div class="project-card-header">
            <div class="project-title">
                {{ project.name }}
                <span v-if="isPinned" class="pinned-badge" title="This project is pinned">
                    <i class="bi bi-pin-fill"></i>
                    Pinned
                </span>
            </div>
        </div>
        <div class="project-meta-row">
            <span class="creator">Creator: <b>{{ project.creator?.name || 'N/A' }}</b></span>
            <span class="date-group">
                <span class="date">Start: {{ formatDate(project.startDate) }}</span>
                <span class="date">End: {{ formatDate(project.endDate) }}</span>
            </span>
        </div>
        <div class="project-members">
            <template v-for="user in project.users" :key="user.id">
                <img v-if="'avatar' in user && typeof user.avatar === 'string' && user.avatar"
                    :src="getAvatarSrc(user.avatar, user.name)" class="member-avatar" :alt="user.name"
                    :title="user.name" />
                <span v-else class="member-avatar member-avatar-fallback" :title="user.name">
                    {{ user.name.charAt(0).toUpperCase() }}
                </span>
            </template>
        </div>
        <div class="project-progress">
            <div class="progress custom-progress" role="progressbar" :aria-valuenow="project?.task_progress?.progress"
                aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-gradient-success"
                    :style="{ width: (project?.task_progress?.progress || 0) + '%' }">
                    {{ project?.task_progress?.progress || 0 }} %
                </div>
            </div>
        </div>
        <div class="project-actions">
            <button v-if="currentUserId !== null && project.creator?.id === currentUserId"
                @click.stop="stopPropagation($event); $emit('editProject', project)" type="button"
                class="btn action-btn action-btn-edit" title="Edit">
                <i class="bi bi-pencil-square"></i>
            </button>
            <button @click.stop="stopPropagation($event); $emit('pinnedProject', project.id)" type="button"
                class="btn action-btn action-btn-pin" :class="{ 'pinned-active': isPinned }" 
                :title="isPinned ? 'Pinned (Click to pin again)' : 'Pin this project'">
                <i :class="isPinned ? 'bi bi-pin-fill' : 'bi bi-pin-angle'"></i>
            </button>
            <RouterLink class="btn action-btn action-btn-view" :to="'/kaban?query=' + project.slug" title="View"
                @click.stop="stopPropagation($event)">
                <i class="bi bi-eye"></i>
            </RouterLink>
            <button v-if="currentUserId !== null && project.creator?.id === currentUserId"
                @click.stop="stopPropagation($event); $emit('deleteProject', project.id)" type="button"
                class="btn action-btn action-btn-delete" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>

<style scoped>
.project-card {
    background: #fff;
    border-radius: 1.25rem;
    border: 1.5px solid #f1f3f6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    padding: 1.5rem 1.2rem 1.2rem 1.2rem;
    margin-bottom: 1.1rem;
    font-size: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
    transition: box-shadow 0.22s, border 0.18s;
}

.project-card:hover {
    box-shadow: 0 6px 24px rgba(34, 34, 59, 0.13);
    border: 1.5px solid #dbeafe;
}

.project-card-header {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    margin-bottom: 0.5rem;
}

.project-title {
    font-weight: 700;
    color: #22223b;
    font-size: 1.18rem;
    word-break: break-word;
    letter-spacing: 0.01em;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* Dates and creator on one row */
.project-meta-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1.2rem;
    font-size: 0.97rem;
    color: #555;
}

.date-group {
    display: flex;
    gap: 0.7rem;
}

.project-members {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.member-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    background: #e0e7ef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2563eb;
}

.member-avatar-fallback {
    background: #e0e7ef;
    color: #2563eb;
}

.project-progress {
    margin-bottom: 0.5rem;
}

.custom-progress {
    border-radius: 0.875rem;
    background: #f1f3f6;
    height: 1.1rem;
    overflow: hidden;
}

.bg-gradient-success {
    background: linear-gradient(90deg, #4ade80 0%, #22d3ee 100%);
    color: #fff;
    font-weight: 500;
    border-radius: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: width 0.4s cubic-bezier(.4, 2.3, .3, 1);
}

.project-actions {
    display: flex;
    align-items: center;
    gap: 1.1rem;
    margin-top: 0.5rem;
}

.action-btn {
    border-radius: 50%;
    width: 2.1rem;
    height: 2.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin: 0 0.1rem;
    border: 2px solid transparent;
    transition: background 0.18s, color 0.18s, border 0.18s;
}

/* Edit (blue) */
.action-btn-edit {
    border-color: #2563eb;
    color: #2563eb;
}

.action-btn-edit:hover {
    background: #e0edff;
    color: #2563eb;
    border-color: #2563eb;
}

/* Pin (yellow) */
.action-btn-pin {
    border-color: #eab308;
    color: #eab308;
}

.action-btn-pin:hover {
    background: #fffbe7;
    color: #eab308;
    border-color: #eab308;
}

/* View (green) */
.action-btn-view {
    border-color: #16a34a;
    color: #16a34a;
}

.action-btn-view:hover {
    background: #e6faed;
    color: #16a34a;
    border-color: #16a34a;
}

/* Delete (red) */
.action-btn-delete {
    border-color: #dc2626;
    color: #dc2626;
}

.action-btn-delete:hover {
    background: #ffeaea;
    color: #dc2626;
    border-color: #dc2626;
}

.clickable-card {
    cursor: pointer;
    transition: box-shadow 0.22s, border 0.18s, background 0.18s;
}

.clickable-card:hover {
    background: #f6faff;
}

/* Pinned badge */
.pinned-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    padding: 0.25rem 0.65rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3);
    animation: pulse-badge 2s ease-in-out infinite;
}

.pinned-badge i {
    font-size: 0.85rem;
}

@keyframes pulse-badge {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* Pinned project highlight */
.pinned-project {
    border: 2px solid #fbbf24;
    background: linear-gradient(to bottom, #fffbeb 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(251, 191, 36, 0.15);
}

.pinned-project:hover {
    box-shadow: 0 6px 24px rgba(251, 191, 36, 0.25);
    border-color: #f59e0b;
}

/* Pin button active state */
.action-btn-pin.pinned-active {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    border-color: #f59e0b;
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4);
}

.action-btn-pin.pinned-active:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    border-color: #d97706;
    transform: rotate(45deg);
    transition: all 0.3s ease;
}
</style>