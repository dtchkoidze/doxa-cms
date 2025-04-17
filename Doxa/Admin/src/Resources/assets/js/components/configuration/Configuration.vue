<template>
    <ConfirmModal />
    <Flash ref="flash" />
    <div class="container">
        <div v-if="rows.length" v-for="(row, row_index) in rows" :key="row_index" class="row">
            <div>
                <div class="doxa-block-title flex items-center justify-between p-2">
                    <div>{{ row.title }}</div>
                    <button @click="handle_row_collapse(row)">
                        <i :class="['fas', row.collapsed ? 'fa-chevron-down' : 'fa-chevron-up']"></i>
                    </button>
                </div>
                <transition name="collapse">
                    <div v-if="!row.collapsed">
                        <div v-for="(value, field) in row.fields" :key="field" class="field">
                            <div class="flex flex-col justify-start gap-y-2">
                                <span v-if="value?.errors?.length" class="text-red-600">{{ value.errors[0] }}</span>
                                <component v-else :is="get_component(value.control)" :value="row.fields[field].value"
                                    :params="{ field: value }" @update-value="handle_update($event, row_index, field)">
                                    <template v-slot:label>
                                        <span class="dark:text-gray-300">{{ value.title }}</span>
                                    </template>
                                    <template v-slot:error v-if="get_errors(`${row.name}.${value.key}`)">
                                        <span class="text-red-500">{{ get_errors(`${row.name}.${value.key}`) }}</span>
                                    </template>
                                </component>

                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
        <div class="flex gap-x-2.5 items-center mt-4 justify-end">
            <button @click="save()" type="button" class="btn-primary">
                Save
            </button>

            <button @click="save(exit = true)" type="button" class="btn-violet-txt">
                Save and Exit
            </button>

            <button @click="toIndex()" type="button" class="btn-secondary">
                Cancel
            </button>
        </div>
    </div>
</template>

