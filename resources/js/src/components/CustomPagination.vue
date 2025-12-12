<script lang="ts" setup>
import { computed } from 'vue';

interface PaginationData {
    current_page?: number;
    data?: any[];
    first_page_url?: string;
    from?: number;
    last_page?: number;
    last_page_url?: string;
    links?: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    next_page_url?: string | null;
    path?: string;
    per_page?: number;
    prev_page_url?: string | null;
    to?: number;
    total?: number;
    // Laravel pagination structure
    [key: string]: any;
}

interface Props {
    data: PaginationData;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false
});

const emit = defineEmits<{
    (e: 'pagination-change-page', page: number): void;
}>();

// Computed properties
const currentPage = computed(() => props.data.current_page || 1);
const lastPage = computed(() => props.data.last_page || 1);
const total = computed(() => props.data.total || 0);
const from = computed(() => props.data.from || 0);
const to = computed(() => props.data.to || 0);

// Calculate page range for display
const pageRange = computed(() => {
    const delta = 2; // Number of pages to show on each side of current page
    const range = [];
    const rangeWithDots = [];

    for (let i = Math.max(2, currentPage.value - delta); i <= Math.min(lastPage.value - 1, currentPage.value + delta); i++) {
        range.push(i);
    }

    if (currentPage.value - delta > 2) {
        rangeWithDots.push(1, '...');
    } else {
        rangeWithDots.push(1);
    }

    rangeWithDots.push(...range);

    if (currentPage.value + delta < lastPage.value - 1) {
        rangeWithDots.push('...', lastPage.value);
    } else if (lastPage.value > 1) {
        rangeWithDots.push(lastPage.value);
    }

    return rangeWithDots;
});

// Navigation functions
function goToPage(page: number) {
    if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
        emit('pagination-change-page', page);
    }
}

function goToFirst() {
    if (currentPage.value > 1) {
        goToPage(1);
    }
}

function goToLast() {
    if (currentPage.value < lastPage.value) {
        goToPage(lastPage.value);
    }
}

function goToPrevious() {
    if (currentPage.value > 1) {
        goToPage(currentPage.value - 1);
    }
}

function goToNext() {
    if (currentPage.value < lastPage.value) {
        goToPage(currentPage.value + 1);
    }
}

// Check if navigation is disabled
const isFirstDisabled = computed(() => currentPage.value <= 1);
const isLastDisabled = computed(() => currentPage.value >= lastPage.value);
const isPreviousDisabled = computed(() => currentPage.value <= 1);
const isNextDisabled = computed(() => currentPage.value >= lastPage.value);

// Show pagination info
const paginationInfo = computed(() => {
    if (total.value === 0) return 'No records found';

    const start = from.value;
    const end = to.value;
    const totalRecords = total.value;

    return `Showing ${start} to ${end} of ${totalRecords} entries`;
});
</script>

<template>
    <div class="custom-pagination-container">
        <!-- Pagination Info -->
        <div v-if="lastPage > 1" class="pagination-info">
            <span class="pagination-text">{{ paginationInfo }}</span>
        </div>

        <!-- Pagination Controls -->
        <div v-if="lastPage > 1" class="pagination-controls">
            <!-- First Page Button -->
            <button @click="goToFirst" :disabled="isFirstDisabled || loading" class="pagination-btn pagination-btn-nav"
                :class="{ 'disabled': isFirstDisabled || loading }" title="Go to first page">
                <i class="bi bi-chevron-double-left"></i>
            </button>

            <!-- Previous Button -->
            <button @click="goToPrevious" :disabled="isPreviousDisabled || loading"
                class="pagination-btn pagination-btn-nav" :class="{ 'disabled': isPreviousDisabled || loading }"
                title="Go to previous page">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Page Numbers -->
            <div class="page-numbers">
                <button v-for="(page, index) in pageRange" :key="index"
                    @click="typeof page === 'number' ? goToPage(page) : null"
                    :disabled="typeof page !== 'number' || loading" class="pagination-btn page-number" :class="{
                        'active': page === currentPage,
                        'disabled': typeof page !== 'number' || loading,
                        'dots': page === '...'
                    }" :title="typeof page === 'number' ? `Go to page ${page}` : ''">
                    {{ page }}
                </button>
            </div>

            <!-- Next Button -->
            <button @click="goToNext" :disabled="isNextDisabled || loading" class="pagination-btn pagination-btn-nav"
                :class="{ 'disabled': isNextDisabled || loading }" title="Go to next page">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Last Page Button -->
            <button @click="goToLast" :disabled="isLastDisabled || loading" class="pagination-btn pagination-btn-nav"
                :class="{ 'disabled': isLastDisabled || loading }" title="Go to last page">
                <i class="bi bi-chevron-double-right"></i>
            </button>
        </div>

        <!-- Loading Indicator -->
        <div v-if="loading" class="pagination-loading">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-pagination-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

.pagination-info {
    text-align: center;
}

.pagination-text {
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    flex-wrap: wrap;
    justify-content: center;
}

.pagination-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
    border-radius: 0.375rem;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    outline: none;
}

.pagination-btn:hover:not(.disabled) {
    border-color: #3b82f6;
    background: #f8fafc;
    color: #3b82f6;
}

.pagination-btn:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.pagination-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}

.pagination-btn.disabled {
    background: #f9fafb;
    border-color: #e5e7eb;
    color: #d1d5db;
    cursor: not-allowed;
}

.pagination-btn.dots {
    background: transparent;
    border-color: transparent;
    color: #6b7280;
    cursor: default;
    min-width: 1.25rem;
}

.pagination-btn.dots:hover {
    background: transparent;
    border-color: transparent;
    color: #6b7280;
}

.pagination-btn-nav {
    min-width: 2rem;
    font-size: 0.85rem;
}

.page-numbers {
    display: flex;
    align-items: center;
    gap: 0.2rem;
}

.page-number {
    min-width: 2rem;
}

.pagination-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 0.375rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .custom-pagination-container {
        padding: 0.5rem 0;
        margin-top: 0.5rem;
    }

    .pagination-controls {
        gap: 0.25rem;
    }

    .pagination-btn {
        min-width: 2.25rem;
        height: 2.25rem;
        font-size: 0.85rem;
        border-radius: 0.375rem;
    }

    .pagination-btn-nav {
        min-width: 2.25rem;
        font-size: 0.9rem;
    }

    .page-number {
        min-width: 2.25rem;
    }

    .pagination-text {
        font-size: 0.8rem;
    }

    /* Hide some navigation on very small screens */
    @media (max-width: 480px) {

        .pagination-btn-nav:first-child,
        .pagination-btn-nav:last-child {
            display: none;
        }
    }
}
</style>