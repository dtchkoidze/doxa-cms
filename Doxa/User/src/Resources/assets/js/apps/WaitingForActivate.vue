<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto">

        <ConfirmModal/>
        
        <Header title="Waiting for Activate"/>

        <div class="mb-6 sm">
            Your account {{ login }} is verifed and waiting for activation. If you are waiting too long, please contact website administrator.
        </div>

        <!------------ FOOTER -------------->
        <div class="flex flex-row justify-between gap-2 pt-5 mt-6 text-sm border-t border-gray-100 dark:border-gray-700/60">
            <a
                class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                href="/auth/login">
                Sign In
            </a>
            <a
                class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                href="/auth/register">
                Sign Up
            </a>
        </div> 

    </div>
</template>



<script>
import Header from "./components/Header.vue";
import ConfirmModal from "./components/ConfirmModal.vue";

export default {
    props: ['login', 'login_type'],
    components: {
        Header,
        ConfirmModal,
    },
};
</script>