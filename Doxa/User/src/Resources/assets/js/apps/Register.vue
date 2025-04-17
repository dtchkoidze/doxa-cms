<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto" :class="processing ? 'pointer-events-none' : ''">
        <ConfirmModal />

        <Header title="Sign Up"></Header>

        <!------------ FORM -------------->
        <div class="space-y-3">

            <!------------ Roles -------------->
            <div v-if="_roles">
                <label class="block mb-1 text-sm font-medium" for="role">
                    Your Role <span class="text-red-500">*</span>
                </label>
                <dropdown :options="_roles" :_selected="form_data.role" />
                <FieldError :error="errors.role" />
            </div>

            <!------------ Email -------------->
            <div>
                <label class="block mb-1 text-sm font-medium" for="email">Email Address <span
                        class="text-red-500">*</span></label>
                <input 
                    class="w-full form-input" 
                    type="email" 
                    v-model="form_data.email" 
                    name="email" 
                    id="email" 
                    autocomplete="email" 
                    inputmode="email"
                    :class="errors.email ? '!border-red-500' : ''"
                    @input="errors.email = false; errors.register_failed = false" />
                <FieldError :error="errors.email" />
            </div>

            <!------------ SUBMIT -------------->
            <div class="flex items-center justify-end">
                <button type="button" class="ml-3 btn-primary hover:bg-gray-800 hover:ring-2 hover:ring-sky-800"
                    @click="submit()">Sign Up</button>
            </div>

            <BannerError :error="errors.register_failed" />

        </div>

        <!------------ FOOTER -------------->
        <div class="pt-5 mt-6 border-t border-gray-100 dark:border-gray-700/60">
            <div class="text-sm">
                Already have an account?
                <a class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/login">
                    Sign In
                </a>
            </div>
        </div>

    </div>

</template>



<script>
import Header from "./components/Header.vue";
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
                role: 0,
            },
            errors: {
                email: '',
                role: '',
                register_failed: '',
            },
            _roles: this.roles,
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
                axios.postForm(`/auth/api/register`, this.form_data)
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

            if(this.roles){
                if(this.form_data.role == 0) {
                    this.errors.role = 'Choose Your role';
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
            if(this.roles){
                if(this.errors.role) {
                    return true;
                }
            }
            return this.errors.email;
        },
        setRole(role) {
            this.form_data.role = role;
            this.errors.role = false;
        },
        removeAccount() {
            axios.get(`api/auth/remove_account`)
                .then(response => {
                    this.$emitter.emit('open-confirm-modal', response.data.confirmation);
                })
                .catch(error => {
                    console.log("error: ", error);
                });
        },
        confirmAccount() {
            axios.get(`auth/api/register/resend-verification-code`)
                .then(response => {
                    if(response.data.url){
                        window.location.href = response.data.url;
                        return;
                    }
                    if(!response.data.success && response.data.error){
                        this.errors.register_failed = response.data.error;
                        return;
                    }
                    if(response.data.success && response.data.confirmation){
                        this.errors.register_failed = '';
                        this.$emitter.emit('open-confirm-modal', response.data.confirmation);
                        return;
                    }

                })
                .catch(error => {
                    console.log("error: ", error);
                });
        },
    },
    created() {
        this._roles = this.roles;
        if(this._roles){
            this._roles.unshift({
                id: 0,
                title: 'Select your Role',
            })
        }
    },
    mounted() {
        this.$emitter.on('select-option', this.setRole);
    },
    unmounted() {
        this.$emitter.off('select-option', this.setRole);
    },
};
</script>
