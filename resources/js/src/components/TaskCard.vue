<script setup lang="ts">
import { getAvatarSrc } from '../helper/avatar';
const props = defineProps<{ task: any }>();
</script>

<template>
    <div class="task-members">
        <template v-for="user in props.task.members" :key="user.id">
            <img v-if="user.avatar && typeof user.avatar === 'string' && user.avatar.trim() !== ''"
                :src="getAvatarSrc(user.avatar, user.name)" class="member-avatar" :alt="user.name" :title="user.name" />
            <span v-else class="member-avatar member-avatar-fallback" :title="user.name">
                {{ user.name.charAt(0).toUpperCase() }}
            </span>
            <span style="display:none">{{ console.log('Avatar:', user.avatar, 'Name:', user.name) }}</span>
        </template>
    </div>
</template>

<style scoped>
.task-members {
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
</style>