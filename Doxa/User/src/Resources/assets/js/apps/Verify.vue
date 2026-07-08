<template>
    <div class="px-4 py-10 mx-auto w-full max-w-sm" :class="processing ? 'pointer-events-none' : ''">

        <ConfirmModal />

        <Error v-if="fatal_error" :error="fatal_error" />

        <template v-else>
            <Header title="Verification" />

            <div class="mb-2 sm">
                Verification instructions have been sent to {{ login }}. Enter verification code below or follow
                provided link. Link and code valid for {{ code_expire_in }} minutes.
            </div>

            <div>
                <div class="flex flex-col justify-between space-y-3 items-left">
                    <div class="my-4">
                        <Otp />
                        <FieldError :error="errors.verification_code" />
                    </div>
                    <div class="flex justify-start w-full">
                        <button @click="submitCode()" type="button" :disabled="locked || processing"
                            class="inline-flex justify-center items-center px-4 py-2 text-sm font-medium transition btn-primary hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span>{{ locked ? `Try again in ${lockoutCountdown}` : 'Verify' }}</span>
                            <i v-if="processing" class="ml-2 w-4 h-4 fa-solid fa-spinner fa-spin-pulse"></i>
                        </button>
                    </div>

                    <div class="flex flex-col mt-4 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span>Did not receive verification message?</span>
                            <a v-if="resend_timer <= 0 && !locked" href="#" class="link" @click.prevent="resendCode()">Resend
                                code</a>
                        </div>

                        <span v-if="resend_timer > 0">Resend code in {{ formatResendTimer(resend_timer) }}.</span>
                    </div>

                </div>
            </div>

            <!------------ FOOTER -------------->
            <div
                class="flex flex-row gap-2 justify-between pt-5 mt-6 text-sm border-t border-gray-100 dark:border-gray-700/60">
                <a class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/login">
                    Sign In
                </a>
                <a class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/register">
                    Sign Up
                </a>
            </div>
        </template>
    </div>
</template>



<script>
import Header from "./components/Header.vue";
import ConfirmModal from "./components/ConfirmModal.vue";
import FieldError from "./components/FieldError.vue";
import BannerError from "./components/BannerError.vue";
import Otp from "./components/Otp.vue";
import Error from "./components/Error.vue";

export default {
    props: ['login', 'login_type', 'timer', 'method', 'code_expire_in'],
    components: {
        Header,
        ConfirmModal,
        FieldError,
        BannerError,
        Otp,
        Error,
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
            fatal_error: null,
            processing: false,
            lockoutSeconds: 0,
            lockoutTimer: null,
        }
    },
    computed: {
        locked() {
            return this.lockoutSeconds > 0;
        },
        lockoutCountdown() {
            const minutes = Math.floor(this.lockoutSeconds / 60);
            const seconds = this.lockoutSeconds % 60;
            return `${minutes}:${String(seconds).padStart(2, '0')}`;
        },
    },
    methods: {
        submitCode() {
            if (this.locked) {
                return;
            }
            this.processing = true;
            this.checkForm();
            this.form_data['method'] = this.method;
            if (!this.isError()) {
                axios.postForm(`auth/api/${this.method}/verify`, this.form_data)
                    .then(response => {
                        if (response.data.confirmation) {
                            this.$emitter.emit('open-confirm-modal', response.data.confirmation);
                            this.processing = false;
                        } else {
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                                return;
                            } else {
                                if (!response.data.success) {
                                    this.errors.verification_code = response.data.error;
                                    if (response.data.retry_after) {
                                        this.startLockout(response.data.retry_after);
                                    }
                                }
                                this.processing = false;
                            }
                        }
                    })
                    .catch(error => {
                        console.log("error: ", error);
                        this.processing = false;
                    });
            } else {
                this.processing = false;
            }
        },
        resendCode() {
            if (this.locked) {
                return;
            }
            if (this.resend_timer <= 0) {
                this.processing = true;
                axios.get(`auth/api/${this.method}/resend-verification-code`)
                    .then(response => {
                        this.errors.verification_code = '';
                        this.clearLockout();

                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                            return;
                        }

                        if (response.data.confirmation) {
                            this.$emitter.emit('open-confirm-modal', response.data.confirmation);
                        }
                        if (response.data.timer) {
                            clearInterval(this.resend_interval);
                            this.resend_timer = response.data.timer;
                            this.incrementCodeTimer();
                        }

                        this.$emitter.emit('clear-otp', true);
                        this.processing = false;
                    })
                    .catch(error => {
                        console.log("error: ", error);
                        this.processing = false;
                    });
            }
        },
        incrementCodeTimer() {
            this.resend_interval = setInterval(() => {
                if (this.resend_timer > 0) {
                    this.resend_timer -= 1;
                }
            }, 1000);
        },
        formatResendTimer(totalSeconds) {
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = Math.floor(totalSeconds % 60);
            const paddedSeconds = seconds < 10 ? '0' + seconds : seconds;
            return `${minutes}:${paddedSeconds}`;
        },
        checkForm() {
            this.errors.verification_code = '';
            if (!this.form_data.verification_code.trim()) {
                this.errors.verification_code = 'Verification code is required';
            }
        },
        isError() {
            return this.errors.verification_code;
        },
        setCode(code) {
            this.form_data.verification_code = code;
        },
        startLockout(seconds) {
            this.clearLockoutTimer();
            this.lockoutSeconds = Math.max(0, parseInt(seconds, 10) || 0);
            if (!this.lockoutSeconds) {
                return;
            }
            this.lockoutTimer = setInterval(() => {
                this.lockoutSeconds -= 1;
                if (this.lockoutSeconds <= 0) {
                    this.clearLockout();
                }
            }, 1000);
        },
        clearLockout() {
            this.clearLockoutTimer();
            this.lockoutSeconds = 0;
            if (this.errors.verification_code && this.errors.verification_code.indexOf('Too many verification attempts') === 0) {
                this.errors.verification_code = '';
            }
        },
        clearLockoutTimer() {
            if (this.lockoutTimer) {
                clearInterval(this.lockoutTimer);
                this.lockoutTimer = null;
            }
        },
    },
    mounted() {
        this.resend_timer = this.timer;
        //console.log('this.login: ', this.login);
        //console.log('this.method: ', this.method);
        if (this.timer) {
            this.incrementCodeTimer();
        }
        this.$emitter.on('set-otp', this.setCode);
    },
    unmounted() {
        this.$emitter.off('set-otp', this.setCode);
        this.clearLockoutTimer();
    },
};
</script>
