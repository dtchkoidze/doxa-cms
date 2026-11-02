<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto">

        <ConfirmModal/>
        
        <Header :title="vocab('vcb.user_waiting_activation_title')"/>

        <div class="mb-6 sm">
            {{ vocab('vcb.user_waiting_activation_text', { login: login }) }}
        </div>

        <!------------ FOOTER -------------->
        <div class="flex flex-row justify-between gap-2 pt-5 mt-6 text-sm border-t border-gray-100 dark:border-gray-700/60">
            <a
                class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                href="/auth/login">
                {{ vocab('vcb.log_in') }}
            </a>
            <a
                class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                href="/auth/register">
                {{ vocab('vcb.register') }}
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