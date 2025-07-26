<template>
  <div class="base-card" :class="cardClass">
    <div class="card-content">
      <div class="card-header">
        <slot name="avatar">
          <img :src="avatarSrc" class="avatar" alt="avatar" />
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
import { computed } from 'vue';
import { getAvatarSrc } from '../helper/avatar';

interface Props {
  title?: string;
  avatar?: string;
  name?: string;
  variant?: 'default' | 'member' | 'invitation' | 'sent-invitation';
  size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'md'
});

const avatarSrc = computed(() => {
  return getAvatarSrc(props.avatar, props.name || props.title || '');
});

const cardClass = computed(() => {
  return {
    [`card-${props.variant}`]: true,
    [`card-${props.size}`]: true
  };
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

.avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e0e7ff;
  flex-shrink: 0;
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