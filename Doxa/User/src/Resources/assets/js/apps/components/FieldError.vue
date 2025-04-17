<template>
    <div class="pt-1 text-xs text-red-600" v-show="error">{{ error }}</div>
</template>

<script>
export default {
    props: ['error'],
};
</script>