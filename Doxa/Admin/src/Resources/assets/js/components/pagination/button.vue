<template>

    <a v-if="button.callback" href="#" class="nav_arrow _link" @click.prevent="button.callback">
        <nav-arrow :button="button"></nav-arrow>
    </a>

    <a v-if="!button.callback" class="nav_arrow _na">
        <nav-arrow :button="button"></nav-arrow>
    </a>
</template>

<script>
import NavArrow from "./arrow.vue";

export default {
    props: [
        'button',
    ],
    components: {
        NavArrow,
    },
    mounted() {
        //console.log("Button mounted", this.button);
    },
};
</script>
