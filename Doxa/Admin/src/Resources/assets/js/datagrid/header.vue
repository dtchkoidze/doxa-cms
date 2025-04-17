<template>
    <!-- Page header -->
    <div class="mb-8 sm:flex sm:justify-between sm:items-center">

        <!-- Left: Title -->
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl font-bold text-gray-800 md:text-3xl dark:text-gray-100">{{ vocab.title }}</h1>
        </div>

        <!-- Right: Actions  -->
        <div class="grid justify-start grid-flow-col gap-2 sm:auto-cols-max sm:justify-end">

            <!-- Add customer button -->
            <button v-if="$parent.permissions.create"
                class="text-gray-100 bg-gray-900 btn hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white"
                @click="newRecord()">
                <svg class="fill-current shrink-0 xs:hidden" width="16" height="16" viewBox="0 0 16 16">
                    <path
                        d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                </svg>
                <span class="max-xs:sr-only">{{ vocab["create-button"] }}</span>
            </button>

        </div>

    </div>


</template>

<script>
export default {
    inject: ["vocab"],
    data() {
        return {
          
        }
    },
    methods: {
        newRecord() {
            this.$emitter.emit("newRecord", {
                route: this.$parent.routes.create,
                use_route: false,
            });
        },

        getDocsRoute() {
            return `${window.location.href}/docs`;
        },

 
    },

    mounted() {
    },

    created() {

    }
};
</script>
