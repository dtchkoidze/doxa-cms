<template>
    <a href="#" v-if="page.callback" class="" :class="classes" @click.prevent="page.callback">{{page.number}}</a>

    <span v-if="!page.callback" class="" :class="classes">{{page.number}}</span>
</template>

<script>

export default {
    props: [
        'page',
        'classes'
    ],
};
</script>