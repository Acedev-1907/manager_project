<template>
    <BaseCard :title="props.invitation.sender ? props.invitation.sender.name : ''"
        :avatar="props.invitation.sender?.avatar" :name="props.invitation.sender?.name" variant="invitation">
        <template #actions>
            <div class="action-btns">
                <button class="btn btn-accept" :disabled="loading" @click="handleAccept(props.invitation.id)">
                    <i class="bi bi-check2"></i>
                </button>
                <button class="btn btn-decline" :disabled="loading" @click="handleDecline(props.invitation.id)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </template>
    </BaseCard>
</template>
<script setup lang="ts">
import { ref, defineProps } from 'vue';
import BaseCard from '../../../../components/BaseCard.vue';
const props = defineProps<{ invitation: any }>();
const emit = defineEmits(['accept', 'decline']);

const loading = ref(false);

function handleAccept(id: number) {
    if (loading.value) return;
    loading.value = true;
    emit('accept', id);
}
function handleDecline(id: number) {
    if (loading.value) return;
    loading.value = true;
    emit('decline', id);
}
</script>
<style scoped>
.action-btns {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-accept {
    background: #fff;
    color: #16a34a;
    border: 1px solid #16a34a;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: background 0.15s, color 0.15s, border 0.15s;
    box-shadow: 0 1px 4px rgba(34, 34, 59, 0.06);
    padding: 0;
}

.btn-accept:hover {
    background: #dcfce7;
    color: #15803d;
    border-color: #15803d;
}

.btn-decline {
    background: #fff;
    color: #ef4444;
    border: 1px solid #ef4444;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: background 0.15s, color 0.15s, border 0.15s;
    box-shadow: 0 1px 4px rgba(34, 34, 59, 0.06);
    padding: 0;
}

.btn-decline:hover {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #b91c1c;
}
</style>