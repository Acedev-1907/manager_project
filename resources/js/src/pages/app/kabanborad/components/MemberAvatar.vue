<template>
    <span>{{ displayChar }}</span>
</template>
<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps<{ member: any }>()
const displayChar = computed(() => {
    if (props.member && props.member.user && props.member.user.name) {
        return props.member.user.name.charAt(0).toUpperCase();
    }
    if (props.member && props.member.members && props.member.members.name) {
        return props.member.members.name.charAt(0).toUpperCase();
    }
    if (props.member && props.member.member && props.member.member.name) {
        return props.member.member.name.charAt(0).toUpperCase();
    }
    if (props.member && props.member.name) {
        return props.member.name.charAt(0).toUpperCase();
    }
    return '?';
});
</script>