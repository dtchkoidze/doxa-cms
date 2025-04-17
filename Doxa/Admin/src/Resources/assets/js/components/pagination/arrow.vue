<template>
    <template v-if="button.type == 'prev'">
        <span class="sr-only">{{button.title}}</span><wbr />
        <svg class="fill-current" width="16" height="16" viewBox="0 0 16 16">
            <path d="M9.4 13.4l1.4-1.4-4-4 4-4-1.4-1.4L4 8z" />
        </svg>
    </template>

    <template v-if="button.type == 'next'">
        <span class="sr-only">{{button.title}}</span><wbr />
        <svg class="fill-current" width="16" height="16" viewBox="0 0 16 16">
            <path d="M6.6 13.4L5.2 12l4-4-4-4 1.4-1.4L12 8z" />
          </svg>
    </template>
</template>

<script>

export default {
    props: [
        'button',
    ],
    mounted() {
        //console.log("Arrow mounted");
    },
};
</script>