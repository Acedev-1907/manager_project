<template>
    <div class="member-card">
        <div class="row-main">
            <img :src="getAvatarSrc(props.invitation.sender?.avatar, props.invitation.sender?.name)" class="avatar"
                alt="avatar" />
            <h5 class="member-name">{{ props.invitation.sender ? props.invitation.sender.name : '' }}</h5>
            <div class="action-btns">
                <button class="btn btn-accept" :disabled="loading" @click="handleAccept(props.invitation.id)"><i
                        class="bi bi-check2"></i></button>
                <button class="btn btn-decline" :disabled="loading" @click="handleDecline(props.invitation.id)"><i
                        class="bi bi-x"></i></button>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, defineProps } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';
const props = defineProps<{ invitation: any }>();
const emit = defineEmits(['accept', 'decline']);

const loading = ref(false);

function handleAccept(id: number) {
    if (loading.value) return;
    loading.value = true;
    emit('accept', id);
}
function handleDecline(id: number) {
    if (loading.value) return;
    loading.value = true;
    emit('decline', id);
}
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

.btn-accept {
    background: #fff;
    color: #16a34a;
    border: 1px solid #16a34a;
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

.btn-accept:hover {
    background: #dcfce7;
    color: #15803d;
    border-color: #15803d;
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