<template>
    <div class="flex items-center justify-start gap-2 px-3 py-2 text-sm text-white bg-red-700 rounded-lg" v-show="error">
        <svg class="fill-current shrink-0 opacity-80" width="16" height="16" viewBox="0 0 16 16">
            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm3.5 10.1l-1.4 1.4L8 9.4l-2.1 2.1-1.4-1.4L6.6 8 4.5 5.9l1.4-1.4L8 6.6l2.1-2.1 1.4 1.4L9.4 8l2.1 2.1z" />            
        </svg>
        <div v-html="error"></div>
    </div>
</template>

<script>
export default {
    props: ['error'],
};
</script>