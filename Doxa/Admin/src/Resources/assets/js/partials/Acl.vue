<template class="">
    <Flash ref="flash" />
    <div class="mx-8">
        <div class="flex items-end justify-end mb-4">
            <button @click="saveNodes" class="btn-primary ">
                <i class="fa mr-2" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                Save
            </button>
        </div>
        <div class="flex justify-between ">
            <div>
                <tree-view :tree="tree"></tree-view>
            </div>
            <div class="flex-row">
                <h1>
                    {{ role.name }}
                </h1>
            </div>
        </div>
    </div>

</template>

<script>
import TreeView from '../components/TreeView.vue';
import Flash from '../tools/flash.vue';

export default {
    components: {
        TreeView,
        Flash,
    },
    props: {
        tree: {
            type: Object,
            required: true,
        },
        role: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            changesQueue: [],
            toastOpen: false,
            saving: false,
        };
    },
    methods: {
        registerEvents() {
            this.$emitter.on('node-checked', (node) => {
                let { key, checked } = node;
                let role_id = this.role.id;

                const existingIndex = this.changesQueue.findIndex((change) => change.key === key);
                if (existingIndex !== -1) {
                    this.changesQueue[existingIndex] = { key, checked, role_id };
                } else {
                    this.changesQueue.push({ key, checked, role_id });
                }
            });
        },

        saveNodes() {
            if (this.changesQueue.length === 0) {
                this.$emitter.emit('add-flash', {
                    type: 'info',
                    message: 'No changes to save.'
                });
                return;
            }

            this.saving = true;

            axios.post('/admin/api/settings/role_permissions/update', { changes: this.changesQueue, role_id: this.role.id })
                .then((response) => {
                    console.log('Nodes saved successfully:', response.data);
                    this.$emitter.emit('add-flash', {
                        type: 'success',
                        message: response.data.message
                    });
                    this.changesQueue = [];
                    this.saving = false;
                })
                .catch((error) => {
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: response.data.message
                    });
                    this.saving = false;
                });
        },
    },

    mounted() {
        this.registerEvents();
    },
};
</script>
