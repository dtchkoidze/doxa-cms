<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto">
        <Header :title="error.header"></Header>
        <div class="flex items-center justify-between mt-6">
            <div class="mr-1">
                {{ error.message }}                 
            </div>
        </div>

        <!------------ FOOTER -------------->
        <div class="flex flex-col gap-2 pt-5 mt-6 border-t border-gray-100 dark:border-gray-700/60">
            <div class="text-sm">
                Have another account? 
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/login">
                    Sign In
                </a>
            </div>
            <div class="text-sm">
                Register another account? 
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/register">
                    Sign Up
                </a>
            </div>
        </div> 

    </div>
</template>

<script>
import Header from "./Header.vue";

export default {
    props: ['error'],
    components: {
        Header,
    },
};
</script>