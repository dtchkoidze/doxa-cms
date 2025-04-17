<template>
    <div class="relative">
        <Preloader v-if="loading" />
        <ConfirmModal />

        <!--Head -->
        <div class="flex items-center justify-between gap-4 mb-4 max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-white">
                {{ set.vocab.title }}
            </p>

            <div class="flex gap-x-2.5 items-center">
                <chlo v-if="set.has_variations" :_channels="set.channels" :_current_channel_id="set.current_channel_id"
                    :_current_locale_id="set.current_locale_id" :callback="applyChanelWithLocale" />

                <!-- Cancel Button -->
                <button @click="toIndex()" type="button" class="btn btn-secondary">
                    {{ set.vocab.back.title }}
                </button>
            </div>
        </div>

        <!---- Editing form ---->
        <div class="flex mt-3.5 max-xl:flex-wrap gap-3">

            <!------ Column ---->
            <div v-for="(column, column_index) in set.package.columns" :class="column.classes">

                <!------ Block ---->
                <div v-if="column.blocks" v-for="(block, index) in column.blocks"
                    class="relative bg-white shadow-sm dark:bg-gray-800 rounded-xl">

                    <BlockTitle :title="block?.title" v-if="block.title"/>

                    <!------ Block fields ---->
                    <div class="px-4 py-4">
                        <template v-for="field in block.fields">
                            <template v-if="field.is_variation" v-for="channel in set.channels">
                                <template v-for="locale in channel.locales" :key="locale.id">
                                    <div :channel_id="channel.id" :locale_id="locale.id" class="mb-4 variation_item"
                                        :class="getVisibility(channel.id, locale.id)">
                                        <FieldControl :field="field" :_set="set" :channel_id="channel.id"
                                            :locale_id="locale.id" :fkey="field.key" />
                                    </div>
                                </template>
                            </template>

                            <template v-else>
                                <div class="mb-4">
                                    <FieldControl :field="field" :_set="set" :fkey="field.key"></FieldControl>
                                </div>
                            </template>
                        </template>
                    </div>
                    <!--END-- Block fields ---->

                </div>
                <!--END-- Block ---->

                <div v-if="
                    column_index == 0 && set.history && set.history.length
                " class="p-4 bg-white rounded dark:bg-gray-900 box-shadow">
                    <div class="flex items-center justify-between mb-4 gap-x-4">
                        <div class="flex items-center flex-grow gap-12 pb-3 doxa-block-title">
                            History
                        </div>
                    </div>

                    <div v-for="history in set.history" :key="history.id"
                        class="flex flex-row gap-4 py-1 text-gray-600 dark:text-gray-300">
                        <div>{{ history.created_at }}</div>
                        <div>{{ history.admin_name }}</div>
                    </div>
                </div>
            </div>
            <!--END-- Column ---->

        </div>

        <div class="flex gap-x-2.5 items-center mt-4 justify-end">
            <!--Save Button -->
            <button @click="submit()" type="button" class="btn-primary">
                {{ set.vocab.save }}
            </button>

            <!--Save and Exit Button -->
            <button @click="submit(exit = true)" type="button" class="btn-violet-txt">
                {{ set.vocab.save_and_exit }}
            </button>

            <!--Save and Exit Button -->
            <button @click="toIndex()" type="button" class="btn-secondary">
                {{ set.vocab.cancel }}
            </button>
        </div>

    </div>
</template>

<script>
import BlockTitle from "./elements/BlockTitle.vue";
import Chlo from "../components/chlo-alt.vue";
import Preloader from "../components/preloader.vue";

import FieldControl from "./FieldControl.vue";
import ConfirmModal from "../components/ConfirmModal.vue";

