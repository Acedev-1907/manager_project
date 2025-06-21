<template>
    <div class="container">
        <!-- <h1>hello world from vue 3</h1>
        {{ count }}

        <button type="button" @click="count++" >button</button> -->
        <RouterView />
    </div>
</template>
<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
    name: 'App'
});
</script>