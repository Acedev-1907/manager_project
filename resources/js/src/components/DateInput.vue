<script lang="ts" setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

interface Props {
    modelValue: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    min?: string;
    max?: string;
    id?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'DD/MM/YYYY',
    required: false,
    disabled: false
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const inputRef = ref<HTMLInputElement>();
const displayValue = ref('');
const isOpen = ref(false);
const currentMonth = ref(new Date());

// Convert YYYY-MM-DD to DD/MM/YYYY
function formatDateForDisplay(dateString: string): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';

    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();

    return `${day}/${month}/${year}`;
}

// Convert DD/MM/YYYY to YYYY-MM-DD
function parseDisplayDate(displayDate: string): string {
    if (!displayDate) return '';

    const parts = displayDate.split('/');
    if (parts.length !== 3) return '';

    const day = parseInt(parts[0]);
    const month = parseInt(parts[1]) - 1;
    const year = parseInt(parts[2]);

    if (isNaN(day) || isNaN(month) || isNaN(year)) return '';

    // Set giờ 12:00 để tránh lệch múi giờ
    const date = new Date(year, month, day, 12, 0, 0);
    if (date.getDate() !== day || date.getMonth() !== month || date.getFullYear() !== year) {
        return '';
    }

    // Xuất yyyy-mm-dd local
    const yyyy = date.getFullYear();
    const mm = (date.getMonth() + 1).toString().padStart(2, '0');
    const dd = date.getDate().toString().padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

// Initialize display value
watch(() => props.modelValue, (newValue) => {
    displayValue.value = formatDateForDisplay(newValue);
}, { immediate: true });

// Handle input change
function handleInputChange(event: Event) {
    const target = event.target as HTMLInputElement;
    let value = target.value;

    // Auto-format as user types
    value = value.replace(/\D/g, '');
    if (value.length > 8) value = value.substring(0, 8);

    if (value.length >= 4) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4);
    } else if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }

    displayValue.value = value;

    // Convert to YYYY-MM-DD and emit
    const isoDate = parseDisplayDate(value);
    emit('update:modelValue', isoDate);
}

// Calendar functions
function openCalendar() {
    if (!props.disabled) {
        isOpen.value = true;
    }
}

function closeCalendar() {
    isOpen.value = false;
}

function selectDate(date: Date) {
    // Lấy ngày theo local, không dùng toISOString
    const yyyy = date.getFullYear();
    const mm = (date.getMonth() + 1).toString().padStart(2, '0');
    const dd = date.getDate().toString().padStart(2, '0');
    const isoDate = `${yyyy}-${mm}-${dd}`;
    displayValue.value = formatDateForDisplay(isoDate);
    emit('update:modelValue', isoDate);
    closeCalendar();
}

// Generate calendar days
const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());

    const days = [];
    const currentDate = new Date(startDate);

    for (let i = 0; i < 42; i++) {
        days.push(new Date(currentDate));
        currentDate.setDate(currentDate.getDate() + 1);
    }

    return days;
});

const currentMonthYear = computed(() => {
    return currentMonth.value.toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric'
    });
});

function previousMonth() {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1);
}

function nextMonth() {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1);
}

// Allow navigation to far future months
function goToFutureMonth() {
    const futureDate = new Date();
    futureDate.setFullYear(futureDate.getFullYear() + 5); // Go 5 years into future
    currentMonth.value = futureDate;
}

function goToFarFutureMonth() {
    const farFutureDate = new Date();
    farFutureDate.setFullYear(farFutureDate.getFullYear() + 20); // Go 20 years into future
    currentMonth.value = farFutureDate;
}

function isToday(date: Date): boolean {
    const today = new Date();
    return date.toDateString() === today.toDateString();
}

function isSelected(date: Date): boolean {
    if (!props.modelValue) return false;
    const selectedDate = new Date(props.modelValue);
    return date.toDateString() === selectedDate.toDateString();
}

function isOtherMonth(date: Date): boolean {
    return date.getMonth() !== currentMonth.value.getMonth();
}

function isDisabled(date: Date): boolean {
    const dateString = date.toISOString().split('T')[0];

    // Only apply constraints if props are provided
    if (props.min && props.min !== undefined && props.min !== '') {
        const minDate = new Date(props.min);
        // Compare only date part (ignoring time)
        const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const minDateOnly = new Date(minDate.getFullYear(), minDate.getMonth(), minDate.getDate());
        if (dateOnly < minDateOnly) {
            return true;
        }
    }
    if (props.max && props.max !== undefined && props.max !== '') {
        const maxDate = new Date(props.max);
        // Compare only date part (ignoring time)
        const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const maxDateOnly = new Date(maxDate.getFullYear(), maxDate.getMonth(), maxDate.getDate());
        if (dateOnly > maxDateOnly) {
            return true;
        }
    }
    return false;
}

// Close calendar when clicking outside
function handleClickOutside(event: Event) {
    if (inputRef.value && !inputRef.value.contains(event.target as Node)) {
        closeCalendar();
    }
}

