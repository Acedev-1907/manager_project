<script lang="ts" setup>
import { computed } from 'vue';

interface Props {
    loading?: boolean;
    label: string;
    type?: 'button' | 'submit' | 'reset';
    variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger';
    size?: 'sm' | 'md' | 'lg';
    disabled?: boolean;
    fullWidth?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    type: 'button',
    variant: 'primary',
    size: 'md',
    disabled: false,
    fullWidth: true
});

const emit = defineEmits<{
    (e: 'click', event: MouseEvent): void;
}>();

const handleClick = (event: MouseEvent) => {
    if (!props.loading && !props.disabled) {
        emit('click', event);
    }
};

const buttonClasses = computed(() => [
    'btn',
    `btn-${props.variant}`,
    `btn-${props.size}`,
    {
        'w-100': props.fullWidth,
        'disabled': props.disabled || props.loading
    }
]);
</script>

<template>
    <button :type="type" :disabled="loading || disabled" :class="buttonClasses" @click="handleClick">
        <span v-if="!loading">{{ label }}</span>
        <div v-else class="d-flex justify-content-center">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </button>
</template>