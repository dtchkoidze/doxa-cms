<template>
    <slot name="label"></slot>

    <div class="mb-2 mt-1.5 w-full">
        <dropdown class="w-full">
            <template #toggle>
                
                <span
                    v-if="!isMultiple() && related_records.length"
                    class="flex items-center text-sm text-gray-400 dark:text-gray-400 gap-x-1"
                >
                    <span
                        class="text-3xl cursor-pointer icon-cancel-1 hover:rounded-md hover:bg-gray-100 dark:hover:bg-gray-950"
                        @click="removeAllAppliedRelated()"
                    ></span>
                    {{ related_records[0].title }}
                </span>
                <span
                    v-else
                    class="text-sm text-gray-400 dark:text-gray-400"
                    v-text="vocab['filters.select']"
                >
                </span>

            </template>

            <template #options>
                    <li
                        v-if="!isMultiple()"
                        class="flex items-center justify-between px-5 py-2 text-sm text-gray-600 cursor-pointer hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                        @click="tryApplyRelated(select_related)"
                    >
                        {{ select_related.title }}
                    </li>
                <template v-for="item in relatedList">
                    <li
                        v-if="!isMultiple()"
                        class="flex items-center justify-between px-5 py-2 text-sm text-gray-600 cursor-pointer hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                        @click="tryApplyRelated(item)"
                    >
                        {{ item.title }}
                        <span
                            v-if="isRelatedApplied(item)"
                            class="text-gray-400 dark:text-gray-500"
                            >exists</span
                        >
                    </li>
                    <li
                        v-if="isMultiple()"
                        class="items-center justify-start gap-4 px-3 py-3 text-sm text-gray-600 cursor-pointer w-fullflex hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                        @click="tryApplyRelated(item)"
                    >
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                class="form-checkbox" 
                                id="related-checkbox" 
                                @click.stop="tryApplyRelated(item)"
                                :checked="isRelatedApplied(item)"
                                :disabled="isLimitExceeded() && !isRelatedApplied(item)"
                            >
                            <span class="ml-2 text-sm">
                                {{ item.title }}
                            </span>
                        </label>
                    </li>
                </template>
            </template>
        </dropdown>
    </div>

    <div class="flex flex-wrap gap-2 mb-4" v-if="isMultiple()">
        <p
            class="flex items-center px-2 py-0.5 text-sm text-white bg-gray-600 rounded"
            v-for="item in related_records"
        >
            <span v-text="item.title"></span>
            <span class="cursor-pointer text-white ltr:ml-1.5 rtl:mr-1.5" @click="removeAppliedRelated(item)">
                <i class="fa-solid fa-xmark"></i>
            </span>
        </p>
    </div>

    <slot name="error"></slot>
</template>

<script>
// import dropdown from "../../components/dropdown.vue";
import dropdown from "../../components/dropdown/DropdownClassic.vue";

export default {
    props: ["_value", "params"],
    inject: ["vocab"],
    components: {
        dropdown,
    },
    data() {
        return {
            related_records: this._value ?? [],
            related_records_ids: [],
            relatedList: [],

            multiple: false,
            limit: 0,
            select_related: {
                id: 0,
                title: "Select related item",
            },
        };
    },
    created() {
        //console.log('this.params',this.params);
        this.getRelationList();
    },
    mounted() {
        this.related_records = this._value ?? [];
        if (!this._value) {
            this.related_records = [];
        }
        if (this.params.field.multiple) {
            this.multiple = true;
            if (this.params.field.limit) {
                this.limit = parseInt(this.params.field.limit);
            } else {
                if (this.isInt(this.params.field.multiple)) {
                    this.limit = this.params.field.multiple;
                }
            }
        }

        if(this.related_records.length){
            for (var i = 0; i < this.related_records.length; i++) {
                if(!this.related_records[i].title){
                    if(this.related_records[i].name){
                        this.related_records[i].title = this.related_records[i].name
                    } else {
                        this.related_records[i].title = this.related_records[i].id
                    }
                }
            }
        }

        //console.log('this.related_records',this.related_records);

    },
    updated() {
        this.$parent.updateData(this.related_records);
    },
    methods: {
        removeAllAppliedRelated() {
            this.related_records = [];
            this.$parent.updateData(this.related_records);
        },
        removeAppliedRelated(item) {
            // console.log("trying to remove");
            for (var i = 0; i < this.related_records.length; i++) {
                if (this.related_records[i].id == item.id) {
                    this.related_records.splice(i, 1);
                }
            }
        },
        tryApplyRelated(item) {
            //console.log('this.limit: ',this.limit, 'this.related_records.length: ',this.related_records.length, 'isLimitExceeded: ',(this.related_records.length >= this.limit));

            if (!this.isMultiple()) {
                // console.log("isn't multiple");
                if (this.isRelatedApplied(item)) {
                    this.$emitter.emit("add-flash", {
                        type: "warning",
                        message: this.vocab["related-already-applied"],
                    });
                    return false;
                } else {
                    this.related_records = [item];
                }
            }

            if (this.isMultiple()) {
                // console.log("is multiple");
                if (this.isRelatedApplied(item)) {
                    // console.log("is related applied");
                    this.removeAppliedRelated(item);
                } else {
                    if (this.isLimitExceeded()) {
                        this.$emitter.emit("add-flash", {
                            type: "warning",
                            message: this.vocab["related-limit-exceeded"],
                        });
                    } else {
                        this.related_records.push(item);
                    }
                }
            }
            //console.log('>>>>>>>>> this.related_records', this.related_records);
            this.$parent.updateData(this.related_records);
        },
        getRelationList() {
            axios
                .get("/admin/" + this.params.field.module + "/relation-list")
                .then((response) => {
                    this.relatedList = response.data.list;
                    if(this.relatedList.length){
                        for (var i = 0; i < this.relatedList.length; i++) {
                            if(!this.relatedList[i].title){
                                if(this.relatedList[i].name){
                                    this.relatedList[i].title = this.relatedList[i].name
                                } else {
                                    this.relatedList[i].title = this.relatedList[i].id
                                }
                            }
                        }
                    }

                    console.log('this.relatedList',this.relatedList);
                });
        },
        isRelatedApplied(item) {
            if (!this.related_records.length) {
                return false;
            }
            for (var i = 0; i < this.related_records.length; i++) {
                if (this.related_records[i].id == item.id) {
                    return true;
                }
            }
            return false;
        },
        isMultiple() {
            return this.params.field.multiple;
        },
        isLimitExceeded() {
            if (!this.limit && this.multiple) {
                return false;
            }
            //console.log('this.limit: ',this.limit, 'this.related_records.length: ',this.related_records.length, 'isLimitExceeded: ',(this.related_records.length >= this.limit));
            return this.related_records.length >= this.limit;
        },
        isInt(value) {
            return (
                !isNaN(value) &&
                parseInt(Number(value)) == value &&
                !isNaN(parseInt(value, 10))
            );
        },
    },
};
</script>
