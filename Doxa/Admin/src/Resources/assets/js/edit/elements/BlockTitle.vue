<template>

        <div v-if="title" class="flex items-center px-4 py-2 doxa-block-title">
            {{ title }}
        </div>    

</template>

<script>
export default {
    props: {
        title: String,
    },
};
</script>
