<template>
    <div class="avatar">
        <img v-if="avatarSrc" :src="avatarSrc" alt="avatar" />
        <span v-else>{{ displayChar }}</span>
    </div>
</template>
<script setup lang="ts">
import { computed } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';
const props = defineProps<{ member: any }>()

// Lấy tên member
const memberName = computed(() => {
    if (props.member?.user?.name) return props.member.user.name;
    if (props.member?.members?.name) return props.member.members.name;
    if (props.member?.member?.name) return props.member.member.name;
    if (props.member?.name) return props.member.name;
    return '';
});

// Lấy avatar member
const memberAvatar = computed(() => {
    if (props.member?.avatar) return props.member.avatar;
    if (props.member?.user?.avatar) return props.member.user.avatar;
    if (props.member?.members?.avatar) return props.member.members.avatar;
    if (props.member?.member?.avatar) return props.member.member.avatar;
    return '';
});

const avatarSrc = computed(() => getAvatarSrc(memberAvatar.value, memberName.value));

// Lấy ký tự đầu tên
const displayChar = computed(() => memberName.value ? memberName.value.charAt(0).toUpperCase() : '?');
</script>

<style scoped>
.avatar {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #3b82f6;
    color: white;
    border-radius: 50%;
    overflow: hidden;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>