<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto" :class="processing ? 'pointer-events-none' : ''">
        <ConfirmModal />

        <Header :title="vocab('vcb.user_welcome_back')"></Header>

        <!------------ FORM -------------->
        <div class="space-y-3">

            <!------------ Email -------------->
            <div>
                <label class="block mb-1 text-sm font-medium" for="email">{{ vocab('vcb.email') }}</label>
                <input class="w-full form-input" type="email" v-model="form_data.email" name="email" id="email"
                    autocomplete="email" inputmode="email"
                    :disabled="locked"
                    :class="!form_data.email && errors.email ? '!border-red-500' : ''" @input="errors.email = false" />
                <FieldError :error="errors.email" />
            </div>

            <!---------- Password ------------>
            <div>
                <label class="block mb-1 text-sm font-medium" for="password">{{ vocab('vcb.password') }}</label>
                <div class="relative">
                    <input class="w-full form-input password" :type="password_visible ? 'text' : 'password'"
                        name="password" id="password" v-model="form_data.password" autocomplete="current-password"
                        :disabled="locked"
                        :class="!form_data.password && errors.password ? '!border-red-500' : ''"
                        @input="errors.password = false" />
                    <FieldError :error="errors.password" />
                    <div class="absolute z-10 cursor-pointer top-2 right-3 w-[22px] text-left" id="toggle-password"
                        @click="password_visible = !password_visible">
                        <i v-if="!password_visible" class="fa-regular fa-eye ml-[1px]"></i>
                        <i v-else class="fa-regular fa-eye-slash"></i>
                    </div>
                </div>
            </div>

            <!------------ SUBMIT -------------->
            <div class="flex items-center justify-between">
                <div class="">
                    <label class="flex items-center">
                        <input type="checkbox" class="form-checkbox" v-model="form_data.remember" :disabled="locked" />
                        <span class="ml-2 text-sm">{{ vocab('vcb.user_remember_me') }}</span>
                    </label>
                </div>
                <button @click="submit()" type="button" :disabled="locked || processing"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition btn-primary hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span>{{ locked ? vocab('vcb.user_try_again_in', { time: lockoutCountdown }) : vocab('vcb.log_in') }}</span>
                    <ButtonSpinner v-if="processing" />
                </button>
            </div>

            <!------------ ERROR -------------->
            <BannerError :error="errors.login_failed" />

            <div v-if="googleAuthEnabled || facebookAuthEnabled" class="relative my-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="px-2 bg-white dark:bg-gray-800 text-gray-400">{{ vocab('vcb.or') }}</span>
                </div>
            </div>

            <GoogleAuthButton v-if="googleAuthEnabled" />
            <FacebookAuthButton v-if="facebookAuthEnabled" />

            <div class="flex items-center justify-end">
                <a class="text-sm underline hover:no-underline" href="/auth/recovery">{{ vocab('vcb.user_forgot_password') }}</a>
            </div>
            <button v-if="auto_login_enabled && apiInWindow" class="btn btn-primary !bg-gray-700 "
                @click="auto_login_enabled = !auto_login_enabled">
                {{ auto_login_enabled ? vocab('vcb.user_disable_auto_login') : vocab('vcb.user_enable_auto_login') }}
            </button>
        </div>

        <!------------ FOOTER -------------->
        <div class="pt-5 mt-6 border-t border-gray-100 dark:border-gray-700/60">
            <div class="text-sm">
                {{ vocab('vcb.user_no_account') }}
                <a class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    :href="registerHref">
                    {{ vocab('vcb.register') }}
                </a>
            </div>
        </div>

    </div>

</template>



<script>
import Header from "./components/Header.vue";
import FieldError from "./components/FieldError.vue";
import BannerError from "./components/BannerError.vue";
import ConfirmModal from "./components/ConfirmModal.vue";
import GoogleAuthButton from "./components/GoogleAuthButton.vue";
import FacebookAuthButton from "./components/FacebookAuthButton.vue";
import ButtonSpinner from "./components/ButtonSpinner.vue";

