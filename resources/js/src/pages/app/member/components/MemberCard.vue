<template>
    <BaseCard :title="member.name" :avatar="member.avatar" :name="member.name" variant="member">
        <template #actions>
            <div class="menu-wrapper" ref="menuWrapperRef" @click.stop="toggleMenu">
                <i class="bi bi-three-dots"></i>
                <div v-if="showMenu" class="menu-dropdown">
                    <div class="menu-item" @click.stop="handleChat">Chat</div>
                    <div class="menu-item remove" @click.stop="handleRemove">Remove</div>
                </div>
            </div>
        </template>
    </BaseCard>
</template>

<script setup lang="ts">
import { ref, defineProps, defineEmits, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import BaseCard from '../../../../components/BaseCard.vue';

const props = defineProps<{ member: { id: number; name: string; email: string; avatar?: string } }>();
const emit = defineEmits(['remove']);

const router = useRouter();
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
    // Điều hướng đến trang chat với user_id của member được chọn
    router.push({ name: 'chat', query: { user_id: props.member.id } });
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