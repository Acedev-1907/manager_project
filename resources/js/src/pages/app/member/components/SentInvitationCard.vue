<template>
    <div class="member-card">
        <div class="row-main">
            <img :src="invitation.receiver && invitation.receiver.avatar ? invitation.receiver.avatar : '/default-avatar.png'"
                class="avatar" alt="avatar" />
            <h5 class="member-name">{{ invitation.receiver ? invitation.receiver.name : '' }}</h5>
            <div class="action-btns">
                <button class="btn btn-decline" @click="$emit('cancel', invitation.id)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
defineProps<{ invitation: any }>();
defineEmits(['cancel']);
</script>
<style scoped>
.member-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(34, 34, 59, 0.08);
    padding: 1.2rem 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 90px;
    position: relative;
    transition: box-shadow 0.2s;
}

.member-card:hover {
    box-shadow: 0 4px 18px rgba(34, 34, 59, 0.16);
}

.row-main {
    display: flex;
    align-items: center;
    gap: 1.1rem;
    width: 100%;
}

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e0e7ff;
}

.member-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    flex: 1;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.action-btns {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-decline {
    background: #fff;
    color: #ef4444;
    border: 1px solid #ef4444;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: background 0.15s, color 0.15s, border 0.15s;
    box-shadow: 0 1px 4px rgba(34, 34, 59, 0.06);
    padding: 0;
}

.btn-decline:hover {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #b91c1c;
}
</style>