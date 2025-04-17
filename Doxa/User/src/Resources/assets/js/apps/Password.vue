<template>
    <div class="w-full max-w-sm px-4 py-8 mx-auto" :class="processing ? 'pointer-events-none' : ''">
        <ConfirmModal />

        <Header :title="method == 'register' ? 'Set up Your password' : 'Set a new password'"></Header>

        <!------------ FORM -------------->
        <div class="space-y-3">

            <!---------- Password ------------>
            <div>
                <label class="block mb-1 text-sm font-medium" for="password">Password <span
                        class="text-red-500">*</span></label>
                <div class="relative">
                    <input class="w-full form-input" :type="password_visible ? 'text' : 'password'"
                        v-model="form_data.password" autocomplete="new-password"
                        :class="!form_data.password && errors.password ? '!border-red-500' : ''"
                        @input="passwordValidate()" @keypress="notSpace($event)" />
                    <div class="pt-1 text-xs text-red-600" v-if="errors.password">{{ errors.password }}</div>
                    <div class="absolute z-2 cursor-pointer top-2 right-3 w-[22px] text-left" id="toggle-password"
                        @click="password_visible = !password_visible">
                        <i v-if="!password_visible" class="fa-regular fa-eye ml-[1px]"></i>
                        <i v-else class="fa-regular fa-eye-slash"></i>
                    </div>
                </div>
            </div>

            <!---------- Password confirmation ------------>
            <div>
                <label class="block mb-1 text-sm font-medium" for="password">Confirm Password <span
                        class="text-red-500">*</span></label>
                <div class="relative">
                    <input class="w-full form-input" :type="password_visible ? 'text' : 'password'"
                        v-model="form_data.password_confirmation" autocomplete="new-password"
                        :class="!form_data.password_confirmation && errors.password_confirmation ? '!border-red-500' : ''"
                        @input="errors.password_confirmation = false" />
                    <div class="pt-1 text-xs text-red-600" v-if="errors.password_confirmation">{{
                        errors.password_confirmation }}</div>
                </div>
            </div>

            <!---------- Password requirements ------------>
            <div>
                <label class="block mb-1 text-sm font-medium" for="password">Password requirements</label>
                <div class="text-xs text-gray-400" :class="password_validate.ln ? 'text-green-700' : ''"><i
                        class="pr-1 fa-solid fa-check"></i> Length 8-20</div>
                <div class="text-xs text-gray-400" :class="password_validate.uppercase ? 'text-green-700' : ''"><i
                        class="pr-1 fa-solid fa-check"></i> One uppercase letter</div>
                <div class="text-xs text-gray-400" :class="password_validate.number ? 'text-green-700' : ''"><i
                        class="pr-1 fa-solid fa-check"></i> One number</div>
                <div class="text-xs text-gray-400" :class="password_validate.spec ? 'text-green-700' : ''"><i
                        class="pr-1 fa-solid fa-check"></i> Any symbol except number and letter</div>
            </div>

            <!------------ SUBMIT -------------->
            <div class="flex items-center justify-end">
                <button type="button" class="ml-3 btn-primary" @click="submit()">Save password</button>
            </div>

            <!------------ ERROR -------------->
            <div class="px-3 py-2 text-sm text-black bg-red-100 rounded-lg" v-if="errors.register_failed">
                {{ errors.failed }}
            </div>

        </div>

        <!------------ FOOTER -------------->
        <!-- <div class="pt-5 mt-6 border-t border-gray-100 dark:border-gray-700/60">
            <div class="text-sm">
                Already have an account?
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/login">
                    Sign In
                </a>
            </div>
        </div>     -->

    </div>

</template>



<script>
import Header from "./components/Header.vue";
import ConfirmModal from "./components/ConfirmModal.vue";

export default {
    props: ['method'],
    data() {
        return {
            form_data: {
                password: '',
                password_confirmation: '',
            },
            errors: {
                password: '',
                password_confirmation: '',
                failed: '',
            },
            password_validate: {
                ln: false,
                uppercase: false,
                spec: false,
                number: false
            },
            password_visible: false,
            password_valid: true,
            processing: false,
        }
    },
    components: {
        Header,
        ConfirmModal,
    },
    methods: {
        submit() {
            this.processing = true;
            this.checkForm();
            if (!this.isError()) {
                axios.postForm(`auth/api/${this.method}/set_password`, this.form_data)
                    .then(response => {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                            return;
                        }
                        if (!response.data.success) {
                            this.setResponceErrors(response.data.errors);
                        } else {
                            if (response.data.confirmation) {
                                response.data.confirmation.parent = this;
                                this.$emitter.emit('open-confirm-modal', response.data.confirmation);
                            }
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
            this.errors.password = '';
            this.errors.password_confirmation = '';
            if (!this.form_data.password.trim()) {
                this.errors.password = 'Password is required';
            } else {
                if (!this.isPasswordValid()) {
                    this.errors.password = 'Password does not meet the requirements';
                } else {
                    if (!this.form_data.password_confirmation.trim()) {
                        this.errors.password_confirmation = 'Password confirmation is required';
                    } else {
                        if (this.form_data.password !== this.form_data.password_confirmation) {
                            this.errors.password_confirmation = 'Passwords does not match';
                        }
                    }
                }
            }
        },
        setResponceErrors(errors) {
            for (const [key, value] of Object.entries(errors)) {
                console.log(key, value);
                if (Array.isArray(value)) {
                    this.errors[key] = value.join(', ');
                } else {
                    this.errors[key] = value;
                }
            }
        },
        isError() {
            return this.errors.password || this.errors.password_confirmation;
        },
        passwordValidate() {
            //console.log('this.form_data.password: ',this.form_data.password);
            this.errors.password = false;
            if (this.form_data.password.length < 8 || this.form_data.password.length > 20) {
                this.password_validate.ln = false;
            } else {
                this.password_validate.ln = true;
            }
            if (this.form_data.password.search(/[A-Z]/) == -1) {
                this.password_validate.uppercase = false;
            } else {
                this.password_validate.uppercase = true;
            }
            if (this.form_data.password.search(/[0-9]/) == -1) {
                this.password_validate.number = false;
            } else {
                this.password_validate.number = true;
            }
            if (this.form_data.password.search(/[^a-zA-Z0-9\s]/) == -1) {
                this.password_validate.spec = false;
            } else {
                this.password_validate.spec = true;

            }
        },
        isPasswordValid() {
            for (var key in this.password_validate) {
                if (!this.password_validate[key]) {
                    return false;
                }
            }
            return true;
        },
        notSpace: function (evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode == 32) {
                evt.preventDefault();;
            } else {
                return true;
            }
        },
    },
    mounted() {
        this.passwordValidate();
    }
};
</script>