<script>
import { defineAsyncComponent, markRaw } from 'vue';
import ConfirmModal from '../ConfirmModal.vue';
import Flash from '../../tools/flash.vue';
import OmniValidator from './modules/omniValidator';
export default {
    data() {
        return {
            rows: [],
            component_cache: {},
            initial_data: null,
            validator: null,
            errors: {},
        };
    },
    components: {
        ConfirmModal,
        Flash,
    },
    computed: {
        unsaved_changes() {
            return JSON.stringify(this.rows) !== JSON.stringify(this.initial_data);
        },

        get_errors() {
            return (key) => {
                console.log("this.errors: ", this.errors);
                console.log("key: ", key);
                console.log("this.errors[key]: ", this.errors[key]);
                if (this.errors[key]) {
                    let err = this.errors[key];
                    return err;
                }
            };
        },
    },

    methods: {

        get_file_name(field_name) {
            let component_name = field_name.toLowerCase();
            return `${component_name.trim()}`;
        },

        handle_update(value, row_index, field) {
            this.rows[row_index].fields[field].value = value;
        },

        get_section_collapsed_state(section_name) {
            let state = localStorage.getItem('state-' + section_name) == 'collapsed';
            return state;
        },

        handle_row_collapse(row) {
            row.collapsed = !row.collapsed;
            localStorage.setItem(`state-${row.name}`, row.collapsed ? 'collapsed' : 'expanded');
        },

        get_component(control_name) {
            let supported_fields = ['input', 'checkbox', 'text', 'datetime', 'img', 'textarea', 'tiny'];
            if (!supported_fields.includes(control_name)) {
                return;
            }

            if (!this.component_cache[control_name]) {
                let file_name = this.get_file_name(control_name);

                this.component_cache[control_name] = markRaw(defineAsyncComponent(() =>
                    import(`./fields/${file_name}.vue`)
                        .catch(error => {
                            console.info(`Failed to load component for field: ${control_name}`, error);
                        })
                ));
            }

            return this.component_cache[control_name];
        },

        fetch_data() {
            axios.get('admin/api/settings/configuration')
                .then(res => {
                    if (res.data.status == 'success') {
                        this.rows = res.data.data;

                        console.log("this.rows: ", this.rows);
                        for (let row of this.rows) {
                            row.collapsed = this.get_section_collapsed_state(row.name);
                        }
                        this.set_initial_data();
                    } else {
                        console.info("Err happened");
                    }
                })
                .catch(error => {
                    console.error('Failed to fetch configuration data.', error);
                });
        },
        async save(exit = false) {
            if (!this.unsaved_changes) {
                this.$emitter.emit('add-flash', {
                    type: 'info',
                    message: 'No changes to save.'
                });

                if (!exit) {
                    return;
                }
            }

            if (exit) {
                this.toIndex({ saved: true });
                return;
            }


            let formData = new FormData();
            let initial_data = JSON.parse(JSON.stringify(this.initial_data));
            let validation_data = {};

            // todo: trim inputs
            for (let row of this.rows) {
                let initial_row = initial_data.find(r => r.name === row.name);

                if (!initial_row) continue;

                for (let field of row.fields) {
                    let key_name = `${row.name}.${field.key}`;

                    if (!validation_data[key_name]) {
                        validation_data[key_name] = {};
                    }

                    validation_data[key_name].rules = field.validation_rules;
                    validation_data[key_name].value = field.value;


                    let initial_field = initial_row.fields.find(f => f.key === field.key);
                    if (!initial_field) continue;
                    if (field.value === initial_field.value) continue;

                    if (field.type === 'image') {
                        if (Array.isArray(field.value) && !field.value.length) {
                            formData.append(`${key_name}`, 'delete-image');
                        }
                    }
                    if (field.type === 'image' && Array.isArray(field.value)) {
                        field.value.forEach((fileObj, index) => {
                            if (fileObj.file instanceof File) {
                                formData.append(`${key_name}[${index}]`, fileObj.file);
                            } else {
                            }
                        });
                    } else {
                        formData.append(key_name, field.value);
                    }
                }
            }

            let validated = await this.validator.validate(validation_data);

            if (!validated) {
                let errs = this.validator.get_errors();
                this.errors = errs;
                for (let key of Object.keys(errs)) {
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: errs[key],
                    });
                }
                return;
            }

            axios.post('admin/api/settings/configuration', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
                .then((res) => {
                    let { status, data } = res.data;
                    let success = status == 'success';
                    if (success) {
                        this.set_initial_data();
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: 'Configuration saved successfully.'
                        });
                    } else {
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: 'Failed to save configuration.'
                        });
                    }
                })
                .catch((err) => {
                    console.error(err);
                });
        },
        set_initial_data() {
            this.initial_data = JSON.parse(JSON.stringify(this.rows));
        },
        toIndex({ saved = false } = {}) {
            if (this.unsaved_changes && !saved) {
                this.$emitter.emit("open-confirm-modal", {
                    title: "Hold on a second!",
                    message: "You have unsaved changes. Are you sure you want to leave?",
                    options: {
                        btnAgree: "Yes",
                        btnDisagree: "No"
                    },
                    agree: () => {
                        window.location.href = '/admin/';
                    },
                    disagree: () => {
                    }
                });
                return;
            }

            window.location.href = '/admin/';
        },
    },

    mounted() {
        this.fetch_data();
        this.validator = new OmniValidator;
    }
}
</script>







<style scoped>
.container {
    margin: 1rem;
}

.row {
    @apply bg-white dark:bg-gray-800 mb-2 p-2 rounded-lg;
}

.subrows {
    padding-left: 1rem;
}

.section-title {
    @apply flex items-center justify-between p-4 rounded-b-lg text-black bg-gray-100;
}

.subrow {
    border-left: 2px solid #eee;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
}

.field {
    @apply m-2 my-6 text-black text-base;
}

.collapse-enter-active,
.collapse-leave-active {
    transition: max-height 0.4s ease, opacity 0.4s ease;
    overflow: hidden;
}

.collapse-enter-from,
.collapse-leave-to {
    max-height: 0;
    opacity: 0;
}

.collapse-enter-to,
.collapse-leave-from {
    max-height: 1000px;
    opacity: 1;
}
</style>
