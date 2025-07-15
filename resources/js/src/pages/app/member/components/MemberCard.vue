<template>
    <div class="member-card">
        <div class="member-info">
            <div class="row-name-id">
                <img :src="getAvatarSrc(member.avatar, member.name)" class="avatar" alt="avatar" />
                <span class="member-id">#{{ member.id }}</span>
                <h5 class="member-name">{{ member.name }}</h5>
            </div>
            <p class="member-email">{{ member.email }}</p>
        </div>
        <button class="btn btn-remove" @click="$emit('remove', member)">
            <i class="bi bi-person-x"></i>
        </button>
    </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';

defineProps<{ member: { id: number; name: string; email: string; avatar?: string } }>();
defineEmits(['remove']);
</script>

<style scoped>
.member-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(34, 34, 59, 0.08);
    padding: 1.2rem 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 140px;
    position: relative;
    transition: box-shadow 0.2s;
}

.member-card:hover {
    box-shadow: 0 4px 18px rgba(34, 34, 59, 0.16);
}

.member-info {
    margin-bottom: 1.2rem;
}

.row-name-id {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    margin-bottom: 0.3rem;
}

.member-id {
    font-size: 1rem;
    font-weight: 500;
    color: #2563eb;
    background: #e0e7ff;
    border-radius: 6px;
    padding: 2px 10px;
    letter-spacing: 0.5px;
}

.member-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.member-email {
    font-size: 0.97rem;
    color: #64748b;
    margin-bottom: 0;
}

.btn-remove {
    align-self: flex-end;
    font-size: 1.3rem;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid #ef4444;
    background: #fff;
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s, color 0.15s, border 0.15s;
    box-shadow: 0 1px 4px rgba(34, 34, 59, 0.06);
    padding: 0;
}

.btn-remove:hover {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #b91c1c;
}

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
    border: 2px solid #e0e7ff;
}
</style>