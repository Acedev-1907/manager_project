<template>
  <div class="base-card" :class="cardClass">
    <div class="card-content">
      <div class="card-header">
        <slot name="avatar">
          <!-- Use optimized avatar with skeleton loading -->
          <div class="avatar-container">
            <div v-if="isAvatarLoading" class="avatar-skeleton"></div>
            <img v-else :src="avatarSrc" class="avatar" :class="{ 'avatar-loaded': avatarLoaded }" alt="avatar"
              @load="handleAvatarLoad" @error="handleAvatarError" />
            <div v-if="showAvatarFallback" class="avatar-fallback">
              {{ fallbackText }}
            </div>
          </div>
        </slot>
        <div class="card-info">
          <slot name="title">
            <h5 class="card-title">{{ title }}</h5>
          </slot>
          <slot name="subtitle"></slot>
        </div>
        <div class="card-actions">
          <slot name="actions"></slot>
        </div>
      </div>
      <div class="card-body">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { getAvatarSrc, isAvatarLoading as checkAvatarLoading } from '../helper/avatar';

interface Props {
  title?: string;
  avatar?: string;
  name?: string;
  userId?: number;
  variant?: 'default' | 'member' | 'invitation' | 'sent-invitation';
  size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'md'
});

const avatarLoaded = ref(false);
const showAvatarFallback = ref(false);

const avatarSrc = computed(() => {
  return getAvatarSrc(props.avatar, props.userId);
});

const isAvatarLoading = computed(() => {
  return checkAvatarLoading(props.avatar, props.userId);
});

const fallbackText = computed(() => {
  if (props.name) {
    return props.name.charAt(0).toUpperCase();
  }
  if (props.userId) {
    return `U${props.userId}`;
  }
  return 'U';
});

const cardClass = computed(() => {
  return {
    [`card-${props.variant}`]: true,
    [`card-${props.size}`]: true
  };
});

function handleAvatarLoad() {
  avatarLoaded.value = true;
  showAvatarFallback.value = false;
}

function handleAvatarError() {
  showAvatarFallback.value = true;
  avatarLoaded.value = false;
}

// Reset avatar state when avatar changes
watch(() => props.avatar, () => {
  avatarLoaded.value = false;
  showAvatarFallback.value = false;
});
</script>

<style scoped>
.base-card {
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

.base-card:hover {
  box-shadow: 0 4px 18px rgba(34, 34, 59, 0.16);
}

.card-content {
  width: 100%;
  height: 100%;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 1.1rem;
  width: 100%;
  height: 100%;
}

.avatar-container {
  position: relative;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.avatar {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e0e7ff;
  transition: opacity 0.3s ease;
  opacity: 0;
}

.avatar.avatar-loaded {
  opacity: 1;
}

.avatar-skeleton {
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 50%;
  border: 2px solid #e0e7ff;
}

.avatar-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
  font-size: 14px;
  border-radius: 50%;
  border: 2px solid #e0e7ff;
  text-transform: uppercase;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }

  100% {
    background-position: 200% 0;
  }
}

.card-info {
  flex: 1;
  min-width: 0;
}

.card-title {
  font-size: 1.13rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.card-actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-body {
  margin-top: 0.5rem;
}

/* Size variants */
.card-sm {
  min-height: 70px;
  padding: 0.8rem 1rem;
}

.card-sm .avatar {
  width: 32px;
  height: 32px;
}

.card-sm .card-title {
  font-size: 1rem;
}

.card-lg {
  min-height: 110px;
  padding: 1.4rem 1.6rem;
}

.card-lg .avatar {
  width: 44px;
  height: 44px;
}

.card-lg .card-title {
  font-size: 1.25rem;
}
</style>