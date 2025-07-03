<script setup lang="ts">
import { ref } from 'vue';
import Swal from 'sweetalert2';
const emit = defineEmits(['close', 'add']);
const input = ref('');
const errorMsg = ref('');

function submit() {
    errorMsg.value = '';
    if (!input.value) {
        errorMsg.value = 'Please enter member name or email';
        return;
    }
    emit('add', input.value, (err: string | null) => {
        if (err) {
            errorMsg.value = err;
        } else {
            Swal.fire({ icon: 'success', title: 'Added successfully', timer: 1200, showConfirmButton: false });
        }
    });
}
</script>
<template>
    <div class="modal-backdrop">
        <div class="modal-content">
            <h5>Add Member</h5>
            <input v-model="input" placeholder="Enter member name or email" class="form-control mb-2" />
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
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
    padding: 2rem 1.5rem;
    min-width: 320px;
    max-width: 400px;
    width: 100%;
}

@media (max-width: 500px) {
    .modal-content {
        min-width: 0;
        max-width: 95vw;
        padding: 1rem 0.5rem;
    }
}
</style>