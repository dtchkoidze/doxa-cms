<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto" :class="processing ? 'pointer-events-none' : ''">
        <Header :title="headerTitle"></Header>

        <div class="space-y-3">
            <div class="text-sm">
                <template v-if="state.channel === 'totp'">
                    {{ vocab('vcb.enter_authenticator_code') }}
                </template>
                <template v-else-if="state.channel === 'email'">
                    {{ vocab('vcb.two_factor_code_sent_to_email').replace('{email}', state.masked_email) }}
                </template>
                <template v-else>
                    {{ vocab('vcb.two_factor_code_sent_to_backup_email').replace('{email}', state.masked_backup) }}
                </template>
            </div>

            <div>
                <Otp />
                <FieldError :error="errors.code" />
            </div>

            <div class="flex justify-start">
                <button @click="submit()" type="button" :disabled="locked || processing || form_data.code.length !== 6"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                    <span>{{ locked ? `Try again in ${lockoutCountdown}` : 'Continue' }}</span>
                    <ButtonSpinner v-if="processing" />
                </button>
            </div>

            <BannerError :error="errors.failed" />

            <div v-if="otherChannels.length" class="flex flex-col gap-2 text-sm">
                <a v-for="ch in otherChannels" :key="ch" href="#" class="font-medium text-violet-500"
                    @click.prevent="switchChannel(ch)">
                    {{ channelLabel(ch) }}
                </a>
            </div>
        </div>

        <div class="pt-5 mt-6 text-sm border-t border-gray-100 dark:border-gray-700/60">
            <a class="font-medium text-violet-500" href="/auth/login">Sign In</a>
        </div>
    </div>
</template>

<script>
import Header from "./components/Header.vue";
import FieldError from "./components/FieldError.vue";
import BannerError from "./components/BannerError.vue";
import Otp from "./components/Otp.vue";
import ButtonSpinner from "./components/ButtonSpinner.vue";

export default {
    props: {
        challenge: { type: Object, required: true },
    },
    components: { Header, FieldError, BannerError, Otp, ButtonSpinner },
    data() {
        return {
            state: this.challenge,
            form_data: { code: '' },
            errors: { code: '', failed: '' },
            processing: false,
            lockoutSeconds: 0,
            lockoutTimer: null,
        };
    },
    computed: {
        headerTitle() {
            return vocab('vcb.2fa_authenticator_title');
        },
        otherChannels() {
            return this.state.channels.filter((ch) => ch !== this.state.channel);
        },
        locked() {
            return this.lockoutSeconds > 0;
        },
        lockoutCountdown() {
            const minutes = Math.floor(this.lockoutSeconds / 60);
            const seconds = this.lockoutSeconds % 60;
            return `${minutes}:${String(seconds).padStart(2, '0')}`;
        },
        authDriver() {
            return document.getElementById('auth-app')?.dataset?.authDriver || 'session';
        },
        tokenStorageKey() {
            return document.getElementById('auth-app')?.dataset?.tokenStorageKey || 'mobile_api_token';
        },
    },
    methods: {
        channelLabel(ch) {
            if (ch === 'totp') {
                return 'Use authenticator app code';
            }
            if (ch === 'backup') {
                return 'Send code to backup email';
            }
            return 'Send code to email';
        },
        switchChannel(channel) {
            this.processing = true;
            this.errors.failed = '';
            axios.post(`auth/api/two-factor/challenge/channel`, { channel })
                .then((response) => {
                    if (response.data.success) {
                        this.state = response.data.challenge;
                        this.form_data.code = '';
                        this.$emitter.emit('clear-otp', true);
                    } else {
                        this.errors.failed = response.data.error || 'Error';
                    }
                })
                .catch((error) => {
                    this.errors.failed = error.response?.data?.error || error.message || 'Error';
                })
                .finally(() => {
                    this.processing = false;
                });
        },
        submit() {
            if (this.locked || this.form_data.code.length !== 6) {
                return;
            }
            this.processing = true;
            this.errors.failed = '';
            axios.post(`auth/api/two-factor/challenge`, { code: this.form_data.code })
                .then((response) => {
                    if (response.data.success && response.data.redirect) {
                        if (response.data.token) {
                            localStorage.setItem(this.tokenStorageKey, response.data.token);
                        }
                        window.location.href = response.data.redirect;
                        return;
                    }
                    this.errors.failed = response.data.error || 'Error';
                    this.processing = false;
                })
                .catch((error) => {
                    const data = error.response?.data || {};
                    if (data.retry_after) {
                        this.startLockout(data.retry_after);
                    }
                    this.errors.failed = data.error || error.message || 'Error';
                    this.processing = false;
                });
        },
        startLockout(seconds) {
            this.lockoutSeconds = seconds;
            clearInterval(this.lockoutTimer);
            this.lockoutTimer = setInterval(() => {
                if (this.lockoutSeconds > 0) {
                    this.lockoutSeconds -= 1;
                }
            }, 1000);
        },
        setCode(code) {
            this.form_data.code = code;
        },
    },
    mounted() {
        this.$emitter.on('set-otp', this.setCode);
    },
    unmounted() {
        this.$emitter.off('set-otp', this.setCode);
    },
};
</script>
