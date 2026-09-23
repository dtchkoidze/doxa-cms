<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto" :class="processing ? 'pointer-events-none' : ''">
        <Header title="Link Google account"></Header>

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
            <p class="mb-2">
                <strong>{{ email }}</strong> is already registered.
            </p>
            <p>
                Link your Google account to this profile? You will be able to sign in with
                email/password or Google.
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

            <button type="button"
                class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium border border-gray-300 rounded-md hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                :disabled="processing" @click="sendCode()">
                <span>{{ codeSent ? 'Resend confirmation code' : 'Send confirmation code to email' }}</span>
                <ButtonSpinner v-if="processing === 'send'" />
            </button>

            <div v-if="codeSent" class="space-y-3">
                <div v-if="successMessage" class="p-3 text-sm text-green-700 bg-green-50 rounded dark:bg-green-900/30 dark:text-green-300">
                    {{ successMessage }}
                </div>
                <div>
                    <Otp />
                    <FieldError :error="errors.code" />
                </div>
                <div class="flex justify-end">
                    <button type="button" class="inline-flex items-center justify-center btn-primary" :disabled="processing" @click="verifyCode()">
                        <span>Verify code</span>
                        <ButtonSpinner v-if="processing === 'verify'" />
                    </button>
                </div>
            </div>

            <BannerError :error="errors.form" />

            <div class="pt-4 text-sm text-center">
                <a class="text-violet-500 hover:underline" href="/auth/google/link/cancel">Cancel</a>
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

export default {
    props: ['email'],
    components: { Header, FieldError, BannerError, ButtonSpinner, Otp },
    data() {
        return {
            password: '',
            code: '',
            processing: false,
            codeSent: false,
            successMessage: '',
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
            this.successMessage = '';
            if (!this.password) {
                this.errors.password = 'Password is required';
                return;
            }
            this.processing = 'password';
            axios.postForm('/auth/google/link/password', { password: this.password })
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
            this.errors.form = '';
            this.errors.code = '';
            this.successMessage = '';
            this.processing = 'send';
            axios.postForm('/auth/google/link/magic')
                .then(response => {
                    if (response.data.success) {
                        this.codeSent = true;
                        this.successMessage = response.data.message;
                        this.$emitter.emit('clear-otp', true);
                        this.code = '';
                    } else {
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
            axios.postForm('/auth/google/link/verify-code', { code: this.code })
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
    },
    mounted() {
        this.$emitter.on('set-otp', this.setCode);
    },
    beforeUnmount() {
        this.$emitter.off('set-otp', this.setCode);
    },
};
</script>
