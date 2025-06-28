<script lang="ts" setup>
import { ErrorObject } from '@vuelidate/core';

const props = defineProps<{
    label: string;
    errors: ErrorObject[];
    showLabel?: boolean;
}>();

// Default showLabel to true for backward compatibility
const shouldShowLabel = props.showLabel !== false;
</script>

<template>
    <label v-if="shouldShowLabel" :for="'base-input-' + props.label">{{ props.label }}</label>
    <div :class="{ error: props.errors.length > 0 }">
        <slot></slot>
        <div v-for="error of props.errors" :key="error.$uid" class="input-errors">
            <div class="error-msg">{{ error.$message }}</div>
        </div>
    </div>
</template>

<style scoped>
.input-errors {
    color: red;  font-size: 0.875rem;
    margin-top: 0.25rem;
}

.error-msg {
    margin-bottom: 0.125rem;
}
</style>
