<template>
    <div class="w-full max-w-sm px-4 py-10 mx-auto">

        <ConfirmModal/>

        <Header :title="header"/>

        <div class="mb-2 sm" v-html="description" />

        <div v-if="login">
            <div class="flex flex-col justify-between space-y-3 items-left">
                <div class="flex flex-col mt-4 space-y-1 text-sm">
                    <div v-if="resend_timer <= 0" class="">
                        <a href="#" class="link" @click.prevent="resendCode()">Resend link</a> to {{ login }}
                    </div>
                    <span v-if="resend_timer > 0">Resend code in {{ resend_timer }} seconds.</span>
                </div>

            </div>
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
import FieldError from "./components/FieldError.vue";
import BannerError from "./components/BannerError.vue";

export default {
    props: ['login', 'login_type', 'timer', 'method', 'header', 'description'],
    components: {
        Header,
        ConfirmModal,
        FieldError,
        BannerError,
    },
    data() {
        return {
            form_data: {
                verification_code: '',
            },
            errors: {
                verification_code: '',
            },
            resend_timer: 0,
            resend_interval: null,
        }
    },
    methods: {
        submitCode() {
            this.checkForm();

            this.form_data['method'] = this.method;
            if(!this.isError()) {
                axios.postForm(`auth/api/${this.method}/verify`, this.form_data)
                    .then(response => {
                        console.log('response.data: ', response.data);
                        if(response.data.confirmation){
                            this.$emitter.emit('open-confirm-modal', response.data.confirmation);
                        } else {
                            if(response.data.redirect) {
                                window.location.href = response.data.redirect;
                                return;
                            } else {
                                if(!response.data.success){
                                    this.errors.verification_code = response.data.error;
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.log("error: ", error);
                    });
            }
        },
        resendCode() {
            if(this.resend_timer <= 0){
                axios.get(`auth/api/${this.method}/resend-verification-code`)
                    .then(response => {
                        console.log('response.data: ', response.data);
                        if(response.data.confirmation){
                            this.$emitter.emit('open-confirm-modal', response.data.confirmation);
                        }
                        if(response.data.timer){
                            clearInterval(this.resend_interval);
                            this.resend_timer = response.data.timer;
                            this.incrementCodeTimer();
                        }
                    })
                    .catch(error => {
                        console.log("error: ", error);
                    });
            }
        },
        incrementCodeTimer() {
            this.resend_interval = setInterval(() => {
                if(this.resend_timer > 0){
                    this.resend_timer -= 1;
                }
            }, 1000);
        },
        checkForm() {
            this.errors.verification_code = '';
            if(!this.form_data.verification_code.trim()) {
                this.errors.verification_code = 'Verification code is required';
            }
        },
        isError() {
            return this.errors.verification_code;
        },
        setCode(code){
            this.form_data.verification_code = code;
        },
    },
    mounted() {
        this.resend_timer = this.timer;
        console.log('this.login: ', this.login);
        console.log('this.method: ', this.method);
        if(this.timer){
            this.incrementCodeTimer();
        }
        this.$emitter.on('set-otp', this.setCode);
    },
    unmounted() {
        this.$emitter.off('set-otp', this.setCode);
    },
};
</script>