export default {
    data() {
        return {
            test_controls: [
                "input",
                "textarea",
                "checkbox",
                "img",
                "tiny",
                "related",
                "tag",
            ],
            set: this._set,
            loading: false,
            fd: new FormData(),
            id: this._id,
            variation_alert: false,
            alerts_for_variations: false,
            initial_set: null,
        };
    },
    props: {
        _set: Object,
        skey: String,
        _id: String | Number,
        method: String,
    },
    components: {
        BlockTitle,
        Chlo,
        Preloader,
        FieldControl,
        ConfirmModal,
    },
    created() { },

    mounted() {
        document.addEventListener("keydown", this.submitOnCtrlEnter);
        this.initial_set = JSON.parse(JSON.stringify(this.set));
        console.log(this.method);
    },

    unmounted() {
        document.removeEventListener("keydown", this.submitOnCtrlEnter);
    },

    computed: {
        unsaved_changes() {
            return JSON.stringify(this.set) !== JSON.stringify(this.initial_set);
        },
    },

    methods: {
        submitOnCtrlEnter(e) {
            if (e.key === "Enter" && e.ctrlKey) {
                e.preventDefault();
                this.submit();
            }
        },

        getUpdateDate(channel_id, locale_id) {
            return channel_id && locale_id
                ? this.set.item.assoc_variations[channel_id]?.[locale_id]
                    ?.updated_at_formatted
                : this.set.item.updated_at_formatted;
        },
        getAdminName(channel_id, locale_id) {
            return channel_id && locale_id
                ? this.set.item.assoc_variations[channel_id]?.[locale_id]
                    ?.admin_name
                : this.set.item?.admin_name;
        },
        isTest(field) {
            return true;
            //return this.test_controls.includes(field.control);
        },
        getVisibility(channel_id, locale_id) {
            return channel_id == this.set.current_channel_id &&
                locale_id == this.set.current_locale_id
                ? ""
                : "hidden";
        },
        applyChanelWithLocale(channel_id, locale_id) {
            this.set.current_channel_id = channel_id;
            this.set.current_locale_id = locale_id;
        },
        submit(exit) {
            this.loading = true;

            const config = {
                headers: { "Content-Type": "multipart/form-data" },
            };

            console.log('this.method: ',this.method);

            let url;
            switch (this.method) {
                case 'create':
                    url = "/admin/" + this.set.module + "/create";
                    break;
                case 'edit':
                    url = "/admin/" + this.set.module + "/update/" + this.id;
                    break;
                case 'copy':
                    url = "/admin/" + this.set.module + "/create";
                    break;    
            }


            axios
                .postForm(
                    url,
                    this.set.data
                )
                .then((response) => {
                    this.loading = false;
                    this.$emitter.emit("add-flash", response.data.notification);

                    if (response.data.success) {
                        this.set.errors = false;
                        this.id = response.data.result;
                        this.initial_set = JSON.parse(JSON.stringify(this.set));
                        if (exit) {
                            this.toIndex({ saved: true });
                        }
                    } else {
                        this.set.errors = response.data.result;

                        if (this.set.errors.variation_alert) {
                            this.alerts_for_variations = {};

                            for (var channel_id in this.set.channels) {
                                for (var locale_id in this.set.channels[channel_id].locales) {
                                    for (var i = 0; i < this.set.errors.variation_alert.length; i++
                                    ) {
                                        var ch = this.set.errors.variation_alert[i];
                                        if (ch.channel.id != channel_id || ch.locale.id != locale_id) {
                                            if (!this.alerts_for_variations[channel_id]) {
                                                this.alerts_for_variations[channel_id] = {};
                                            }
                                            if (!this.alerts_for_variations[channel_id][locale_id]) {
                                                this.alerts_for_variations[channel_id][locale_id] = [];
                                            }
                                            this.alerts_for_variations[channel_id][locale_id].push(ch);
                                        }
                                    }
                                }
                            }

                            console.log("this.alerts_for_variations", this.alerts_for_variations);
                        }
                    }
                })
                .catch((error) => {
                    this.loading = false;
                    this.$emit("add-flash", {
                        type: "error",
                        message: error.message,
                    });
                });

        },

        switchChLo(channel, locale) {
            this.$emitter.emit("switch-chlo", {
                channel: channel,
                locale: locale,
            });
        },
        prepareSubmit() {
            //console.log('submit',this.set.data);
            for (var key in this.set.data) {
                //console.log('key', key);

                var val = this.set.data[key];
                //console.log('val', val);
                if (key == "variation") {
                    //console.log('variation', val);
                    for (var channel_id in this.set.channels) {
                        for (var locale_id in this.set.channels[channel_id]
                            .locales) {
                            for (var fkey in val[channel_id][locale_id]) {
                                this.fd.append(
                                    "variation[" + channel_id + "][" + locale_id + "][" + fkey + "]",
                                    val[channel_id][locale_id][fkey]
                                );
                            }
                        }
                    }
                } else {
                    if (Array.isArray(val)) {
                        //console.log('Array.isArray val');
                        for (var i = 0; i < val.length; i++) {
                            if (val[i].type == "image") {
                                //console.log('val[i].id: ',val[i].id);
                                if (val[i].file) {
                                    this.fd.append(
                                        key + "[" + val[i].id + "]",
                                        val[i].file
                                    );
                                } else {
                                    this.fd.append(
                                        key + "[" + val[i].id + "]",
                                        val[i].url
                                    );
                                }
                            } else {
                                this.fd.append(
                                    key + "[" + val[i].id + "]",
                                    val[i].id
                                );
                            }
                        }
                        if (!val.length) {
                            this.fd.append(key, "");
                        }
                    } else {
                        this.fd.append(key, this.set.data[key]);
                    }
                }
            }
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
                        this.$emitter.emit("toIndex", {
                            route: this.set.vocab.back.link,
                            use_route: false,
                        });
                    },
                    disagree: () => {
                    }
                });
                return;
            }

            this.$emitter.emit("toIndex", {
                route: this.set.vocab.back.link,
                use_route: false,
            });
        },
    },

};
</script>

<style></style>
