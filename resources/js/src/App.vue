<script lang="ts" setup>
import { ref } from 'vue';

const count = ref<number>(0)
</script>
<template>
    <div class="container">
        <!-- <h1>hello world from vue 3</h1>
        {{ count }}

        <button type="button" @click="count++" >button</button> -->
        <RouterView />
    </div>
</template>