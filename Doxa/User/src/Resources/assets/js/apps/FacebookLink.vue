<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto" :class="processing ? 'pointer-events-none' : ''">
        <ConfirmModal />

        <Header title="Link Facebook account"></Header>

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
            <p class="mb-2">
                <strong>{{ email }}</strong> is already registered.
            </p>
            <p>
                Link your Facebook account to this profile? You will be able to sign in with
                email/password or Facebook.
            </p>
        </div>

        <div class="space-y-3">
            <div>
                <label class="block mb-1 text-sm font-medium" for="password">Confirm with password</label>
                <input class="w-full form-input" type="password" v-model="password" id="password"
                    autocomplete="current-password"
                    :class="errors.password ? '!border-red-500' : ''"
                    @input="errors.password = false" />
                <FieldError :error="errors.password" />
            </div>

            <div class="flex justify-end">
                <button type="button" class="inline-flex items-center justify-center btn-primary" :disabled="processing" @click="linkWithPassword()">
                    <span>Link with password</span>
                    <ButtonSpinner v-if="processing === 'password'" />
                </button>
            </div>

            <div class="relative my-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="px-2 bg-white dark:bg-gray-800 text-gray-400">or</span>
                </div>
            </div>

            <template v-if="!codeSent">
                <button type="button"
                    class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium border border-gray-300 rounded-md hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                    :disabled="processing" @click="sendCode()">
                    <span>Send confirmation code to email</span>
                    <ButtonSpinner v-if="processing === 'send'" />
                </button>
            </template>

            <template v-else>
                <div class="mb-2 sm">
                    Verification instructions have been sent to {{ email }}. Enter verification code below.
                    Code valid for {{ codeExpireIn }} minutes.
                </div>

                <div class="flex flex-col justify-between space-y-3 items-left">
                    <div class="my-4">
                        <Otp />
                        <FieldError :error="errors.code" />
                    </div>
                    <div class="flex justify-start w-full">
                        <button @click="verifyCode()" type="button" :disabled="processing"
                            class="inline-flex justify-center items-center px-4 py-2 text-sm font-medium transition btn-primary hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span>Verify</span>
                            <ButtonSpinner v-if="processing === 'verify'" />
                        </button>
                    </div>

                    <div class="flex flex-col mt-4 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span>Did not receive verification message?</span>
                            <a v-if="resend_timer <= 0" href="#" class="link" @click.prevent="sendCode()">Resend
                                code</a>
                        </div>

                        <span v-if="resend_timer > 0">Resend code in {{ formatResendTimer(resend_timer) }}.</span>
                    </div>
                </div>
            </template>

            <BannerError :error="errors.form" />

            <div class="pt-4 text-sm text-center">
                <a class="text-violet-500 hover:underline" href="/auth/facebook/link/cancel">Cancel</a>
            </div>
        </div>
    </div>
</template>

<script>
import Header from "./components/Header.vue";
import FieldError from "./components/FieldError.vue";
import BannerError from "./components/BannerError.vue";
import ButtonSpinner from "./components/ButtonSpinner.vue";
import Otp from "./components/Otp.vue";
import ConfirmModal from "./components/ConfirmModal.vue";

export default {
    props: ['email'],
    components: { Header, FieldError, BannerError, ButtonSpinner, Otp, ConfirmModal },
    data() {
        return {
            password: '',
            code: '',
            processing: false,
            codeSent: false,
            codeExpireIn: 15,
            resend_timer: 0,
            resend_interval: null,
            errors: {
                password: '',
                code: '',
                form: '',
            },
        };
    },
    methods: {
        linkWithPassword() {
            this.errors.password = '';
            this.errors.form = '';
            if (!this.password) {
                this.errors.password = 'Password is required';
                return;
            }
            this.processing = 'password';
            axios.postForm('/auth/facebook/link/password', { password: this.password })
                .then(response => {
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                        return;
                    }
                    if (!response.data.success) {
                        if (response.data.errors && response.data.errors.password) {
                            const p = response.data.errors.password;
                            this.errors.password = Array.isArray(p) ? p.join(', ') : p;
                        } else {
                            this.errors.form = response.data.error || response.data.message || 'Failed';
                        }
                    }
                    this.processing = false;
                })
                .catch(() => {
                    this.errors.form = 'Request failed';
                    this.processing = false;
                });
        },
        sendCode() {
            if (this.resend_timer > 0) {
                return;
            }
            this.errors.form = '';
            this.errors.code = '';
            this.processing = 'send';
            axios.postForm('/auth/facebook/link/magic')
                .then(response => {
                    if (response.data.confirmation) {
                        this.$emitter.emit('open-confirm-modal', {
                            ...response.data.confirmation,
                            parent: this,
                        });
                    }
                    if (response.data.success) {
                        this.codeSent = true;
                        this.$emitter.emit('clear-otp', true);
                        this.code = '';
                        if (response.data.timer) {
                            clearInterval(this.resend_interval);
                            this.resend_timer = response.data.timer;
                            this.incrementCodeTimer();
                        }
                    } else if (response.data.timer) {
                        clearInterval(this.resend_interval);
                        this.resend_timer = response.data.timer;
                        this.incrementCodeTimer();
                    } else if (!response.data.confirmation) {
                        this.errors.form = response.data.error || response.data.message || 'Failed';
                    }
                    this.processing = false;
                })
                .catch(() => {
                    this.errors.form = 'Request failed';
                    this.processing = false;
                });
        },
        verifyCode() {
            this.errors.code = '';
            this.errors.form = '';
            if (!this.code || this.code.length < 6) {
                this.errors.code = 'Verification code is required';
                return;
            }
            this.processing = 'verify';
            axios.postForm('/auth/facebook/link/verify-code', { code: this.code })
                .then(response => {
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                        return;
                    }
                    if (!response.data.success) {
                        if (response.data.errors && response.data.errors.code) {
                            const c = response.data.errors.code;
                            this.errors.code = Array.isArray(c) ? c.join(', ') : c;
                        } else {
                            this.errors.form = response.data.error || response.data.message || 'Failed';
                        }
                    }
                    this.processing = false;
                })
                .catch(() => {
                    this.errors.form = 'Request failed';
                    this.processing = false;
                });
        },
        setCode(code) {
            this.code = code;
            this.errors.code = '';
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
    },
    mounted() {
        this.$emitter.on('set-otp', this.setCode);
    },
    beforeUnmount() {
        this.$emitter.off('set-otp', this.setCode);
        if (this.resend_interval) {
            clearInterval(this.resend_interval);
        }
    },
};
</script>
