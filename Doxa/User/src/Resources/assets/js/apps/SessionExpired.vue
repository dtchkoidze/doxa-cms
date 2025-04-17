<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto">
        <Header title="Session  expired"></Header>
        <div class="flex items-center justify-between mt-6">
            <div class="mr-1">
                Your session is expired. Please note than confirmation link and code expires in {{ code_expire_in }} minutes.            
            </div>
        </div>

        <!------------ FOOTER -------------->
        <div class="flex items-center justify-between gap-2 pt-5 mt-6 border-t border-gray-100 dark:border-gray-700/60">
            <div class="text-sm">
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/login">
                    Sign In
                </a>
            </div>
            <div class="text-sm">
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
import Header from "./components/Header.vue";

export default {
    props: ['code_expire_in'],
    components: {
        Header,
    },
};
</script>