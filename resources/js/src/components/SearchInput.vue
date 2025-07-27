<script lang="ts" setup>
import { ref, watch } from 'vue';
import { createDebouncedFunction } from '../helper/utils';

interface Props {
    modelValue?: string;
    placeholder?: string;
    debounceTime?: number;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Search...',
    debounceTime: 200,
    loading: false
});

const emit = defineEmits<{
    (e: 'search', query: string): void;
    (e: 'update:modelValue', value: string): void;
}>();

const query = ref(props.modelValue);

// Watch for external changes to query
watch(() => props.modelValue, (newValue) => {
    query.value = newValue || '';
});

const search = createDebouncedFunction(async function () {
    emit('search', query.value);
    emit('update:modelValue', query.value);
}, props.debounceTime);

const clearSearch = () => {
    query.value = '';
    emit('search', '');
    emit('update:modelValue', '');
};
</script>

<template>
    <div class="search-bar-card">
        <div class="search-input-wrapper">
            <span class="search-icon">
                <i class="bi bi-search"></i>
            </span>
            <BaseInput @keydown="search" v-model="query" :placeholder="placeholder" class="search-input-beauty" />
            <span v-if="query && !loading" class="clear-icon" @click="clearSearch">
                <i class="bi bi-x-circle"></i>
            </span>
            <span v-if="loading" class="loading-spinner">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </span>
        </div>
    </div>
</template>

<style scoped>
.search-bar-card {
    background: #fff;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.search-input-wrapper {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
    background: #f8fafc;
    border-radius: 2.5rem;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
    padding: 0.01rem 0.7rem 0.01rem 0.5rem;
    transition: box-shadow 0.2s, border 0.2s;
}

.search-input-beauty {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-size: 0.98rem;
    padding: 0.3rem 0.5rem 0.3rem 1.7rem;
    border-radius: 2.5rem;
    color: #22223b;
    transition: box-shadow 0.2s, border 0.2s;
}

.search-input-beauty:focus {
    box-shadow: 0 0 0 2px #a5b4fc;
    background: #fff;
}

.search-icon {
    position: absolute;
    left: 0.5rem;
    color: #94a3b8;
    font-size: 1.05rem;
    z-index: 2;
    pointer-events: none;
}

.clear-icon {
    position: absolute;
    right: 1.7rem;
    color: #cbd5e1;
    font-size: 1.05rem;
    cursor: pointer;
    z-index: 2;
    transition: color 0.2s;
}

.clear-icon:hover {
    color: #ef4444;
}

.loading-spinner {
    position: absolute;
    right: 1.7rem;
    display: flex;
    align-items: center;
    top: 50%;
    transform: translateY(-50%);
    z-index: 2;
}

/* Dropdown suggestion cho search project */
.search-suggestion-dropdown {
    margin-top: 2px !important;
    padding-top: 0 !important;
}

@media (max-width: 768px) {
    .search-input-beauty {
        font-size: 0.93rem;
    }

    .search-input-wrapper {
        padding: 0rem 0.3rem 0rem 0.2rem;
    }

    .search-icon {
        left: 0.3rem;
        font-size: 0.95rem;
    }

    .clear-icon {
        right: 1.1rem;
        font-size: 0.95rem;
    }

    .loading-spinner {
        right: 1.1rem;
    }

    .search-suggestion-dropdown {
        margin-top: 1px !important;
        padding-top: 0 !important;
    }
}
</style>