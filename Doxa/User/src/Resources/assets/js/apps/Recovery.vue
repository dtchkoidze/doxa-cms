<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto" :class="processing ? 'pointer-events-none' : ''">
        <ConfirmModal/>

        <Header title="Enter your email"></Header>

        <!------------ FORM -------------->
        <div class="space-y-3">

            <div class="mb-2 sm">
                Enter your email please. We'll send you recovery code and instructions.
            </div>

            <!------------ Email -------------->
            <div>
                <label class="block mb-1 text-sm font-medium" for="email">Email Address <span class="text-red-500">*</span></label>
                <input
                    class="w-full form-input"
                    type="email"
                    v-model="form_data.email"
                    name="email" 
                    id="email" 
                    autocomplete="email" 
                    inputmode="email"
                    :class="errors.email ? '!border-red-500' : ''"
                    @input="errors.email = false; errors.register_failed = false"
                />
                <FieldError :error="errors.email" />
            </div>

            <!------------ SUBMIT -------------->
            <div class="flex items-center justify-end">
                <button type="button" class="ml-3 btn-primary" @click="submit()">Next</button>
            </div>

            <BannerError :error="errors.recovery_failed" />

        </div>

        <!------------ FOOTER -------------->
        <div class="flex flex-row justify-between gap-2 pt-5 mt-6 text-sm border-t border-gray-100 dark:border-gray-700/60">
            <a class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400" href="/auth/login">
                Sign In
            </a>
            <a class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400" href="/auth/register">
                Sign Up
            </a>
        </div>

    </div>

</template>



<script>
import Header from "./components/Header.vue"
import Dropdown from './components/DropdownFull.vue';
import ConfirmModal from "./components/ConfirmModal.vue";
import FieldError from "./components/FieldError.vue";
import BannerError from "./components/BannerError.vue";

export default {
    props: ['roles'],
    data() {
        return {
            form_data: {
                email: '',
            },
            errors: {
                email: '',
                recovery_failed: '',
            },
            processing: false,
        }
    },
    components: {
        Header,
        Dropdown,
        ConfirmModal,
        FieldError,
        BannerError,
    },
    methods: {
        submit() {
            this.processing = true;
            this.checkForm();
            if(!this.isError()) {
                axios.postForm(`/auth/api/recovery/check_login`, this.form_data)
                .then(response => {
                    if(!response.data.success){
                        this.setResponceErrors(response.data.errors);
                        this.processing = false;
                    } else {
                        if(response.data.confirmation){
                            response.data.confirmation.parent = this;
                            this.$emitter.emit('open-confirm-modal', response.data.confirmation);
                            this.processing = false;
                        } else {
                            if(response.data.success && response.data.redirect){
                                window.location.href = response.data.redirect;
                                return;
                            }
                        }
                    }
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
            if(!this.form_data.email.trim()) {
                this.errors.email = 'Email is required';
            } else {
                var email_valid = emailValidate(this.form_data.email);
                if(!email_valid) {
                    this.errors.email = 'Email is not valid';
                }
            }
        },
        setResponceErrors(errors) {
            for (const [key, value] of Object.entries(errors)) {
                console.log(key, value);
                if(Array.isArray(value)){
                    this.errors[key] = value.join(', ');
                } else {
                    this.errors[key] = value;
                }
            }
        },
        isError() {
            return this.errors.email;
        },
    },
};
</script>
