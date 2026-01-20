<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto" :class="processing ? 'pointer-events-none' : ''">
        <ConfirmModal />

        <Header title="Welcome back!"></Header>

        <!------------ FORM -------------->
        <div class="space-y-3">

            <!------------ Email -------------->
            <div>
                <label class="block mb-1 text-sm font-medium" for="email">Email Address</label>
                <input class="w-full form-input" type="email" v-model="form_data.email" name="email" id="email"
                    autocomplete="email" inputmode="email"
                    :class="!form_data.email && errors.email ? '!border-red-500' : ''" @input="errors.email = false" />
                <FieldError :error="errors.email" />
            </div>

            <!---------- Password ------------>
            <div>
                <label class="block mb-1 text-sm font-medium" for="password">Password</label>
                <div class="relative">
                    <input class="w-full form-input password" :type="password_visible ? 'text' : 'password'"
                        name="password" id="password" v-model="form_data.password" autocomplete="current-password"
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
                        <input type="checkbox" class="form-checkbox" v-model="form_data.remember" />
                        <span class="ml-2 text-sm">Remember Me</span>
                    </label>
                </div>
                <button @click="submit()" type="button"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition btn-primary hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span>Sign In</span>
                    <i v-if="processing" class="w-4 h-4 ml-2 fa-solid fa-spinner fa-spin-pulse"></i>
                </button>
            </div>

            <!------------ ERROR -------------->
            <BannerError :error="errors.login_failed" />

            <div class="flex items-center justify-end">
                <a class="text-sm underline hover:no-underline" href="/auth/recovery">Forgot Password?</a>
            </div>
            <button v-if="auto_login_enabled && apiInWindow" class="btn btn-primary !bg-gray-700 "
                @click="auto_login_enabled = !auto_login_enabled">
                {{ auto_login_enabled ? 'Disable Auto Login' : 'Enable Auto Login' }}
            </button>
        </div>

        <!------------ FOOTER -------------->
        <div class="pt-5 mt-6 border-t border-gray-100 dark:border-gray-700/60">
            <div class="text-sm">
                Don’t you have an account?
                <a class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/register">
                    Sign Up
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

export default {
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
        }
    },
    components: {
        Header,
        FieldError,
        BannerError,
        ConfirmModal,
    },
    computed: {
        apiInWindow() {
            return false;
            //return window.PasswordCredential;
        }
    },
    methods: {
        submit() {
            this.processing = true;
            this.checkForm();
            if (!this.isError()) {
                axios.postForm(`auth/api/login`, this.form_data)
                    .then(response => {
                        if (response.data.success) {
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
                        }
                        this.processing = false;
                    })
                    .catch(error => {
                        console.log("error: ", error);
                    });
            } else {
                this.processing = false;
            }
        },
        checkForm() {
            this.errors.email = '';
            if (!this.form_data.email) {
                this.errors.email = 'Email is required';
            }

            this.errors.password = '';
            if (!this.form_data.password) {
                this.errors.password = 'Password is required';
            }

        },
        setResponceErrors(errors) {
            for (const [key, value] of Object.entries(errors)) {
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
        // async autoLogin() {
        //     if (window.PasswordCredential && this.auto_login_enabled) {
        //         const credentials = await navigator.credentials.get({
        //             password: true,
        //         });

        //         if (credentials) {
        //             const user = {
        //                 email: credentials.id,
        //                 password: credentials.password,
        //             };

        //             axios.postForm(`auth/api/login`, user)
        //                 .then(response => {
        //                     if (response.data.confirmation) {
        //                         console.log('confirmation open');
        //                         this.$emitter.emit('open-confirm-modal', response.data.confirmation);
        //                         return;
        //                     }
        //                     if (response.data.redirect) {
        //                         window.location.href = response.data.redirect;
        //                         return;
        //                     }
        //                     if (!response.data.success) {
        //                         this.setResponceErrors(response.data.errors);
        //                     }
        //                 })
        //                 .catch(error => {
        //                     console.log("error: ", error);
        //                 });
        //         }
        //     }
        // },
    },

    mounted() {
        // Parse URL parameters for demo access
        const urlParams = new URLSearchParams(window.location.search);
        const emailParam = urlParams.get('email') || urlParams.get('login');
        const passwordParam = urlParams.get('password');

        console.log('emailParam:', emailParam, 'passwordParam:', passwordParam);

        if (emailParam) {
            this.form_data.email = emailParam;
        }

        if (passwordParam) {
            this.form_data.password = passwordParam;
        }

        //this.auto_login_enabled = localStorage.getItem('auto_login') == 'true' ? true : false;
        //this.autoLogin();
    },
    watch: {
        auto_login_enabled: function (newVal) {
            localStorage.setItem('auto_login', newVal);
        }
    }
};
</script>