// Add/remove click outside listener
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="date-input-container" ref="inputRef">
        <div class="date-input-wrapper">
            <input :id="id" :value="displayValue" :placeholder="placeholder" :required="required" :disabled="disabled"
                class="date-input-field" @input="handleInputChange" @focus="openCalendar" @click="openCalendar" />
            <i class="bi bi-calendar3 date-icon" @click="openCalendar"></i>
        </div>

        <!-- Calendar Dropdown -->
        <div v-if="isOpen" class="calendar-dropdown">
            <div class="calendar-header">
                <button type="button" class="calendar-nav-btn" @click="previousMonth">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span class="calendar-title">{{ currentMonthYear }}</span>
                <button type="button" class="calendar-nav-btn" @click="nextMonth">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <!-- Quick navigation buttons for testing -->
            <div v-if="false" class="calendar-quick-nav">
                <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="goToFutureMonth">
                    +5 Years
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="goToFarFutureMonth">
                    +20 Years
                </button>
            </div>

            <!-- Date constraints info -->
            <div v-if="props.min || props.max" class="calendar-constraints">
                <small class="text-muted">
                    <span v-if="props.min">From: {{ formatDateForDisplay(props.min) }}</span>
                    <span v-if="props.min && props.max"> | </span>
                    <span v-if="props.max">To: {{ formatDateForDisplay(props.max) }}</span>
                    <!-- <span v-if="props.min && !props.max"> | No maximum limit</span> -->
                </small>
            </div>

            <!-- Show when no constraints -->
            <!-- <div v-if="!props.min && !props.max" class="calendar-constraints">
                <small class="text-muted">
                    <span>No date restrictions - free selection</span>
                </small>
            </div> -->

            <!-- Debug info for troubleshooting -->
            <div v-if="false" class="calendar-debug">
                <small class="text-muted">
                    <strong>Debug:</strong> Min={{ props.min || 'None' }}, Max={{ props.max || 'None' }}
                </small>
            </div>

            <div class="calendar-weekdays">
                <div class="weekday">Sun</div>
                <div class="weekday">Mon</div>
                <div class="weekday">Tue</div>
                <div class="weekday">Wed</div>
                <div class="weekday">Thu</div>
                <div class="weekday">Fri</div>
                <div class="weekday">Sat</div>
            </div>

            <div class="calendar-days">
                <button v-for="date in calendarDays" :key="date.toISOString()" type="button" class="calendar-day"
                    :class="{
                        'other-month': isOtherMonth(date),
                        'today': isToday(date),
                        'selected': isSelected(date),
                        'disabled': isDisabled(date)
                    }" :disabled="isDisabled(date)" @click="selectDate(date)"
                    :title="isDisabled(date) ? 'Date not available' : ''">
                    {{ date.getDate() }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.date-input-container {
    position: relative;
    width: 100%;
}

.date-input-wrapper {
    position: relative;
    width: 100%;
}

.date-input-field {
    width: 100%;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 0.75rem 2.5rem 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #f9fafb;
    color: #374151;
    cursor: pointer;
}

.date-input-field:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.date-input-field:hover {
    border-color: #d1d5db;
    background: #f3f4f6;
}

.date-input-field:disabled {
    background: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
}

.date-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 1.1rem;
    cursor: pointer;
    z-index: 2;
    transition: color 0.2s;
}

.date-icon:hover {
    color: #3b82f6;
}

.calendar-dropdown {
    position: absolute;
    left: 0;
    top: 100%;
    right: auto;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 3000;
    margin-left: 0;
    margin-top: 0.5rem;
    padding: 1rem;
    min-width: 210px;
    padding-top: 0.3rem;
    padding-bottom: 0.3rem;
}

.calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.3rem;
    min-height: 1.5rem;
}

.calendar-nav-btn {
    background: none;
    border: none;
    color: #6b7280;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.2s;
}

.calendar-nav-btn:hover {
    background: #f3f4f6;
    color: #3b82f6;
}

.calendar-title {
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
    line-height: 1.1;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.1rem;
    margin-bottom: 0.1rem;
}

.weekday {
    text-align: center;
    font-size: 0.7rem;
    font-weight: 600;
    color: #6b7280;
    padding: 0.15rem 0;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.05rem;
}

.calendar-day {
    background: none;
    border: none;
    padding: 0.15rem;
    border-radius: 0.25rem;
    cursor: pointer;
    font-size: 0.8rem;
    color: #374151;
    transition: all 0.2s;
    min-height: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-day:hover:not(.disabled) {
    background: #f3f4f6;
}

.calendar-day.other-month {
    color: #d1d5db;
}

.calendar-day.today {
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: 600;
}

.calendar-day.selected {
    background: #3b82f6;
    color: white;
    font-weight: 600;
}

.calendar-day.disabled {
    color: #d1d5db;
    cursor: not-allowed;
    background: #f9fafb;
    opacity: 0.5;
    text-decoration: line-through;
}

.calendar-day.disabled:hover {
    background: #f9fafb;
    color: #d1d5db;
}

.calendar-constraints {
    margin-bottom: 1rem;
    text-align: center;
}

.calendar-debug {
    margin-top: 0.5rem;
    text-align: center;
}

.calendar-quick-nav {
    margin-bottom: 1rem;
    text-align: center;
}

@media (max-width: 768px) {
    .date-input-field {
        font-size: 0.9rem;
        padding: 0.65rem 2.25rem 0.65rem 0.875rem;
    }

    .date-icon {
        right: 0.875rem;
        font-size: 1rem;
    }

    .calendar-dropdown {
        min-width: 260px;
        padding: 0.75rem;
    }

    .calendar-day {
        font-size: 0.85rem;
        padding: 0.4rem;
        min-height: 1.8rem;
    }
}
</style>