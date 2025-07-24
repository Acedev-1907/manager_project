<script lang="ts" setup>
import { computed } from 'vue';

interface Props {
    type?: string;
    modelValue?: string;
    placeholder?: string;
    id?: string;
    name?: string;
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    size?: 'sm' | 'md' | 'lg';
    min?: string;
    max?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    modelValue: '',
    placeholder: '',
    required: false,
    disabled: false,
    readonly: false,
    size: 'md'
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'input', event: Event): void;
    (e: 'focus', event: FocusEvent): void;
    (e: 'blur', event: FocusEvent): void;
    (e: 'change', event: Event): void;
}>();

const inputId = computed(() => props.id || `base-input-${props.type}-${Math.random().toString(36).substr(2, 9)}`);
const inputName = computed(() => props.name || `base-input-${props.type}`);

const inputClasses = computed(() => [
    'form-control',
    `form-control-${props.size}`,
    'base-input-custom',
    {
        'is-invalid': false, // Can be extended for validation
        'is-valid': false,   // Can be extended for validation
        'date-input': props.type === 'date'
    }
]);

function updateValue(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
    emit('input', event);
    emit('change', event);
}

function handleFocus(event: FocusEvent) {
    emit('focus', event);
}

function handleBlur(event: FocusEvent) {
    emit('blur', event);
}
</script>

<template>
    <div class="input-wrapper" :class="{ 'date-input-wrapper': type === 'date' }">
        <input :id="inputId" :name="inputName" :value="modelValue" :type="type" :placeholder="placeholder"
            :required="required" :disabled="disabled" :readonly="readonly" :min="min" :max="max" :class="inputClasses"
            @input="updateValue" @focus="handleFocus" @blur="handleBlur" />
        <i v-if="type === 'date'" class="bi bi-calendar3 date-icon"></i>
    </div>
</template>

<style scoped>
.input-wrapper {
    position: relative;
    width: 100%;
}

.base-input-custom {
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #f9fafb;
    color: #374151;
    height: 44px;
}

.base-input-custom:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.base-input-custom:hover {
    border-color: #d1d5db;
    background: #f3f4f6;
}

.date-input-wrapper {
    position: relative;
}

.date-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 1.1rem;
    pointer-events: none;
    z-index: 2;
}

.date-input {
    padding-right: 2.5rem;
}

.date-input::-webkit-calendar-picker-indicator {
    background: transparent;
    bottom: 0;
    color: transparent;
    cursor: pointer;
    height: auto;
    left: 0;
    position: absolute;
    right: 0;
    top: 0;
    width: auto;
}

.date-input::-webkit-datetime-edit {
    color: #374151;
}

.date-input::-webkit-datetime-edit-fields-wrapper {
    padding: 0;
}

.date-input::-webkit-datetime-edit-text {
    color: #6b7280;
    padding: 0 0.2rem;
}

.date-input::-webkit-datetime-edit-month-field,
.date-input::-webkit-datetime-edit-day-field,
.date-input::-webkit-datetime-edit-year-field {
    color: #374151;
}

/* Responsive */
@media (max-width: 768px) {
    .base-input-custom {
        font-size: 0.9rem;
        padding: 0.65rem 0.875rem;
    }

    .date-input {
        padding-right: 2.25rem;
    }

    .date-icon {
        right: 0.875rem;
        font-size: 1rem;
    }

    .base-input-custom {
        width: 100%;
    }
}
</style>
