<template>
    <div class="member-card">
        <div class="row-main">
            <img :src="getAvatarSrc(member.avatar, member.name)" class="avatar" alt="avatar" />
            <h5 class="member-name">{{ member.name }}</h5>
            <div class="menu-wrapper" ref="menuWrapperRef" @click.stop="toggleMenu">
                <i class="bi bi-three-dots"></i>
                <div v-if="showMenu" class="menu-dropdown">
                    <div class="menu-item" @click.stop="handleChat">Chat</div>
                    <div class="menu-item remove" @click.stop="handleRemove">Remove</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, defineProps, defineEmits, onMounted, onBeforeUnmount } from 'vue';
import { getAvatarSrc } from '../../../../helper/avatar';

const props = defineProps<{ member: { id: number; name: string; email: string; avatar?: string } }>();
const emit = defineEmits(['remove']);

const showMenu = ref(false);
const menuWrapperRef = ref<HTMLElement | null>(null);

function toggleMenu() {
    showMenu.value = !showMenu.value;
}
function handleRemove() {
    showMenu.value = false;
    emit('remove', props.member);
}
function handleChat() {
    showMenu.value = false;
    // TODO: emit chat event or handle chat logic
    alert('Chat feature coming soon!');
}

function handleClickOutside(event: MouseEvent) {
    if (showMenu.value && menuWrapperRef.value && !menuWrapperRef.value.contains(event.target as Node)) {
        showMenu.value = false;
    }
}
onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});
</script>

<style scoped>
.member-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(34, 34, 59, 0.08);
    padding: 1.1rem 1.3rem;
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
    height: 100%;
}

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e0e7ff;
}

.member-name {
    font-size: 1.13rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    flex: 1;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.menu-wrapper {
    position: relative;
    margin-left: auto;
    cursor: pointer;
    font-size: 1.3rem;
    color: #64748b;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.15s;
    padding: 0;
}

.menu-wrapper:hover {
    background: #f3f4f6;
}

.menu-wrapper i {
    font-size: 1.35rem;
    line-height: 1;
}

.menu-dropdown {
    position: absolute;
    top: 120%;
    right: 0;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(34, 34, 59, 0.13);
    min-width: 120px;
    z-index: 10;
    padding: 0.3rem 0;
    display: flex;
    flex-direction: column;
}

.menu-item {
    padding: 0.6rem 1.2rem;
    font-size: 1rem;
    color: #22223b;
    cursor: pointer;
    transition: background 0.13s, color 0.13s;
}

.menu-item:hover {
    background: #f3f4f6;
}

.menu-item.remove {
    color: #ef4444;
}

.menu-item.remove:hover {
    background: #fee2e2;
    color: #b91c1c;
}
</style>