export default {
    props: {
        googleAuthEnabled: { type: Boolean, default: false },
        facebookAuthEnabled: { type: Boolean, default: false },
    },
    data() {
        return {
            form_data: {
                email: '',
                password: '',
                remember: true,
            },
            errors: {
                email: '',
                password: '',
                login_failed: '',
            },
            password_visible: false,
            auto_login_enabled: false,
            processing: false,
            lockoutSeconds: 0,
            lockoutTimer: null,
            lockoutError: '',
        }
    },
    components: {
        Header,
        FieldError,
        BannerError,
        ConfirmModal,
        GoogleAuthButton,
        FacebookAuthButton,
        ButtonSpinner,
    },
    computed: {
        apiInWindow() {
            return false;
            //return window.PasswordCredential;
        },
        locked() {
            return this.lockoutSeconds > 0;
        },
        registerHref() {
            return "/auth/register" + window.location.search;
        },
        lockoutCountdown() {
            const minutes = Math.floor(this.lockoutSeconds / 60);
            const seconds = this.lockoutSeconds % 60;
            return `${minutes}:${String(seconds).padStart(2, '0')}`;
        },
    },
    methods: {
        authDriver() {
            return document.getElementById('auth-app')?.dataset?.authDriver || 'session';
        },
        tokenStorageKey() {
            return document.getElementById('auth-app')?.dataset?.tokenStorageKey || 'mobile_api_token';
        },
        authSuccessUrl() {
            return document.getElementById('auth-app')?.dataset?.authSuccessUrl || '/welcome';
        },
        submit() {
            if (this.locked) {
                return;
            }
            this.processing = true;
            this.checkForm();
            if (!this.isError()) {
                if (this.authDriver() === 'mobile_token') {
                    this.submitMobileToken();
                    return;
                }
                axios.postForm(`auth/api/login`, this.form_data)
                    .then(response => {
                        if (response.data.needs_2fa && response.data.redirect) {
                            this.clearLockout();
                            window.location.href = response.data.redirect;
                            return;
                        }
                        if (response.data.success) {
                            this.clearLockout();
                            this.afterLogin();
                        }
                        if (response.data.confirmation) {
                            console.log('confirmation open');
                            this.$emitter.emit('open-confirm-modal', response.data.confirmation);
                            return;
                        }
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                            return;
                        }
                        if (!response.data.success) {
                            this.setResponceErrors(response.data.errors);
                            if (response.data.retry_after) {
                                this.lockoutError = this.errors.login_failed;
                                this.startLockout(response.data.retry_after);
                            }
                        }
                        this.processing = false;
                    })
                    .catch(error => {
                        console.log("error: ", error);
                        this.processing = false;
                    });
            } else {
                this.processing = false;
            }
        },
        submitMobileToken() {
            axios.post('/api/auth/mobile/login', {
                email: this.form_data.email,
                password: this.form_data.password,
            })
                .then(response => {
                    if (response.data.needs_2fa && response.data.redirect) {
                        this.clearLockout();
                        window.location.href = response.data.redirect;
                        return;
                    }
                    if (response.data.success && response.data.token) {
                        this.clearLockout();
                        localStorage.setItem(this.tokenStorageKey(), response.data.token);
                        this.afterLogin();
                        window.location.href = response.data.redirect || this.authSuccessUrl();
                        return;
                    }
                    if (!response.data.success) {
                        const message = response.data.message || vocab('vcb.user_login_failed');
                        this.errors.login_failed = message;
                        if (response.data.errors) {
                            this.setResponceErrors(response.data.errors);
                        }
                    }
                    this.processing = false;
                })
                .catch(error => {
                    console.log("error: ", error);
                    const message = error.response?.data?.message;
                    if (message) {
                        this.errors.login_failed = message;
                    }
                    this.processing = false;
                });
        },
        checkForm() {
            this.errors.email = '';
            if (!this.form_data.email) {
                this.errors.email = vocab('vcb.email_required');
            }

            this.errors.password = '';
            if (!this.form_data.password) {
                this.errors.password = vocab('vcb.password_required');
            }

        },
        setResponceErrors(errors) {
            for (const [key, value] of Object.entries(errors || {})) {
                if (Array.isArray(value)) {
                    this.errors[key] = value.join(', ');
                } else {
                    this.errors[key] = value;
                }
            }
        },
        isError() {
            return this.errors.email || this.errors.password;
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
            // Убираем только текст блокировки, другую ошибку входа оставляем
            if (this.lockoutError && this.errors.login_failed === this.lockoutError) {
                this.errors.login_failed = '';
            }
            this.lockoutError = '';
        },
        clearLockoutTimer() {
            if (this.lockoutTimer) {
                clearInterval(this.lockoutTimer);
                this.lockoutTimer = null;
            }
        },
        afterLogin() {
            if (window.PasswordCredential) {
                navigator.credentials.store(new window.PasswordCredential({
                    id: this.form_data.email,
                    password: this.form_data.password,
                    name: this.form_data.email,
                }));
            } else {
                console.log("No PasswordCredential API");
            }
        },
    },

    mounted() {
        //this.auto_login_enabled = localStorage.getItem('auto_login') == 'true' ? true : false;
        //this.autoLogin();
    },
    beforeUnmount() {
        this.clearLockoutTimer();
    },
    watch: {
        auto_login_enabled: function (newVal) {
            localStorage.setItem('auto_login', newVal);
        }
    }
};
</script>
