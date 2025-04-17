<template>
    <Flash ref="flash" />
    <OmniEdit
        v-if="_mode === 'edit'"
        :id="_id"
        :module="module"
        :copied="_copied"
        :method="_method"
        ref="OmniEdit"
    />
    <OmniDatagrid
        v-if="_mode === 'datagrid'"
        :module="module"
        ref="OmniDatagrid"
    />
</template>

<script>
import { computed, ref } from "vue";

// import OmniEdit from "../Modes/OmniEdit.vue";
import OmniDatagrid from "./mode/OmniDatagrid.vue";
import OmniEdit from "./mode/OmniEdit.vue";
import Flash from "../tools/flash.vue";


export default {
    data() {
        return {
            _mode: this.mode,
            _id: this.id,
            _copied: this.copied,
            _method: this.method,
            routing: "vue",
        };
    },
    props: {
        mode: String,
        id: String | Number,
        module: String,
        method: String,
        copied: {
            type: String,
            default: "",
        },
    },
    components: {
        OmniEdit,
        OmniDatagrid,
        Flash,
        //ConfirmModal,
    },
    provide() {
        return {
            //test: computed(() => this.test()),
            //test: this.test(),
        };
    },
    created() {
        this.registerGlobalEvents();
        window.addEventListener("popstate", this.handlePopState);
    },
    mounted() {
        //console.log("Omni.vue mounted");
    },
    methods: {
        createNew(params) {
            if (this.tryUseStandartRouting(params)) {
                return;
            }

            if (this._mode != "create") {
                let url = "/admin/" + this.module + "/create";

                this._mode = "edit";
                this._id = 0;
                this._method = "create";
                this._copied = false;

                history.pushState({}, "", url);
            }
        },
        edit(params) {
            if (this.tryUseStandartRouting(params)) {
                return;
            }

            if (this._mode != "edit") {
                let url = "/admin/" + this.module + "/edit/" + params.id;

                this._mode = "edit";
                this._id = params.id;
                this._method = "edit";
                this._copied = false;

                history.pushState({}, "", url);
            }
        },
        copy(params) {
            if (this.tryUseStandartRouting(params)) {
                return;
            }

            //console.log('copy, params: ', params);

            if (this._mode != "edit" && this._method != "copy") {
                let url = "/admin/" + this.module + "/copy/" + params.id;

                this._mode = "edit";
                this._id = params.id;
                this._method = "copy";
                this._copied = true;

                history.pushState({}, "", url);
            }
        },
        toIndex(params) {
            if (this.tryUseStandartRouting(params)) {
                return;
            }

            if (this._mode != "datagrid") {
                let url = "/admin/" + this.module + "";
                this._mode = "datagrid";

                history.pushState({}, "", url);
            }
        },
        registerGlobalEvents() {
            this.$emitter.on("newRecord", this.createNew);
            this.$emitter.on("toIndex", this.toIndex);
            this.$emitter.on("edit", this.edit);
            this.$emitter.on("copy", this.copy);
        },
        tryUseStandartRouting(params) {
            if (params.route && (params.use_route || this.routing != "vue")) {
                document.location.href = params.route;
                return true;
            }
        },

        //____Handling browser back and forward buttons, may need refractoring___
        handlePopState(event) {
            console.log("Current history state:", history.state);
            console.log("history:", history);
            console.log("event.state:", event.state);
            console.log("event:", event);
            console.log("this.routing:", this.routing);
            console.log("isvuerouting:", this.routing == "vue");
            
            if (event && this.routing == "vue") {
                let path = window.location.pathname;
                console.log(window.location);
                let parts = path.split("/");
                let mode = parts[3];
                let id = parts[4];

                let setState = (mode, id, method, copied) => {
                    this._mode = mode;
                    this._id = id;
                    this._method = method;
                    this._copied = copied;
                };
                // manually setting everything based on URL.
                switch (mode) {
                    case "edit":
                        setState("edit", id, "edit", false);
                        break;
                    case "copy":
                        setState("edit", id, "copy", true);
                        break;
                    case "create":
                        setState("edit", 0, "create", false);
                        break;
                    default:
                        setState("datagrid", null, null, false);
                        break;
                }
                history.replaceState(
                    {
                        mode: this._mode,
                        id: this._id,
                        method: this._method,
                        copied: this._copied,
                    },
                    "",
                    window.location.href
                );
            }
        },
    },

    beforeUnmount() {
        window.removeEventListener("popstate", this.handlePopState);
    },
};
</script>
