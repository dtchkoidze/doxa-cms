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
                <button type="button" class="btn-primary" :disabled="processing" @click="linkWithPassword()">
                    <span>Link with password</span>
                    <i v-if="processing === 'password'" class="w-4 h-4 ml-2 fa-solid fa-spinner fa-spin-pulse"></i>
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
                :disabled="processing" @click="sendMagic()">
                <span>Send confirmation link to email</span>
                <i v-if="processing === 'magic'" class="w-4 h-4 ml-2 fa-solid fa-spinner fa-spin-pulse"></i>
            </button>

            <BannerError :error="errors.form" />
            <div v-if="successMessage" class="p-3 text-sm text-green-700 bg-green-50 rounded dark:bg-green-900/30 dark:text-green-300">
                {{ successMessage }}
            </div>

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

export default {
    props: ['email'],
    components: { Header, FieldError, BannerError },
    data() {
        return {
            password: '',
            processing: false,
            successMessage: '',
            errors: {
                password: '',
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
        sendMagic() {
            this.errors.form = '';
            this.successMessage = '';
            this.processing = 'magic';
            axios.postForm('/auth/google/link/magic')
                .then(response => {
                    if (response.data.success) {
                        this.successMessage = response.data.message;
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
    },
};
</script>
