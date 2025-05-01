<template>
    <template v-if="structure">
        <template v-for="(set, index) in structure" key="index">
            <EditForm :_set="set" :skey="index"  :copied="copied" :_id="copied == 1?0:id" ref="EditForm" :method="method"></EditForm>
        </template>
    </template>
    <template v-else>
        no structure
    </template>
    <template v-if="error">
        <div class="flex items-center justify-center pt-10">
            <div class="px-10 py-5 mt-[200px] text-red-500 border rounded">
                {{ error }}
            </div>
        </div>
    </template>

</template>

<script>
import EditForm from "../../edit/EditForm.vue";

import { computed, ref } from 'vue'

export default {
    data() {
       return {
            structure: {},
            set: Object,
            current_channel_id: '',
            current_locale_id: '',
            csrf_token: '',
            error: null,
        }
    },
    props: {
        id: String|Number,
        module: String,
        method: String,
        copied: {
            //type: String,
            default: '',
        }
    },
    components: {
        EditForm,
        // Flash,
    },
    provide() {
        return {
            current_channel_id: computed(() => this.current_channel_id),
            current_locale_id: computed(() => this.current_locale_id),
            csrf_token: computed(() => this.csrf_token),
            vocab: computed(() => this.set.vocab),
        }
    },
    created() {
        axios.get('/admin/'+this.module+'/get_item_set/'+this.id, {params: {method: this.method}})
            .then(response => {
                if(!response.data.success){
                    this.error = response.data.notification.message;
                    return;
                }

                this.error = null;
                this.set = response.data;

                //console.log('axios current_locale_id: '+this.set.current_locale_id);

                document.title = this.set.vocab.title;

                this.setCurrentChannelAndLocale();

                this.csrf_token = this.set.csrf_token;

                this.structure[this.getStructureKey()] = this.set;
            })
            .catch((error) => {
                console.log(error);
            });
    },
    methods: {
        setCurrentChannelAndLocale() {
            var currentDatagrid;
            let datagrids = this.getDatagrids();
            if (datagrids?.length) {
                currentDatagrid = datagrids.find(({
                    module
                }) => module === this.module);
            }

            if(currentDatagrid?.applied.current_channel_id){
                this.set.current_channel_id = currentDatagrid?.applied.current_channel_id;
            }

            if(currentDatagrid?.applied.current_locale_id){
                this.set.current_locale_id = currentDatagrid?.applied.current_locale_id;
            }
        },
        getStructureKey() {
            return this.set.module + '-' + this.id
        },
        getDatagridsStorageKey() {
            return 'dgrids';
        },

        getDatagrids() {
            let datagrids = localStorage.getItem(
                this.getDatagridsStorageKey()
            );
            return JSON.parse(datagrids) ?? [];
        },
    },

};
</script>

