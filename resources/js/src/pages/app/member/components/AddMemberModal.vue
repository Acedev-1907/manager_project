<script setup lang="ts">
import { ref } from 'vue';
import Swal from 'sweetalert2';
import { makeHttpReq } from '../../../../helper/makeHttpReq';
type UserSuggestionType = { id: number; name: string; friend_code: string; avatar?: string; status: string };
const emit = defineEmits(['close', 'add']);
const input = ref('');
const errorMsg = ref('');
const suggestions = ref<UserSuggestionType[]>([]);
const loading = ref(false);
const isFocused = ref(false);

async function fetchSuggestions(query: string) {
    if (!query) {
        suggestions.value = [];
        return;
    }
    loading.value = true;
    try {
        const res = await makeHttpReq<undefined, any>(`users/all?query=${query}`, 'GET');
        suggestions.value = res.data || [];
    } catch (e) {
        suggestions.value = [];
    }
    loading.value = false;
}

function onInput() {
    errorMsg.value = '';
    clearTimeout((onInput as any).timer);
    (onInput as any).timer = setTimeout(() => fetchSuggestions(input.value), 250);
}

function onFocus() {
    isFocused.value = true;
    if (input.value) fetchSuggestions(input.value);
}
function onBlur() {
    setTimeout(() => { isFocused.value = false; }, 150);
}

function selectSuggestion(user: UserSuggestionType) {
    input.value = user.friend_code;
    suggestions.value = [];
}

function submit() {
    errorMsg.value = '';
    if (!input.value) {
        errorMsg.value = 'Please enter friend code or select user';
        return;
    }
    emit('add', input.value, (err: string | null) => {
        if (err) {
            errorMsg.value = err;
        } else {
            Swal.fire({ icon: 'success', title: 'Invitation sent', timer: 1200, showConfirmButton: false });
        }
    });
}
</script>
<template>
    <div class="modal-backdrop">
        <div class="modal-content">
            <h5>Add Member</h5>
            <div class="autocomplete-wrapper">
                <input v-model="input" @input="onInput" @focus="onFocus" @blur="onBlur"
                    placeholder="Enter name or friend code" class="form-control mb-2" />
                <ul v-if="isFocused && suggestions.length && input" class="suggestion-list">
                    <li v-for="user in suggestions" :key="user.id"
                        :class="['suggestion-item', { disabled: user.status !== 'available' }]"
                        @click="user.status === 'available' && selectSuggestion(user)">
                        <img :src="user.avatar || '/default-avatar.png'" class="avatar-suggestion" />
                        <span>{{ user.name }}</span>
                        <span class="text-muted">({{ user.friend_code }})</span>
                        <span v-if="user.status !== 'available'" class="check-icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M5 10.5L9 14.5L15 7.5" stroke="#16a34a" stroke-width="2.2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </li>
                </ul>
            </div>
            <div v-if="errorMsg" class="text-danger mb-2">{{ errorMsg }}</div>
            <div class="d-flex gap-2 justify-content-end">
                <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
                <button class="btn btn-primary" @click="submit">Add</button>
            </div>
        </div>
    </div>
</template>
<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.2);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 2px 16px rgba(34, 34, 59, 0.13);
    padding: 2rem 1.5rem 1.5rem 1.5rem;
    min-width: 320px;
    max-width: 400px;
    width: 100%;
    position: relative;
}

.autocomplete-wrapper {
    position: relative;
}

.suggestion-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 2px 8px rgba(34, 34, 59, 0.10);
    z-index: 10;
    max-height: 220px;
    overflow-y: auto;
    margin: 0;
    padding: 0;
    list-style: none;
}

.suggestion-list li {
    padding: 0.7rem 1.1rem;
    cursor: pointer;
    transition: background 0.15s;
    font-size: 1.04rem;
}

.suggestion-list li:hover {
    background: #f3f4f6;
}

input.form-control {
    width: 100%;
    border-radius: 8px;
    border: 1.5px solid #bfc9d1;
    font-size: 1.05rem;
    padding: 0.55rem 0.9rem;
    margin-bottom: 0.2rem;
    transition: border 0.15s;
}

input.form-control:focus {
    border-color: #2563eb;
    outline: none;
    background: #f8fafc;
}

.btn-primary {
    background: #2563eb;
    border: none;
    font-weight: 500;
    border-radius: 8px;
    padding: 0.45rem 1.2rem;
    font-size: 1.05rem;
}

.btn-secondary {
    background: #6b7280;
    border: none;
    font-weight: 500;
    border-radius: 8px;
    padding: 0.45rem 1.2rem;
    font-size: 1.05rem;
    color: #fff;
}

.d-flex {
    display: flex;
}

.gap-2 {
    gap: 0.7rem;
}

.justify-content-end {
    justify-content: flex-end;
}

.avatar-suggestion {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 0.7rem;
    border: 1.5px solid #e5e7eb;
    background: #f3f4f6;
}

.suggestion-item.disabled {
    color: #bdbdbd;
    pointer-events: none;
    background: #f8fafc;
}

.check-icon {
    color: #16a34a;
    font-size: 1.2rem;
    margin-left: 0.5rem;
}

@media (max-width: 500px) {
    .modal-content {
        min-width: 0;
        max-width: 95vw;
        padding: 1rem 0.5rem;
    }
}
</style>